/* FRitZ catalog — browser state: inquiry list, compare (max 3), theme, toast.
   Everything lives in localStorage so the list survives reloads and a failed network.
   Behaviour follows the clickable prototype in project/Main.dc.html. */
(() => {
  const LRI = '\u2066', PDI = '\u2069'; // keep Latin model codes in order inside Persian lines
  const FA = (n) => String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
  const cfg = window.FZ || {};
  const load = (k, d) => { try { const v = JSON.parse(localStorage.getItem(k)); return v ?? d; } catch (e) { return d; } };
  const save = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* private mode: state stays in memory */ } };
  window.FA = FA;

  document.addEventListener('alpine:init', () => {
    const A = window.Alpine;

    A.store('toast', {
      msg: '', act: '', t: null,
      show(msg, act = '') {
        clearTimeout(this.t);
        this.msg = msg; this.act = act;
        this.t = setTimeout(() => { this.msg = ''; }, 3200);
      },
    });

    A.store('theme', {
      dark: document.documentElement.classList.contains('dark'),
      toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        save('fz-theme', this.dark ? 'dark' : 'light');
      },
    });

    A.store('view', {
      cols: load('fz-cols', 2),
      set(n) { this.cols = n; save('fz-cols', n); },
    });

    A.store('cmp', {
      items: load('fz-cmp', []),
      has(id) { return this.items.some((p) => p.id === id); },
      toggle(p) {
        if (this.has(p.id)) this.items = this.items.filter((x) => x.id !== p.id);
        else if (this.items.length >= 3) { A.store('toast').show('حداکثر ۳ محصول. برای افزودن، یکی را بردارید.'); return; }
        else this.items = [...this.items, p];
        save('fz-cmp', this.items);
      },
      remove(id) { this.items = this.items.filter((x) => x.id !== id); save('fz-cmp', this.items); },
      get url() { return cfg.compareUrl + '?ids=' + this.items.map((p) => p.id).join(','); },
    });

    A.store('inq', {
      items: load('fz-inq', []),
      name: load('fz-inq-name', ''),
      project: load('fz-inq-project', ''),
      open: false,
      sent: false,
      persist() { save('fz-inq', this.items); },
      get count() { return this.items.length; },
      has(id) { return this.items.some((p) => p.id === id); },
      qty(id) { const p = this.items.find((x) => x.id === id); return p ? p.qty : 0; },
      add(p, quiet) {
        if (!this.has(p.id)) this.items = [...this.items, { ...p, qty: 1 }];
        this.sent = false; this.persist();
        if (!quiet) A.store('toast').show('به لیست استعلام اضافه شد', 'list');
      },
      addAll(list) {
        list.forEach((p) => this.add(p, true));
        A.store('toast').show(FA(list.length) + ' محصول به لیست استعلام اضافه شد', 'list');
      },
      toggle(p) {
        if (this.has(p.id)) { this.remove(p.id); A.store('toast').show('از لیست استعلام حذف شد'); }
        else this.add(p);
      },
      remove(id) { this.items = this.items.filter((x) => x.id !== id); this.sent = false; this.persist(); },
      inc(id) { const p = this.items.find((x) => x.id === id); if (p) { p.qty++; this.persist(); } },
      dec(id) { const p = this.items.find((x) => x.id === id); if (p && p.qty > 1) { p.qty--; this.persist(); } },
      show() { A.store('toast').msg = ''; this.open = true; },
      close() { this.open = false; },
      setName(v) { this.name = v; save('fz-inq-name', v); },
      setProject(v) { this.project = v; save('fz-inq-project', v); },

      /* Message from the admin template (A-Settings). A line whose variable is empty is dropped. */
      get message() {
        const list = this.items.map((p, i) => FA(i + 1) + '. ' + p.name + '\n    کد ' + LRI + p.code + PDI + ' — ' + FA(p.qty) + ' عدد').join('\n');
        let date = '';
        try { date = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { dateStyle: 'medium' }).format(new Date()); } catch (e) { /* old browser */ }
        const vars = { 'اقلام': list, 'نام': this.name.trim(), 'پروژه': this.project.trim(), 'تاریخ': date };
        return (cfg.template || '{اقلام}')
          .split('\n')
          .filter((line) => !Object.keys(vars).some((k) => line.includes('{' + k + '}') && !vars[k]))
          .map((line) => line.replace(/\{([^}]+)\}/g, (m, k) => (k in vars ? vars[k] : m)))
          .join('\n')
          .replace(/\n{3,}/g, '\n\n')
          .trim();
      },
      get waUrl() { return 'https://wa.me/' + (cfg.wa || '') + '?text=' + encodeURIComponent(this.message); },

      /* Open WhatsApp, then log the click (not the delivery) without blocking. */
      send() {
        if (!this.items.length) return;
        const url = this.waUrl;
        // no 'noopener' feature: it makes window.open return null even on success
        const w = window.open(url, '_blank');
        if (w) w.opener = null;
        else window.location.href = url; // popup blocked: go to WhatsApp in this tab
        try {
          const fd = new FormData();
          fd.append('_token', cfg.csrf);
          fd.append('payload', JSON.stringify({
            items: this.items.map((p) => ({ id: p.id, qty: p.qty })),
            name: this.name.trim(), project: this.project.trim(), message: this.message, page: location.pathname,
          }));
          navigator.sendBeacon(cfg.inquiryUrl, fd);
        } catch (e) { /* logging never blocks the inquiry */ }
        this.sent = true;
      },
    });

    /* Sticky search bar on home: mark it .stuck once it really sticks (fz.css lifts it then). */
    A.directive('stuck', (el) => {
      const s = document.createElement('div');
      s.className = 'stick-sentinel';
      el.before(s);
      new IntersectionObserver(([e]) => el.classList.toggle('stuck', !e.isIntersecting)).observe(s);
    });
  });

  /* Ctrl+K: pages, categories and brands filter locally; products come from /search/suggest. */
  window.cpalette = (cmds) => ({
    open: false, q: '', i: 0, found: [], busy: false, ctl: null,
    norm(s) { return String(s || '').toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/[‌\s\-_./]+/g, ''); },
    show() { this.open = true; this.q = ''; this.found = []; this.i = 0; this.$nextTick(() => this.$refs.q.focus()); },
    get list() {
      const q = this.norm(this.q);
      const local = q ? cmds.filter((c) => this.norm(c.l).includes(q)).slice(0, 8) : cmds.filter((c) => c.g !== 'برندها').slice(0, 10);
      const all = this.q.trim() ? [{ l: 'همه نتایج برای «' + this.q.trim() + '»', i: 'search', u: cfg.search + '?q=' + encodeURIComponent(this.q.trim()), g: 'جست‌وجو' }] : [];
      return [...this.found, ...local, ...all];
    },
    icon(name) {
      const el = document.querySelector('#pal-icons')?.content.querySelector('[data-i="' + name + '"]');
      return el ? el.innerHTML : '';
    },
    async find() {
      this.i = 0;
      const q = this.q.trim();
      if (this.ctl) this.ctl.abort();
      if (!q) { this.found = []; return; }
      this.ctl = new AbortController();
      this.busy = true;
      try {
        const r = await fetch(cfg.suggest + '?q=' + encodeURIComponent(q), { signal: this.ctl.signal, headers: { Accept: 'application/json' } });
        const d = r.ok ? await r.json() : { products: [] };
        const text = (h) => { const t = document.createElement('div'); t.innerHTML = h; return t.textContent; };
        this.found = (d.products || []).map((p) => ({ l: p.name, s: p.brand, c: text(p.code), img: p.img, art: p.art, u: p.url, g: 'محصولات' }));
      } catch (e) { /* aborted or offline: keep the local results */ }
      this.busy = false;
    },
    move(d) { const n = this.list.length; if (n) this.i = (this.i + d + n) % n; },
    go() { const r = this.list[this.i]; if (r) window.location.href = r.u; },
  });

})();

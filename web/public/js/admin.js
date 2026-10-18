/* FRitZ admin — confirmation dialog for dangerous actions (A-FlowEdit modal).
   Every dangerous action says what it will do before it runs:
   <form data-confirm='{"title":"…","body":"…","ok":"بله، حذف شود","danger":true}'> … </form>
   or a button with the same attribute and form="…". */
document.addEventListener('alpine:init', () => {
  window.Alpine.store('confirm', {
    open: false, title: '', body: '', ok: 'تأیید', danger: false, form: null, submitter: null, fn: null, fnCancel: null,
    cancel() {
      const c = this.fnCancel;
      Object.assign(this, { open: false, fn: null, fnCancel: null });
      if (c) c();
    },
    ask(opts, form, submitter, fn = null) {
      Object.assign(this, { title: opts.title || '', body: opts.body || '', ok: opts.ok || 'تأیید', danger: !!opts.danger, form, submitter, fn, open: true });
    },
    yes() {
      const { form, submitter, fn } = this;
      this.open = false;
      this.fn = null;
      this.fnCancel = null;
      if (fn) { fn(); return; }
      if (!form) return;
      form.dataset.confirmed = '1';
      if (form.requestSubmit) form.requestSubmit(submitter || undefined); else form.submit();
    },
  });
});

document.addEventListener('submit', (e) => {
  const form = e.target;
  const btn = e.submitter;
  const raw = (btn && btn.dataset.confirm) || form.dataset.confirm;
  if (!raw || form.dataset.confirmed === '1') { delete form.dataset.confirmed; return; }
  e.preventDefault();
  let opts = {};
  try { opts = JSON.parse(raw); } catch (err) { opts = { title: raw }; }
  if (typeof opts.dynamic === 'string' && window[opts.dynamic]) opts = { ...opts, ...window[opts.dynamic](form, btn) };
  window.Alpine.store('confirm').ask(opts, form, btn);
}, true);

/* Catalog tree panel: open or closed, remembered per browser. */
window.fzTree = (open) => {
  document.documentElement.classList.toggle('no-tree', !open);
  try { localStorage.setItem('fz-tree', open ? '1' : '0'); } catch (e) { /* private mode */ }
};

/* Ctrl+K palette: the page's own commands filter locally; products come from the lookup endpoint. */
window.palette = (cmds) => ({
  open: false, q: '', i: 0, found: [], busy: false, ctl: null,
  norm(s) { return String(s || '').toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/[‌\s\-_./]+/g, ''); },
  show() { this.open = true; this.q = ''; this.found = []; this.i = 0; this.$nextTick(() => this.$refs.q.focus()); },
  get list() {
    const q = this.norm(this.q);
    const local = (q ? cmds.filter((c) => this.norm(c.l).includes(q)) : cmds.filter((c) => c.g !== 'دسته‌ها')).slice(0, q ? 8 : 12);
    return [...this.found, ...local];
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
      const r = await fetch(window.FZ.lookup + '?q=' + encodeURIComponent(q), { signal: this.ctl.signal, headers: { Accept: 'application/json' } });
      const rows = r.ok ? await r.json() : [];
      this.found = rows.map((p) => ({ l: p.name, c: p.code, i: 'box', u: p.url, g: 'محصولات' }));
    } catch (e) { /* aborted or offline: keep local results */ }
    this.busy = false;
  },
  move(d) { const n = this.list.length; if (n) this.i = (this.i + d + n) % n; },
  go() { const r = this.list[this.i]; if (r) window.location.href = r.u; },
});

/* Inline status change (list, board, peek). Taking a product off the catalog is confirmed first.
   Resolves to the new status, or null when cancelled or refused. */
window.fzStatus = (url, to, name, from) => new Promise((resolve) => {
  const run = async () => {
    try {
      const r = await fetch(url, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ status: to }),
      });
      const d = await r.json().catch(() => ({}));
      window.Alpine.store('toast').show(d.msg || 'تغییر ذخیره نشد. صفحه را دوباره باز کنید.');
      resolve(r.ok && d.ok ? d.status : null);
    } catch (e) {
      window.Alpine.store('toast').show('اتصال برقرار نشد؛ تغییری ذخیره نشد.');
      resolve(null);
    }
  };
  if (to === from) { resolve(null); return; }
  if (to === 'draft') {
    window.Alpine.store('confirm').ask({ title: 'برگرداندن به پیش‌نویس؟', body: '«' + name + '» از کاتالوگ عمومی، جست‌وجو و مقایسه برداشته می‌شود تا دوباره منتشر شود.', ok: 'برگرداندن به پیش‌نویس' }, null, null, run);
    window.Alpine.store('confirm').fnCancel = () => resolve(null);
  } else run();
});

/* A status chip that is also a menu. */
window.statusChip = (status, url, name) => ({
  status, o: false, busy: false,
  get label() { return this.status === 'published' ? 'منتشرشده' : 'پیش‌نویس'; },
  async set(to) {
    this.o = false;
    this.busy = true;
    const s = await window.fzStatus(url, to, name, this.status);
    this.busy = false;
    if (s) { this.status = s; this.$dispatch('fz-status', { url, status: s }); }
  },
});

/* Side peek: the row opens beside the list; the list keeps its place and filters. */
window.peek = () => ({
  open: false, html: '', url: '', back: null,
  async show(url, el) {
    this.back = el || document.activeElement;
    this.url = url; this.open = true; this.html = '<div class="pk-load" aria-busy="true"><span class="sk"></span><span class="sk"></span><span class="sk s"></span></div>';
    try {
      const r = await fetch(url, { headers: { Accept: 'text/html' } });
      if (this.url !== url) return;
      this.html = r.ok ? await r.text() : '<p class="pk-err">این محصول باز نشد. صفحه را دوباره بارگذاری کنید.</p>';
    } catch (e) { this.html = '<p class="pk-err">اتصال برقرار نشد.</p>'; }
    this.$nextTick(() => this.$refs.pk?.focus());
  },
  close() { this.open = false; this.url = ''; this.back?.focus?.(); },
});

/* Board: drag a card to another status column. The status menu on each card is the keyboard path. */
window.board = () => ({
  drag: null, over: null,
  start(e, card) { this.drag = card; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', card.url); },
  async drop(col) {
    const c = this.drag; this.over = null; this.drag = null;
    if (!c || col === c.status) return;
    const s = await window.fzStatus(c.url, col, c.name, c.status);
    if (s) {
      const el = document.getElementById('card-' + c.id);
      document.querySelector('[data-col="' + s + '"] .bcards')?.prepend(el);
      el.dispatchEvent(new CustomEvent('fz-moved', { detail: s }));
      this.$dispatch('fz-recount');
    }
  },
});

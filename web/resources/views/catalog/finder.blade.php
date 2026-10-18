{{-- Product finder (M-Wizard): need → protocol → brand. Step 3 only offers brands with products for the choices so far. --}}
@php $icons = collect(['bulb', 'curtain', 'thermo', 'shield', 'music', 'panel', 'wifi', 'cable', 'help', 'box'])->mapWithKeys(fn ($i) => [$i => \App\Support\Icons::path($i)]); @endphp
<x-layouts.site title="محصول مناسبم را پیدا کن" stick>
  <div x-data="finder(@js($needs), @js($protocols), @js($brands), @js($matrix), @js(route('finder.results')))" style="display:flex;flex-direction:column;min-height:100dvh">
    <div style="display:flex;align-items:center;gap:4px;padding:8px">
      <a class="ib" href="{{ route('home') }}" x-show="step === 1" aria-label="انصراف"><x-icon i="x" /></a>
      <button class="ib" type="button" x-show="step > 1" x-cloak @click="step--" aria-label="مرحله قبل"><x-icon i="chevBack" /></button>
      <span class="t-s" style="flex:1;text-align:center;font-weight:700" x-text="'مرحله ' + FA(step) + ' از ۳'">مرحله ۱ از ۳</span>
      <a class="ib" href="{{ route('home') }}" aria-label="بستن راهنما"><x-icon i="x" /></a>
    </div>
    <div class="prog" style="margin:0 16px" role="progressbar" aria-label="پیشرفت" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="[33, 66, 100][step - 1]"><i :style="'width:' + [33, 66, 100][step - 1] + '%'" style="width:33%"></i></div>

    <div style="flex:1;padding:28px 16px 16px">
      <h1 class="t-h1" x-text="['چه چیزی را می‌خواهید کنترل کنید؟', 'کدام پروتکل؟', 'برند مورد نظرتان؟'][step - 1]">چه چیزی را می‌خواهید کنترل کنید؟</h1>
      <p class="t-s mut" style="margin-top:6px" x-text="['هر چند مورد که لازم دارید انتخاب کنید.', 'اگر نمی‌دانید، «نمی‌دانم» را بزنید؛ بر اساس نیازتان پیشنهاد می‌دهیم.', 'برندهایی که برای انتخاب‌های شما محصول دارند.'][step - 1]"></p>
      <div class="chips" style="margin-top:12px" x-show="picked.length" x-cloak>
        <template x-for="c in picked" :key="c"><span class="chip" style="height:32px;font-size:13px" x-text="c"></span></template>
      </div>

      <div class="g2" style="margin-top:20px" role="group" :aria-label="['نیاز', 'پروتکل', 'برند'][step - 1]">
        <template x-for="o in options" :key="o.key">
          <button type="button" class="wopt" :class="o.on && 'on'" :aria-pressed="o.on" @click="o.pick()">
            <span class="ck" x-show="o.on"><x-icon i="check" s="14" sw="2.4" /></span>
            <template x-if="o.icon"><span class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="icons[o.icon]"/></svg></span></template>
            <template x-if="o.word"><span class="ico" style="width:auto;padding:0 12px"><span class="brand" style="font-size:12px;color:inherit" x-text="o.label"></span></span></template>
            <span><b :class="o.lat && 'lat'" x-text="o.label"></b><span x-text="o.hint"></span></span>
          </button>
        </template>
      </div>
      <div class="alert" style="margin-top:16px" x-show="step === 3 && protos.length && !protos.includes('any')" x-cloak>
        <x-icon i="info" s="18" /><span x-text="'فقط برندهایی نمایش داده می‌شوند که در کاتالوگ محصول ' + protoNames + ' دارند.'"></span>
      </div>
    </div>

    <div class="stick" style="background:var(--bg)">
      <button class="btn btn-p btn-lg" type="button" style="flex:1" :disabled="!canNext" @click="next()">
        <span x-text="step === 3 ? 'نمایش محصولات پیشنهادی' : 'ادامه'">ادامه</span><x-icon i="arrowNext" s="20" />
      </button>
    </div>
  </div>

  @push('head')
  <script>
    function finder(needs, protocols, brands, matrix, url) {
      return {
        step: 1, needs: [], protos: [], brands: [],
        icons: {!! json_encode($icons) !!},
        toggle(list, v) { return list.includes(v) ? list.filter((x) => x !== v) : [...list.filter((x) => x !== 'any'), v]; },
        get cats() { return needs.filter((n) => this.needs.includes(n.id)).flatMap((n) => n.cats); },
        get protoNames() { return protocols.filter((p) => this.protos.includes(p.code)).map((p) => p.name).join(' و '); },
        get available() {
          const any = !this.protos.length || this.protos.includes('any');
          const set = new Set(matrix.filter((m) => this.cats.includes(m.c) && (any || m.p.some((x) => this.protos.includes(x)))).map((m) => m.b));
          return brands.filter((b) => set.has(b.slug));
        },
        get picked() {
          const out = [];
          if (this.step > 1) out.push(...needs.filter((n) => this.needs.includes(n.id)).map((n) => n.label));
          if (this.step > 2) out.push(...protocols.filter((p) => this.protos.includes(p.code)).map((p) => p.name));
          return out;
        },
        get options() {
          if (this.step === 1) return needs.map((n) => ({ key: 'n' + n.id, label: n.label, hint: n.hint, icon: n.icon, on: this.needs.includes(n.id), pick: () => { this.needs = this.toggle(this.needs, n.id); } }));
          if (this.step === 2) return [...protocols.map((p) => ({ key: p.code, label: p.name, hint: p.hint, icon: p.icon, lat: true, on: this.protos.includes(p.code), pick: () => { this.protos = this.toggle(this.protos, p.code); } })),
            { key: 'any', label: 'نمی‌دانم', hint: 'کارشناس پیشنهاد می‌دهد', icon: 'help', on: this.protos.includes('any'), pick: () => { this.protos = this.protos.includes('any') ? [] : ['any']; } }];
          return [...this.available.map((b) => ({ key: b.slug, label: b.name, hint: b.hint, word: true, lat: true, on: this.brands.includes(b.slug), pick: () => { this.brands = this.toggle(this.brands, b.slug); } })),
            { key: 'any', label: 'نمی‌دانم / فرقی ندارد', hint: 'بهترین گزینه را پیشنهاد می‌دهیم', icon: 'help', on: this.brands.includes('any'), pick: () => { this.brands = this.brands.includes('any') ? [] : ['any']; } }];
        },
        get canNext() { return [this.needs, this.protos, this.brands][this.step - 1].length > 0; },
        next() {
          if (this.step < 3) { this.step++; if (this.step === 3) this.brands = this.brands.filter((b) => b === 'any' || this.available.some((x) => x.slug === b)); window.scrollTo(0, 0); return; }
          const q = new URLSearchParams();
          this.needs.forEach((v) => q.append('n[]', v));
          this.protos.forEach((v) => q.append('p[]', v));
          this.brands.forEach((v) => q.append('b[]', v));
          location.href = url + '?' + q.toString();
        },
      };
    }
  </script>
  @endpush
</x-layouts.site>

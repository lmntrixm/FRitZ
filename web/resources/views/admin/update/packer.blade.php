{{-- Offline .fritz packer: downloaded from the admin, opened from disk by whoever prepares the product files.
     Same layout as App\Support\FritzPackage, written with WebCrypto. Nothing leaves the browser. --}}
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>ساخت بسته FRitZ</title>
@verbatim
<style>
:root{--bg:#FFFFFF;--sf2:#F3F3F1;--sf3:#E7E7E3;--bd2:#8A8A84;--tx:#141414;--tx2:#4D4D49;--tx3:#62625D;--ac:#AFCB08;--acI:#141414;--acT:#566400;--err:#B42318;--errS:#FCEDEB;--okT:#2F6B3A;--okS:#E7F2E9;color-scheme:light}
@media (prefers-color-scheme:dark){:root{--bg:#0F0F0E;--sf2:#1C1C1A;--sf3:#282826;--bd2:#8E8E87;--tx:#F3F3EF;--tx2:#BDBDB6;--tx3:#9C9C95;--acT:#C5DE33;--err:#FF8F7E;--errS:#311915;--okT:#8FD49B;--okS:#16271A;color-scheme:dark}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--tx);font-family:Kalameh,Tahoma,sans-serif;line-height:1.8;font-size:15px}
main{max-width:560px;margin:0 auto;padding:40px 20px 64px;display:flex;flex-direction:column;gap:20px}
h1{font-size:26px;line-height:1.4;margin:0}
p{margin:0;color:var(--tx2)}
.lat,code{direction:ltr;unicode-bidi:isolate;font-family:inherit}
label.lab{display:block;font-weight:700;margin-bottom:6px}
.fld{display:flex;flex-direction:column}
.inp{width:100%;min-height:48px;padding:0 14px;border:0;border-bottom:2px solid var(--bd2);background:var(--sf2);color:var(--tx);font:inherit;font-size:16px;direction:ltr}
.inp:focus{outline:none;border-bottom-color:var(--tx)}
.drop{display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center;padding:36px 20px;border:1.5px dashed var(--bd2);cursor:pointer}
.drop.over{border-color:var(--tx);background:var(--sf2)}
.drop:focus-within{outline:2px solid var(--tx);outline-offset:3px}
.drop input{position:absolute;width:1px;height:1px;opacity:0}
.hint{font-size:13px;color:var(--tx3)}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:48px;padding:0 24px;border-radius:999px;border:0;background:var(--ac);color:var(--acI);font:inherit;font-weight:700;cursor:pointer;align-self:flex-start}
.btn:disabled{background:var(--sf2);color:var(--tx3);cursor:not-allowed}
.btn:focus-visible,.show:focus-visible{outline:2px solid var(--tx);outline-offset:3px}
.show{background:none;border:0;color:var(--acT);font:inherit;font-weight:700;cursor:pointer;padding:4px 0;align-self:flex-start}
.msg{padding:12px 14px;font-size:14px}
.msg.er{background:var(--errS);color:var(--err)}
.msg.ok{background:var(--okS);color:var(--okT)}
.msg.in{background:var(--sf2);color:var(--tx2)}
ol{margin:0;padding-inline-start:20px;color:var(--tx2);list-style:persian}
.fp{font-weight:700;color:var(--tx)}
[hidden]{display:none!important}
</style>
@endverbatim
</head>
<body>
<main>
  <div>
    <h1>ساخت بسته <span class="lat">.fritz</span></h1>
    <p>فایل اکسل محصولات را با رمز بسته قفل می‌کند تا در پنل FRitZ{{ $site ? ' (' : '' }}<span class="lat">{{ $site }}</span>{{ $site ? ')' : '' }} بارگذاری شود. همه کار در همین مرورگر انجام می‌شود و فایل یا رمز به هیچ جا فرستاده نمی‌شود؛ این صفحه بدون اینترنت هم کار می‌کند.</p>
  </div>

  <ol>
    <li>در پنل، از «به‌روزرسانی ← ورود از اکسل» خروجی اکسل محصولات را بگیرید و ویرایش کنید.</li>
    <li>فایل <span class="lat">.xlsx</span> را اینجا انتخاب کنید و رمز بسته را وارد کنید.</li>
    <li>فایل <span class="lat">.fritz</span> ساخته‌شده را به مدیر کل بدهید تا در تب «فایل رمزنگاری‌شده» بارگذاری کند.</li>
  </ol>

  <div class="msg er" id="nocrypto" hidden>این مرورگر رمزنگاری را پشتیبانی نمی‌کند. صفحه را در نسخه تازه Chrome، Edge یا Firefox باز کنید.</div>

  <form id="f" style="display:flex;flex-direction:column;gap:18px" novalidate>
    <label class="drop" id="drop">
      <b id="dt">فایل اکسل را اینجا رها کنید</b>
      <span class="hint" id="ds"><span class="lat">.xlsx</span> · همان قالب خروجی پنل</span>
      <span class="btn" style="background:var(--sf2);color:var(--tx);align-self:center;min-height:40px">انتخاب فایل</span>
      <input type="file" id="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
    </label>

    <div class="fld">
      <label class="lab" for="pw">رمز بسته</label>
      <input class="inp" id="pw" type="password" autocomplete="off" spellcheck="false">
      <button type="button" class="show" id="toggle" aria-controls="pw" aria-pressed="false">نمایش رمز</button>
      <span class="hint">همان رمزی که مدیر کل در «تنظیمات ← کلید رمزنگاری» تعیین کرده است.</span>
      <span class="hint" id="fpline" hidden>اثر انگشت این رمز: <bdi class="lat fp" id="fp"></bdi></span>
      @if ($fp)
        <span class="hint">اثر انگشت رمز سایت هنگام دانلود این ابزار: <bdi class="lat fp">{{ $fp }}</bdi></span>
      @endif
      <span class="msg ok" id="fpok" hidden style="margin-top:8px">اثر انگشت با رمز سایت یکی است.</span>
      <span class="msg er" id="fpbad" hidden style="margin-top:8px">اثر انگشت با رمز سایت یکی نیست. اگر رمز در پنل عوض شده، رمز تازه را از مدیر کل بگیرید؛ وگرنه فایل در پنل باز نمی‌شود.</span>
    </div>

    <div class="msg er" id="err" role="alert" hidden></div>
    <div class="msg ok" id="done" role="status" hidden></div>
    <button class="btn" id="go" type="submit" disabled>ساخت و دانلود بسته</button>
  </form>
</main>

<script>
  const SITE_FP = @json($fp);
@verbatim
  const $ = (id) => document.getElementById(id);
  const enc = new TextEncoder();
  const FA = (s) => String(s).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]).replace('.', '٫');
  const join = (...parts) => { const out = new Uint8Array(parts.reduce((n, p) => n + p.length, 0)); let o = 0; for (const p of parts) { out.set(p, o); o += p.length; } return out; };
  const hex = (b) => Array.from(new Uint8Array(b), (x) => x.toString(16).padStart(2, '0')).join('').toUpperCase();
  let file = null;

  if (!window.crypto || !crypto.subtle) { $('nocrypto').hidden = false; $('f').hidden = true; }

  // must match FritzPackage::fingerprint()
  async function fingerprint(pw) {
    const h = hex(await crypto.subtle.digest('SHA-256', enc.encode('fritz-fp:' + pw)));
    const pairs = (s) => s.match(/../g).join(':');
    return pairs(h.slice(0, 4)) + ':…:' + pairs(h.slice(-4));
  }

  // "FRITZ1" | salt 16 | iv 12 | check 8 | AES-256-GCM(ciphertext | tag 16), header as additional data
  async function pack(bytes, pw) {
    const salt = crypto.getRandomValues(new Uint8Array(16));
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const base = await crypto.subtle.importKey('raw', enc.encode(pw), 'PBKDF2', false, ['deriveBits']);
    const raw = new Uint8Array(await crypto.subtle.deriveBits({ name: 'PBKDF2', salt, iterations: 200000, hash: 'SHA-256' }, base, 256));
    const check = new Uint8Array(await crypto.subtle.digest('SHA-256', join(raw, enc.encode('fritz-check')))).slice(0, 8);
    const header = join(enc.encode('FRITZ1'), salt, iv, check);
    const key = await crypto.subtle.importKey('raw', raw, 'AES-GCM', false, ['encrypt']);
    const body = new Uint8Array(await crypto.subtle.encrypt({ name: 'AES-GCM', iv, additionalData: header, tagLength: 128 }, key, bytes));
    return new Blob([header, body], { type: 'application/octet-stream' });
  }

  function ready() { $('go').disabled = !file || $('pw').value.length < 1; }
  function choose(f) {
    $('err').hidden = true; $('done').hidden = true;
    file = null;
    if (!f) return ready();
    if (!/\.xlsx$/i.test(f.name)) { $('err').textContent = 'فقط فایل .xlsx پذیرفته می‌شود. فایل .xls یا .csv را در اکسل با «Save As → Excel Workbook» دوباره ذخیره کنید.'; $('err').hidden = false; return ready(); }
    if (f.size > 20 * 1024 * 1024) { $('err').textContent = 'فایل بزرگ‌تر از ۲۰ مگابایت است؛ آن را در چند فایل تهیه کنید.'; $('err').hidden = false; return ready(); }
    file = f;
    $('dt').textContent = f.name;
    $('ds').textContent = f.size >= 1048576 ? FA((f.size / 1048576).toFixed(1)) + ' مگابایت' : FA(Math.max(1, Math.round(f.size / 1024))) + ' کیلوبایت';
    ready();
  }

  $('file').addEventListener('change', (e) => choose(e.target.files[0]));
  const drop = $('drop');
  drop.addEventListener('dragover', (e) => { e.preventDefault(); drop.classList.add('over'); });
  drop.addEventListener('dragleave', () => drop.classList.remove('over'));
  drop.addEventListener('drop', (e) => { e.preventDefault(); drop.classList.remove('over'); choose(e.dataTransfer.files[0]); });
  $('toggle').addEventListener('click', () => {
    const on = $('pw').type === 'password';
    $('pw').type = on ? 'text' : 'password';
    $('toggle').textContent = on ? 'پنهان کردن رمز' : 'نمایش رمز';
    $('toggle').setAttribute('aria-pressed', String(on));
  });
  let fpTimer;
  $('pw').addEventListener('input', () => {
    ready();
    clearTimeout(fpTimer);
    fpTimer = setTimeout(async () => {
      const pw = $('pw').value;
      $('fpline').hidden = !pw;
      $('fpok').hidden = $('fpbad').hidden = true;
      if (!pw) return;
      const fp = await fingerprint(pw);
      $('fp').textContent = fp;
      if (SITE_FP) { $('fpok').hidden = fp !== SITE_FP; $('fpbad').hidden = fp === SITE_FP; }
    }, 250);
  });

  $('f').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!file) return;
    $('err').hidden = true; $('done').hidden = true;
    $('go').disabled = true;
    $('go').textContent = 'در حال ساخت…';
    try {
      const bytes = new Uint8Array(await file.arrayBuffer());
      if (bytes[0] !== 0x50 || bytes[1] !== 0x4b) throw new Error('این فایل اکسل (.xlsx) معتبر نیست.');
      const blob = await pack(bytes, $('pw').value);
      const name = file.name.replace(/\.xlsx$/i, '') + '.fritz';
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = name;
      document.body.appendChild(a);
      a.click();
      setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 1000);
      $('done').textContent = 'بسته «' + name + '» ساخته و دانلود شد. آن را به مدیر کل بدهید. رمز را همراه فایل نفرستید.';
      $('done').hidden = false;
    } catch (err) {
      $('err').textContent = err.message || 'ساخت بسته ممکن نشد.';
      $('err').hidden = false;
    } finally {
      $('go').textContent = 'ساخت و دانلود بسته';
      ready();
    }
  });
@endverbatim
</script>
</body>
</html>

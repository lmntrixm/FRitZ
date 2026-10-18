/* FRitZ admin — confirmation dialog for dangerous actions (A-FlowEdit modal).
   Every dangerous action says what it will do before it runs:
   <form data-confirm='{"title":"…","body":"…","ok":"بله، حذف شود","danger":true}'> … </form>
   or a button with the same attribute and form="…". */
document.addEventListener('alpine:init', () => {
  window.Alpine.store('confirm', {
    open: false, title: '', body: '', ok: 'تأیید', danger: false, form: null, submitter: null,
    ask(opts, form, submitter) {
      Object.assign(this, { title: opts.title || '', body: opts.body || '', ok: opts.ok || 'تأیید', danger: !!opts.danger, form, submitter, open: true });
    },
    yes() {
      const { form, submitter } = this;
      this.open = false;
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

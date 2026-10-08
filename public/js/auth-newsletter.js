(() => {
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

  function showPanel(name) {
    document.querySelectorAll('[data-auth-panel]').forEach((el) => {
      el.hidden = el.getAttribute('data-auth-panel') !== name;
    });
  }

  function setError(msg) {
    const el = document.getElementById('auth-form-error');
    if (!el) return;
    if (!msg) {
      el.hidden = true;
      el.textContent = '';
      return;
    }
    el.hidden = false;
    el.textContent = msg;
  }

  async function sendMagicLink(form) {
    setError('');
    const intent = form.getAttribute('data-auth-intent') || 'login';
    const fd = new FormData(form);
    fd.set('intent', intent);
    if (intent === 'signup' && !form.querySelector('[name=age]')?.checked) {
      setError('Please confirm that you are over 18 years old!');
      const ageErr = document.getElementById('auth-age-error');
      if (ageErr) ageErr.hidden = false;
      return;
    }
    const ageErr = document.getElementById('auth-age-error');
    if (ageErr) ageErr.hidden = true;

    const back = sessionStorage.getItem('authReturn');
    if (back) fd.set('return_to', back);

    const res = await fetch('/auth/magic-link', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: fd,
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const msg =
        data?.errors?.age?.[0] ||
        data?.errors?.email?.[0] ||
        data?.message ||
        'Something went wrong';
      setError(msg);
      return;
    }

    const emailEl = document.getElementById('auth-check-email');
    if (emailEl) emailEl.textContent = data.email || fd.get('email');
    window.__lastMagicEmail = data.email || fd.get('email');
    window.__lastMagicIntent = intent;
    showPanel('check-email');
  }

  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;

    if (form.id === 'auth-login-form' || form.id === 'auth-signup-form') {
      e.preventDefault();
      sendMagicLink(form);
      return;
    }

    if (form.id === 'newsletter-form') {
      e.preventDefault();
      const msg = document.getElementById('newsletter-message');
      const fd = new FormData(form);
      fetch('/newsletter/subscribe', {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: fd,
      })
        .then(async (res) => {
          const data = await res.json().catch(() => ({}));
          if (!msg) return;
          msg.hidden = false;
          if (!res.ok) {
            msg.textContent =
              data?.errors?.email?.[0] ||
              data?.errors?.age?.[0] ||
              data?.errors?.newsletter?.[0] ||
              data?.message ||
              'Could not subscribe';
            return;
          }
          msg.textContent = data.message || 'Thanks! Please check your inbox.';
          form.reset();
          form.querySelectorAll('input[type=checkbox]').forEach((c) => {
            c.checked = true;
          });
        })
        .catch(() => {
          if (msg) {
            msg.hidden = false;
            msg.textContent = 'Could not subscribe';
          }
        });
    }
  });

  document.getElementById('auth-resend')?.addEventListener('click', () => {
    const email = window.__lastMagicEmail;
    const intent = window.__lastMagicIntent || 'login';
    if (!email) return;
    const fd = new FormData();
    fd.set('email', email);
    fd.set('intent', intent);
    if (intent === 'signup') fd.set('age', '1');
    const back = sessionStorage.getItem('authReturn');
    if (back) fd.set('return_to', back);
    fetch('/auth/magic-link', {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: fd,
    }).then(() => {
      setError('');
      const el = document.getElementById('auth-check-email');
      if (el) el.textContent = email;
    });
  });

  // Open auth modal when redirected as guest (?auth=1)
  try {
    const params = new URLSearchParams(window.location.search);
    if (params.get('auth') === '1') {
      const openBtn = document.querySelector('.js-open-auth');
      if (openBtn) {
        openBtn.click();
      }
      params.delete('auth');
      const next = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash;
      window.history.replaceState({}, '', next);
    }
  } catch (_) {}

})();

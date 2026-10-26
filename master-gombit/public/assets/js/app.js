/* Master Gombit - vanilla JS, CSP-uyumlu (inline script/eval YOK).
 * Yalnızca UX iyileştirmesi: form çift-tıklama koruması ve CSRF header'lı fetch örneği. */
(function () {
  'use strict';

  // State-changing formlarda çift gönderimi engelle (kuyruk/tekrar hatası azaltma)
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function () {
      var btn = f.querySelector('button[type="submit"]');
      if (btn) { setTimeout(function () { btn.disabled = true; }, 0); }
    });
  });

  // AJAX + CSRF header örneği (Lab 04): meta yerine body'den token okunur
  window.mgFetchJson = function (url, method, payload, csrfToken) {
    return fetch(url, {
      method: method || 'GET',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken || ''
      },
      body: payload ? JSON.stringify(payload) : undefined
    }).then(function (r) { return r.json(); });
  };
})();

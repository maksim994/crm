(function () {
  'use strict';

  var script =
    document.currentScript ||
    document.querySelector('script[data-token][src*="wbooster.js"]');

  if (!script) {
    return;
  }

  var token = script.getAttribute('data-token');
  if (!token) {
    return;
  }

  var baseUrl = script.src.replace(/\/embed\/wbooster\.js(\?.*)?$/, '');
  var cachedEmails = null;
  var cachedTrafficType = null;
  var cachedSiteId = null;

  var SEARCH_ENGINE_HOSTS = [
    'google.',
    'yandex.',
    'ya.ru',
    'bing.com',
    'yahoo.',
    'duckduckgo.com',
    'mail.ru',
    'rambler.',
    'search.',
  ];

  var PAID_MEDIUMS = ['cpc', 'ppc', 'paid', 'cpm', 'cpv', 'display', 'retargeting', 'remarketing', 'banner'];

  function detectTrafficType() {
    var params = new URLSearchParams(window.location.search);
    var referrer = document.referrer || '';
    var refHost = '';

    try {
      refHost = referrer ? new URL(referrer).hostname.toLowerCase() : '';
    } catch (error) {
      refHost = '';
    }

    if (
      params.has('yclid') ||
      params.has('gclid') ||
      params.has('fbclid') ||
      params.has('msclkid') ||
      params.has('dclid')
    ) {
      return 'ads';
    }

    var medium = (params.get('utm_medium') || '').toLowerCase();
    var source = (params.get('utm_source') || '').toLowerCase();

    if (PAID_MEDIUMS.indexOf(medium) >= 0) {
      return 'ads';
    }

    if (medium === 'organic' || medium === 'search') {
      return 'seo';
    }

    if (
      source &&
      (source.indexOf('direct') >= 0 ||
        source.indexOf('yandex') >= 0 ||
        source.indexOf('google') >= 0 ||
        source.indexOf('vk_ads') >= 0 ||
        source.indexOf('mytarget') >= 0)
    ) {
      if (medium && medium !== 'organic' && medium !== 'referral') {
        return 'ads';
      }
    }

    if (refHost) {
      if (refHost === window.location.hostname.toLowerCase()) {
        return null;
      }

      for (var i = 0; i < SEARCH_ENGINE_HOSTS.length; i++) {
        if (refHost.indexOf(SEARCH_ENGINE_HOSTS[i]) >= 0) {
          return 'seo';
        }
      }

      return 'other';
    }

    return 'other';
  }

  function getTrafficType(siteId) {
    if (cachedTrafficType) {
      return cachedTrafficType;
    }

    var storageKey = 'wbooster_traffic_' + siteId;

    try {
      var stored = sessionStorage.getItem(storageKey);
      if (stored === 'ads' || stored === 'seo' || stored === 'other') {
        cachedTrafficType = stored;
        return stored;
      }
    } catch (error) {}

    var detected = detectTrafficType();

    if (detected === null) {
      try {
        var previous = sessionStorage.getItem(storageKey);
        if (previous === 'ads' || previous === 'seo' || previous === 'other') {
          cachedTrafficType = previous;
          return previous;
        }
      } catch (error) {}

      detected = 'other';
    }

    cachedTrafficType = detected;

    try {
      sessionStorage.setItem(storageKey, detected);
    } catch (error) {}

    return detected;
  }

  function pick(emails, type) {
    var selected = emails[type];

    if (selected) {
      return selected;
    }

    return emails.ads || emails.seo || emails.other || null;
  }

  function setEmail(el, email) {
    if (!email) {
      return;
    }

    if (el.tagName === 'A') {
      el.setAttribute('href', 'mailto:' + email);
    }

    if (el.hasAttribute('data-wbooster-email') || el.getAttribute('data-wbooster-replace-text') === 'true') {
      el.textContent = email;
      return;
    }

    if (/@/.test(el.textContent || '')) {
      el.textContent = email;
    }
  }

  function applyEmails(emails, trafficType) {
    var email = pick(emails, trafficType);

    if (!email) {
      return;
    }

    document.querySelectorAll('a[href^="mailto:"]').forEach(function (el) {
      setEmail(el, email);
    });

    document.querySelectorAll('[data-wbooster-email]').forEach(function (el) {
      setEmail(el, email);
    });
  }

  function render() {
    if (!cachedEmails || !cachedSiteId) {
      return;
    }

    applyEmails(cachedEmails, getTrafficType(cachedSiteId));
  }

  function load() {
    fetch(baseUrl + '/embed/config?token=' + encodeURIComponent(token), {
      credentials: 'omit',
      mode: 'cors',
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('config failed');
        }

        return response.json();
      })
      .then(function (payload) {
        if (!payload || !payload.emails) {
          return;
        }

        cachedEmails = payload.emails;
        cachedSiteId = payload.site_id || token.split(':')[0] || 'site';
        render();
      })
      .catch(function () {});
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', load);
  } else {
    load();
  }

  if (typeof MutationObserver === 'undefined') {
    return;
  }

  var scheduled = false;
  var observer = new MutationObserver(function () {
    if (!cachedEmails || scheduled) {
      return;
    }

    scheduled = true;
    setTimeout(function () {
      scheduled = false;
      render();
    }, 300);
  });

  observer.observe(document.documentElement, { childList: true, subtree: true });
})();

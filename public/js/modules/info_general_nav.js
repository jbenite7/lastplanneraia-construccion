(function (window, document) {
  'use strict';

  var ITEMS = {
    listado: {
      id: 'btn_Actividades',
      label: 'Familias de obra',
      icon: 'fas fa-table',
      path: '/listado-actividades',
      origin: 'info_listadoActividades',
    },
    contratos: {
      id: 'btn_contratos',
      label: 'Paquetes de contratacion',
      icon: 'fas fa-file-alt',
      path: '/contratos',
      origin: 'info_contratos',
    },
    pdc: {
      id: 'btn_planCompras',
      label: 'Plan de Compras y Contrataciones',
      icon: 'fas fa-shopping-cart',
      path: '/pdc',
      origin: 'planCompras',
    },
  };

  var ORDER = ['listado', 'contratos', 'pdc'];

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function buildUrl(item, semana, originOverride) {
    var url = item.path + '?semana=' + encodeURIComponent(semana || '');
    var origin = originOverride || item.origin;
    if (item.path === '/pdc') {
      url += '&origen=' + encodeURIComponent(origin || 'planCompras');
    }
    return url;
  }

  function render(activeKey, semana, pdcOrigin) {
    var active = ITEMS[activeKey] || ITEMS.pdc;
    var activeUrl = buildUrl(active, semana, pdcOrigin);
    var buttons = ORDER.map(function (key) {
      var item = ITEMS[key];
      var isActive = key === activeKey;
      var href = key === 'pdc' ? buildUrl(item, semana, pdcOrigin) : buildUrl(item, semana);
      return [
        '<button id="', item.id, '" type="button" class="aia-info-nav__item', isActive ? ' is-active' : '', '"',
        ' data-href="', escapeHtml(href), '" role="menuitem"',
        isActive ? ' aria-current="page"' : '',
        '>',
        '<i class="', item.icon, '" aria-hidden="true"></i>',
        '<span>', escapeHtml(item.label), '</span>',
        isActive ? '<i class="fas fa-check aia-info-nav__check" aria-hidden="true"></i>' : '',
        '</button>',
      ].join('');
    }).join('');

    return [
      '<div class="grupo_botones_semanal_madre ps-toolbar-nav-wrap">',
      '<div class="ps-module-switcher aia-info-nav" data-aia-info-nav>',
      '<button type="button" class="aia-info-nav__trigger" aria-haspopup="menu" aria-expanded="false" data-aia-info-nav-trigger data-href="', escapeHtml(activeUrl), '">',
      '<span class="aia-info-nav__eyebrow">Informacion General</span>',
      '<span class="aia-info-nav__current"><i class="', active.icon, '" aria-hidden="true"></i><span>', escapeHtml(active.label), '</span></span>',
      '<i class="fas fa-chevron-down aia-info-nav__chevron" aria-hidden="true"></i>',
      '</button>',
      '<div class="aia-info-nav__menu" role="menu" data-aia-info-nav-menu>',
      buttons,
      '</div>',
      '</div>',
      '</div>',
    ].join('');
  }

  function closeAll(except) {
    document.querySelectorAll('[data-aia-info-nav].is-open').forEach(function (nav) {
      if (nav === except) return;
      nav.classList.remove('is-open');
      var trigger = nav.querySelector('[data-aia-info-nav-trigger]');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
    });
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-aia-info-nav-trigger]');
    if (trigger) {
      var nav = trigger.closest('[data-aia-info-nav]');
      var willOpen = nav && !nav.classList.contains('is-open');
      closeAll(nav);
      if (nav) {
        nav.classList.toggle('is-open', willOpen);
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      }
      return;
    }

    var item = event.target.closest('.aia-info-nav__item');
    if (item && item.dataset.href) {
      window.location.href = item.dataset.href;
      return;
    }

    if (!event.target.closest('[data-aia-info-nav]')) {
      closeAll();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeAll();
    }
  });

  window.AIAInfoGeneralNav = {
    render: render,
  };
})(window, document);

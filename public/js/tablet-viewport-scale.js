(function () {
  'use strict';

  var TABLET_VIEWPORT = 'width=device-width, initial-scale=0.85, minimum-scale=0.85, maximum-scale=1.2, user-scalable=yes';
  var DEFAULT_VIEWPORT = 'width=device-width, initial-scale=1';
  var DESKTOP_SCALE = 1;
  var TABLET_WIDTH_MIN = 700;
  var TABLET_WIDTH_MAX = 1180;
  var RESIZE_DEBOUNCE_MS = 180;
  var lastScaleMode = '';

  function notifyScaleMode(mode) {
    if (lastScaleMode === mode) {
      return;
    }

    lastScaleMode = mode;

    var event;
    try {
      event = new CustomEvent('aia:viewport-scale-change', { detail: { mode: mode } });
    } catch (error) {
      event = document.createEvent('CustomEvent');
      event.initCustomEvent('aia:viewport-scale-change', true, true, { mode: mode });
    }

    window.dispatchEvent(event);
  }

  function isTabletDevice() {
    var ua = navigator.userAgent || '';
    var minEdge = Math.min(window.screen.width || 0, window.screen.height || 0);
    var maxEdge = Math.max(window.screen.width || 0, window.screen.height || 0);
    var touchPoints = navigator.maxTouchPoints || 0;
    var coarsePointer = !!(window.matchMedia && window.matchMedia('(pointer: coarse)').matches);

    var isIPad = /iPad/.test(ua) || (navigator.platform === 'MacIntel' && touchPoints > 1);
    var isAndroidTablet = /Android/.test(ua) && !/Mobile/.test(ua);

    if (isIPad || isAndroidTablet) {
      return true;
    }

    var isTouchTabletWindow = (touchPoints > 1 || coarsePointer) && minEdge >= 600 && minEdge <= 1100 && maxEdge <= TABLET_WIDTH_MAX;
    return isTouchTabletWindow;
  }

  function getViewportWidth() {
    var winWidth = window.innerWidth || 0;
    var docWidth = (document.documentElement && document.documentElement.clientWidth) || 0;
    return Math.max(winWidth, docWidth);
  }

  function isTabletSizedViewport() {
    var width = getViewportWidth();
    return width >= TABLET_WIDTH_MIN && width <= TABLET_WIDTH_MAX;
  }

  function supportsZoom() {
    return !!(window.CSS && typeof window.CSS.supports === 'function' && window.CSS.supports('zoom', '1'));
  }

  function ensureViewportMeta() {
    var viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
      return viewport;
    }

    viewport = document.createElement('meta');
    viewport.setAttribute('name', 'viewport');
    viewport.setAttribute('content', DEFAULT_VIEWPORT);
    if (document.head) {
      document.head.appendChild(viewport);
    }

    return viewport;
  }

  function resetDesktopScale() {
    var root = document.documentElement;
    var body = document.body;
    if (!root || !body) {
      return;
    }

    root.classList.remove('desktop-tablet-scale-70');
    root.style.removeProperty('zoom');
    root.style.removeProperty('width');

    body.style.removeProperty('transform');
    body.style.removeProperty('transform-origin');
    body.style.removeProperty('width');
    body.style.removeProperty('min-height');
  }

  function applyDesktopScale() {
    var root = document.documentElement;
    var body = document.body;
    if (!root || !body) {
      return;
    }

    var scaleText = String(DESKTOP_SCALE);
    root.classList.add('desktop-tablet-scale-70');

    if (supportsZoom()) {
      body.style.removeProperty('transform');
      body.style.removeProperty('transform-origin');
      body.style.removeProperty('width');
      body.style.removeProperty('min-height');

      root.style.zoom = scaleText;
      root.style.width = (100 / DESKTOP_SCALE) + '%';
      return;
    }

    root.style.removeProperty('zoom');
    root.style.removeProperty('width');

    body.style.transform = 'scale(' + scaleText + ')';
    body.style.transformOrigin = 'top left';
    body.style.width = (100 / DESKTOP_SCALE) + '%';
    body.style.minHeight = (100 / DESKTOP_SCALE) + 'vh';
  }

  function applyTabletViewportScale() {
    var viewport = ensureViewportMeta();
    if (!viewport) {
      return;
    }

    var original = viewport.getAttribute('data-original-viewport');
    if (!original) {
      original = viewport.getAttribute('content') || DEFAULT_VIEWPORT;
      viewport.setAttribute('data-original-viewport', original);
    }

    var tabletDevice = isTabletDevice();

    if (tabletDevice) {
      viewport.setAttribute('content', TABLET_VIEWPORT);
      document.documentElement.classList.add('tablet-scale-70');
      resetDesktopScale();
      notifyScaleMode('tablet-device');
      return;
    }

    viewport.setAttribute('content', original);
    document.documentElement.classList.remove('tablet-scale-70');

    if (isTabletSizedViewport()) {
      applyDesktopScale();
      notifyScaleMode('desktop-tablet');
      return;
    }

    resetDesktopScale();
    notifyScaleMode('default');
  }

  var resizeTimer = null;
  function onResize() {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(applyTabletViewportScale, RESIZE_DEBOUNCE_MS);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyTabletViewportScale, { once: true });
  } else {
    applyTabletViewportScale();
  }

  window.addEventListener('resize', onResize);
  window.addEventListener('orientationchange', applyTabletViewportScale);
})();

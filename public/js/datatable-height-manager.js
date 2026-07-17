(function (global) {
  'use strict';

  var DEFAULTS = {
    container: '#cuadroTabla',
    internalChrome: 170,
    bottomMargin: 25,
    minHeight: 200,
    widthRatio: 0.98,
    minWidth: 320,
  };

  function toNumber(value, fallback) {
    var parsed = parseFloat(value);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function getViewportHeight() {
    if (global.visualViewport && Number.isFinite(global.visualViewport.height) && global.visualViewport.height > 0) {
      return global.visualViewport.height;
    }

    var docHeight = document.documentElement && document.documentElement.clientHeight;
    return toNumber(global.innerHeight, toNumber(docHeight, 0));
  }

  function getViewportWidth() {
    if (global.visualViewport && Number.isFinite(global.visualViewport.width) && global.visualViewport.width > 0) {
      return global.visualViewport.width;
    }

    var docWidth = document.documentElement && document.documentElement.clientWidth;
    return toNumber(global.innerWidth, toNumber(docWidth, 0));
  }

  function resolveContainer(containerRef) {
    if (typeof containerRef === 'string') {
      return document.querySelector(containerRef);
    }

    return containerRef || null;
  }

  function getContainerTop(containerRef) {
    var container = resolveContainer(containerRef);

    if (!container || !container.getBoundingClientRect) {
      return 0;
    }

    var top = container.getBoundingClientRect().top;
    return top > 0 ? top : 0;
  }

  function getScaleFactor() {
    var root = document.documentElement;
    if (!root) {
      return 1;
    }

    var zoom = toNumber(root.style.zoom, NaN);

    if (Number.isFinite(zoom) && zoom > 0) {
      return zoom;
    }

    if (root.classList.contains('tablet-scale-70')) {
      return 0.85;
    }

    return 1;
  }

  function mergeOptions(options) {
    var merged = {
      container: DEFAULTS.container,
      internalChrome: DEFAULTS.internalChrome,
      bottomMargin: DEFAULTS.bottomMargin,
      minHeight: DEFAULTS.minHeight,
      widthRatio: DEFAULTS.widthRatio,
      minWidth: DEFAULTS.minWidth,
    };

    if (!options) {
      return merged;
    }

    if (options.container) merged.container = options.container;
    if (options.internalChrome !== undefined) merged.internalChrome = toNumber(options.internalChrome, merged.internalChrome);
    if (options.bottomMargin !== undefined) merged.bottomMargin = toNumber(options.bottomMargin, merged.bottomMargin);
    if (options.minHeight !== undefined) merged.minHeight = toNumber(options.minHeight, merged.minHeight);
    if (options.widthRatio !== undefined) merged.widthRatio = toNumber(options.widthRatio, merged.widthRatio);
    if (options.minWidth !== undefined) merged.minWidth = toNumber(options.minWidth, merged.minWidth);

    return merged;
  }

  function calcWidth(options) {
    var cfg = mergeOptions(options);
    var viewportWidth = getViewportWidth();
    var scaleFactor = getScaleFactor();
    var ratio = cfg.widthRatio > 0 && cfg.widthRatio <= 1 ? cfg.widthRatio : DEFAULTS.widthRatio;
    var width = viewportWidth * ratio;

    if (scaleFactor > 0 && scaleFactor < 1) {
      width = width / scaleFactor;
    }

    width = Math.max(cfg.minWidth, Math.floor(width));
    return width + 'px';
  }

  function applyContainerWidth(containerRef, options) {
    var container = resolveContainer(containerRef || (options && options.container));
    if (!container) {
      return null;
    }

    // Skip fixed-width injection for containers inside Bootstrap modals
    if (container.closest && container.closest('.modal')) {
      return null;
    }

    var width = calcWidth(options);
    container.style.width = width;
    container.style.maxWidth = width;
    container.style.marginLeft = 'auto';
    container.style.marginRight = 'auto';

    return width;
  }

  function calcHeight(options) {
    var cfg = mergeOptions(options);
    applyContainerWidth(cfg.container, cfg);
    var viewportHeight = getViewportHeight();
    var topOffset = getContainerTop(cfg.container);
    var availableHeight = viewportHeight - topOffset - cfg.internalChrome - cfg.bottomMargin;
    var scaleFactor = getScaleFactor();

    if (scaleFactor > 0 && scaleFactor < 1) {
      availableHeight = availableHeight / scaleFactor;
    }

    var finalHeight = Math.max(cfg.minHeight, Math.floor(availableHeight));
    return finalHeight + 'px';
  }

  function resolveDataTable(tableRef) {
    var $ = global.jQuery;
    if (!$ || !$.fn || !$.fn.DataTable) {
      return null;
    }

    if (tableRef && typeof tableRef.settings === 'function') {
      return tableRef;
    }

    if (tableRef && tableRef.jquery) {
      if (tableRef.length && $.fn.DataTable.isDataTable(tableRef.get(0))) {
        return tableRef.DataTable();
      }
      return null;
    }

    if (typeof tableRef === 'string') {
      var $table = $(tableRef);
      if ($table.length && $.fn.DataTable.isDataTable($table.get(0))) {
        return $table.DataTable();
      }
      return null;
    }

    if (tableRef && tableRef.nodeType === 1 && $.fn.DataTable.isDataTable(tableRef)) {
      return $(tableRef).DataTable();
    }

    return null;
  }

  function applyToDataTable(tableRef, options) {
    var cfg = mergeOptions(options);
    var width = applyContainerWidth(cfg.container, cfg);
    var newHeight = calcHeight(cfg);
    var api = resolveDataTable(tableRef);

    if (!api) {
      return newHeight;
    }

    var settings = api.settings && api.settings()[0];
    if (!settings) {
      return newHeight;
    }

    var scrollBody = settings.nScrollBody;
    if (scrollBody) {
      scrollBody.style.height = newHeight;
      scrollBody.style.maxHeight = newHeight;
    }

    if (settings.oScroll) {
      settings.oScroll.sY = newHeight;
    }

    if (width) {
      var wrapper = settings.nTableWrapper;
      if (wrapper) {
        wrapper.style.width = '100%';
        wrapper.style.maxWidth = '100%';
      }

      var tableNode = api.table().node();
      if (tableNode) {
        tableNode.style.width = '100%';
      }
    }

    api.columns.adjust();
    return newHeight;
  }

  global.DataTableHeightManager = {
    calcHeight: calcHeight,
    calcWidth: calcWidth,
    applyContainerWidth: applyContainerWidth,
    applyToDataTable: applyToDataTable,
    getScaleFactor: getScaleFactor,
  };
})(window);

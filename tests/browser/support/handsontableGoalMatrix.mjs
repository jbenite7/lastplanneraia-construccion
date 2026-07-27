export const HANDSONTABLE_GOAL_VIEWPORTS = [
  { name: 'mobile', width: 390, height: 844 },
  { name: 'tablet', width: 1024, height: 768 },
  { name: 'desktop', width: 1440, height: 900 },
];

// F0/Task 9: dark es el unico tema. El array sobrevive como marcador del eje
// "por cada tema gobernado" (hoy uno solo) para los tres specs que lo consumen.
export const HANDSONTABLE_GOAL_THEMES = ['dark'];

const DEFAULT_SELECTORS = {
  hot: '.handsontable',
  cards: '[data-hot-mobile-cards]',
  controls: 'button, a, input, select, textarea, [role="button"]',
  headers: '#hot-container .ht_clone_top thead th',
  cells: '#hot-container .ht_master tbody tr:first-child td',
  dataTableWrappers: '.dataTables_wrapper',
};

function resolveSelectors(selectors = {}) {
  return { ...DEFAULT_SELECTORS, ...selectors };
}

export async function setHandsontableGoalTheme(page, theme) {
  if (!HANDSONTABLE_GOAL_THEMES.includes(theme)) {
    throw new Error(`Unsupported Handsontable goal theme: ${theme}`);
  }

  // F0/Task 8: theme.js ya no expone setTheme; dark se aplica sin conmutacion.
  // Esta llamada solo confirma el estado en vez de forzarlo.
  await page.waitForFunction(
    (expected) => document.documentElement.dataset.aiaTheme === expected,
    theme,
  );
}

export async function measureHandsontableGoalMatrix(page, selectors = {}) {
  const resolved = resolveSelectors(selectors);
  return page.evaluate((config) => {
    const isVisible = (element) => {
      if (!element || element.hidden || getComputedStyle(element).display === 'none'
        || getComputedStyle(element).visibility === 'hidden' || !element.getClientRects().length) return false;
      const rect = element.getBoundingClientRect();
      return rect.bottom > 0 && rect.right > 0 && rect.top < innerHeight && rect.left < innerWidth;
    };
    const text = (element) => (element.ariaLabel || element.textContent || element.value || '').trim();
    const html = document.documentElement;
    const controls = [...document.querySelectorAll(config.controls)].filter(isVisible);
    const hotNodes = [...document.querySelectorAll(config.hot)];
    const hotHolders = hotNodes
      .filter((node) => node.classList.contains('ht_master'))
      .flatMap((node) => [...node.querySelectorAll('.wtHolder')]);
    const cards = [...document.querySelectorAll(config.cards)];
    const headers = [...document.querySelectorAll(config.headers)].filter(isVisible);
    const cells = [...document.querySelectorAll(config.cells)].filter(isVisible);
    const alignment = headers.map((header, index) => {
      const cell = cells[index];
      const headerRect = header.getBoundingClientRect();
      const cellRect = cell?.getBoundingClientRect();
      return {
        index,
        headerLeft: headerRect.left,
        headerWidth: headerRect.width,
        cellLeft: cellRect?.left ?? null,
        cellWidth: cellRect?.width ?? null,
        aligned: Boolean(
          cellRect
          && Math.abs(headerRect.left - cellRect.left) <= 3
          && Math.abs(headerRect.width - cellRect.width) <= 4,
        ),
      };
    });
    const alignmentAvailable = headers.length > 0 && cells.length >= headers.length;
    const dataTableScripts = [...document.scripts]
      .map((script) => script.src)
      .filter((src) => /datatables|gyrocode/i.test(src));
    const dataTableStyles = [...document.querySelectorAll('link[rel="stylesheet"]')]
      .map((link) => link.href)
      .filter((href) => /datatables|gyrocode/i.test(href));
    const delegatedSelectors = Object.values(window.jQuery?._data(document, 'events') || {})
      .flatMap((handlers) => Array.from(handlers))
      .map((handler) => handler.selector || '')
      .filter((selector) => /dataTables/i.test(selector));
    const listenerTargets = [
      document,
      window,
      ...document.querySelectorAll('#dt_cliente, #dt_definirContratos, #pdc-hot-shell'),
    ];
    const legacyListeners = listenerTargets.flatMap((target) => (
      Object.entries(window.jQuery?._data(target, 'events') || {}).flatMap(([type, handlers]) => (
        Array.from(handlers).map((handler) => ({
          type: handler.origType || type,
          namespace: handler.namespace || '',
          selector: handler.selector || '',
        }))
      ))
    )).filter((listener) => (
      /(^|\.)dt(\.|$)|datatables/i.test(listener.namespace)
      || /dataTables|\.dataTable|#dt_cliente_wrapper/i.test(listener.selector)
    ));
    const rawHtmlText = [...new Set([
      ...hotNodes.flatMap((node) => [...node.querySelectorAll('td')]),
      ...cards,
    ].filter(isVisible).map(text).filter((value) => /<\/?[a-z][^>]*>/i.test(value)))];
    const contentNodes = [...document.querySelectorAll([
      config.headers,
      config.cells,
      '.ct-mobile-card__title',
      '.ct-mobile-card__summary',
      '.ct-mobile-card__description',
    ].join(','))].filter((element) => isVisible(element) && text(element) !== '');

    return {
      theme: html.dataset.aiaTheme || null,
      darkBody: document.body.classList.contains('dark-mode'),
      pageOverflowX: Math.max(0, html.scrollWidth - html.clientWidth),
      overflowingControls: controls
        .filter((element) => element.scrollWidth > element.clientWidth + 1)
        .map(text),
      overflowingContent: contentNodes
        .filter((element) => element.scrollWidth > element.clientWidth + 1)
        .map((element) => ({ text: text(element), client: element.clientWidth, scroll: element.scrollWidth })),
      hot: {
        count: hotNodes.length,
        visible: hotNodes.filter(isVisible).length,
        masters: hotNodes.filter((node) => node.classList.contains('ht_master')).length,
        overflowX: Math.max(0, ...hotHolders.filter(isVisible)
          .map((holder) => holder.scrollWidth - holder.clientWidth)),
        holderWidths: hotHolders.filter(isVisible).map((holder) => ({
          client: holder.clientWidth,
          scroll: holder.scrollWidth,
        })),
        masterWidths: hotNodes.filter((node) => node.classList.contains('ht_master')).map((node) => ({
          client: node.clientWidth,
          parent: node.parentElement?.clientWidth || 0,
          container: node.closest('.pdc-hot-grid, #hot-container')?.clientWidth || 0,
        })),
      },
      cards: { count: cards.length, visible: cards.filter(isVisible).length },
      dataTables: {
        wrappers: document.querySelectorAll(config.dataTableWrappers).length,
        scripts: dataTableScripts,
        styles: dataTableStyles,
        plugin: Boolean(window.jQuery?.fn?.dataTable || window.jQuery?.fn?.DataTable),
        legacyDom: document.querySelectorAll('table.dataTable').length,
        delegatedSelectors,
      },
      legacyDataTableListeners: legacyListeners,
      rawHtmlText,
      headerCellAlignment: {
        available: alignmentAvailable,
        aligned: alignmentAvailable && alignment.every((item) => item.aligned),
        columns: alignment,
      },
    };
  }, resolved);
}

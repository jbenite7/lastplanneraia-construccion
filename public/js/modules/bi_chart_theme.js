/**
 * bi_chart_theme.js
 *
 * Tema dark de Chart.js para BI Control Tower: fija los defaults globales
 * (texto, grid, tooltip) leyendo tokens del DS (`--ds-*` / `--aia-*` en
 * public/css/tokens.css), y expone `window.BiChartTheme` con una paleta
 * categórica de series validada para dark con la skill `dataviz`.
 *
 * La validación se corrió contra el color YA CONVERTIDO por el motor CSS
 * del navegador (algunos tokens se declaran en oklch(); el fillStyle real
 * que Canvas/Chart.js pinta puede diferir del hex aproximado en los
 * comentarios de tokens.css por el gamut-mapping de cada motor) — se leyó
 * cada token con getComputedStyle + un <canvas> de prueba y ESE hex es el
 * que se validó: `node scripts/validate_palette.js "#009b93,#e85851,
 * #877cd1,#c57247,#2f79b7,#e36307" --mode dark --surface "#0b100d"` →
 * ALL CHECKS PASS (banda de luminosidad, piso de croma, separación CVD
 * adyacente y piso de visión normal, contraste >= 3:1). Los primeros 3
 * slots también pasan `--pairs all` (seguro para donut/pie hasta 3
 * categorías simultáneas; más allá de eso, la identidad se apoya en la
 * leyenda + tooltip ya presentes en cada chart, no solo en el color).
 *
 * No crea charts ni toca la lógica de datos de bi-spa.js — solo defaults
 * globales + la paleta. Debe cargarse ANTES de bi-spa.js (ver
 * views/bi/_layout.php) para que Chart.defaults ya esté fijado cuando
 * bi-spa.js instancie los Chart.js.
 */
(() => {
  var TEXT_TOKENS = {
    text: "--ds-active-text-primary",
    muted: "--ds-active-text-secondary",
    grid: "--ds-active-border",
    surface: "--ds-active-surface",
    tooltipSurface: "--ds-active-surface-raised",
  };

  // Orden fijo, nunca ciclado (ver dataviz: color-formula.md, check 1).
  // Cada slot resuelve un token de public/css/tokens.css — sin hex nuevos.
  // Los fallbacks son el hex realmente pintado por Canvas para ese token
  // (ver nota arriba sobre gamut-mapping de oklch()), no el comentario de
  // tokens.css.
  var SERIES_TOKENS = [
    "--aia-aqua-primary", // #009b93 (renderizado)
    "--aia-alert-medium", // #e85851 (renderizado)
    "--ds-color-domain-architecture-on-dark", // #877cd1
    "--ds-color-domain-construction-on-dark", // #c57247
    "--aia-blue-primary", // #2f79b7 (renderizado)
    "--aia-orange-medium", // #e36307 (renderizado)
  ];

  var SERIES_FALLBACKS = ["#009b93", "#e85851", "#877cd1", "#c57247", "#2f79b7", "#e36307"];

  var TEXT_FALLBACKS = {
    text: "#f7faf8",
    muted: "#c7d4cc",
    grid: "rgba(221, 239, 230, 0.22)",
    surface: "rgba(28, 36, 31, 0.92)",
    tooltipSurface: "rgba(35, 48, 41, 0.86)",
  };

  function readToken(name) {
    var value = getComputedStyle(document.documentElement).getPropertyValue(name);
    return value ? value.trim() : "";
  }

  function buildTheme() {
    var text = readToken(TEXT_TOKENS.text) || TEXT_FALLBACKS.text;
    var muted = readToken(TEXT_TOKENS.muted) || TEXT_FALLBACKS.muted;
    var grid = readToken(TEXT_TOKENS.grid) || TEXT_FALLBACKS.grid;
    var surface = readToken(TEXT_TOKENS.surface) || TEXT_FALLBACKS.surface;
    var tooltipSurface = readToken(TEXT_TOKENS.tooltipSurface) || TEXT_FALLBACKS.tooltipSurface;
    var colors = SERIES_TOKENS.map((token, index) => readToken(token) || SERIES_FALLBACKS[index]);

    return {
      text: text,
      muted: muted,
      grid: grid,
      surface: surface,
      tooltipSurface: tooltipSurface,
      colors: colors,
      color: (index) => (colors.length ? colors[((index % colors.length) + colors.length) % colors.length] : muted),
    };
  }

  function applyDefaults(theme) {
    var Chart = window.Chart;
    if (!Chart?.defaults) return;

    Chart.defaults.color = theme.muted;
    Chart.defaults.borderColor = theme.grid;

    Chart.defaults.plugins = Chart.defaults.plugins || {};
    Chart.defaults.plugins.tooltip = Object.assign({}, Chart.defaults.plugins.tooltip, {
      backgroundColor: theme.tooltipSurface,
      titleColor: theme.text,
      bodyColor: theme.text,
      borderColor: theme.grid,
      borderWidth: 1,
    });
  }

  function refresh() {
    var theme = buildTheme();
    window.BiChartTheme = theme;
    applyDefaults(theme);
    return theme;
  }

  refresh();

  // El toggle de tema (si existiera) dispara `aia-theme-change`; bi-spa.js
  // ya destruye y re-renderiza los charts en ese evento (wireThemeEvents),
  // así que solo necesitamos recalcular el tema/paleta antes de que lo haga.
  document.addEventListener("aia-theme-change", refresh);
})();

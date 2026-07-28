// Sonda de contraste para modales. Compone alpha sobre los ancestros hasta la
// primera capa opaca y convierte cualquier notacion de color (color-mix, oklch,
// color(srgb ...)) via canvas: un parser de rgb() las descartaria en silencio y
// devolveria ceros falsos. Mismo metodo que la sonda del tramo 5c.

export const VIEWPORT = { width: 1180, height: 820 };

const PROBE = () => {
  const canvas = document.createElement('canvas');
  canvas.width = 1;
  canvas.height = 1;
  const ctx = canvas.getContext('2d', { willReadFrequently: true });

  const parseColor = (value) => {
    if (!value || value === 'transparent' || value === 'none') return [0, 0, 0, 0];
    ctx.globalCompositeOperation = 'copy';
    ctx.fillStyle = '#000000';
    ctx.fillStyle = value;
    ctx.fillRect(0, 0, 1, 1);
    const d = ctx.getImageData(0, 0, 1, 1).data;
    return [d[0], d[1], d[2], d[3] / 255];
  };

  const over = (fg, bg) => {
    const a = fg[3] + bg[3] * (1 - fg[3]);
    if (a === 0) return [0, 0, 0, 0];
    const mix = (i) => (fg[i] * fg[3] + bg[i] * bg[3] * (1 - fg[3])) / a;
    return [mix(0), mix(1), mix(2), a];
  };

  // LIMITE CONOCIDO: compone solo la cadena de ancestros. Para un elemento
  // `position` fuera de flujo (absolute/fixed) y translucido, el fondo real es
  // lo que solapa en orden Z (otro subarbol, no un ancestro), y esta funcion no
  // lo ve — infla el contraste medido (~0,2 puntos en el menu del conmutador,
  // /pdc: 3,85:1 real vs 4,09:1 de la sonda). No cambia ninguna conclusion hoy
  // (todo sigue >=3:1), pero el guard no detectaria una regresion causada por
  // el contenido que quede debajo de una superficie translucida superpuesta.
  //
  // Otro LIMITE CONOCIDO: compone solo `backgroundColor`, nunca
  // `backgroundImage`. Sobre una cabecera con degradado sigue subiendo por los
  // ancestros y sobreestima el contraste (tramo 5i: informo 15,5:1 donde la
  // verdad, contra las paradas reales del degradado, era 8,25:1). Ahi hay que
  // medir contra esas paradas, no con esta funcion.
  const effectiveBackground = (el) => {
    let acc = [0, 0, 0, 0];
    for (let node = el; node; node = node.parentElement) {
      acc = over(acc, parseColor(getComputedStyle(node).backgroundColor));
      if (acc[3] >= 0.999) return acc;
    }
    return over(acc, [255, 255, 255, 1]);
  };

  const luminance = ([r, g, b]) => {
    const f = (c) => {
      const s = c / 255;
      return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
    };
    return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
  };

  const fmt = (c) => `rgb(${c.slice(0, 3).map((v) => Math.round(v)).join(', ')})`;

  // La `opacity` de la cadena de ancestros multiplica la tinta pintada. Sin esto
  // un control deshabilitado (Bootstrap baja a .65) mide su color crudo y la
  // sonda sobreestima el contraste.
  const accumulatedOpacity = (el) => {
    let acc = 1;
    for (let node = el; node; node = node.parentElement) {
      const o = Number.parseFloat(getComputedStyle(node).opacity);
      if (Number.isFinite(o)) acc *= o;
    }
    return acc;
  };

  const contrast = (a, b) => {
    const [l1, l2] = [luminance(a), luminance(b)];
    return Math.round(((Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05)) * 100) / 100;
  };

  window.__aiaContrast = (selector) => {
    const el = document.querySelector(selector);
    if (!el) return null;
    const bg = effectiveBackground(el);
    const ink = parseColor(getComputedStyle(el).color);
    ink[3] *= accumulatedOpacity(el);
    const fg = over(ink, bg);
    return { ratio: contrast(fg, bg), fg: fmt(fg), bg: fmt(bg) };
  };

  // Un objeto grafico sin texto (una muestra de color) no se mide con
  // `__aiaContrast`: no tiene tinta. Lo que WCAG 1.4.11 pide es que su FRONTERA
  // se distinga de lo adyacente, y esa frontera tiene dos vecinos: el relleno
  // propio hacia dentro y el entorno hacia fuera. Se devuelven los dos ratios y
  // ademas el del relleno contra el entorno, para que quien aserte pueda exigir
  // "es perceptible" sin fijar CUAL de los dos mecanismos lo consigue.
  window.__aiaBoundary = (selector) => {
    const el = document.querySelector(selector);
    if (!el) return null;
    const cs = getComputedStyle(el);
    const opacity = accumulatedOpacity(el);
    const surround = el.parentElement
      ? effectiveBackground(el.parentElement)
      : [255, 255, 255, 1];

    // Relleno y borde ocupan regiones disjuntas del border-box, asi que una
    // `opacity` de grupo se aplica a cada uno por separado; con
    // `background-clip: border-box` (el defecto) el fondo si llega bajo el
    // borde, de ahi que el borde se componga sobre el relleno y no sobre el
    // entorno.
    const fillPaint = parseColor(cs.backgroundColor);
    fillPaint[3] *= opacity;
    const fill = over(fillPaint, surround);

    const borderPaint = parseColor(cs.borderTopColor);
    borderPaint[3] *= opacity * (Number.parseFloat(cs.borderTopWidth) > 0 ? 1 : 0);
    const border = over(borderPaint, fill);

    return {
      borderVsFill: contrast(border, fill),
      borderVsSurround: contrast(border, surround),
      fillVsSurround: contrast(fill, surround),
      border: fmt(border),
      fill: fmt(fill),
      surround: fmt(surround),
    };
  };
};

export async function installContrastProbe(page) {
  await page.addInitScript(PROBE);
  await page.evaluate(PROBE);
}

export async function openModal(page, modalId) {
  await page.evaluate((id) => window.jQuery(`#${id}`).modal('show'), modalId);
  await page.waitForFunction((id) => {
    const el = document.getElementById(id);
    if (!el) return false;
    return el.getBoundingClientRect().height > 0 && getComputedStyle(el).display !== 'none';
  }, modalId);
  // Las transiciones del shell corren a 0.15s; 450ms las cubre con margen.
  await page.waitForTimeout(450);
}

export async function closeModal(page, modalId) {
  await page.evaluate((id) => window.jQuery(`#${id}`).modal('hide'), modalId);
  await page.waitForTimeout(350);
}

export async function measure(page, selector) {
  return page.evaluate((sel) => window.__aiaContrast(sel), selector);
}

export async function measureBoundary(page, selector) {
  return page.evaluate((sel) => window.__aiaBoundary(sel), selector);
}

// Quien gana de verdad la cascada. Sin esto, un cambio puede "funcionar" por una
// regla heredada mientras la que se escribio queda inerte — que es exactamente lo
// que le paso al shell dentro de #modalContrato.
//
// IMPORTANTE: `matchedCSSRules` NO llega ordenado por prioridad real — su orden
// ignora `!important` y la inversion de capas. Tomar el ultimo elemento da un
// ganador falso (comprobado: reportaba styles.css donde ganaba un `!important`
// de vendor). La unica fuente de verdad es el valor COMPUTADO; las reglas se
// devuelven enteras, para diagnostico.
export async function matchedStyles(page, selector, property) {
  const cdp = await page.context().newCDPSession(page);
  const sheets = new Map();
  // El listener va ANTES de CSS.enable: enable reemite styleSheetAdded de todas
  // las hojas ya cargadas, y es la unica forma de mapear styleSheetId -> archivo.
  cdp.on('CSS.styleSheetAdded', ({ header }) => sheets.set(header.styleSheetId, header.sourceURL));
  await cdp.send('DOM.enable');
  await cdp.send('CSS.enable');

  const { root } = await cdp.send('DOM.getDocument', { depth: -1 });
  const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector });
  if (!nodeId) {
    await cdp.detach();
    return null;
  }

  const matched = await cdp.send('CSS.getMatchedStylesForNode', { nodeId });
  const hits = [];
  for (const entry of matched.matchedCSSRules ?? []) {
    const rule = entry.rule;
    const decls = (rule.style?.cssProperties ?? []).filter(
      (p) => p.name === property && !p.disabled && p.text,
    );
    if (decls.length === 0) continue;
    const last = decls[decls.length - 1];
    hits.push({
      selector: rule.selectorList?.text ?? '',
      layers: (rule.layers ?? []).map((l) => l.text).join('.') || '(sin capa)',
      file: sheets.get(rule.styleSheetId) ?? '(inline)',
      line: (last.range?.startLine ?? rule.style?.range?.startLine ?? -1) + 1,
      value: last.value,
      important: last.important === true,
    });
  }
  await cdp.detach();

  // La verdad sobre quien gano: el valor computado. `rules` es solo el mapa de
  // candidatos para poder decir DONDE hay que tocar cuando la asercion falla.
  const computed = await page.evaluate(
    ([sel, prop]) => {
      const el = document.querySelector(sel);
      return el ? getComputedStyle(el).getPropertyValue(prop) : null;
    },
    [selector, property],
  );

  return { computed, rules: hits };
}

// Formatea las reglas candidatas para el mensaje de fallo de una asercion.
export function explainRules({ rules }) {
  if (rules.length === 0) return 'ninguna regla declara esa propiedad';
  return rules
    .map((r) => `${r.file.split('/').pop()}:${r.line} [${r.layers}]${r.important ? ' !important' : ''} ${r.selector} -> ${r.value}`)
    .join('\n      ');
}

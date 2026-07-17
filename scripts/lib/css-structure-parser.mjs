function lineAt(source, index) {
  return source.slice(0, index).split('\n').length;
}

function cleanPrelude(value) {
  return value.replace(/\/\*[\s\S]*?\*\//g, ' ').trim();
}

function findClosingBrace(source, openIndex, limit) {
  let depth = 1;
  let quote = null;
  for (let index = openIndex + 1; index < limit; index += 1) {
    const char = source[index];
    if (quote) {
      if (char === '\\') index += 1;
      else if (char === quote) quote = null;
      continue;
    }
    if (char === '/' && source[index + 1] === '*') {
      index = source.indexOf('*/', index + 2);
      if (index < 0) return limit - 1;
      index += 1;
      continue;
    }
    if (char === '"' || char === "'") quote = char;
    else if (char === '{') depth += 1;
    else if (char === '}') {
      depth -= 1;
      if (depth === 0) return index;
    }
  }
  return limit - 1;
}

function pushDeclaration(source, segment, offset, declarations) {
  const colon = segment.indexOf(':');
  if (colon < 1) return;
  const property = segment.slice(0, colon).trim().toLowerCase();
  const rawValue = segment.slice(colon + 1).trim();
  const important = /!\s*important\s*$/i.test(rawValue);
  const value = rawValue.replace(/!\s*important\s*$/i, '').trim();
  if (!property) return;
  declarations.push({ property, value, important, line: lineAt(source, offset) });
}

function parseDeclarations(source, start, end) {
  const declarations = [];
  let segmentStart = start;
  let quote = null;
  let parentheses = 0;
  for (let index = start; index <= end; index += 1) {
    const char = source[index] || ';';
    if (quote) {
      if (char === '\\') index += 1;
      else if (char === quote) quote = null;
      continue;
    }
    if (char === '/' && source[index + 1] === '*') {
      index = source.indexOf('*/', index + 2);
      if (index < 0) break;
      index += 1;
      continue;
    }
    if (char === '"' || char === "'") quote = char;
    else if (char === '(') parentheses += 1;
    else if (char === ')') parentheses = Math.max(0, parentheses - 1);
    else if (char === ';' && parentheses === 0) {
      const segment = source.slice(segmentStart, index);
      pushDeclaration(source, segment, segmentStart, declarations);
      segmentStart = index + 1;
    }
  }
  return declarations;
}

function isNestedContainer(prelude) {
  return /^@(media|supports|container|scope|document)\b/i.test(prelude);
}

function layerName(prelude, currentLayer) {
  const match = prelude.match(/^@layer\s+([\w.-]+)/i);
  if (!match) return currentLayer;
  return currentLayer ? `${currentLayer}.${match[1]}` : match[1];
}

function parseRange(source, start, end, layer, rules) {
  let cursor = start;
  while (cursor < end) {
    while (/\s/.test(source[cursor] || '')) cursor += 1;
    if (source[cursor] === '/' && source[cursor + 1] === '*') {
      const close = source.indexOf('*/', cursor + 2);
      cursor = close < 0 ? end : close + 2;
      continue;
    }
    const preludeStart = cursor;
    let quote = null;
    let parentheses = 0;
    let delimiter = null;
    for (; cursor < end; cursor += 1) {
      const char = source[cursor];
      if (quote) {
        if (char === '\\') cursor += 1;
        else if (char === quote) quote = null;
        continue;
      }
      if (char === '/' && source[cursor + 1] === '*') {
        const close = source.indexOf('*/', cursor + 2);
        cursor = close < 0 ? end : close + 1;
        continue;
      }
      if (char === '"' || char === "'") quote = char;
      else if (char === '(') parentheses += 1;
      else if (char === ')') parentheses = Math.max(0, parentheses - 1);
      else if (parentheses === 0 && (char === '{' || char === ';' || char === '}')) {
        delimiter = char;
        break;
      }
    }
    if (!delimiter || delimiter === '}') return;
    if (delimiter === ';') {
      cursor += 1;
      continue;
    }
    const open = cursor;
    const close = findClosingBrace(source, open, end);
    const prelude = cleanPrelude(source.slice(preludeStart, open));
    if (/^@layer\b/i.test(prelude)) {
      parseRange(source, open + 1, close, layerName(prelude, layer), rules);
    } else if (isNestedContainer(prelude)) {
      parseRange(source, open + 1, close, layer, rules);
    } else if (!prelude.startsWith('@')) {
      rules.push({
        selector: prelude,
        layer,
        line: lineAt(source, preludeStart),
        declarations: parseDeclarations(source, open + 1, close),
      });
    }
    cursor = close + 1;
  }
}

export function parseCssStructure(source) {
  const rules = [];
  parseRange(String(source), 0, String(source).length, null, rules);
  return rules;
}

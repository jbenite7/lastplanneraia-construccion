const PATRON_CAPITULO = /<small>\s*\[\s*Cap[ií]tulo\s*:\s*([^\]]+?)\s*\]\s*<\/small>|\[\s*Cap[ií]tulo\s*:\s*([^\]]+?)\s*\]/iu;

function quitarEtiquetas(valor) {
  return String(valor).replace(/<[^>]*>/g, '');
}

export function separarCapitulo(actividad) {
  if (actividad === null || actividad === undefined) return { titulo: '', capitulo: null };
  const bruto = String(actividad);
  const coincidencia = bruto.match(PATRON_CAPITULO);
  const capitulo = coincidencia ? (coincidencia[1] || coincidencia[2] || '').trim() : null;
  const sinCapitulo = coincidencia ? bruto.replace(coincidencia[0], '') : bruto;
  return {
    titulo: quitarEtiquetas(sinCapitulo).replace(/\s+/g, ' ').trim(),
    capitulo: capitulo || null,
  };
}

if (typeof window !== 'undefined') {
  window.AIACardTitle = { separarCapitulo };
}

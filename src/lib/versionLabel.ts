export function etiquetaVersion(v: { versionNumero: number; createdAt: string; versionLabel?: string | null }): string {
  const base = `Versión ${v.versionNumero} · ${v.createdAt}`
  const label = (v.versionLabel ?? '').trim()
  return label === '' ? base : `${base} · ${label}`
}

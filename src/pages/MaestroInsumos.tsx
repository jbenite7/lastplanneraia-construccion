import { useEffect, useMemo, useState } from 'react'
import { AgGridReact } from 'ag-grid-react'
import { AllCommunityModule, ModuleRegistry, themeQuartz } from 'ag-grid-community'
import type { ColDef } from 'ag-grid-community'
import { getBootstrap } from '../lib/bootstrap'
import type { Bootstrap } from '../lib/types'

// AG Grid v33+: registro explícito de módulos (Community completo).
ModuleRegistry.registerModules([AllCommunityModule])

// Tema oscuro alineado al design system aia — Theming API de Community.
const pdcTheme = themeQuartz.withParams({
  backgroundColor: '#1c1c1e',
  foregroundColor: '#f4f1ea',
  accentColor: '#69b578',
  headerBackgroundColor: '#1a3c2a',
})

type Fila = { campo: string; valor: string }

export default function MaestroInsumos() {
  const [boot, setBoot] = useState<Bootstrap | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    getBootstrap().then(setBoot).catch((e: Error) => setError(e.message))
  }, [])

  const rows: Fila[] = useMemo(() => boot ? [
    { campo: 'Proyecto', valor: `${boot.projectId} — ${boot.proyectoNombre}` },
    { campo: 'Usuario', valor: boot.usuario },
    { campo: 'Rol', valor: boot.rol },
  ] : [], [boot])

  const cols: ColDef<Fila>[] = [
    { field: 'campo', headerName: 'Campo', flex: 1 },
    { field: 'valor', headerName: 'Valor', flex: 2 },
  ]

  if (error) return <div className="pdc-error" role="alert">Error: {error}</div>

  return (
    <section className="pdc-page">
      <header className="pdc-header">
        <h1>Plan de Compras</h1>
        <p data-testid="pdc-contexto">
          {boot ? `${boot.proyectoNombre} · ${boot.usuario} (${boot.rol})` : 'Cargando contexto…'}
        </p>
      </header>
      <div style={{ height: 320 }}>
        <AgGridReact<Fila> theme={pdcTheme} rowData={rows} columnDefs={cols} />
      </div>
    </section>
  )
}

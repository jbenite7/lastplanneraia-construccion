---
capa: wiki
tipo: mapa
estado: vigente
fecha: 2026-08-19
areas: [bi]
fuente: los módulos y decisiones del área
resumen: "Los dos módulos de consulta —Indicadores y Torre de Control— qué cifra muestra cada uno y a quién"
---

# Mapa · BI

## Qué manda

`docs/flujos/lectura-bi.md` — los escenarios `BI-`: qué cifras se muestran, a quién y de qué
fuente. El alcance por proyecto lo resuelve `App\Support\BiProjectScope`.

## Los dos módulos, que no son lo mismo

- [[indicadores]] — dashboard de KPIs que **embebe un informe de Power BI**. Público interno.
- [[torre-de-control-bi]] — los reportes consolidados de LPS y PDC. **Fuera de la navegación
  mientras se desarrolla**, a propósito: ver [[control-tower-oculto-mientras-se-desarrolla]].

## Decisiones que explican lo que ves

- [[powerbi-indicadores]] — `/indicadores` dejó Data Studio y embebe Power BI por *publish-to-web*.
- [[control-tower-oculto-mientras-se-desarrolla]] — vivo pero sin entrada en el menú.

## Trampas

- [[el-item-oculto-del-sidebar-rompe-su-propio-modulo]] — quitar un item del sidebar rompe con 500
  las pantallas de ese módulo. Es la trampa que paga ocultar la Torre de Control.
- [[indicadores-oculta-en-cliente-bi-en-servidor]] — **derogada** el 2026-08-06; se conserva porque
  saber que algo dejó de ser cierto también es memoria.

## Vecinos

[[lps-dominio]] para de dónde salen las cifras · [[rbac-y-rutas]] para quién puede verlas ·
[[design-system]] para cómo se pintan.

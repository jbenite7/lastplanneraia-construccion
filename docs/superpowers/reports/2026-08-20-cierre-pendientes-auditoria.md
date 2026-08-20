---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-20
areas: [proceso]
fuente: docs/superpowers/reports/2026-08-20-cierre-pendientes-auditoria.md
resumen: "Cierre de los tres pendientes de la auditoría de specs: spec de severidad reescrita a 3 niveles, veredicto de indicadores y CNP/CNC/CIC, y humo anónimo de prueba-lps con códigos HTTP"
---

# Cierre de los pendientes de la auditoría de specs — 2026-08-20

Seguimiento de los puntos 2–4 de [[docs/superpowers/reports/2026-08-20-auditoria-estado-specs]]
(el punto 1, `organizar-la-casa`, cerró el mismo día con frente propio).

## 1 · `estados-severidad-contrato` reescrita bajo el contrato de tres niveles

[[docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design]] queda subordinada a
[[docs/design-system/ds-f1a-escala-estado]] tal como decidió Felipe el 2026-08-19 (registrado en
`goals/ds-f1a-estados-severidad/goal.md` §Cierre-retirado): tres niveles, filete con dos
tratamientos dibujados y apagado en `Controlado`, Programa General remapeado a los estados reales.
Las secciones normativas se reescribieron con notas de revisión fechadas; la procedencia y las
mediciones originales se conservan como historia.

**Lo que la reescritura deja explícito y no resuelve:** con la proyección a tres niveles, en
Intermedia seis de ocho estados dibujan barra — la proporción inversa a la del contrato hermano,
donde la ausencia es mayoría y por eso es señal. Si en pantalla se lee saturado, la salida es
material de la **coordinación previa** que la propia decisión exige (tercera parte, intacta):
**la ejecución del frente sigue pausada** hasta coordinarse con esa sesión.

## 2 · `/indicadores` y CNP/CNC/CIC — veredicto medido

| Superficie | Veredicto | Evidencia |
|---|---|---|
| `/indicadores` | **Migrada** (pilot) | `views/indicadores/indicadores.view.php:14` usa `aia-shell`; su contenido es un iframe de Power BI (F0-082, `sin-problema`) — no hay superficie propia que migrar |
| CNP · CNC · CIC | **Legacy real** bajo shell `aia-*` | Las tres vistas cargan `public/js/modules/programacion_semanal/legacyCards.js`, que genera `ps-legacy-card*`, `btn btn-success/danger` — cero clases `aia-*`. El censo DS-F0 las marca `ausente-del-inventario` |

La sospecha de la auditoría era correcta con matiz: **el culpable es el JS, no el PHP.** Los dos
planes de UI-audit (2026-07-31 y 2026-08-01) eran el único lugar donde ese trabajo estaba
prometido y ninguno cerró; el hallazgo **F0-022 (mayor)** lo detecta pero no tiene tarea F1/F2
que lo cierre. **Conclusión: los dos planes viejos quedan superados como vehículo** — la
migración de CNP/CNC/CIC entra al programa design system como entrada de DS-F2 con dueño, no se
reabre un plan archivado sin owner. Anotado en [[TASKS]].

## 3 · Humo de `prueba-lps` — la mitad anónima, con códigos

Corrido el 2026-08-20 con `curl` anónimo contra `https://prueba-lps.lastplanneraia.com` (URL de
`docs/siteground-deploy-routine.md`). **Sin autenticación: teclear credenciales en `/login` está
prohibido para las sesiones** (AGENTS.md) y la puerta de servicio no existe fuera de desarrollo.

| Check | Código | Lectura |
|---|---|---|
| `GET /` | 200 | Responde; sirve la pantalla de login |
| `GET /login` | 200 | Formulario real (Usuario/Contraseña, enlace a `/password/forgot`) |
| `GET /plan-compras` | 302 → `/login` | La ruta **existe y está protegida** — no es 404: el PDC v2 está enrutado |
| `GET /pdc-app/assets/pdc.js` | 200 | El bundle de la SPA **está desplegado** (JS minificado válido, React) |
| `GET /dev/entrar` | 302 → `/login` | **La puerta de servicio no está abierta** en pruebas — el candado se sostiene |

**Qué prueba esto:** despliegue enrutado, bundle presente, login operativo y dev door cerrada.
**Qué sigue pendiente y por qué:** el humo **autenticado** (que `/plan-compras` sirva la SPA con
sesión, que las API respondan datos, RBAC permitido/denegado) exige una sesión real que ninguna
sesión de asistente puede abrir allí — lo hace Felipe a mano, o se decide un mecanismo autorizado
(p. ej. sembrar una cuenta de prueba y validarla él). Es paso previo de CP-F-E y queda anotado
así en [[TASKS]].

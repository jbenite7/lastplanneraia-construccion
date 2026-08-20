---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-20
areas: [proceso]
fuente: goals/cierre-pendientes-auditoria/goal.md
resumen: "Cerrar los tres pendientes restantes de la auditoría de specs del 2026-08-20: reescritura de la spec de severidad, veredicto de indicadores/CNP-CNC-CIC y humo anónimo de prueba-lps"
---

# cierre-pendientes-auditoria

**Objetivo.** Cerrar los puntos 2–4 del bloque «Auditoría de specs 2026-08-20» de [[TASKS]]:
(2) reescribir [[docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design]] bajo el
contrato de tres niveles que Felipe decidió el 2026-08-19; (3) veredicto medido de `/indicadores`
y CNP/CNC/CIC frente a los dos planes de UI-audit sin cierre; (4) el humo de `prueba-lps` hasta
donde alcanza una sesión sin credenciales.

**Condición de hecho.**
1. La spec de severidad declara los tres niveles, el filete apagado en `Controlado` y la
   coordinación previa como condición — sin borrar procedencia ni mediciones originales.
2. Veredicto por superficie con evidencia, y el trabajo real recolocado donde tiene dueño
   (DS-F2), no en planes archivados.
3. Humo anónimo con códigos HTTP crudos documentado, y la mitad autenticada anotada con su
   bloqueo real (credenciales) y a quién le toca.
4. `npm run test:wiki` en verde y publicado en `main`.

## Cierre

Cumplida el 2026-08-20. Evidencia y detalle en
[[docs/superpowers/reports/2026-08-20-cierre-pendientes-auditoria]]; verificación en el commit de
publicación (cuatro gates de `publicar.sh` replicados en el worktree). La ejecución del frente
`ds-f1a-estados-severidad` sigue pausada a propósito: reescribir la spec no levanta la
coordinación previa que la decisión exige.

## Archivos de este goal

- [[docs/superpowers/reports/2026-08-20-cierre-pendientes-auditoria|Informe de cierre]]
- [[docs/superpowers/specs/2026-08-19-estados-severidad-contrato-design|Spec reescrita]]
- [[docs/superpowers/reports/2026-08-20-auditoria-estado-specs|La auditoría origen]]
- [[memoria/goals/estado]]

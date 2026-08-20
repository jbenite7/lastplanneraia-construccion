---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-19
areas: [proceso]
tags: [pendiente]
fuente: decisiones/linea-base-contractual-coordinadora.md
resumen: Decisiones del usuario — frente linea-base-contractual
---

<!-- cas:cita-textual — registro de decisiones del usuario sobre el frente linea-base-contractual -->
# Decisiones del usuario — frente linea-base-contractual

## 1. El sembrado va como migración SQL, y migrations/** queda autorizado (2026-08-19)

Presentadas a Felipe las tres salidas al hueco de la spec (el CI no tiene línea base declarada y
nada la siembra): (a) sembrado por migración SQL que el CI aplica solo, (b) caer a la deducida si
falta —descartada porque reintroduce las dos definiciones conviviendo, que él ya había descartado—,
(c) dejar el test rojo —descartada porque no cierra nada—.

**Felipe eligió (a) en el canal de la coordinadora: «Migración SQL + autorizo migrations/**»** —
autorización explícita para tocar `database/migrations/**` en este frente. Límites: el dry-run del
efecto (SELECT con el mismo JOIN) se pega como evidencia antes de commitear; la ejecución contra la
base de dev se coordina con la coordinadora; contra producción no la ejecuta nadie — viaja
versionada y entra con el próximo deploy autorizado.

Contexto previo (fijado en la spec del frente): la fuente es la línea base declarada; se siembra
sola al consolidar la primera semana; bajo filtro la fecha es siempre la del proyecto; los tres
proyectos ya cargados (68, 69, 77) se siembran por migración.

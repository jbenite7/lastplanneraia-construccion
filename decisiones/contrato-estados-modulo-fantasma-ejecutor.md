<!-- cas:cita-textual — registro de hallazgos: cita comandos defectuosos tal como se dieron -->
# Decisiones pendientes — frente contrato-estados-modulo-fantasma

<!-- Una entrada por decisión, con estos campos:
**Qué se decide** · **Qué se midió** (con sha) · **Opciones reales** · **Recomendación** ·
**Qué quedó saltado** -->

## 1 · Ajustar las dos aserciones de censo de `states-feedback.test.mjs` — ESCALADA, esperando visto

- **Qué se decide:** si se ajustan `tests/design-system/states-feedback.test.mjs:55`
  (`assert.ok(semantics.moduleMappings.length >= 13)` → `>= 12`) y `:57` (quitar
  `'programa-general-actualizar'` de la lista literal de módulos).
- **Qué se midió** (sobre `44917bc1`): son los **dos únicos** consumidores del contrato de estados
  que nombran ese módulo. `ops-state-contract.test.mjs` solo consulta `programacion-intermedia`,
  `programacion-semanal` y `programa-general`; `state-tint-ladder.test.mjs` y
  `ops-state-chip-hue.mjs` recorren lo que haya en `moduleMappings`. Los demás usos del literal en
  `tests/`, `docs/design-system/manifests/` y `state-tint-exceptions.json` apuntan a la ruta, la
  vista y el CSS del módulo, que este frente no toca.
- **Opciones:** (a) ajustar ambas; (b) no retirar el módulo.
- **Recomendación:** (a). La aserción mide el censo, y el censo cambia por decisión del usuario:
  actualizarla refleja la decisión en vez de relajar la prueba.
- **Por qué se escala en vez de anotarse:** alterar lo que una prueba mide está en la lista de
  bloqueo incondicional. No se aplica la prueba del bloqueo: se escala.
- **Qué quedó saltado esperando:** los pasos 1, 2 y 3 del plan. Se adelantó el paso 4
  (`D-CEF-1` en `docs/decisiones-pendientes.md`) y la medición de línea base.
- **Estado:** escalada a la coordinadora, sin respuesta todavía.

## 2 · `decisiones/` está en `.gitignore` — observación, no de este frente

- **Qué se midió** (sobre `44917bc1`): `git check-ignore -v` sobre este mismo archivo devuelve
  `.gitignore:404 decisiones/`, `rc=0`.
- **Por qué importa:** es exactamente el caso que la skill `coordinating-agent-sessions` describe
  como medido el 2026-08-11 —una sesión pisó con `Write` la cola de otra y se llevó doce hallazgos
  sin conflicto, sin diff y sin rastro, **porque el archivo no estaba versionado**—. Versionarlo
  convierte la pérdida muda en un conflicto ruidoso.
- **Qué NO se hace aquí:** tocar `.gitignore` está fuera del alcance de este frente y afecta a
  todas las sesiones vivas. Queda dicho, no hecho.
- **Estado:** informada a la coordinadora en la escalada.

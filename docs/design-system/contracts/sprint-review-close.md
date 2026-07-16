# Contrato de revisión y cierre de sprint

El cierre es una decisión verificable, no una inferencia basada en apariencia.
Cada gate conserva evidencia esencial y la aprobación humana es bloqueante.

## Registro de evidencia

Cada registro identifica ruta, rol, estado, viewport, tema, archivo, overflow,
targets, consola, contraste, gate y checksum. Los goldens deterministas se
versionan; videos, traces y capturas extensas permanecen como artefactos.

Un gate solo puede pasar con recibos estructurados que registren resumen,
ID y comando exactos del registro canónico, código de salida cero, artefacto persistente dentro de
`docs/design-system/evidence/`, checksum, commit fuente y fingerprint. Los gates
que usan la fixture CI registran también su hash. El validador contrasta el
artefacto actual con el blob del commit; una ruta inexistente, externa, alterada
o no persistida bloquea la activación.

La activación `1.0.0` solo es válida cuando índice y worktree están limpios y
`closeout-evidence.json`, `version.json` y `stable-api-1.0.0.json` coinciden byte
por byte con `HEAD`. Ese commit contiene los quince gates `passed`, la versión
estable y la API garantizada; los artefactos pueden conservar como `sourceRef`
el commit candidato verificado anterior.

## Matriz de autoridad

- Playwright ejecuta flujos, axe y regresión visual.
- Axe bloquea hallazgos automáticos critical y serious.
- Accessibility Insights aporta revisión automatizada básica separada del laboratorio,
  del piloto y de sus estados revelados. Cada export debe registrar cero reglas fallidas
  y cero instancias fallidas.
- Teclado y reflow se conservan como evidencia no bloqueante; no forman parte de
  los quince gates de activación.
- El laboratorio aprueba familias; el piloto aprueba integración real.

## Secuencia de cierre

1. Ejecutar contratos, auditor, runtime, seguridad y pruebas del piloto.
2. Presentar evidencia consolidada, límites y hunks exactos para aprobación.
3. Actualizar SemVer y changelog; repetir todos los gates.
4. Ejecutar la revisión local sobre el diff y resolver sus observaciones.
5. Hacer staging selectivo y comprobar el diff staged.
6. Crear un commit atómico exclusivo, sin push ni deploy.

Una falla, excepción vencida, dato sin restauración o ausencia de aprobación
impide cerrar el sprint y publicar la versión.

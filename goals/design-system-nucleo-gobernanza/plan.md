# Plan: Sprint 00 — Núcleo y gobernanza del Design System AIA

## Solution Approach

Continuar desde el estado actual y cerrar una fuente de verdad ejecutable antes de reanudar migraciones por módulo. No se reinicia Sprint 00, no se crea otro Epic y no se rehace PR 0–2. El núcleo combina cascada CSS determinista, tokens semánticos, API híbrida CSS/PHP/JS, adaptadores, catálogo contractual, laboratorio protegido, manifiestos, auditoría y regresión visual. Programa General sigue como único piloto.

La estética se homologará por familias, no por ocurrencias aisladas. Cada elemento pasará por inventario, contrato, comparación de precedentes, revisión local, espécimen real, aprobación en navegador nativo y golden bloqueante antes de quedar disponible para consumo.

El rollout será compatible y gradual: solo la API `stable`, ejercida por laboratorio y consumidor real, entra en la garantía `1.0.0`; `candidate`, BI y adaptadores no consumidos permanecen fuera de ella. Un puente inventariado preserva legacy mediante ratchet. El Sprint 00 no migra BI runtime, no cambia negocio ni base de datos, no despliega y no activa protección externa de main.

La capa accesible reutiliza Playwright y añade únicamente `@axe-core/playwright`: un helper central aplica tags A/AA, fingerprints estables, baseline y excepciones con expiración. Accessibility Insights se limita a revisiones automatizadas básicas separadas de laboratorio, piloto y estados revelados; cada export requiere cero reglas fallidas y cero instancias fallidas. Teclado y reflow producen evidencia no bloqueante. No se incorporan Storybook, Pa11y, html-validate sobre PHP, React Aria, Radix, Angular, Lighthouse como gate, accessibility overlays ni plataformas comerciales.

El prerrequisito Git no es una fase de implementación ni aprobación visual: solo preserva, clasifica y aísla trabajo preexistente mediante una maniobra aprobada. Ningún feedback de un consumidor se corrige allí; se convierte en un contrato transversal que se resuelve por familia en el laboratorio.

`goal.md`, `facts.md` y `plan.md` son la autoridad canónica. Los resultados e interviews anteriores quedan marcados `superseded` en un manifiesto de procedencia y no pueden ordenar commits, restauraciones ni cambios; se conservan para trazabilidad, no como instrucciones activas.

## Enrutamiento de capacidades y orquestación

Antes de iniciar cada fase, el agente principal consulta el catálogo efectivo del runtime y selecciona la combinación mínima de skills, plugins, MCPs, conectores, hooks y subagentes que aporte evidencia, calidad, velocidad o seguridad material. La selección se registra en el artefacto de fase o en el reporte de cierre con: capacidad, motivo, acción, evidencia esperada y permiso o riesgo añadido. Una capacidad instalada no se usa por defecto ni sustituye el juicio técnico.

| Situación | Capacidad preferida | Evidencia exigida |
| --- | --- | --- |
| Inventario, contratos o arquitectura | Búsqueda local y skill especializada mínima | Hallazgos con rutas, fuente de verdad y decisión registrada |
| Componentes, tokens o accesibilidad visible | Skill de frontend/UI + navegador nativo | Especímenes, viewport/tema, consola y aprobación visual de familia |
| Regresión o comportamiento reproducible | Playwright y pruebas enfocadas | Comando, resultado, reportes y artefactos de fallo cuando apliquen |
| Datos persistentes, seguridad, permisos o contratos compartidos | Revisión local y validación específica | Revisión resuelta, respaldo/restauración cuando corresponda y prueba de persistencia o denegación |
| Servicio, vendor o formato externo | Plugin, API, MCP o skill oficial mínimo | Respuesta verificable y solo los datos mínimos enviados |
| Dos o más subtareas independientes | Subagentes con alcances disjuntos | Entregables separados, rutas no solapadas y revisión de integración |

Los hooks son controles obligatorios y acumulativos; no se ejecutan manualmente para simular su resultado, no se deshabilitan y no amplían permisos. Los subagentes solo reciben subtareas independientes con alcance, rutas permitidas, criterio de salida, pruebas y prohibiciones explícitas; no hacen commit, push, deploy, cambios externos ni mutaciones fuera de su alcance. El agente principal conserva la integración, la revisión de conflictos y la verificación final.

Si una capacidad requerida no está disponible, falla o exige una autorización no concedida, se conserva el diagnóstico, se selecciona el fallback más seguro, se declara la cobertura perdida y no se afirma que la capacidad fue usada. Todo envío de diffs o datos a un servicio externo requiere la autorización correspondiente; los datos enviados se minimizan.

## Ordered Steps

### 0. Preservar y aislar el trabajo preexistente fuera del Sprint 00

- Inventariar git status, staged, unstaged, untracked, commits locales y divergencia; registrar hashes o checksums suficientes para demostrar preservación exacta antes de cualquier mutación.
- Clasificar por objetivo y riesgo sin ejecutar tests, correcciones ni aprobaciones visuales módulo por módulo; el estado actual se conserva hasta aprobar una maniobra concreta.
- Aislar únicamente mediante mecanismos aprobados: commits atómicos para trabajo coherente y aprobado, o preservación externa verificable para trabajo incompleto, scratch, sensible o riesgoso; nunca forzar su commit.
- Exigir una base limpia antes del Sprint 00, registrar HEAD y origin/main, y mantener todos los commits de aislamiento fuera del commit final; no usar add masivo, reset, stash, rama, worktree, restauración o movimiento no aprobado.

### 1. Versionar el contrato y el inventario antes de cambiar la apariencia

- Mantener `version.json` en la versión de construcción vigente (`0.3.6` al reanudar) y completar changelog, schemas, catálogo, vendors, aliases y decisiones sin rehacer artefactos válidos. `0.3.3` queda exclusivamente como baseline de medición y no como versión de cierre.
- Cada ficha tendrá ID, propósito/no-uso, API, markup, variantes, lifecycle, severidad, densidad, tokens, responsive, accesibilidad, prueba, vendor, consumidores, reemplazo, golden, aprobación visual y madurez `stable|candidate|compatibility|deprecated`.
- Crear un manifiesto canónico del objetivo que enumere `goal.md`, `facts.md` y `plan.md`, registre `sourceCommit` y marque los facts/interviews anteriores como `superseded` y solo de procedencia.
- Inventariar todos los elementos visibles y vendors actuales, incluidos Bootstrap, Handsontable, DataTables, Select2, Tom Select, SweetAlert2, Toastr, AnyChart, Chart.js, Font Awesome, AdminLTE y jQuery UI; no cambiar versiones.
- Escanear todas las vistas y módulos como evidencia, agrupar las ocurrencias por patrón reusable y registrar fuente, familia, componentes de catálogo, API de estilo, dark/linen y selector visible de laboratorio; ningún grupo puede quedar sin mapear.
- Inventariar en BI Curva S, PPC, PAC vs Programado, dona, radar, proyecciones, vacío y KPI; estabilizar primitivas de presentación, pero conservar tipos concretos de chart como `candidate/analytics-guideline` hasta probarlos en un dashboard real.
- Clasificar cada fila como canónica, adaptador, legacy deprecado o excepción y fijar el reemplazo esperado.
- Crear module-manifest.schema.json, manifests/programa-general.json e manifests/inventory.json; el manifiesto declara versión, rutas, fuentes, componentes, vendors, layouts, estados, roles, persistencia, excepciones, tests y evidencia.
- Implementar scripts/design-system-contracts.mjs y fixtures en tests/design-system/ mediante RED/GREEN para validar catálogo, decisiones, vendors, aliases, manifiestos, SemVer y archivos/rutas existentes.

### 2. Convertir deuda y excepciones en contratos bloqueantes

- Ampliar scripts/design-system-audit.mjs con un parser CSS real para detectar CSS fuera de capa, important no autorizado, tokens crudos en módulos, escalas inválidas, selectores globales, overrides vendor dispersos, aliases nuevos y primitivas duplicadas.
- Separar audit-baseline.json de exceptions.json; cada excepción individual tendrá módulo, regla, archivo/selector, responsable, razón y milestone.
- Añadir docs/design-system/baseline-approvals/ para exigir hashes before/after y referencia de aprobación; retirar la actualización libre que pueda ocultar deuda.
- Escribir primero fixtures negativos y positivos por regla; confirmar RED por la causa correcta y GREEN tras cada regla.

### 3. Definir tokens, capas y carga determinista

- Consolidar en public/css/tokens.css la API semántica de color, tipografía, spacing, forma, sombra, z-index, movimiento, cuatro bandas responsive y densidades compact|touch.
- Mantener --aia-* y escalas legacy como implementación/deprecación controlada; los módulos migrados solo podrán consumir --ds-*.
- Mantener un entrypoint único y documentar la semántica del orden reset, vendor, theme, base, layout, components, utilities, module y legacy-overrides; no reordenar al cierre sin evidencia.
- Probar la precedencia de utilities frente a module; toda regla en legacy-overrides referencia una excepción vigente para impedir que esa capa se convierta en el nuevo styles.css.
- Organizar reglas nuevas bajo public/css/design-system/ y adapters vendor bajo public/css/design-system/adapters/.
- Crear DesignSystemHeadComponent.php para emitir assets estáticos; sustituir la inyección CSS de linksComunesHead2.js en sus 15 vistas consumidoras, conservando allí solo compatibilidad JavaScript necesaria.
- Extraer estilos compartidos generados desde JS —bordes globales, AiaAlertInterceptor, semi-auto y estados del LPS drawer— a componentes/adaptadores; permitir estilos runtime solo para geometría medida y mediante allowlist.
- Localizar las mismas versiones de fuentes/assets remotos necesarias para goldens reproducibles, registrando origen, versión, hash y licencia sin upgrades.

### 4. Homologar cómo debe quedar cada familia

- Revisar exclusivamente en el laboratorio por familias: fundamentos; shell/navegación; estructura de página; acciones; formularios/filtros; estados/feedback; datos; overlays; vendors; primitivas BI.
- Cuando existan precedentes distintos, registrar candidatos A/B con el mismo contenido y estados; la revisión local inspecciona la intención y el navegador nativo decide la variante real.
- Usar viewports para shell/evidencia y container queries para componentes reusables en drawer, sidebar, modal, split view y panel BI; incluir fallback probado.
- Validar dark/linen, viewports, contenedores estrechos, textos extremos, teclado, foco, reduced motion y overlays; Touch exige 44x44, Compacta 24x24 o separación equivalente. Lenguaje natural no fragmenta palabras; identificadores usan quiebres seguros u overflow explícito.
- En Estados, conservar `lifecycle-state` del dominio y mapear por separado gravedad, urgencia y `severity`; info/success/warning/critical gobiernan presentación y acción, no sustituyen el proceso.
- Mantener el contrato auditable en `state-semantics.json` con todas las etiquetas encontradas, incluidas las ocho o más de Programación Intermedia, sin forzarlas a cuatro estados de dominio ni renombrar consumidores.
- Registrar la decisión por ID en decisions.md; solo una variante aprobada puede pasar a approved y adquirir golden bloqueante.
- No visitar ni perfeccionar módulos consumidores para aprobar familias: las decisiones se congelan en el laboratorio y después se validan únicamente en el piloto Programa General.

### 5. Implementar la API canónica y sus adaptadores mediante TDD

- Crear DesignSystemComponent.php y partials seguros para page header, toolbar/action group, botones, fields, search/filter/pagination, estados, cards/table shells, overlays, feedback y BI shells.
- Añadir public/js/modules/aia_ui/components.js con inicialización idempotente, disclosures, menús/popovers, foco de dialog, anuncios, toast/confirm y API pública; reutilizar theme.js y nav_drawer.js.
- Implementar CSS por familia dentro de layer components, usando exclusivamente tokens semánticos y las dos densidades.
- Clasificar adapters como `stable-adapter`, `candidate-adapter`, `compatibility-skin` o `deprecated-adapter`; solo ascienden a stable con consumidor real y estados críticos cubiertos. DataTables/jQuery UI quedan en compatibilidad.
- Mantener los charts BI actuales como candidatos; estabilizar tipografía, tokens, contenedor, leyenda, tooltip, vacío, tabla equivalente y accesibilidad sin congelar todavía dona, gauge o geometría de gráfico.
- Corregir el defecto compartido de `.btn:focus`/`:focus-visible` antes de recopilar la evidencia no bloqueante de teclado; la corrección pertenece al núcleo, no a un módulo.
- Para cada familia: prueba contractual RED, implementación mínima completa, GREEN, espécimen real, gate accesible y aprobación visual; no crear variantes locales provisionales.
- Crear tests/browser/support/accessibility.mjs como única configuración axe; adjuntar JSON en fallo y comparar solo fingerprints estables de regla, impacto, superficie y selector.
- Probar escaping, variantes inválidas, markup/ARIA, inicialización repetida y ausencia de inline styles en tests PHP y Node enfocados.

### 6. Construir el laboratorio protegido sobre el runtime real

- Añadir APP_ENV a .env.example y docker-compose.yml; crear AppEnvironment.php con default production y allowlist development|testing.
- Registrar /internal/design-system en public/index.php solo cuando el entorno lo permita y defender nuevamente en DesignSystemLabAccessPolicy.php.
- Crear DesignSystemLabController.php; exigir sesión y capability `internal.design-system.view`, responder 403 sin ella y no exponer el laboratorio en producción, sin migración DB.
- Renderizar la misma API en views/design-system/lab.view.php y partials por familia; design_system_lab.js solo prepara escenarios deterministas mediante APIs reales.
- Incluir fixtures estáticos BI sin llamadas a APIs ni cambios en views/bi, dashboards o bi-spa.js.
- Cubrir entorno/rol, IDs, estados, temas persistidos, teclado, foco, contraste, targets, overflow, consola y reduced motion con PHP y Playwright.
- Ejecutar `@axe-core/playwright` sobre cada familia, tema, viewport y estado determinista; overlays se analizan abiertos y los hallazgos manuales quedan en checklist separado.
- Registrar Chrome/Edge mínimo, Chromium fijado en CI y features CSS/fallback usadas por la matriz automatizada.

### 7. Bloquear regresiones visuales y preparar CI reproducible

- Versionar package.json y lockfile raíz con `@axe-core/playwright`, scripts `test:a11y:*`, proyecto Chromium y tests visuales y accesibles del laboratorio y piloto.
- Crear a11y-baseline.json, a11y-exceptions.json y schema; bloquear `critical|serious`, reportar `moderate|minor`, rechazar excepciones vencidas y prohibir regeneración automática o exclusiones generales.
- Generar un golden por cada escenario determinista declarado en manifests —60 es la cuenta inicial, no un límite—; desactivar animaciones y separar baselines de plataforma cuando sea necesario.
- Crear fixture CI con schema versionado, IDs deterministas, guard `APP_ENV=testing` y base allowlisted; abortar ante volumen local/productivo y no importar dumps.
- Crear .github/workflows/design-system.yml con pull_request, push main y workflow_dispatch, permisos contents:read, jobs design-system-static y design-system-runtime, sin deploy, secretos ni pull_request_target.
- El job estático ejecuta contratos, manifests, schemas, auditor, SemVer y documentación; el runtime levanta Docker/DB efímeros, Chromium, laboratorio y piloto, ejecuta axe y publica JSON/trace/captura solo ante fallo.
- Validar YAML y ejecutar localmente el equivalente. Llamar `blocking` solo a los gates locales; describir GitHub como workflow definido y reproducible hasta que `DS-GOV-001` active required checks tras un push autorizado.
- Medir contra baseline aprobado CSS/JS gzip, adapters cargados, requests duplicadas, flash de tema, inicialización, Handsontable y ausencia de assets del laboratorio en producción; documentar tolerancias en lugar de inventar presupuestos.
- Conservar `0.3.3` como retrospectiva histórica portable pero incompleta, con `sourceRef:null`, `rawSamplesPreserved:false` y un manifiesto de recuperación comprometido que no dependa de objetos o checkpoints Git ocultos. Mantener el gate fail-closed hasta tomar tres muestras frescas del candidato sobre un `HEAD` limpio, conservar sus recibos, agregar la mediana y compararla contra las tolerancias aprobadas.

### 8. Migrar exclusivamente /programa-general como piloto

- Limitar el manifiesto a /programa-general, filtros/set-filtro y APIs generales ya consumidas; excluir /programa-general-actualizar, BI, importación y rediseño backend.
- Empezar con una matriz baseline antes/después de comportamiento, roles, peticiones, datos y geometría; escribir un contrato RED que demuestre primitivas/aliases locales.
- Migrar programa_general.view.php, programa-general.css y hot.js a componentes aprobados; conservar en layer module solo composición y reglas de dominio.
- Mover overrides Handsontable al adaptador, sustituir tokens crudos, eliminar important local o registrar excepción exacta y no tocar GeneralApiController sin brecha indispensable reproducida.
- Verificar filtros por ratón/Enter/Espacio, limpieza, cards, Handsontable, modal, exportar, recargar, error, doble envío, foco, política Touch/Compacta, temas, consola y overflow.
- Ejecutar axe después de revelar cada estado crítico de Programa General y completar las revisiones automatizadas básicas separadas de Accessibility Insights con cero reglas fallidas y cero instancias fallidas. Teclado y reflow se recopilan aparte como evidencia no bloqueante; ningún resultado automático demuestra por sí solo un estándar completo.
- Probar test.A, test.R, test.C y Visualizador cuando exista fixture seguro; confirmar UI y rechazo backend ante POST manipulado y corregir los falsos roles/imports del E2E vigente.
- Para persistencia: snapshot de fila/proyecto, interacción UI real, request/response, DB, recarga card/tabla, restauración en finally y fingerprint final idéntico al inicial.
- Mantener comportamiento/datos aprobados; cualquier cambio visible debe corresponder a una variante aprobada en el laboratorio y quedar en la comparación antes/después.

### 9. Publicar los contratos futuros y la política de evidencia

- Crear contracts/governance.md, module-migration.md y sprint-review-close.md; probar que fijan versión/manifiesto, límites de CSS local, gates, evidencia, aprobación y commit.
- Para cada artefacto de gobierno, documentar fallo evitado, consumidor automático y efecto de desactualización; el gate rechaza artefactos ceremoniales o huérfanos.
- Versionar catálogo, manifests, métricas, decisiones, goldens y evidencia esencial bajo docs/design-system/; conservar videos, traces y capturas extensivas como artifacts ignorados.
- Cada evidencia registra ruta, viewport, tema, rol, estado, archivo, overflow, targets, consola, contraste, gate y checksum.
- Documentar la matriz: Playwright runner, `@axe-core/playwright` motor automático, Accessibility Insights revisión automatizada básica separada y teclado/reflow evidencia no bloqueante; el workflow es la especificación reproducible y su enforcement remoto queda pendiente de `DS-GOV-001`.
- Definir clases de cambio: patch compatible = gates + changelog; minor compatible = revisión visual acotada; major = ADR, aprobación explícita y plan de migración.
- Documentar `Emergency UI Fix Lane` para defectos productivos, accesibles, de seguridad u operación, sin nuevas primitivas ni rediseño y con excepción temporal exacta cuando sea inevitable.
- Dejar preparada la medición de PDC: tiempo de migración, reutilización, variantes nuevas, literales, archivos por token, duración CI, falsos positivos, excepciones y delta runtime; no migrar PDC en Sprint 00.
- Actualizar README.md, tokens.md, components.md y migration.md para que el contrato ejecutable, no el prompt de un módulo, sea la autoridad.

### 10. Ejecutar cierre técnico, revisión consolidada y publicación 1.0.0

- Ejecutar gates enfocados, smokes de las 15 vistas, laboratorio, piloto PG, auditor global, manifests, PHPStan y seguridad de tablas globales.
- Presentar en navegador nativo catálogo/decisiones, seis vistas del laboratorio y seis vistas de Programa General, interacciones, comparación antes/después, métricas, limitaciones y lista exacta de hunks; no realizar una gira visual por otros módulos.
- Antes de release, enumerar en changelog y catálogo la API `stable` garantizada por `1.0.0`; mantener `candidate`, BI y adapters no consumidos fuera de esa promesa. Repetir gates y ejecutar una revisión local del diff exacto.
- El array machine-readable conserva exactamente, en orden: `static`, `runtime`, `runtime-budgets`, `phpstan-scoped`, `phpstan-global`, `global-table-safety`, `pg-roles`, `pg-persistence`, `data-restoration`, `accessibility-insights`, `consolidated-lab`, `consolidated-pilot`, `git-preservation`, `review` y `atomic-commit`. IDs duplicados, ausentes o adicionales bloquean la activación.
- Validar cada recibo `passed` contra el `commandId` y comando exactos del registro canónico. Rechazar la activación si índice o worktree tienen cambios, si los documentos de activación no coinciden byte por byte con `HEAD`, o si ese commit no contiene simultáneamente los quince gates `passed`, `1.0.0 / stable` y la API garantizada; el `sourceRef` del artefacto puede ser el commit candidato verificado anterior.
- Preparar staging selectivo y una serie de commits coherentes: contratos/auditor, núcleo, laboratorio/goldens, CI, piloto PG y release. Verificar cada diff; no hacer commit gigante ni reescribir historial a ciegas.
- El commit de release actualiza versión/changelog después de todos los gates; no hacer push, deploy ni activar branch protection. Registrar `DS-GOV-001` como tarea posterior con owner Felipe.
- Reanudar después PDC bajo el núcleo; registrar módulos ya auditados sin reabrirlos salvo fallo objetivo; mantener BI runtime al final.

## Verification

Prerrequisito y contratos:

- git status --short --branch
- git diff --check
- Verificar que cada reporte de fase declara capacidades seleccionadas, evidencia y fallback cuando aplique; rechazar una referencia ceremonial a una skill, plugin, MCP, hook o subagente sin consumidor o resultado verificable.
- node --test tests/design-system/*.test.mjs tests/scripts/design-system-audit.test.mjs
- node scripts/design-system-contracts.mjs
- node scripts/design-system-audit.mjs

Sintaxis, seguridad y runtime:

- node --check public/js/modules/aia_ui/components.js
- node --check public/js/modules/aia_ui/design_system_lab.js
- node --check public/js/modules/programa_general/hot.js
- PHP lint de controller, policy, componentes y vistas nuevas/afectadas.
- tests PHP de acceso al laboratorio y contrato de componentes.

Navegador y piloto:

- npm run test:a11y:lab
- npm run test:a11y:pilot
- npx playwright test tests/browser/design-system-lab.mjs tests/browser/design-system-lab.visual.mjs --project=chromium --workers=1
- node tests/test_programa_general_sprint_contract.mjs
- npx playwright test tests/browser/programa-general-design-system.mjs tests/browser/design-system-compliance.mjs --project=chromium --workers=1
- npx playwright test --config=e2e/playwright.config.mjs e2e/tests/workflows/pg-interactions.spec.mjs --workers=1
- docker compose exec -T app php tests/test_global_table_safety.php
- Matriz nativa dark/linen en 390x844, 1180x820 y 1440x900 para laboratorio y Programa General.
- Revisiones automatizadas básicas separadas de Accessibility Insights para laboratorio, piloto y estados revelados, cada una con cero reglas fallidas y cero instancias fallidas.
- Evidencia no bloqueante: `npm run test:design-system:evidence` para teclado y reflow.

CI y cierre:

- docker compose -f docker-compose.yml -f docker-compose.ci.yml config
- Ejecución local completa de design-system-static y design-system-runtime.
- Validación de madurez del catálogo, precedencia canónica del objetivo y cobertura golden derivada de manifests.
- Reporte de budgets contra baseline `0.3.3`, sin assets de laboratorio en rutas productivas ni adapters no declarados por manifest.
- docker compose exec -T app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
- git diff --cached --check
- git diff --cached --stat
- Revisión local del diff, alcance, resultados y disposiciones documentadas.

## Risks And Open Questions

- El baseline observado es 1.687 hallazgos y el worktree estaba muy mezclado; deben aislarse sin reset, staging masivo ni conversión del prerrequisito en revisiones o correcciones por módulo.
- main estaba 42 commits adelante y 3 detrás; este goal no autoriza reconciliar ni publicar contra origin/main.
- Sustituir el loader CSS afecta 15 vistas; sus smokes son bloqueantes aunque no se rediseñen.
- CSS vendor sin capas siempre gana sobre capas propias; los assets deben entrar por adapters/layer vendor o permanecer explícitamente en el puente.
- El auditor regex actual produciría falsos positivos; las nuevas reglas deben usar AST y distinguir visuales authored de geometría calculada.
- APP_ENV no existe hoy. El default debe ser production; un default permisivo expondría el laboratorio.
- Fuentes y assets remotos vuelven inestables los goldens; deben localizarse en la misma versión o bloquear el cierre.
- El compose actual depende de un volumen DB local externo; el workflow necesita fixture efímero sanitizado y no puede usar dumps productivos.
- Los goldens pueden diferir entre Linux y macOS; se prefieren snapshots por componente y baselines de plataforma.
- El E2E PG existente no importa correctamente editCell y etiqueta como Residente una sesión Admin; no prueba RBAC hasta corregirse.
- Los checks GitHub reales solo existen después de un push. Sprint 00 valida el workflow localmente; branch protection y publicación remota requieren aprobación separada.
- Axe y Accessibility Insights cubren reglas automatizables: un resultado sin fallos no demuestra el estándar completo ni permite ampliar el alcance evaluado.
- Baselines axe con HTML completo serían frágiles; solo se versionan fingerprints mínimos y toda excepción necesita propietario y expiración.
- goals/, package.json, lockfiles y muchos tests están ignorados; solo se versionarán los contratos/goldens necesarios mediante reglas explícitas.
- Todo feedback visual vuelve a la misma familia; ninguna variante se declara canónica por lectura de código o solo por auditoría estática.
- El núcleo es un framework interno y su costo de mantenimiento es real; ninguna abstracción entra a `stable` sin consumidor real, necesidad futura inventariada y gate que detecte desactualización.
- Si PDC exige reconstruir la mayoría de componentes, crear muchas variantes o desactivar gates, la API estable del Sprint 00 no demostró reutilización suficiente y debe revisarse antes de ampliar migraciones.

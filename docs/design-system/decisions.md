# Decisiones del Design System AIA

Las variantes permanecen como `candidate` hasta aprobación en el laboratorio.

| ID | Familia | Decisión | Estado |
|---|---|---|---|
| DS-000 | Gobierno | Programa General es el único piloto del Sprint 00 | approved |
| DS-001 | Estados | El texto envuelve entre palabras y nunca fragmenta una palabra | approved |
| DS-002 | Catálogo | Implementado no significa aprobado; toda entrada inicia candidate | approved |
| DS-003 | Vendors | Se congelan versiones observadas sin upgrades durante Sprint 00 | approved |
| DS-004 | Datos | DataTables y jQuery UI permanecen como compatibilidad legacy | approved |
| DS-005 | BI | Las primitivas BI se homologan solo con fixtures del laboratorio | approved |
| DS-006 | Cascada | El entrypoint declara reset, vendor, theme, base, layout, components, utilities, module y overrides | approved |
| DS-007 | Fuentes | Inter v20 y Montserrat v31 latino se sirven localmente con hash y licencia OFL | approved |
| DS-008 | Color | Cada submarca tiene una variante `on-dark` perceptual, con matiz preservado y contraste AA | approved |
| DS-009 | Tema | Dark es el modo inicial cuando el usuario no ha guardado una preferencia | approved |
| DS-010 | Densidad | Desktop desde 1200 px inicia Compacta; tablet y mobile inician Touch | approved |
| DS-011 | Navegación | La barra contextual permanece visible desde 1200 px; en anchos menores la misma lista usa drawer táctil | approved |
| DS-012 | Estructura de página | El encabezado se integra al canvas en todos los viewports; todo bloque delimitado conserva padding semántico y contiene sus textos | approved |
| DS-013 | Acciones | El grupo de acciones se integra al canvas; la superficie elevada no es la variante canónica por defecto | approved |
| DS-014 | Formularios y filtros | Los filtros permanecen visibles en todos los anchos y cambian únicamente su composición responsive | approved |
| DS-015 | Estados y retroalimentación | El fondo semántico tenue es el patrón general; el texto expresa siempre el significado y ninguna palabra se fragmenta | approved |
| DS-016 | Presentación de datos | Una colección alimenta tabla compacta desde 1200 px y tarjetas Touch por debajo de 1200 px | approved |
| DS-017 | Capas y diálogos | El mismo dialog se presenta como modal desde 1200 px y drawer inferior Touch en anchos menores, con Escape y restauración de foco | approved |
| DS-018 | Adaptadores de terceros | Cada vendor conserva su versión inventariada y recibe una única skin central tokenizada; no se permiten skins locales ni estilos inline | approved |
| DS-019 | Elementos de analítica | Toda figura incluye título, resumen, leyenda textual y tabla equivalente; el color no es la única vía y no se migra BI runtime | approved |
| DS-020 | Primitivas canónicas | Icono, búsqueda, paginación, progreso, región viva, menú y popover reutilizan contratos ejecutables del núcleo, sin variantes locales | approved |
| DS-021 | Evidencia visual | El laboratorio versiona 20 goldens deterministas por familia, dark y dos viewports; CI solo los compara y nunca los regenera | approved |
| DS-022 | Piloto | Programa General versiona 3 goldens sanitizados, ejecuta axe en la misma matriz y mantiene acciones y estados dentro de sus contenedores | approved |
| DS-023 | Transición de tema | Los chips semánticos aplican inmediatamente superficie, borde y texto del tema destino; solo pueden animar transform y sombra, nunca color o fondo | candidate |
| DS-024 | Estado de revisión | El laboratorio muestra el estado del candidato activo que renderiza; una base aprobada no aprueba automáticamente una variante visual distinta | candidate |
| DS-025 | Acciones por tema | La acción primaria aplica de inmediato superficie, borde y texto del tema destino; solo puede animar transform y sombra, nunca color o fondo | candidate |
| DS-026 | Sidebar desktop | El sidebar persistente es el candidato canónico para desktop, con Información, Obra y Compras como agrupaciones operativas; el drawer adaptativo permanece como compatibilidad hasta completar aprobación visual | approved |
| DS-027 | Shell global | El sidebar canónico colapsable reemplaza al navbar superior como navegación global de la app (piloto: Programación Intermedia): rail visible colapsado por defecto en vistas de grilla con persistencia local, cambio de semana en el chip de la context-bar y grupos filtrados por rol en servidor | approved |
| DS-028 | Viewport accesible | La meta viewport compartida nunca bloquea el zoom del usuario (`width=device-width, initial-scale=1.0`, sin `user-scalable=no` ni `maximum-scale`); el modo tablet conserva su escala propia con `user-scalable=yes` (WCAG 1.4.4, 2026-07-22) | approved |
| DS-029 | Colapsado canónico | El estado colapsado pulido del sidebar (sin-scroll, píldora de label, separadores, iconos 20px) es primitiva de navigation.css versionada por el laboratorio; el lab captura el rail colapsado por defecto y todos los consumidores lo heredan | approved |
| DS-030 | Retirada de `linen` | 2026-07-25: `linen` queda retirado del producto (F0 del goal `dark-mode-todos-los-modulos`, ver `goals/dark-mode-todos-los-modulos/facts.md`); dark es el único tema, sin conmutador. Un solo tema reduce a la mitad la superficie de tokens, gates y evidencia, y es coherente con el alcance desktop-dark de `AGENTS.md` | approved |
| DS-031 | Retirada del viewport `390x844` | 2026-08-06: el viewport móvil queda retirado de `homologation.json`, de las aprobaciones de familia, de los esquemas y de los tres gates. Cierra la retirada que DS-030 dejó a medias: `AGENTS.md` §Routing acota el alcance a desktop ≥1180px, los carriles visual y de compliance ya lo excluían por su cuenta, y el de accesibilidad corría un tercio de sus escenarios fuera de alcance (30 → 20). Candado en `tests/design-system/mobile-viewport-removal.test.mjs`; goldens huérfanos eliminados | approved |
| DS-032 | Reapertura del viewport `390x844` | 2026-08-07: el viewport móvil vuelve a ser un valor **soportado** en los esquemas, las aprobaciones y los tres gates, pero **no requerido**: la cobertura obligatoria sigue siendo `1180x820` y `1440x900`. Supersede a DS-031, que lo había retirado cuando `AGENTS.md` §Routing acotaba el alcance a desktop; esa prohibición se retiró de los `.md` normativos el 2026-08-07. El gate pasa a distinguir `SUPPORTED_VIEWPORTS` de `REQUIRED_VIEWPORTS` y, por primera vez, valida los viewports declarados en `homologation.json`. El candado de DS-031 se renombra a `tests/design-system/mobile-viewport-scope.test.mjs` y cambia de intención: ya no prohíbe el ancho, exige evidencia para todo escenario declarado. No se genera evidencia móvil: eso es F2 | approved |

Cada aprobación futura registra evidencia, fecha y versión.

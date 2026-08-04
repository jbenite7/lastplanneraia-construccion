# Barrido único de auditoría de diseño — 2026-08-03

Alcance: todas las rutas HTML servidas de la app principal (~25 vistas navegables desde el router,
sesión `test.R` / `test.A` vía dev door) y las vistas de `admin/` (7 vistas HTML; `/usuarios/cargos`
resultó ser un endpoint JSON, no una vista). Viewport 1180×820, dark. Solo lectura + navegador —
no se editó ni un archivo del repo. Sin interacción con toolbars que mutan datos (semanal/cic/cnc/cnp
vistos solo en su estado inicial vacío, sin clicks de acción).

Script suelto (no forma parte de `tests/`): capturó screenshot + errores de consola por ruta,
guardado en `.superpowers/sdd/2026-08-03-cierre-dark-mode-fases-0-3/barrido/` (`results.json` +
PNGs, referenciados por nombre abajo).

## Matriz por superficie

### App principal (sesión test.R, proyecto PDC Sandbox E2E)

| Ruta | Capturable | Consola | Hallazgos |
|---|---|---|---|
| `/dashboard` (Programación Semanal) | sí (`app-_dashboard.png`) | 0 errores | Estado vacío correcto y explicativo. Sin hallazgos nuevos. |
| `/dashboard/escalamientos` | sí (`app-_dashboard_escalamientos.png`) | 0 | Kanban por rol claro; columnas vacías bien resueltas con check verde. Sin hallazgos. |
| `/indicadores` | sí (`app-_indicadores.png`) | 0 | **[a] Severidad 3** — el contenido central es un iframe/gráfico embebido con fondo **blanco sólido** a pantalla casi completa, rompiendo el contrato dark por completo (ver top-1 global). |
| `/internal/design-system` | con `test.R`: 403 (esperado, requiere rol A vía `DesignSystemLabAccessPolicy`); con `test.A`: sí (`app-testA-_internal_design_system.png`) | 0 (con test.A) | Es la superficie más pulida del barrido: jerarquía tipográfica, tokens visibles, sin ruido. Sin hallazgos. |
| `/control-cambios` | sí | 0 | Tabla de filtros + estado vacío correcto. Sin hallazgos. |
| `/pdc` (Plan de Compras) | sí | 0 | Chips de estado con conteos en 0, coherente. Sin hallazgos nuevos. |
| `/plan-compras` | sí | 0 | Onboarding modal "Paso 1 de 6" se ve bien integrado al dark; historial de versiones vacío correcto. |
| `/profesionales` | sí | 0 | Tabla live-edición legible. Sin hallazgos. |
| `/programa-general` | sí | 0 | **[b] Severidad 3** — mojibake: "CapÃ­tulo" en vez de "Capítulo" (doble-encoding UTF-8/Latin1) visible en la celda Actividad. Además iconos de datepicker se superponen a los dígitos de fecha en columnas angostas ("2026-04-3⊙"). |
| `/programacion-intermedia` | sí | 0 | **[c] Severidad 3** — cabeceras de columna parten palabras carácter a carácter en vez de hacer word-wrap ("Sem-anas Inicio", "Diseño s y Especi ficacio nes"), ilegible sin zoom. |
| `/programacion-semanal` | sí | 0 | Igual a `/dashboard`. Sin hallazgos nuevos. |
| `/programacion-semanal/cic` | sí | 0 | Mismo patrón [c] en cabeceras ("Sema-nas en el Proyec-to"). |
| `/programacion-semanal/cnc` | sí | 0 | Limpia, sin datos de ejemplo — sin el patrón [c] porque las cabeceras son cortas. |
| `/programacion-semanal/cnp` | sí | 0 | Igual, limpia. |
| `/subcontratistas` | sí | 0 | Tabla live-edición vacía, correcta. |
| `/proyectos` | sí | 0 | Cards de proyecto bien jerarquizadas, badges de rol legibles. Sin hallazgos. |
| `/bi/contratistas` | sí | 0 | **[d]** ver top-1 (overflow de tabs). Resto de la superficie limpio. |
| `/bi/control-tower` | sí | 0 | **[d]** overflow de tabs. KPIs en 0 bien resueltos. |
| `/bi/curva-s` | sí | 0 | **[d]** overflow de tabs. Gráfico vacío sin datos, ejes limpios. |
| `/bi/intermedia` | sí | 0 | **[d]** overflow de tabs. |
| `/bi/pdc` | sí | 0 | **[d]** overflow de tabs. |
| `/bi/programa-general` | sí | 0 | **[d]** overflow de tabs. Donas en gris monocromo sin diferenciación fuerte entre estados. |
| `/bi/responsables` | sí | 0 | **[d]** overflow de tabs. |
| `/bi/semanal` | sí | 0 | **[d]** overflow de tabs. |
| `/login` | sí | 0 | Bien resuelto, consistente con el resto. |

### admin/ (sesión test.A vía `/admin/dev/entrar`)

| Ruta | Capturable | Consola | Hallazgos |
|---|---|---|---|
| `/admin/dashboard` | sí | 0 | **[e] Severidad 2** — íconos decorativos de las stat cards se superponen visualmente al número/texto ("17/50" cruzado por el ícono de proyectos, "141.72MB" sobre el ícono de base de datos). Paleta de cards (teal/verde-oliva/naranja/rojo/violeta) no corresponde a los tokens del design system dark de la app principal — es esperable por ser mini-app arquitectónicamente separada (AGENTS.md), pero es un salto de consistencia visual notorio si el usuario navega entre ambas. |
| `/admin/matching/config` | sí | 0 | Formulario claro, buen uso de ayudas inline. Sin hallazgos nuevos. |
| `/admin/matching/family-catalog` | sí | 0 | Tres paneles bien organizados. Sin hallazgos. |
| `/admin/pdc/limpieza` | sí | 0 | Jerarquía de peligro (checkboxes + confirmación por nombre exacto) bien resuelta para una acción destructiva. Sin hallazgos. |
| `/admin/proyectos` | sí | 0 | Tabla densa con toggles y acciones en semáforo de colores brillantes (naranja/verde/rojo) — mismo salto de paleta que [e]. |
| `/admin/usuarios` | sí | 0 | Igual a proyectos. Mojibake también presente en datos: "InnovaciÃ³n" en el nombre de cargo de un usuario — mismo origen que [b], confirma que es un problema de datos/encoding, no solo de una vista. |
| `/admin/usuarios/cargos` | responde 200 pero es JSON crudo, no HTML — no es una "vista" auditable con las tres lentes. El JSON en sí usa escapes `ñ`/`ó` correctos (evidencia de que el mojibake de [b]/[e] ocurre en la capa de salida HTML, no en el JSON). |

## Rutas rotas o inaccesibles

Ninguna. Las 25 rutas de la app principal y las 7 de `admin/` respondieron 200 (o 403 esperado por
RBAC en `/internal/design-system` con rol R). No hubo errores de consola en ninguna superficie.

## Top-5 hallazgos globales

1. **[Severidad 3-4, Refactoring UI/contrato] Iframe blanco en `/indicadores`** — rompe el contrato
   dark-only de punta a punta con un bloque casi a pantalla completa en blanco sólido. Es la
   superficie con peor infracción visual del barrido.
2. **[Severidad 3, contrato "sin overflow horizontal"] Tab bar de `/bi/*` desbordado** — en las 8
   superficies de BI el último tab ("Plan de Compras") se corta a media palabra ("Plan d") fuera del
   viewport de 1180px. Viola explícitamente la regla de AGENTS.md de "ausencia de overflow
   horizontal en el viewport permitido".
3. **[Severidad 3, Refactoring UI] Cabeceras de tabla que parten palabras carácter a carácter** en
   Programación Intermedia y CIC ("Sem-anas", "Diseño s y Especi ficacio nes") — el contenedor de la
   cabecera no hace word-wrap, hace character-wrap; ilegible sin entrecerrar los ojos.
4. **[Severidad 2-3, Nielsen: match entre el sistema y el mundo real / integridad de datos]
   Mojibake UTF-8/Latin1 doble-codificado** — "CapÃ­tulo" en Programa General y "InnovaciÃ³n" en un
   cargo de `admin/usuarios`. Aparece en al menos dos módulos distintos con datos reales, sugiere un
   problema de encoding en la capa de salida (no en el JSON crudo, que sí escapa bien).
5. **[Severidad 2, Nielsen: consistencia] Salto de lenguaje visual entre `admin/` y la app
   principal** — stat cards con paleta saturada (teal/naranja/violeta/rojo) e íconos que se
   superponen al texto en `/admin/dashboard`, frente al dark minimalista y tokenizado del resto de
   la app. Arquitectónicamente admin/ está fuera del alcance del design system (AGENTS.md), pero el
   contraste es abrupto para quien navega entre ambos paneles.

## Pasada horaria del 2026-08-04 — solo lo nuevo o lo que cambió

No se repite la matriz: las 32 superficies siguen respondiendo y sin errores de consola. Lo que
cambió respecto al barrido del 03:

**Cerrados desde entonces** — [b] mojibake (task 20), [c] cabeceras partidas carácter a carácter
(tasks 24 y 26), y el disparador de menú blanco de PS (task 28).

**Top-1 reclasificado, no resuelto.** El bloque blanco de `/indicadores` **no es CSS nuestro**: es
un `<iframe>` de `app.powerbi.com`, otro origen, cuyo interior ninguna hoja de este repo puede
tematizar. El barrido anterior lo daba a entender como infracción propia y por eso nunca se
arregló. Va como **C-22**; la salida está en el tema del informe en Power BI, fuera del repo.

**Top-2 medido y parcialmente resuelto.** Las 8 pestañas de `/bi/*` suman 1626 px en un carril de
1116: quedan invisibles «Plan de Compras», «Proveedores (CIC)» y «Responsables (CIP)». **No caben**
— sin iconos y a 13 px siguen sumando 1363 px, y acortar etiquetas es tocar texto de navegación.
Queda como **C-23**.

**Hallazgo nuevo, arreglado (`e38be1c`).** Al medir el carril apareció lo que nadie había mirado:
el pulgar del scrollbar de las 8 rutas de BI usaba `--aia-separators`, un gris claro **estático sin
variante oscura**, rindiendo una franja casi blanca sobre el lienzo oscuro — justo bajo las
pestañas, el elemento más brillante de la vista. Retokenizado al par dark-aware del shell. Misma
familia que C-20.

**Hallazgo nuevo, arreglado (`ed8c411`).** Los chips contadores de PI y PS estaban encajonados a
155 px con `!important` desde `legacy-bridge.css`: seis de los ocho de PI partían en dos renglones
y ocupaban tres filas sobre la tabla. Ahora se dimensionan por su contenido — PI baja a dos filas
(98 → 88 px), PS cabe en una. Programa General no usa ese selector y queda intacto, así que sus
goldens pendientes no se mueven.

**Método, dos veces en la misma pasada.** Un desvanecido de borde para anunciar el corte de las
pestañas se probó y **se descartó**: la ruta tiene presupuesto de cero funciones de color y mover
la regla de archivo habría sido esquivar el gate. Y el primer intento del arreglo de BI dejó el
audit en rojo porque **el comentario citaba valores de color literales** — la trampa que la wiki ya
tenía puesta en `audit-ve-color-en-comentarios`. Ambas cosas las atrapó el gate, no yo.

## Segunda pasada horaria del 2026-08-04 — solo lo nuevo

Método distinto y más barato: en vez de navegar ruta por ruta, se miden las 28 rutas
(20 de la app principal + 8 de `admin/`) desde iframes del mismo origen, con la misma batería por
superficie. Resultado global: **cero fugas de color claro y cero overflow horizontal en las 28**.
Las dos fugas que quedaban vivas —el disparador de menú de PS y «Ver alertas» de PDC— ya no
aparecen: los cerraron `312ba9b` y `be5eae7`.

**Lo que quedaba era accesibilidad, no color.** Cinco controles de solo icono se anunciaban sin
nombre, arreglados en `0471e2f`: los dos botones de **borrar** de `/profesionales` y
`/subcontratistas` —un botón que elimina una fila, anunciado como nada—, el de editar de CIC y CNC,
y el menú lateral de `admin/`, presente en sus 8 rutas. Todo metadato: no cambia comportamiento.

Dos cosas que enseñó el barrido:

- **El patrón correcto ya existía en el repo.** `CNP.view.php` ya traía `aria-label` más
  `aria-hidden` en el icono; CIC y CNC se habían quedado atrás. Se copió en vez de inventar otro.
  Es el mismo hallazgo que con los chips de PG: cuando algo falta, conviene mirar si un módulo
  hermano ya lo resolvió.
- **Una tabla vacía esconde defectos.** CNC salió limpia en la medición porque no tenía filas; su
  botón de editar tiene el mismo defecto que CIC, solo que latente. Medir solo el estado vacío da
  una foto incompleta.

Falsos positivos descartados: los `htFocusCatcher` de Handsontable (`role="presentation"`,
`aria-hidden="true"`) y los radios del laboratorio, que usan etiqueta envolvente y mi sonda solo
miraba `label[for]`.

Al registro: **C-25**, la marca «AIA» de `admin/` a 4,46:1 frente al mínimo de 4,5.

## Documento

`docs/superpowers/barrido-diseno-2026-08-03.md` (este archivo). Capturas en
`.superpowers/sdd/2026-08-03-cierre-dark-mode-fases-0-3/barrido/`.

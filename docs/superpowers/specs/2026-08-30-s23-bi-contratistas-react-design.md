---
capa: fuente
tipo: spec
estado: vigente
id: S23
fecha: 2026-08-31
superficie: bi-proveedores
rutas:
  - "/bi/contratistas"
  - "/api/bi/report/cic"
depende_de: [T01, T03, S11, S14, S17]
views: [VIEW-04, VIEW-05, VIEW-06, VIEW-08]
areas: [bi, rbac, design-system]
fuente: "auditoria de public/index.php, BiViewController, BiControlTowerApiController, ControlTowerService, CicApiController, ReportProcessor, MetricDictionaryService, LineageService, StorytellingService, ActionRecommendationService, RiskScoringService, BiPreviewAccessPolicy, RbacCatalog, bi_cic_contratistas, cic, subcontratistas, programacion_semanal, VIEW-04/05/06/08, control-tower.php, bi-spa.js, pruebas, respuestas read-only servidas, specs CT-6.2/CT-8.7/CT-9, S11, S14, S17 y frontend actual en shell-minimo-react, 2026-08-31"
resumen: "Migracion vertical S23 de la hoja BI Proveedores a React: poblacion y corte canonicos desde CIC, cinco componentes con completitud obligatoria, integral y estado inhabilitados cuando falta evidencia, decision actual/acumulada, comparacion, filtros, detalle, enlaces a CIC/Subcontratistas, responsive y oscuro/claro, sin mutaciones ni cambios RLS/schema/datos."
---

# S23 — Hoja BI Proveedores en React

> **Estado:** diseño técnico autorrevisado y decision-complete. No quedan decisiones de negocio,
> producto, estrategia o PM que impidan escribir el plan. Esta spec no autoriza implementación,
> commits, DDL/DML, cambios RLS, cambios de capacidades, correo, deploy, publicación ni trabajo en
> `/admin/`. Su plan se escribe a continuación con `superpowers:writing-plans`, conforme al
> programa aprobado de 27 specs y 27 planes.

## Relación con el programa

S23 desarrolla CT-8.7 y la regla de completitud CT-6.2 para una hoja compartida por los lienzos
Gerencia y Obra. Sirve dos decisiones:

- revisar antes de volver a asignar a un proveedor cuya evaluación acumulada completa está por
  debajo del umbral;
- intervenir en el período a un proveedor cuya evaluación actual completa requiere seguimiento.

Consume:

- T01 para sesión, proyecto, shell, sidebar, tema, route outlet y único cliente HTTP;
- T03 para política por hoja, scope, período, filtros, estados, drawer y linaje;
- S11 como dueño de población, identidad, presencia, métricas CIC, cuestionarios, completitud
  operativa y mutaciones;
- S14 como dueño del maestro de subcontratistas/proveedores;
- S17 como panorama ejecutivo y enlace hacia la hoja.

S23 se construye una vez para A, D y R. No hay variantes de datos ni composición por audiencia. El
scope inicial y las acciones efectivas se resuelven en servidor.

S23 es read-only. No califica, no guarda cuestionarios, no crea proveedores, no corrige metadatos y
no bloquea contrataciones. Cuando hace falta completar evidencia o revisar la ficha, el servidor
entrega enlaces autorizados a S11 o S14.

## Resultado buscado

`/bi/contratistas` pasa a la SPA principal y:

1. presenta proveedores reales, no filas de KPI dentro de una tabla de proveedores;
2. usa una fila por proveedor y proyecto al corte seleccionado;
3. conserva identidad estable sin usar el nombre como clave;
4. muestra PAC, Calidad, Socioambiental, SST y Administración por separado;
5. trata cero como un puntaje válido;
6. distingue sin calificar, no aplica, inválido y con puntaje;
7. no publica integral actual ni acumulada hasta tener los cinco componentes puntuados;
8. declara cuántos componentes hay, cuáles faltan y qué rol los carga;
9. clasifica solo una integral completa en aprobado, seguimiento o no aceptado;
10. normaliza el contrato interno a 0..1 y muestra porcentajes 0..100;
11. separa decisión del período y señal acumulada;
12. nunca convierte ausencia de evidencia en mal desempeño;
13. compara contra la evaluación previa real del mismo proveedor;
14. evita promedios de proveedores y rankings ordinales sin base;
15. filtra y busca gobernando toda la hoja;
16. conserva proyecto y período en cada resultado;
17. abre un drawer con evidencia, componentes, base, observación y acciones;
18. permite ir a CIC o al maestro solo si el servidor autoriza;
19. funciona en desktop, tablet y móvil, oscuro y claro, teclado, touch, zoom y lector de pantalla;
20. permanece completamente read-only.

## Alcance

### Incluido

- `GET /bi/contratistas` como ruta SPA al corte.
- `GET /api/bi/report/cic` estabilizado como único snapshot/paginación canónicos.
- A/D/R conforme a `BiSheetAccessPolicy`, flag y `lps.indicadores.ver`.
- Un solo modelo de hoja para ambos lienzos.
- Scope inicial T03: A todas sus obras autorizadas; D/R proyecto activo autorizado.
- Semana como atajo de rango y rango de fechas como autoridad.
- Período resuelto por las semanas reales de cada proyecto.
- Población CIC reutilizada de S11, sin side effects.
- Última evaluación hasta el corte y evaluación previa real.
- Presencia/actividad en el rango declarada.
- Cinco componentes actuales y acumulados.
- Completitud, base, faltantes, no aplica e inválidos.
- Integral canónica solo con cinco componentes puntuados.
- Estado actual y acumulado con umbrales normalizados.
- Acciones de atención en período, revisión antes de asignar y completar evaluación.
- Resumen, breakdown por decisión/componente/proyecto/tipo y lista paginada.
- Búsqueda, filtros, orden y focus en URL.
- Drawer contextual sin endpoint nuevo.
- Observación más reciente como evidencia autorizada de CIC.
- Enlaces a S11/S14 según capacidades.
- Linaje y limitaciones de `cic_cal_integral` y `cic_aprobacion_status`.
- Corrección del conteo de contratistas en alerta consumido por S17.
- Tabla desktop/tablet y tarjetas móviles.
- Oscuro/claro, cinco viewports, zoom, reduced motion y accesibilidad.
- Contratos PHP/Zod, pruebas puras y navegador totalmente interceptado.
- Convivencia, corte, rollback y retiro diferido del bloque legacy.

### Fuera de alcance

- Todo `/admin/`.
- Cambiar RLS, runtime boundary, `ProjectScope`, grants, usuarios, credenciales, schema, vista SQL,
  tabla, columna, índice, trigger, datos, seeds o fixtures persistentes.
- Ejecutar DDL/DML, aun con rollback.
- Modificar `database/bi/005_bi_cic_contratistas.sql` para resolver la hoja.
- Cambiar `internal.bi.preview`, flag, roles, aliases o capacidades.
- Dar acceso a OT, DCV, V, C, G, SG, S u otros roles fuera de A/D/R.
- Editar cuestionarios CIC o observaciones desde BI.
- Crear/materializar una fila CIC al leer, filtrar, recargar o abrir drawer.
- Recalcular/actualizar `Cal_Integral` persistida.
- Crear, editar, renombrar, activar o borrar subcontratistas.
- Cambiar las 59 preguntas, tipos soportados, disciplinas o fórmula operativa de S11.
- Reutilizar una integral redistribuida como si tuviera cinco componentes.
- Contar `NA` o `NR` como componente puntuado.
- Tratar cero como dato faltante.
- Publicar clasificación derivada de integral incompleta.
- Usar `alert_contractor_future_risk` o `aprobacion_status` de la vista como autoridad.
- Ocultar la incompletitud detrás de “Sin datos”.
- Mostrar email, teléfono, contacto, NIT, respuestas individuales o versión de cuestionario.
- Comparar proveedores de proyectos distintos como una liga.
- Publicar un promedio general de integrales.
- Automatizar una prohibición contractual o decisión de contratación.
- Enviar correo/notificación o crear una señal de distribución.
- Añadir endpoint de detalle, mutación, exportación o descarga.
- Duplicar la exportación de S14 o el editor S11.
- Añadir librería de tabla, gráfica, estado o formularios.
- Usar canvas, hover-only o color-only.
- Crear variantes A/D/R.
- Retirar vistas/scripts BI compartidos antes del gate T03/S17–S24.
- Regenerar goldens sin aprobación explícita.

## Fuentes y precedencia

Para S23 manda:

1. código y contratos ejecutables;
2. AGENTS raíz/frontend;
3. CT-6.2, CT-8.7 y CT-9;
4. S11 para semántica CIC;
5. S17/T03 para lienzos y marco BI;
6. S14 para maestro/enlaces;
7. notas medidas y legacy como caracterización.

La integral operativa de S11 conserva su fórmula y persistencia. S23 añade una regla de publicación
BI más estricta: una cifra puede existir almacenada y aun ser insuficiente para el lienzo.

## Punto de partida medido

### React

- No existe página, schema, gateway, dominio ni componentes React de BI Proveedores.
- El frontend contiene solo el shell mínimo.
- S11, S14, S17 y T03 están documentados, no implementados en este worktree.
- La ruta `/programacion-semanal/cic` es la superficie operativa, no S23.

### Ruta y acceso

| Verbo | Ruta | Uso actual |
|---|---|---|
| GET | `/bi/contratistas` | layout BI compartido |
| GET | `/api/bi/report/cic` | brief CIC legacy |
| GET | `/api/bi/projects` | scope compartido |
| GET | `/api/bi/weeks` | semanas |
| GET | `/api/bi/filter-options` | opciones globales |
| GET | `/api/bi/lineage` | linaje lazy |

S17 aprobó Proveedores en ambos lienzos:

| Lienzo | Roles | Hoja |
|---|---|---|
| Gerencia | A | Proveedores |
| Obra | D, R | Proveedores |

Por tanto A/D/R comparten la hoja. Otros roles reciben 404. Cada proyecto exige membresía y
`lps.indicadores.ver`. Las acciones hacia CIC requieren además `lps.cic.ver/editar` y disciplinas
efectivas de S11; las acciones hacia el maestro requieren `lps.subcontratistas.ver`.

### Vista legacy

`view-cic` contiene una tabla con columnas:

- Proveedor;
- Contacto;
- Servicio;
- Vigencia;
- Estado.

`renderCIC()` no usa filas de proveedores. Recorre `scorecard` y las coloca así:

- nombre del KPI en Proveedor;
- contacto vacío;
- servicio vacío;
- puntaje vacío;
- status del KPI en Estado.

El resultado observable son dos filas como “Contratistas evaluados” y “En alerta futura”, con tres
columnas vacías. No hay:

- proveedor real;
- componentes;
- integral;
- completitud;
- comparación;
- filtro propio;
- detalle;
- gráfico;
- tarjetas móviles;
- acción visible;
- linaje visible.

### Endpoint legacy

`fetchCic()` ejecuta `SELECT * FROM bi_cic_contratistas` para la semana/rango y filtros. Sin embargo
el resultado bruto no cruza el envelope hacia la UI. El brief expone:

- dos KPI;
- titular narrativo;
- riesgos;
- acciones;
- linaje;
- metadata de fuente.

No expone una colección de proveedores.

El scorecard:

- cuenta filas como contratistas evaluados, aunque un proveedor puede tener varias semanas;
- cuenta `alert_contractor_future_risk=1`;
- rotula el status del KPI como OK incluso cuando hay alertas.

`StorytellingService` y `ActionRecommendationService` confían en el mismo alert. Pueden declarar
riesgo futuro y recomendar intervención con evidencia incompleta.

### Vista SQL y escala

`bi_cic_contratistas` deriva:

- `aprobacion_status` con umbrales 70/50 sobre `Cal_Integral`;
- `alert_contractor_future_risk` cuando `Cal_Integral_Acum < 50`.

El contrato S11 usa valores puntuados en 0..1. Los umbrales BI equivalentes son 0,70/0,50. Por tanto
la vista puede clasificar como “No Aceptado” y alertar casi todo valor real.

La vista tampoco conoce la regla CT-6.2: publica integral/estado aunque falten componentes.

No se corrige con DDL en S23. El read model canónico aplica escala y completitud en código. La vista
queda como compatibilidad hasta un frente de schema expresamente autorizado.

### Evidencia global aprobada

La medición de producto sobre 323 filas encontró:

| Componente | Peso | Filas con valor > 0 |
|---|---:|---:|
| PAC | 30 % | 229 |
| Calidad | 20 % | 5 |
| Socioambiental | 20 % | 23 |
| SST | 20 % | 6 |
| Administración | 10 % | 6 |
| Integral almacenada | — | 171 |

El producto concluyó que la integral publicada se comportaba en la práctica como PAC renombrado.

### Lectura read-only de caracterización

En el proyecto auditado:

| Magnitud | Valor |
|---|---:|
| Filas históricas | 6 |
| Semanas | 2 |
| Proveedores distintos | 3 |
| Proveedores en último corte | 3 |
| Cinco componentes actuales completos | 0 |
| Cinco componentes acumulados completos | 0 |
| Integral almacenada no null en último corte | 3 |
| Alertas legacy en último corte | 3 |
| Status legacy “No Aceptado” | 3 |

El endpoint sirvió:

- `raw_row_count=3`;
- KPI evaluados=3;
- KPI alertas=3;
- tres riesgos;
- tres acciones;
- titular “3 contratistas están en alerta de riesgo futuro”.

Con CT-6.2, los tres estados son `insufficient`, no “No Aceptado”. La lectura fue SELECT-only,
calificada por `MultiProjectScope` y no mostró nombres/contactos. Un primer intento sin scope fue
rechazado por `ProjectSqlGuard` y no cambió estado.

### Pruebas existentes con escritura

`tests/test_bi_source_reconciliation.php` siembra fixtures dentro de una transacción. Aunque
revierte, es DML y no se usa como evidencia S23. Las pruebas CIC de navegador existentes también
guardan/restauran evaluaciones. S23 crea pruebas puras y browser interceptado.

## Modelo canónico

### Grano e identidad

La fila es:

`projectId + providerRef al corte`.

`providerRef` reutiliza la identidad opaca S11:

- proveedor maestro cuando se resuelve;
- evaluación persistida cuando no hay maestro compatible;
- nunca el nombre como clave;
- nunca un ID de otro proyecto.

Un mismo proveedor nominal en dos proyectos son dos filas. Duplicados/ambigüedad de S11 permanecen
diagnosticados y no se fusionan.

### Población

S23 consume la población canónica S11, sin escribir:

- presencia en `programacion_semanal`;
- maestro scoped;
- evaluaciones CIC persistidas;
- proyecciones read-only;
- diagnósticos.

Para semana:

- la semana seleccionada se resuelve a sus fechas reales por proyecto;
- la población incluye proveedores elegibles al corte;
- la fila muestra si tuvo presencia/evaluación en la semana.

Para rango explícito:

- la población incluye proveedores con presencia o evaluación dentro del rango;
- la evaluación visible es la última `<= hasta`;
- la comparación usa la evaluación real anterior;
- una evaluación anterior a `desde` puede viajar solo como base previa de comparación y se rotula;
- ningún bloque usa filas posteriores a `hasta`.

Sin rango:

- A recibe el scope inicial T03 de todas sus obras autorizadas;
- D/R reciben proyecto activo autorizado;
- cada proyecto resuelve su semana vigente real.

### Período y filtros

El período canónico es `desde/hasta`. `semana` es un atajo que T03 transforma por proyecto. En
multi-proyecto el payload conserva `periodByProject`.

Todos los filtros activos gobiernan:

- titular;
- conteos;
- distribución;
- cobertura de componentes;
- lista;
- breakdown;
- comparación.

Solo `filterOptions` se calcula sobre el scope/período antes del filtro que representa y declara
`applicability: "scope-period"`.

### Valores de componente

Cada componente usa unión discriminada:

- `scored { value: 0..1 }`;
- `not-rated`;
- `not-applicable`;
- `invalid { reasonCode }`.

Reglas:

1. cero es `scored(0)`;
2. null, vacío o `NR` es `not-rated`;
3. `NA` es `not-applicable`;
4. numérico fuera de 0..1 es `invalid`;
5. strings no reconocidos son `invalid`;
6. React no castea;
7. display multiplica por 100 y redondea según el design system;
8. raw storage strings no cruzan HTTP.

Los cinco componentes:

| Key | Etiqueta | Peso | Roles que pueden cargar según S11 |
|---|---|---:|---|
| `pac` | PAC | 0,30 | deriva de programación, no cuestionario |
| `quality` | Calidad | 0,20 | A, D, R |
| `socioEnvironmental` | Socioambiental | 0,20 | A, D, G, SG |
| `safety` | SST | 0,20 | A, D, S, SG |
| `administration` | Administración | 0,10 | A, D, OT |

Los owner roles se generan en servidor desde el catálogo vigente; React no conserva esta tabla.

### Completitud

Integral actual completa exige los cinco componentes actuales `scored`.

Integral acumulada completa exige los cinco acumulados `scored`.

`not-applicable`:

- es evidencia explícita y se muestra;
- no es “faltante por capturar”;
- no cuenta como uno de los cinco puntajes comparables;
- deja integral `insufficient`;
- no crea CTA de captura para esa disciplina.

`not-rated`:

- deja integral `insufficient`;
- añade faltante y roles dueños;
- puede ofrecer acción S11 si la persona tiene capacidad.

`invalid`:

- deja integral `invalid`;
- no se autocorrige;
- muestra acción de revisar fuente, sin valor.

Envelope de métrica:

- `value`: 0..1 o null;
- `basis.componentsExpected=5`;
- `basis.componentsScored`;
- `basis.evaluationsUsed`;
- `completeness: complete | insufficient | invalid`;
- `missing[]`;
- `notApplicable[]`;
- `invalid[]`;
- `cutoff`;
- `sourceVersion`.

### Integral

Solo si los cinco son `scored`:

`0.30×PAC + 0.20×Calidad + 0.20×Socioambiental + 0.20×SST + 0.10×Administración`.

Con todos presentes, esta fórmula coincide con el calculador S11. No hay redistribución porque la
regla BI no publica integrales parciales.

El servicio puede contrastar el valor recalculado con `Cal_Integral` almacenado:

- tolerancia absoluta 0,001;
- si coincide, fuente `recomputed-confirmed`;
- si no coincide, `invalid/source_mismatch`;
- el valor almacenado nunca completa componentes ausentes.

### Estado de decisión

Política `CIC_DECISION_LEVELS_1.0`, sobre score 0..1 completo:

- `approved`: >=0,70;
- `follow_up`: >=0,50 y <0,70;
- `not_accepted`: <0,50;
- `unavailable`: insufficient;
- `invalid`: fuente inválida.

El display muestra 70/50 por ciento, no compara un 0,8 con 70.

`cic_aprobacion_status` hereda completitud de `cic_cal_integral`. No puede clasificar por una puerta
lateral.

El estado es apoyo a decisión:

- no bloquea creación/asignación/contrato;
- no reemplaza debida diligencia;
- “No aceptado” se acompaña de “Revisar antes de una nueva asignación”;
- no afirma fraude, incumplimiento futuro ni causalidad.

### Decisión actual y acumulada

Cada proveedor tiene dos ejes:

- `currentDecision`: evaluación visible del período;
- `cumulativeDecision`: componentes acumulados hasta el corte.

Acciones:

1. `reviewBeforeAssignment` si acumulada completa es `not_accepted`;
2. `attentionThisPeriod` si actual completa es `follow_up` o `not_accepted` y la evaluación/presencia
   pertenece al período;
3. `completeEvaluation` si hay `not-rated`;
4. `reviewSource` si hay inválidos;
5. sin acción de desempeño si integral es insuficiente.

Un proveedor puede requerir completar evidencia y no tener veredicto. No se inventa “aceptable”.

### Comparación

La comparación es intraproveedor:

- evaluación actual vs evaluación real previa del mismo proyecto/proveedor;
- componentes por componente;
- integral solo si ambos cortes completos;
- delta en puntos porcentuales;
- corte anterior explícito;
- `insufficient` si falta alguno;
- no usa semana - 1;
- no compara proveedor contra promedio de obra;
- no crea posición/ranking ordinal;
- no mezcla proyectos.

La vista puede ordenar por estado o score completo, pero lo llama orden de atención, no ranking de
calidad.

### Resumen y breakdowns

Resumen filtrado:

- población;
- con evaluación en período;
- integral actual completa/insuficiente/inválida;
- integral acumulada completa/insuficiente/inválida;
- approved/follow-up/not-accepted/unavailable;
- review-before-assignment;
- attention-this-period;
- sin evaluación actual.

Cada conteo expone numerador/denominador cuando se muestra porcentaje.

Breakdowns:

- cobertura por componente;
- decisión actual/acumulada;
- proyecto;
- tipo de proveedor;
- alcance;
- diagnóstico.

No se publica promedio global de integral. Multi agrega conteos, no scores.

### Titular factual

Plantillas:

1. “<N> proveedores requieren revisión antes de una nueva asignación” si acumulada completa no
   aceptada;
2. “<N> proveedores requieren seguimiento en este período” si actual completa bajo 0,70;
3. “No hay una integral publicable: faltan componentes en <N> proveedores” si todos insuficientes;
4. “<N> de <T> proveedores tienen evaluación integral completa” si hay mezcla;
5. “No hay proveedores en el alcance y período seleccionados” para vacío real.

El titular no dice “todos aceptables” si hay insuficientes ni “riesgo futuro” a partir de datos
incompletos.

### Lista y orden

Orden por defecto:

1. revisión antes de asignar;
2. atención en período;
3. fuente inválida;
4. integral insuficiente;
5. approved;
6. nombre normalizado;
7. projectId;
8. providerRef.

No hay número de ranking.

Cada fila muestra:

- proyecto;
- proveedor;
- tipo/alcance;
- presencia/evaluación y corte;
- cinco componentes actuales;
- integral/estado actual o razón de inhabilitación;
- integral/estado acumulado o razón;
- comparación;
- acción principal;
- diagnóstico.

### Drawer

El drawer T03 muestra:

- identidad/proyecto/tipo/alcance;
- período y evaluación previa;
- cinco componentes actuales/acumulados;
- peso, valor, estado y fuente;
- base de completitud;
- faltantes, no aplica, inválidos y owner roles;
- fórmula y umbrales;
- observación CIC más reciente, texto plano;
- diagnósticos S11;
- acciones/hrefs autorizados;
- limitaciones y linaje.

No muestra email, NIT, contacto, respuestas de las 59 preguntas ni tokens de versión.

## Búsqueda, filtros, orden y paginación

Query S23 extiende T03:

| Campo | Regla |
|---|---|
| `projects` | scope autorizado |
| `semana` | atajo de período |
| `desde/hasta` | fechas ISO, desde <= hasta |
| `q` | 0–100, proveedor/tipo/alcance |
| `type` | opción servida |
| `scope` | opción servida |
| `decision` | approved/follow_up/not_accepted/unavailable/invalid |
| `decisionBasis` | current/cumulative |
| `completeness` | complete/insufficient/invalid |
| `missingComponent` | componente cerrado |
| `project` | dentro del scope |
| `sort` | priority/name/evaluation/current/cumulative |
| `direction` | asc/desc donde aplica |
| `limit` | 1–100, default 25 |
| `offset` | >=0 |
| `focus` | providerRef opaco autorizado |

Reglas:

- valores desconocidos -> 422 por campo;
- q no busca email/NIT/contacto;
- búsqueda espera 250 ms;
- filtros cambian toda la hoja y reinician offset;
- paginación no cambia resumen;
- sort de score pone unavailable al final;
- focus no concede acceso;
- URL es compartible y no contiene PII;
- catálogo no colapsa al elegir opción;
- servidor devuelve query normalizada.

## Contrato HTTP objetivo

### GET /api/bi/report/cic

No se añade endpoint. Query, resumen, página y detail embebido viajan juntos:

```json
{
  "ok": true,
  "data": {
    "reportKey": "cic",
    "catalogVersion": "CIC_DECISION_LEVELS_1.0",
    "scope": {},
    "period": {
      "from": "2026-08-25",
      "to": "2026-08-31",
      "byProject": []
    },
    "query": {},
    "capabilities": {},
    "hrefs": {},
    "headline": {},
    "summary": {},
    "componentCoverage": [],
    "decisionBreakdowns": [],
    "providers": {
      "items": [],
      "pagination": {
        "limit": 25,
        "offset": 0,
        "total": 0,
        "returnedCount": 0,
        "nextOffset": 0,
        "hasMore": false
      }
    },
    "filterOptions": {},
    "lineage": [],
    "limitations": []
  },
  "meta": {}
}
```

La fila incluye detail suficiente para el drawer. Paginar/filtrar vuelve a llamar el mismo GET. No
hay request por fila.

Compatibilidad:

- `BiCicPresenter` produce contrato canónico;
- un adaptador conserva `scorecard/executive_brief/recommended_actions/lineage` para callers legacy;
- los conteos legacy se derivan del modelo corregido;
- `alert_contractor_future_risk` de la vista no gobierna;
- S17 consume el mismo conteo acumulado completo no aceptado.

### Errores

| HTTP | code | Caso |
|---:|---|---|
| 401 | `AUTH_REQUIRED` | sin sesión |
| 403 | `PROJECT_SCOPE_FORBIDDEN` | proyecto no autorizado |
| 404 | `NOT_FOUND` | hoja oculta |
| 409 | `CIC_SOURCE_AMBIGUOUS` | duplicado/identidad no resoluble para decisión |
| 422 | `VALIDATION_ERROR` | query inválida |
| 500 | `CIC_REPORT_UNAVAILABLE` | fallo interno |
| 503 | `CIC_SOURCE_UNAVAILABLE` | fuente temporalmente no disponible |

Un duplicado puede permanecer como fila diagnóstica de solo lectura cuando S11 tiene una elección
determinista. 409 se reserva para una petición `focus` que no puede resolverse sin ambigüedad.

Errores no exponen SQL, tabla, prefijo, otro proyecto, stack, contacto o cuestionario.

## Arquitectura backend

### BiCicReadService

Orquesta:

1. gate por hoja;
2. scope T03;
3. período por proyecto;
4. query;
5. reader S11;
6. normalización de componentes;
7. completitud;
8. integral;
9. estado;
10. comparación;
11. acciones;
12. resumen/breakdowns;
13. titular;
14. linaje;
15. presenter.

No delega decisiones a `aprobacion_status`, `alert_contractor_future_risk`,
`StorytellingService::briefCIC()` o `ActionRecommendationService::actionsFromCIC()`.

### Reader compartido con S11

`BiCicProviderReader` consume la frontera canónica de S11. Si S11 expone un read service reutilizable,
se adapta sin duplicar SQL. Si la implementación aún no existe, S11 se ejecuta primero.

La implementación:

- usa scope explícito;
- no materializa proyecciones;
- no recalcula almacenamiento;
- no abre transacción de escritura;
- devuelve valores tipados;
- carga evaluación anterior en una consulta acotada;
- evita N+1;
- preserva diagnósticos;
- no devuelve 59 respuestas al presenter BI.

### Políticas puras

- `CicComponentNormalizer`.
- `CicBiCompletenessPolicy`.
- `CicBiIntegralPolicy`.
- `CicDecisionLevelPolicy`.
- `CicProviderComparison`.
- `CicProviderPriority`.
- `CicProviderHeadline`.
- `CicProviderActionProjector`.

La política integral BI puede reutilizar el calculador S11 solo en el caso de cinco puntuados. Una
prueba fija equivalencia.

### Métricas y linaje

`cic_cal_integral` se corrige a:

- pesos exactos;
- score 0..1/display %;
- cinco componentes obligatorios;
- completitud/basis/missing;
- no synthetic defaults;
- grano proveedor-proyecto-corte;
- multi por filas calificadas;
- rango soportado con población declarada;
- versión 2.0.

`cic_aprobacion_status`:

- umbrales 0,70/0,50;
- hereda completitud;
- no se ejecuta con valor null;
- actual y acumulado declaran basis;
- versión 2.0.

No se altera la vista SQL. `MetricExecutor` o el servicio canónico gobierna ejecución.

### Corrección de S17

El KPI `contractors_at_risk` del overview usa:

- proveedores distintos;
- última evaluación acumulada al corte;
- cinco componentes acumulados scored;
- integral <0,50;
- scope/período del overview;
- numerador y denominador.

No cuenta filas históricas ni integrales insuficientes. S17 conserva el drilldown a S23.

## Arquitectura frontend

### Estructura

- schemas/gateway en `frontend/src/lib/api/`;
- UI/estado en `frontend/src/modulos/bi/proveedores/`;
- página `frontend/src/modulos/bi/ProveedoresPagina.tsx`;
- T03 frame/drawer compartidos;
- CSS token-only.

No hay `fetch`, role branching, cálculo integral, comparación o umbral en React.

### Estado remoto

- `idle`;
- `loading`;
- `ready`;
- `refreshing`;
- `partial`;
- `empty`;
- `insufficient`;
- `offline`;
- `invalid-contract`;
- `forbidden`;
- `not-found`;
- `error`.

Reglas:

- skeleton estable;
- refresh conserva snapshot y antigüedad;
- contrato inválido no pinta valores;
- partial mantiene bloques válidos;
- offline solo conserva misma identidad;
- abort/stale guard;
- 404 desmonta nav/contenido;
- cambio de período invalida comparación y página;
- detail es local al row payload;
- recarga no escribe.

### Orden del lienzo

1. titular y período;
2. acciones de revisión/atención;
3. declaración de completitud;
4. lista de proveedores;
5. cobertura de componentes;
6. distribución de estados;
7. breakdown por proyecto/tipo;
8. linaje/limitaciones.

La lista está antes de promedios; no hay promedio integral.

### Tabla y tarjetas

`>=768px` tabla semántica única:

- proyecto;
- proveedor;
- evaluación;
- componentes;
- integral actual;
- acumulada;
- decisión;
- acción.

Tablet puede abrir una fila expandible; no oculta una segunda tabla.

`<768px` tarjetas únicas:

- proveedor/proyecto;
- acción/estado;
- cinco componentes;
- integral o por qué está inhabilitada;
- actual/acumulada;
- período/comparación;
- CTA.

No se montan ambas.

### Visualizaciones

- barras/segmentos SVG nativos;
- tabla textual visible;
- unidades y denominadores;
- estados con texto/icono/patrón;
- no canvas;
- no tooltip exclusivo;
- nombres completos accesibles;
- zero, no-calificado y no-aplica visualmente distintos.

### Acciones

Server hrefs:

- `cicHref` a `/programacion-semanal/cic` con contexto canónico S11;
- `providerHref` a S14 cuando aplica;
- null si no autorizado.

La UI distingue:

- “Completar evaluación”;
- “Solicitar <componente> a <roles>”;
- “Revisar antes de asignar”;
- “Dar seguimiento en este período”;
- “Revisar fuente”.

No hay botón guardar.

## Estados de producto

### Vacíos

1. sin población: “No hay proveedores con presencia o evaluación en el alcance y período”.
2. filtro sin resultados: conserva alcance y ofrece limpiar.
3. todos insuficientes: muestra componentes existentes y faltantes/owner.
4. todos completos sin señales: “No hay proveedores bajo umbral; <N>/<T> tienen integral completa”.
5. fuente ambigua: diagnóstico y acción S11/S14, sin decisión.

### Parcial e insuficiente

La integral inhabilitada conserva su espacio y dice:

- “Integral no disponible”;
- “<N>/5 componentes con puntaje”;
- lista de faltantes;
- no aplica separado;
- owner roles;
- CTA autorizada.

No muestra guion ambiguo ni 0 %.

### Recarga

- botón visible;
- conserva query;
- anuncia resultado;
- no duplica requests;
- muestra generatedAt;
- sin polling;
- no muta.

## Tema, sidebar y design system

- `public/css/tokens.css`;
- oscuro default/fallback;
- claro equivalente;
- sin hex/rgb/hsl;
- sin `!important`;
- sin inline colors;
- sin familia local de tokens;
- 44 px touch;
- focus visible;
- reduced motion.

Sidebar:

- etiqueta “Proveedores”;
- ruta `/bi/contratistas`;
- visible A/D/R según hoja/flag;
- en Gerencia y Obra;
- activa exacta;
- sin lógica de roles en React.

## Accesibilidad

- h1 único;
- headings correctos;
- tabla con caption/scope;
- tarjetas como lista;
- filtros con labels;
- sort con `aria-sort`;
- estado de componente no depende de color;
- integral inhabilitada se anuncia con razón;
- drawer focus trap/Escape/return;
- observación texto plano;
- live region para carga/recarga/conteos;
- SVG con título/descripción/tabla;
- zoom 200 % sin overflow de página;
- contraste AA;
- axe serious/critical cero.

## Seguridad, privacidad y RLS

- A/D/R según shared sheet policy;
- otros roles 404;
- proyecto ajeno 403;
- scope antes del reader;
- projectId en cada consulta/fila;
- multi sin mezcla;
- URL no autoriza;
- acciones/hrefs servidor;
- email/NIT/contacto/respuestas/versiones fuera del payload;
- observación solo a usuarios con `lps.cic.ver` ya admitidos;
- texto sin HTML;
- no `dangerouslySetInnerHTML`;
- cache por usuario/scope/período/query;
- logs sin payload sensible;
- no RLS/grants/schema/data;
- no DDL/DML;
- no `/admin/`.

## Coexistencia, corte y rollback

### Coexistencia

- read model canónico único;
- presenter React y adaptador legacy;
- S17 usa conteo corregido;
- S11/S14 siguen dueños de sus rutas;
- `bi-spa.js` se conserva hasta gate compartido;
- no se toca la vista SQL;
- no se elimina bloque CIC legacy en esta entrega individual.

### Corte

1. políticas/componentes puros;
2. reader S11 adapter;
3. contrato main;
4. corrección overview;
5. Zod/gateway/state;
6. núcleo visible;
7. filtros/drawer/comparación;
8. responsive/a11y/themes;
9. route handoff;
10. caller census;
11. retiro posterior T03.

### Rollback

- devuelve página a legacy;
- conserva endpoint/compat;
- no revierte datos;
- no recompone vista SQL;
- no cambia permisos;
- no elimina S11/S14.

## Pruebas

### PHP puras

Fakes/fixtures para:

- A/D/R y roles ocultos;
- scope/multi;
- períodos por proyecto;
- población/última/prior;
- cero/NR/NA/null/inválido;
- cinco componentes;
- integral/pesos/tolerancia;
- completitud actual/acumulada;
- umbrales exactos 0,49/0,50/0,69/0,70;
- herencia de status;
- decisiones/acciones;
- comparación;
- prioridad;
- filtros/paginación;
- resumen/breakdowns;
- titular;
- S17 count;
- privacidad;
- compatibilidad;
- no writes.

No ejecutar suites que siembran CIC o BI aun con rollback.

### Frontend

- Zod success/error strict;
- gateway solo cliente;
- query;
- hook abort/cache;
- estados;
- titular/resumen;
- componentes/completitud;
- tabla/cards;
- filtros/sort;
- comparación;
- drawer;
- acciones null/allowed;
- temas;
- teclado/a11y;
- no dual render.

### Navegador interceptado

- A/D/R misma hoja;
- oculto 404;
- proyecto ajeno 403;
- 0 válido;
- todos insuficientes;
- mezcla completa/incompleta;
- umbrales;
- búsqueda/filtro/página;
- drawer;
- links;
- dark/light;
- cinco viewports/zoom;
- offline/partial/invalid;
- cero mutación;
- requests inesperados cero;
- consola limpia;
- axe cero.

## Criterios de aceptación

### Acceso, scope y período

1. **S23-AC-001:** `/bi/contratistas` sirve React tras el corte.
2. **S23-AC-002:** A autorizado abre la misma hoja.
3. **S23-AC-003:** D autorizado abre la misma hoja.
4. **S23-AC-004:** R autorizado abre la misma hoja.
5. **S23-AC-005:** OT y otros roles reciben 404.
6. **S23-AC-006:** flag apagado oculta la hoja según T03.
7. **S23-AC-007:** proyecto ajeno devuelve 403 antes de leer.
8. **S23-AC-008:** A inicia con sus obras autorizadas según T03.
9. **S23-AC-009:** D/R inician en proyecto activo autorizado.
10. **S23-AC-010:** multi conserva projectId/obra por fila.
11. **S23-AC-011:** semana se resuelve a rango real por proyecto.
12. **S23-AC-012:** rango explícito gobierna la hoja.
13. **S23-AC-013:** no entra una evaluación posterior al corte.
14. **S23-AC-014:** período por proyecto es visible en multi.
15. **S23-AC-015:** URL/role/capability cliente no conceden autoridad.
16. **S23-AC-016:** no cambia RBAC, RLS, grants ni flag.

### Población e identidad

17. **S23-AC-017:** población reutiliza S11 sin side effects.
18. **S23-AC-018:** fila representa proveedor+proyecto al corte.
19. **S23-AC-019:** proveedor nominal de dos proyectos no se fusiona.
20. **S23-AC-020:** nombre no es clave estable.
21. **S23-AC-021:** providerRef opaco no revela otro proyecto.
22. **S23-AC-022:** última evaluación es <= corte.
23. **S23-AC-023:** evaluación previa es la real anterior, no semana-1.
24. **S23-AC-024:** presencia/evaluación en período se declaran.
25. **S23-AC-025:** proyección S11 sigue read-only.
26. **S23-AC-026:** duplicado/metadata ambigua permanece diagnosticado.
27. **S23-AC-027:** paginación no duplica proveedor.
28. **S23-AC-028:** refresh no materializa ni recalcula CIC.

### Componentes y completitud

29. **S23-AC-029:** se muestran los cinco componentes por nombre.
30. **S23-AC-030:** score interno válido está en 0..1.
31. **S23-AC-031:** display muestra porcentaje sin cambiar valor.
32. **S23-AC-032:** cero es scored, no faltante.
33. **S23-AC-033:** NR/null/vacío son not-rated.
34. **S23-AC-034:** NA es not-applicable.
35. **S23-AC-035:** fuera de rango/texto raro es invalid.
36. **S23-AC-036:** raw strings no cruzan HTTP.
37. **S23-AC-037:** integral actual exige cinco scored actuales.
38. **S23-AC-038:** integral acumulada exige cinco scored acumulados.
39. **S23-AC-039:** not-applicable no completa integral.
40. **S23-AC-040:** not-applicable no genera CTA de captura.
41. **S23-AC-041:** not-rated declara faltante y owner roles.
42. **S23-AC-042:** invalid declara fuente a revisar.
43. **S23-AC-043:** basis declara 5 esperados y N puntuados.
44. **S23-AC-044:** insufficient no publica valor.
45. **S23-AC-045:** stored integral no completa evidencia ausente.
46. **S23-AC-046:** owner roles salen del servidor/catálogo.
47. **S23-AC-047:** React no contiene matriz de disciplinas.
48. **S23-AC-048:** actual/acumulada tienen completitud independiente.

### Integral, estado y decisiones

49. **S23-AC-049:** integral completa usa pesos 30/20/20/20/10.
50. **S23-AC-050:** no redistribuye peso en BI.
51. **S23-AC-051:** recomputada coincide con S11 al tener cinco.
52. **S23-AC-052:** mismatch >0,001 produce invalid.
53. **S23-AC-053:** >=0,70 es approved.
54. **S23-AC-054:** >=0,50 y <0,70 es follow_up.
55. **S23-AC-055:** <0,50 es not_accepted.
56. **S23-AC-056:** insufficient produce unavailable.
57. **S23-AC-057:** status hereda completitud.
58. **S23-AC-058:** no compara score 0..1 contra 70/50.
59. **S23-AC-059:** view status/alert no gobiernan target.
60. **S23-AC-060:** current/cumulative se muestran separados.
61. **S23-AC-061:** reviewBeforeAssignment exige acumulada completa <0,50.
62. **S23-AC-062:** attentionThisPeriod exige actual completa <0,70 y período.
63. **S23-AC-063:** incomplete genera completar evidencia, no veredicto.
64. **S23-AC-064:** invalid genera revisar fuente.
65. **S23-AC-065:** approved no genera acción negativa.
66. **S23-AC-066:** status no bloquea automáticamente contratación.
67. **S23-AC-067:** lenguaje no predice incumplimiento futuro.
68. **S23-AC-068:** titular nunca llama aceptable a insufficient.

### Comparación, resumen y breakdowns

69. **S23-AC-069:** comparación es mismo proveedor/proyecto.
70. **S23-AC-070:** integral compara solo dos cortes completos.
71. **S23-AC-071:** delta usa puntos porcentuales.
72. **S23-AC-072:** corte previo se muestra.
73. **S23-AC-073:** comparación insuficiente se declara.
74. **S23-AC-074:** no hay ranking ordinal.
75. **S23-AC-075:** no se promedian integrales de proveedores.
76. **S23-AC-076:** multi agrega conteos, no scores.
77. **S23-AC-077:** resumen distingue población/evaluados/completos.
78. **S23-AC-078:** porcentajes incluyen numerador/denominador.
79. **S23-AC-079:** breakdown de componentes declara cobertura.
80. **S23-AC-080:** breakdown actual/acumulado no mezcla bases.
81. **S23-AC-081:** breakdown por proyecto conserva identidad.
82. **S23-AC-082:** breakdown por tipo/alcance declara unidad.
83. **S23-AC-083:** orden default sigue prioridad sin número de ranking.
84. **S23-AC-084:** S17 cuenta proveedores distintos acumulados completos <0,50.
85. **S23-AC-085:** S17 no cuenta filas históricas ni insufficient.
86. **S23-AC-086:** drilldown S17 conserva contexto S23.

### Filtros, contrato y drawer

87. **S23-AC-087:** único GET sirve snapshot/página/detalle embebido.
88. **S23-AC-088:** no existe request por fila.
89. **S23-AC-089:** q busca proveedor/tipo/alcance.
90. **S23-AC-090:** q no busca ni expone PII.
91. **S23-AC-091:** q >100 devuelve 422.
92. **S23-AC-092:** filtros aceptan solo catálogos servidos.
93. **S23-AC-093:** filtros gobiernan titular/resumen/lista/breakdowns.
94. **S23-AC-094:** filterOptions declara scope-period.
95. **S23-AC-095:** filtro reinicia offset.
96. **S23-AC-096:** limit 1–100 y offset >=0.
97. **S23-AC-097:** sort score pone unavailable al final.
98. **S23-AC-098:** focus se revalida y no autoriza.
99. **S23-AC-099:** URL no contiene PII.
100. **S23-AC-100:** success/error pasan contratos PHP/Zod.
101. **S23-AC-101:** errores son tipados y no filtran internos.
102. **S23-AC-102:** drawer abre desde fila/estado/componente.
103. **S23-AC-103:** drawer muestra base, fórmula, umbral y evidencia.
104. **S23-AC-104:** drawer muestra observación como texto plano.
105. **S23-AC-105:** drawer excluye respuestas/contacto/NIT/email/version.
106. **S23-AC-106:** drawer conserva foco/Escape/return.
107. **S23-AC-107:** href CIC/S14 es autorizado o null.
108. **S23-AC-108:** ninguna acción BI muta datos.

### Estado, responsive, tema y accesibilidad

109. **S23-AC-109:** loading usa skeleton/aria-busy.
110. **S23-AC-110:** refreshing conserva snapshot y antigüedad.
111. **S23-AC-111:** partial identifica bloque/reintento.
112. **S23-AC-112:** invalid-contract no pinta valores.
113. **S23-AC-113:** offline no cruza identidad.
114. **S23-AC-114:** respuesta tardía no pisa query.
115. **S23-AC-115:** vacío real y filtro vacío son distintos.
116. **S23-AC-116:** todos insufficient muestran N/5, faltantes y owners.
117. **S23-AC-117:** <768 monta solo tarjetas.
118. **S23-AC-118:** >=768 monta solo tabla.
119. **S23-AC-119:** móvil conserva componentes/estado/razón/acción.
120. **S23-AC-120:** cinco viewports no tienen overflow de página.
121. **S23-AC-121:** zoom 200 % conserva operación.
122. **S23-AC-122:** oscuro default y claro equivalente.
123. **S23-AC-123:** CSS usa tokens sin literales/`!important`.
124. **S23-AC-124:** touch targets mínimo 44 px.
125. **S23-AC-125:** reduced motion elimina movimiento no esencial.
126. **S23-AC-126:** teclado opera filtros/sort/lista/drawer.
127. **S23-AC-127:** lector distingue 0/no calificado/no aplica/inválido.
128. **S23-AC-128:** SVG tiene alternativa textual visible.
129. **S23-AC-129:** axe serious/critical es cero.
130. **S23-AC-130:** consola queda limpia.

### Integridad y cierre

131. **S23-AC-131:** solo `cliente.ts` llama fetch.
132. **S23-AC-132:** browser no emite mutación/request inesperado.
133. **S23-AC-133:** queries usan scope y prepared statements.
134. **S23-AC-134:** payload no expone PII ni respuestas.
135. **S23-AC-135:** vista SQL y schema permanecen intactos.
136. **S23-AC-136:** tests S23 no ejecutan DML.
137. **S23-AC-137:** `/admin/` permanece intacto.
138. **S23-AC-138:** presenter legacy deriva del read model corregido.
139. **S23-AC-139:** storytelling/actions viejos no gobiernan.
140. **S23-AC-140:** sidebar muestra Proveedores en lienzos A y D/R.
141. **S23-AC-141:** caller census precede retiro legacy.
142. **S23-AC-142:** bloque/vistas compartidas esperan T03/S17–S24.
143. **S23-AC-143:** rollback cambia ruta/código, nunca datos.
144. **S23-AC-144:** diff no incluye RLS/RBAC/schema/data/credenciales.
145. **S23-AC-145:** no hay commit/push/PR/deploy sin autorización.

## Entregas verticales

1. **Verdad de evidencia:** componentes, completitud, integral y estado.
2. **Lectura de decisión:** población/período/comparación, resumen y contrato.
3. **Núcleo visible:** titular, acciones, completitud y lista.
4. **Exploración:** filtros, breakdowns, drawer y enlaces.
5. **Calidad:** responsive, temas, accesibilidad y estados.
6. **Corte:** S17, compatibilidad, ruta, censo y rollback.

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Clasificar 0..1 con 70/50 | policy 0,70/0,50 y prueba de borde |
| Integral parcial aparenta certeza | cinco scored obligatorios |
| Cero confundido con vacío | unión discriminada |
| NA fuerza captura imposible | insufficient sin CTA de captura |
| Alertas legacy falsas | no consumir columnas derivadas de vista |
| Promedio/ranking injustificado | conteos + comparación intraproveedor |
| Nombre como identidad | providerRef S11 |
| Mezcla entre proyectos | project-qualified key/scope |
| Acción no permitida | href/capability servidor |
| Duplicar editor/maestro | S11/S14 dueños |
| Exponer PII/cuestionario | DTO BI reducido + Zod strict |
| Prueba toca CIC | fakes; suites DML excluidas |
| Dos modelos BI/overview | S17 delega al mismo read model |
| Retiro rompe otras hojas | gate T03/S17–S24 |

## Alternativas descartadas

- Portar la tabla legacy: no contiene proveedores.
- Mostrar `Cal_Integral` almacenada: viola CT-6.2.
- Cambiar la vista SQL: DDL/schema fuera de alcance.
- Dividir por 100 si valor >1: autocorrección ambigua; se marca inválido.
- Contar NA como puntuado: no hay valor comparable.
- Redistribuir pesos en BI: vuelve PAC una integral parcial.
- Ocultar integral incompleta: pierde la acción de completar evidencia.
- Usar alert legacy: escala y completitud incorrectas.
- Crear ranking: no aprobado y mezcla contextos.
- Promediar proveedores: oculta base y heterogeneidad.
- Añadir endpoint detail: la fila canónica basta.
- Editar en drawer: duplica S11.
- Exponer contacto/NIT: no ayuda a D45.
- Añadir notificación: CT-11 no define evento para S23.
- Retirar legacy ahora: archivo compartido.

## Decisiones pendientes

Ninguna para implementar S23 dentro del alcance aprobado.

La regla de publicación de cinco componentes ya está aprobada por CT-6.2/D44. Los umbrales
0,70/0,50 son la normalización exacta del contrato catalogado 70/50 sobre score 0..1, no un umbral
nuevo.

## Autor revisión

La autorrevisión comprobó:

- ruta/página/endpoint reales;
- ausencia React;
- tabla legacy sin proveedores;
- payload sin colección;
- fórmula/escala S11;
- vista SQL 70/50 contra 0..1;
- herencia de completitud;
- medición global 323 filas;
- medición local 3/3 alertas con 0/3 completos;
- acceso A/D/R en dos lienzos;
- S11/S14 como dueños operativos;
- filtros/períodos/comparación;
- privacidad;
- sidebar, oscuro/claro, responsive y accesibilidad;
- RLS/schema/datos intactos;
- ausencia de DDL/DML;
- 145 criterios observables;
- plan habilitado sin decisión abierta.

El siguiente artefacto obligatorio es
`docs/superpowers/plans/2026-08-30-s23-bi-contratistas-react.md` mediante
`superpowers:writing-plans`. No se implementa en esta sesión.

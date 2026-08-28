---
capa: fuente
tipo: spec
estado: abierto
fecha: 2026-08-28
areas: [arquitectura, design-system]
fuente: "sesión de brainstorming con Felipe, 2026-08-28, tras el cierre de la fase cero de temas y forma (PR #18, merge d397aa6a)"
resumen: "Convergencia del frontend a una SPA única React + TypeScript, con PHP reducido a API. Shell primero, módulos después, sitio viejo conviviendo hasta que el último cruce."
---

# Migración a React + TypeScript — diseño v0

> **Estado: borrador (v0).** Las 8 decisiones de Felipe están tomadas y son firmes.
> Lo que falta antes de un plan ejecutable está listado en «Preguntas abiertas»; ninguna
> de ellas invalida las decisiones, pero R6 (grilla) puede encarecerse según su respuesta.

## Por qué

Cuatro dolores, confirmados por Felipe en el mismo orden de peso:

1. **El asistente de IA rinde peor en el JS viejo.** Medido en este repo: los agentes producen
   mejores resultados en `pdc-app/` (React + TS) que en `public/js/modules/programa_general/hot.js`
   (jQuery + Handsontable). La causa es legible: en el primero el asistente *lee* la forma de los
   datos y el catálogo de componentes; en el segundo la adivina.
2. **Desarrollar features nuevas es lento** sobre el stack tradicional.
3. **El legacy crece solo.** Cada mes que se construye sobre el stack viejo es trabajo que hay
   que reescribir después.
4. **Dos stacks conviviendo producen dos formas de hacer todo.**

Un quinto motivo, no mencionado en la sesión pero encontrado al inventariar y que endurece el
caso: **los tres módulos de programación corren Handsontable con
`licenseKey: 'non-commercial-and-evaluation'`** (`public/js/modules/{programa_general,
programacion_intermedia,programa_actualizar}/hot.js`). Esa licencia no cubre uso comercial. Es
exposición legal real, no deuda técnica, y R6 la resuelve.

## Punto de partida medido (2026-08-28)

| Qué | Cuánto |
|---|---|
| Vistas PHP (`views/` + `admin/views/`) | 58 archivos |
| JS del lado PHP (`public/js/`) | 50 archivos, 30.242 líneas |
| — de las cuales, los 3 módulos de programación | 15.977 líneas (53 %) |
| Endpoints `/api/*` ya sirviendo JSON | 70 |
| `pdc-app/` (React + TS + Vite + AG Grid, con Vitest) | 83 archivos, 6.316 líneas |
| `ct-app/` (React + TS) | 33 archivos |

**El patrón ya existe en el repo.** `pdc-app/` no es una hipótesis: es una SPA React + TypeScript
+ Vite que consume el PHP por JSON, publica su bundle en `public/pdc-app/` y corre sus tests con
Vitest. Esta spec generaliza y corrige ese patrón, no lo inventa.

**La fase cero de temas y forma es el habilitador.** Fusionada el 2026-08-28 (PR #18, `d397aa6a`):
63 tokens, 7 guards, ambos temas contractuales. Los componentes React consumen esos mismos tokens
— sin esa capa, cada módulo migrado habría reinventado su color y su forma.

## Las 8 decisiones

### R1 — Destino: una sola SPA; el PHP queda como API pura

Al final del camino hay **una** aplicación React + TypeScript que contiene shell y todos los
módulos. `src/` (controladores, servicios, RBAC, `Database`) se conserva íntegro y sirve JSON;
`views/` desaparece por completo.

**Descartado:** SPAs por módulo dentro del shell PHP (el patrón `pdc-app` extendido a todo). Deja
el shell, el login y la navegación en PHP + JS viejo para siempre, lo que no resuelve los dolores
1 y 4 — el asistente seguiría enfrentando dos mundos, y el shell seguiría siendo artesanal.

### R2 — Camino: shell React primero, módulos después

Se construye primero la cáscara (login, selector de proyecto, navegación, sesión, tema). Cada
módulo migrado se monta ahí dentro; los no migrados siguen en el sitio PHP.

**El porqué que decide:** con módulos-primero, la integración con el shell PHP (montaje, sesión,
tema por vista) se hace N veces y se bota al final. Con shell-primero se hace una vez. Además, el
shell nuevo **reemplaza** las 7 páginas que hoy fuerzan el tema oscuro vía
`public/js/modules/aia_ui/theme.js` — el bloqueante heredado de la fase cero se disuelve sin
tocar el código viejo, en vez de arreglarse aparte.

### R3 — Programa General espera al shell y nace en React

El próximo frente de producto **no** se ejecuta sobre Handsontable + jQuery. Temas y forma se
aplican a Programa General **una sola vez**, ya en el stack definitivo.

**Lo que cuesta:** la obra ve el tema claro en Programa General más tarde.
**Lo que evita:** hacer dos veces el trabajo visual del módulo más avanzado en diseño.

Consecuencia operativa: el chip de «arrancar el plan de Programa General» desplegado el 2026-08-28
antes de esta decisión quedó obsoleto y fue retirado en la misma sesión.

### R4 — `pdc-app` y `ct-app` se absorben sin prisa

El shell nace como aplicación nueva. PDC y Control Tower siguen funcionando donde están y se mudan
a `frontend/modules/` cuando les toque mantenimiento real, no por deporte. Ambas ya son React +
TypeScript: la mudanza es mecánica (mover archivos, unificar el router y el cliente HTTP), no
reescritura.

### R5 — Ritmo: shell mínimo, estrenar rápido

El shell v1 contiene **exactamente cinco piezas** y nada más:

1. Login contra el endpoint PHP existente.
2. Selector de proyecto.
3. Navegación lateral (misma estructura del sidebar actual).
4. Conmutador de tema, con claro de entrada — D12 de la spec de temas, por fin real.
5. Enrutado con guardas de sesión y RBAC, leyendo las capacidades que el PHP ya expone.

**Fuera del shell v1, explícitamente:** notificaciones, búsqueda global, preferencias de usuario,
laboratorio del design system en React. Ninguna tiene usuarios hasta que haya módulos que las
necesiten; incluirlas solo aleja el estreno.

### R6 — Grilla: AG Grid Community para todo

Un solo vendor de grilla, MIT, gratis, ya probado en `pdc-app` (`ag-grid-community` ^36.0.2).
Retira Handsontable del producto y con él la exposición de licencia.

**El riesgo que hay que cerrar antes de reescribir la primera grilla:** el copy/paste de rangos
estilo Excel y la selección de rangos son features de AG Grid **Enterprise** (de pago). Hay que
preguntarle a los residentes si de verdad los usan o si la edición celda a celda basta. Si los
usan de verdad, la decisión se revisa con el costo sobre la mesa (~1.000 USD/desarrollador,
licencia perpetua por versión) — no se resuelve reescribiendo la feature a mano.

**Descartado:** licencia comercial de Handsontable (~890 USD/dev/año). Mantiene dos vendors de
grilla en el producto para siempre y es un gasto recurrente contra uno perpetuo.

### R7 — Frontera de datos: esquemas Zod

Cada endpoint que React consume tiene su esquema Zod. El esquema **valida el JSON al entrar** y
**genera el tipo TypeScript** — una sola definición, imposible de desincronizar.

Es la decisión que más paga para el dolor 1: es exactamente donde los asistentes adivinan hoy.

**Descartado:** OpenAPI generado desde PHP (más infraestructura y disciplina del lado PHP de la
que este frente puede costear al arrancar; reconsiderable más adelante) y tipos TS a mano sin
validación runtime (los errores de contrato seguirían apareciendo en producción, como hoy).

### R8 — Testing: contrato de API como red de seguridad, sin gate de cobertura

Tres capas, las tres con precedente en el repo:

- **Unitarias (Vitest):** lógica pura y esquemas. Ya corre en `pdc-app`.
- **Contrato de API:** un test por endpoint migrado que le pega al PHP real y valida la respuesta
  contra su esquema Zod. **Es la red que atrapa cuando el backend cambia sin avisar.**
- **Visuales (Playwright):** los goldens existentes apuntando a las rutas nuevas; la matriz por
  tema de la fase cero sirve tal cual.

**Descartado explícitamente: el gate de cobertura mínima (el clásico 80 %).** Empuja a escribir
tests tautológicos que suben el número sin atrapar nada; este repo ya midió esa trampa. La
exigencia es test de contrato por endpoint migrado, que sí atrapa roturas reales.

## Arquitectura

### La transición: dos mundos, un dominio

```
                    ┌─────────────────────────┐
   navegador  ───►  │  public/index.php       │
                    │  (FastRoute, el mismo)  │
                    └───────────┬─────────────┘
                                │
              ┌─────────────────┴──────────────────┐
              ▼                                    ▼
   ruta migrada → entrega              ruta vieja → vista PHP
   el index.html de la SPA             como siempre
              │                                    │
              ▼                                    ▼
   ┌────────────────────┐              ┌────────────────────┐
   │  frontend/ (React) │              │  views/ (PHP)      │
   │  shell + módulos   │◄─ pasarela ─►│  módulos restantes │
   └─────────┬──────────┘   por URL    └─────────┬──────────┘
             │                                   │
             └────────────┬──────────────────────┘
                          ▼
              ┌───────────────────────┐
              │  src/ — PHP, 70 /api  │
              │  RBAC · sesión · BD   │
              └───────────────────────┘
```

Tres propiedades que hacen la convivencia barata:

- **La sesión es una sola.** La SPA se sirve del mismo origen, así que la cookie de sesión PHP
  vale para ambos mundos. El shell React hace login contra el endpoint existente. **No se inventa
  autenticación nueva** — duplicar auth es la fuente clásica de agujeros de seguridad en
  migraciones y no compra nada.
- **La frontera se cruza por URL**, con carga completa de página. El menú de cada mundo enlaza al
  otro; el usuario no percibe el cruce.
- **Un solo punto de decisión**, en `public/index.php`: ruta migrada o ruta vieja.

### Dónde vive el código

```
frontend/                    ← la SPA única (nueva)
  src/
    shell/                   ← login, navegación, tema, guardas de sesión y RBAC
    design-system/           ← componentes aia-* en React
    lib/
      api/                   ← cliente HTTP único + esquemas Zod por dominio
    modules/
      programa-general/      ← el primero
  publica su bundle en public/app/
  AGENTS.md                  ← reglas del stack nuevo, igual que la raíz tiene el suyo

pdc-app/  ct-app/            ← siguen donde están (R4)
public/css/tokens.css        ← SIN CAMBIOS: los componentes React lo consumen tal cual
```

**Los componentes React no reinventan el CSS.** Consumen `public/css/tokens.css` y las clases
`aia-*` existentes. La fase cero es literalmente la capa compartida entre ambos mundos — es para
lo que se construyó.

**Descartado por YAGNI:** monorepo con workspaces (turborepo, pnpm workspaces). Con una SPA nueva
y dos mini-apps que se absorben después, es infraestructura sin usuarios. Se agrega cuando de
verdad haya dos paquetes compartiendo código.

**Descartado:** reescribir el design system en CSS-in-JS o Tailwind. Los tokens y las clases
`aia-*` acaban de estabilizarse con goldens y contratos; cambiarles el vehículo sería tirar la
fase cero recién fusionada.

### La frontera de datos, en concreto

**Un archivo de esquemas por dominio, no por endpoint.** Los 70 endpoints se agrupan en ~10
dominios (`bi`, `semanal`, `intermedia`, `programa-general`, `cnc`, `cic`, `pdc`, …).

Un **cliente HTTP único** envuelve todas las llamadas: inyecta el token CSRF que el PHP ya exige,
valida la respuesta contra su esquema, y ante un desajuste lanza un error que nombra endpoint y
campo culpable. **Nadie llama `fetch` directo.**

**Los 70 esquemas no se escriben por adelantado.** Serían semanas sobre endpoints que pueden
cambiar antes de migrarse. Cada módulo trae los suyos; el shell arranca con los 4 o 5 que necesita
(sesión, proyectos, semanas, capacidades).

## Orden de migración

| # | Frente | Por qué en ese punto |
|---|---|---|
| 1 | Shell mínimo | Sin cáscara no hay dónde montar nada. Mata el bloqueante de `theme.js` |
| 2 | Programa General | Diseño más avanzado: fase cero aplicada y goldens ya aprobados |
| 3 | Programación Semanal | El corazón del negocio (Last Planner); se hace con dos módulos de experiencia encima |
| 4 | Programación Intermedia | Hermana de Semanal, reusa casi todo lo aprendido |
| 5 | BI y Control Tower | CT ya es React: mudanza, no reescritura |
| 6 | Gestión, admin y el resto | Pantallas simples, van rápido al final |
| — | PDC | Se absorbe cuando le toque mantenimiento propio (R4) |

El sitio PHP muere cuando el último módulo cruza. No antes, y sin fecha forzada.

**Descartado: migrar BI antes que Programación Semanal**, aunque sea más fácil. Semanal es donde
vive el valor del producto; hacer lo cómodo primero deja la deuda cara para el final, cuando hay
menos impulso.

## Prácticas IA-first

Lo que hace el código nuevo legible para un asistente — el dolor 1, atacado directamente:

- **Archivos chicos, un solo trabajo cada uno.** Techo blando: 300 líneas. Un archivo de 5.000
  líneas no cabe en la cabeza de nadie, humano o no.
- **Nombres del negocio, no del framework.** `RestriccionLiberada`, `SemanaComprometida`. El
  `GLOSARIO.md` existente es el diccionario.
- **`DESIGN.md` como contrato de consumo**, igual que hoy: el asistente lee qué componente usar
  antes de inventarse uno.
- **Los componentes del design system, primero.** Antes de la primera pantalla se construyen
  botón, chip, campo, tabla — así ningún módulo improvisa.
- **`frontend/AGENTS.md`** con las reglas del stack nuevo.

## Lo que NO cambia

Dicho explícitamente porque es la principal fuente de ansiedad en una migración:

- **La lógica de negocio se queda en PHP.** RBAC, cálculos, base de datos, servicios: intactos.
  El PHP solo deja de pintar HTML.
- **Los usuarios no aprenden nada nuevo.** Misma aplicación, mismas pantallas, mismo flujo.
- **No hay día de corte.** Los dos mundos conviven meses; nadie se queda sin sistema.
- **El design system se reusa tal cual.** Mismos colores, mismas esquinas, mismos tokens.
- **El deploy sigue siendo el mismo**, y sigue exigiendo autorización explícita de Felipe.

## CI

El pipeline actual se **extiende**, no se reemplaza. Se le suman `typecheck`, `lint` y `test` de la
SPA. Los gates de diseño existentes siguen midiendo lo mismo, porque los tokens no cambian.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| **Los 3 módulos de programación son el 70 % del esfuerzo** (15.977 líneas) | Se atacan tercero y cuarto, con el shell y un módulo de experiencia encima. No son el primer frente |
| **AG Grid Community puede no cubrir el copy/paste de rangos** | Validar con los residentes ANTES de reescribir la primera grilla (ver Preguntas abiertas) |
| **Migración larga sin valor visible** | R5 lo ataca de frente: shell mínimo y estrenar con un módulo real adentro |
| **El equipo no conoce React** | Los frentes 1 y 2 son también el aprendizaje; `pdc-app` sirve de referencia viva |
| **Deriva entre los dos mundos durante la convivencia** | Tokens compartidos (una sola fuente) + tests de contrato sobre los mismos endpoints |

## Preguntas abiertas

Ninguna invalida las decisiones; se responden antes o durante el primer plan.

1. **¿Los residentes usan copy/paste de rangos estilo Excel en las hojas de programación?**
   Decide si AG Grid Community basta o si R6 se revisa con el costo de Enterprise sobre la mesa.
   **Se responde preguntándoles, antes de reescribir la primera grilla.**
2. **¿Cuánto toma el shell mínimo?** Se estima al escribir su plan, no aquí.
3. **¿El login React reusa la vista PHP de recuperación de clave, o se migra también?** Afecta el
   alcance exacto del shell v1.
4. **¿Qué pasa con `admin/`?** Es una mini-app PHP arquitectónicamente aislada (su propio front
   controller, router y modelos). Puede migrarse al final, quedarse en PHP indefinidamente, o
   salir del alcance. No se decidió en esta sesión.

## Qué sigue

1. Felipe revisa esta spec y aprueba o pide cambios.
2. Con la spec aprobada: `superpowers:writing-plans` para el **plan del shell mínimo** (frente 1),
   que es el único ejecutable hoy.
3. El plan de Programa General en React (frente 2) se escribe después, cuando el shell exista.

## Archivos de esta spec

- [[docs/superpowers/specs/2026-08-28-temas-claro-oscuro-end-to-end-design|Spec de temas]] · [[docs/superpowers/specs/2026-08-28-forma-bordes-radios-relieves-design|Spec de forma]] — la fase cero que habilita esta migración.
- [[docs/pdc-v2|PDC v2]] — el módulo cuyo patrón React + Vite generaliza esta spec.
- [[TASKS]] — el bloqueante de `theme.js` que R2 disuelve.

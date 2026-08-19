---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-03
areas: [arquitectura]
tags: [generado]
fuente: docs/superpowers/specs/2026-08-03-arquitectura-en-la-wiki-design.md
resumen: La wiki explica por qué se decidieron las cosas y qué trampas tiene el repositorio, pero no responde a las preguntas de orientación: qué módulos existen, qué…
---

# Arquitectura del proyecto en la wiki, generada desde el código

**Fecha:** 2026-08-03
**Estado:** aprobado
**Alcance:** `memoria/arquitectura/`, `memoria/flujos/`, un script generador, y el retiro de
`docs/ROUTES.md`. No modifica código de aplicación ni schema.
**Depende de:** `2026-08-03-lint-wiki-memoria-design.md`, que se ejecuta antes.

## Problema

La wiki explica *por qué* se decidieron las cosas y *qué trampas* tiene el repositorio, pero no
responde a las preguntas de orientación: qué módulos existen, qué expone cada uno, a qué otros
módulos conduce, quién tiene permiso para usarlo y qué lugar ocupa en el flujo de trabajo. Quien
entra al proyecto tiene que reconstruirlo leyendo 222 rutas en `public/index.php`.

Parte de esa información ya existe, dispersa y con un fallo grave:

- `docs/VISTAS-MODULOS.md` (1276 líneas) cataloga los módulos de vistas, sus paradigmas de UI y sus
  ficheros.
- `docs/ROUTES.md` (146 líneas) es el inventario canónico de rutas **y la matriz de navegación
  cruzada** — exactamente «a dónde conduce cada superficie». **No viaja en git**: está en
  `.gitignore`, así que desaparece en un clon fresco. Es el mismo patrón que dejó cuatro goals
  fuera del repositorio hasta el 2026-08-02.

El terreno: 222 rutas, 49 controladores, 40 servicios, 40 vistas, 15 scripts legados y 40 módulos
JS.

## Decisión de fondo: generar lo que cambia

Un inventario escrito a mano envejece sin avisar. Esta misma semana el lint encontró una nota
apuntando a `styles.css:6476` en un archivo que hoy tiene 4380 líneas. Con 222 rutas, un catálogo
manual estaría desfasado en semanas y nadie se enteraría.

Por eso cada página de módulo tiene **dos zonas**:

```markdown
<!-- generado:inicio -->
… rutas, controladores, servicios, tablas y capacidades RBAC …
<!-- generado:fin -->
```

Dentro de los marcadores manda el script. Fuera, la prosa escrita a mano: qué resuelve el módulo,
por qué está así, qué trampas tiene, a qué flujo pertenece. **Regenerar no toca la prosa.** Ese es
el mecanismo que impide el envejecimiento sin renunciar al criterio.

## El generador

`scripts/wiki-arquitectura.mjs`, ejecutado a mano cuando cambian rutas, controladores o permisos.
Extrae:

| Dato | De dónde |
|---|---|
| Rutas: verbo, path, destino | `public/index.php` |
| Controlador y método, o script legado | el destino de cada ruta |
| Servicios que usa cada controlador | `use`/instanciación en `src/Controllers/` |
| Tablas que toca | consultas en controladores y servicios |
| Capacidades por rol | `RbacCatalog` y las listas de `RbacManager::getCapabilities()` |

No inventa: lo que no puede determinar lo deja marcado como indeterminado en vez de adivinarlo.

## Las páginas

**~20 módulos** en `memoria/arquitectura/`, uno por dominio real: autenticación, selector de
proyectos, programa general, programación intermedia, programación semanal, sus submódulos
CNP/CNC/CIC, plan de compras, contratos, listado de actividades, profesionales, subcontratistas,
control de cambios, cronograma, indicadores, torre de control BI, integración, admin y laboratorio
del design system.

Cada página lleva, además de las zonas generadas, una tabla que responde de un vistazo las dos
preguntas de rol:

- **Quién** — qué roles RBAC pueden ver y cuáles editar.
- **Dónde encaja** — su posición en el flujo LPS, en el del PDC, o en ambos.

**Dos páginas de flujo** en `memoria/flujos/`:

- `lps.md` — Programa General → Programación Intermedia → Programación Semanal → CNC/CNP/CIC →
  indicadores.
- `pdc.md` — presupuesto → maestro de insumos → paquetes → plan con fechas → seguimiento, según
  `docs/pdc-v2.md`.

Cada módulo enlaza al flujo donde participa y cada flujo a sus módulos. Eso teje el grafo por
dependencia real, no por catálogo: es la diferencia entre un mapa y una lista.

## Retiro de `docs/ROUTES.md`

El inventario de rutas y la matriz de navegación pasan a generarse en la wiki, versionados. Con
eso, mantener `docs/ROUTES.md` significaría dos fuentes del mismo dato condenadas a contradecirse,
y una de ellas invisible en cualquier clon.

`docs/ROUTES.md` se retira y `AGENTS.md` pasa a apuntar a la wiki. Antes de borrarlo se comprueba
que lo generado cubre todo lo que él decía: si algo suyo no es derivable del código —una nota de
criterio, una advertencia— se rescata como prosa en la página del módulo correspondiente. **No se
borra nada que no esté cubierto.**

## Verificación

- **Cobertura de rutas:** el número de rutas en las páginas generadas coincide con las declaradas
  en `public/index.php`; ninguna queda sin módulo asignado.
- **Fidelidad del RBAC:** para tres módulos, contrastar la tabla generada con `RbacManager` leyendo
  el código, y comprobar en el navegador con un rol permitido y uno denegado por la puerta de
  servicio, como exige `AGENTS.md`.
- **Los marcadores protegen:** escribir prosa en una página, regenerar, y confirmar que la prosa
  sigue intacta.
- **Nada perdido de `ROUTES.md`:** revisar sus 146 líneas frente a lo generado antes de retirarlo.
- **Enlaces:** cero rotos y cero ambiguos, también sobre un clon fresco.
- **Idempotencia:** regenerar dos veces seguidas no produce diferencias en git.

## Fuera de alcance

- Documentar clase por clase y método por método: se decidió el nivel de módulo.
- Reescribir o mover `docs/VISTAS-MODULOS.md`, que sigue siendo la fuente sobre vistas y se enlaza
  desde las páginas de módulo.
- Automatizar la regeneración con hooks o CI: el script se corre a mano.
- Documentar `admin/` al mismo nivel de detalle que la aplicación principal: es un mini-app
  arquitectónicamente aislado y basta con una página que lo explique como tal.

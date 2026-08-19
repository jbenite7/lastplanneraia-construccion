---
capa: wiki
tipo: mapa
estado: vigente
fecha: 2026-08-19
areas: [admin]
fuente: AGENTS.md, CLAUDE.md §admin, el módulo del área
resumen: "El panel admin es una mini-app aparte: no reutiliza src/Core ni src/Security, y tratarla como extensión es el error del área"
---

# Mapa · Admin

## Qué manda

[[CLAUDE|CLAUDE.md]] §`admin/` is a separate mini-app.

## La idea que ordena el área

**`admin/` no es una extensión de `src/`: es otra aplicación dentro del mismo repo.** Tiene su
propio front controller (`admin/index.php`), su propio `Router`, `RoleManager` y `Security`, sus
propios modelos, vistas y CSS. Comparte el autoloader de Composer y el **mismo esquema MySQL** —eso
es lo que cruza `tests/test_global_table_safety.php`— y nada más.

**La consecuencia práctica:** al rastrear un bug, no supongas que una capacidad, un middleware o un
helper de `src/` está en juego. Probablemente hay un gemelo en `admin/src/` con otras reglas.

## Antes de tocar

`Admin\Core\RoleManager::cleanCargo()` **no devuelve un código de rol**: normaliza texto de cargo
(minúsculas, sin acentos, sin género) para el emparejamiento aproximado de `suggestRoleByCargo()`.
El que traduce alias a código canónico es `App\Security\RbacService::normalizeRole()`. Confundirlas
rompe `$_SESSION['permiso']` y toda llamada a `hasCapability()` — está corregido en `AGENTS.md` y en
`CLAUDE.md` desde el 2026-08-10 precisamente porque la instrucción equivocada llegó a estar escrita.

## Dónde vive

[[panel-admin]] — crear proyectos, usuarios y familias de catálogo.

## Trampas

- [[admin-adminlte-adaptador]] — `admin/` tiene entrypoint CSS propio por aislamiento de PHP.

## Vecinos

[[rbac-y-rutas]] para los roles de verdad · [[arquitectura]] para la separación de las dos apps ·
[[design-system]] para por qué su CSS va aparte.

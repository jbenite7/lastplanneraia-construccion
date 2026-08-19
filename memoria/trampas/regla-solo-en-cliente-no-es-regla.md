---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-06
areas: [rbac, qa]
fuente: docs/superpowers/specs/2026-08-06-cierre-hallazgos-seguridad-biblia-design.md, docs/superpowers/plans/2026-08-06-cierre-hallazgos-seguridad-biblia.md, commits 88ba6e0d, ca642189, 32cccddf, 23d27bb7, 4b1a2be0, 4ba845dc, 80e25c35
resumen: "Una regla que solo vive en el cliente no es una regla: declarar una capacidad en RbacManager y en rbac_capabilities.js no implica que nadie la aplique — desde el 2026-08-10 el gate de paridad lo mide, y sus dos primeras divergencias resultaron capacidades sin ningún consumidor"
---
# Una regla que solo vive en el cliente no es una regla

Frase que resume el cierre del 2026-08-06 de toda una familia de hallazgos que la biblia de flujos
(`goals/biblia-t1..t5`) fue encontrando entre el 2026-08-04 y el 2026-08-06: controles que la
interfaz mostraba como aplicados pero que el servidor nunca comprobaba.

## Los cuatro casos cerrados el mismo día

| Hallazgo | Dónde vivía la regla falsa | Commit que la cerró |
|---|---|---|
| CSRF ausente en 6 módulos (Subcontratistas, Profesionales, Control de Cambios, CIC, CNC, CNP) | `rbac_guard_require_permission()` autoriza pero nunca valida token | `88ba6e0d` + `ca642189` |
| `sanear` sin CSRF | Endpoint de mutación sin validación de token | `32cccddf` |
| Candado de semanas pasadas del Programa General | Solo existía como restricción en JavaScript | `23d27bb7` |
| `/indicadores` ocultando el informe de Power BI | JavaScript escondía el `<iframe>`, pero la URL viajaba igual en el HTML servido | `4b1a2be0` (ver [[indicadores-oculta-en-cliente-bi-en-servidor]], ahora corregida) |

## La trampa reutilizable

**Una capacidad declarada en `App\Security\RbacManager` y espejada en
`public/js/rbac_capabilities.js` puede no tener NINGÚN consumidor en PHP.** Declarar no es
aplicar. El patrón que hace saltar la alarma:

```bash
grep -rn "<capacidad>" src/Controllers/ src/Legacy/    # ¿algún controlador la comprueba?
grep -rn "<capacidad>" public/js/                       # ¿el cliente la usa para pintar/ocultar?
```

Si la capacidad aparece en el segundo grep y no en el primero, es una regla de adorno: se salta
viendo el código fuente de la página o posteando directo al endpoint.

## Cómo no caer

Al añadir o auditar cualquier restricción de negocio (permiso, candado de fecha, visibilidad de un
dato):

1. Verifica el control **en el servidor primero**, con una petición directa (curl, script, o test)
   que no pase por el navegador.
2. Si el control solo existe en JS/CSS (deshabilitar botón, ocultar bloque, `display:none`), trátalo
   como decoración de UX, no como seguridad ni como regla de negocio cumplida.
3. Al cerrar el hallazgo, añade una prueba de servidor que golpee el endpoint sin el control de
   cliente — es la única forma de que la regresión no vuelva en silencio.

## Desde el 2026-08-10 hay un gate que mide esta familia

`npm run test:rbac-parity` (`scripts/rbac-parity.mjs`, `tests/rbac/parity.test.mjs`, commit
`4ba845dc`) compara la matriz de `RbacManager` con la del cliente y falla ante cualquier
divergencia. Las justificadas se declaran en `docs/rbac-parity-exceptions.json` con motivo y fecha.

Nació en rojo con dos divergencias del rol **R** (Residente) y **se cerraron el mismo día** en
`80e25c35`. Hoy el gate está en verde; si vuelve a rojo, es nuevo.

| Capacidad | Servidor | Cliente (antes) | Cliente (hoy) |
|---|---|---|---|
| `canManageContracts` | `true` | `false` | — |
| `canManagePdC` | `true` (era alias de la anterior) | `false` | `true` |

**Los dos nombres ya no existen por separado:** el 2026-08-10, después de este cierre, los cuatro
pares de alias exactos se colapsaron a un solo nombre cada uno (RBAC-A) y `canManageContracts`
quedó absorbida por `canManagePdC`. La lección de abajo no cambia: la capacidad sigue sin tener un
solo consumidor en PHP.

**La lección no es la divergencia, es lo que se encontró al medirla.** Ninguna de las dos
capacidades tenía **un solo consumidor**: ni en `src`, ni en `admin/src`, ni en `public/js`, ni en
`pdc-app/src`, ni en `views` — solo sus dos declaraciones. Y `/plan-compras` no comprueba ninguna
capacidad: exige `project_id` y pasa el rol al bootstrap de la SPA. O sea que **no había nada
explotable**, al revés que en [[reabrir-semana-asimetria-cliente-servidor]], donde el endpoint sí
concedía de más. La misma forma en la declaración, consecuencia distinta: aquí era una mina para el
primero que cableara el permiso, no una puerta abierta.

El cliente llevaba la duda escrita sin resolver desde su origen —
`['A','D','OT']; // Asumiendo que… Ajustar si R necesita` — y **esa era la pregunta real**, de
negocio, no de código: la respondió el usuario el 2026-08-10 (el Residente también compra en obra),
así que el servidor estaba bien y se alineó el cliente.

> **Si este gate vuelve a rojo:** no se apaga metiendo capacidades en
> `docs/rbac-parity-exceptions.json`. Eso convierte un hallazgo vivo en excepción documentada, que
> es el error que esta página existe para evitar. Averigua primero **quién consume** la capacidad:
> si nadie, la pregunta es de negocio y la responde el usuario, no el código.

Vecina de esta trampa por el lado de las pruebas: [[test-nuevo-rompe-en-silencio-suites-viejas]] —
al blindar el servidor, las suites E2E que ya posteaban directo pueden quedar rotas o, peor, con
aserciones muertas.

Mapas: [[rbac-y-rutas]] · [[qa-y-gates]].

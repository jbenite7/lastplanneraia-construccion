---
tipo: trampa
estado: vigente
fecha: 2026-08-06
areas: [rbac, qa]
fuente: docs/superpowers/specs/2026-08-06-cierre-hallazgos-seguridad-biblia-design.md, docs/superpowers/plans/2026-08-06-cierre-hallazgos-seguridad-biblia.md, commits 88ba6e0d, ca642189, 32cccddf, 23d27bb7, 4b1a2be0, 4ba845dc
resumen: "Una regla que solo vive en el cliente no es una regla: declarar una capacidad en RbacManager y en rbac_capabilities.js no implica que el servidor la aplique — desde el 2026-08-10 hay un gate que lo mide, y está publicado en rojo a propósito"
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

**Está publicado en `main` en ROJO, y es deliberado.** Decisión explícita del usuario al cerrar el
día. Reporta dos divergencias vivas, ambas del rol **R** (Residente):

| Capacidad | Servidor | Cliente |
|---|---|---|
| `canManageContracts` | `true` (`RbacManager.php:28`, rol `R` en la lista) | `false` |
| `canManagePdC` | `true` (alias de la anterior, `RbacManager.php:48`) | `false` |

No son vestigios del PDC v1 borrado: `/plan-compras` (PDC **v2**) está vivo en
`public/index.php:141`. Es el mismo patrón que [[reabrir-semana-asimetria-cliente-servidor]] —
el cliente esconde, el servidor permite— y su cierre pertenece al Frente 1A de seguridad y permisos.

> **Aviso para la próxima sesión que lo vea rojo:** no es una regresión tuya y **no se apaga
> metiendo las dos capacidades en `rbac-parity-exceptions.json`**. Eso convertiría un hallazgo de
> permisos vivo en una excepción documentada, que es exactamente el error que esta página existe
> para evitar. El gate en rojo está haciendo su trabajo.

Vecina de esta trampa por el lado de las pruebas: [[test-nuevo-rompe-en-silencio-suites-viejas]] —
al blindar el servidor, las suites E2E que ya posteaban directo pueden quedar rotas o, peor, con
aserciones muertas.

Mapas: [[rbac-y-rutas]] · [[qa-y-gates]].

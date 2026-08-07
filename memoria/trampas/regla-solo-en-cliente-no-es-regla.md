---
tipo: trampa
estado: vigente
fecha: 2026-08-06
areas: [rbac, qa]
fuente: docs/superpowers/specs/2026-08-06-cierre-hallazgos-seguridad-biblia-design.md, docs/superpowers/plans/2026-08-06-cierre-hallazgos-seguridad-biblia.md, commits 88ba6e0d, ca642189, 32cccddf, 23d27bb7, 4b1a2be0
resumen: "Una regla que solo vive en el cliente no es una regla: declarar una capacidad en RbacManager y en rbac_capabilities.js no implica que el servidor la aplique — hay que buscar el consumidor PHP"
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

Vecina de esta trampa por el lado de las pruebas: [[test-nuevo-rompe-en-silencio-suites-viejas]] —
al blindar el servidor, las suites E2E que ya posteaban directo pueden quedar rotas o, peor, con
aserciones muertas.

Mapas: [[rbac-y-rutas]] · [[qa-y-gates]].

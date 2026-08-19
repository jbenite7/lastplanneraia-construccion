---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-07-29
areas: [pdc]
fuente: goals/pdc-preparar-b1/evidence/b3-aislamiento-http.md
resumen: worktree-pdc-b3-torre-control · Usuario: jbenitez (rol A, miembro de Da Porto / 73)
---

# B3 — Aislamiento por obra, verificado por HTTP (punto 2 de la condición de hecho)

**Fecha:** 2026-07-30 · **Servidor:** contenedor propio del worktree en `:8113`, sirviendo
`worktree-pdc-b3-torre-control` · **Usuario:** `jbenitez` (rol `A`, miembro de Da Porto / 73)

El test `tests/test_pdc_v2_torre_control_rbac.php` prueba la regla a nivel de servicio. Esto la prueba
**por HTTP**, que es como la usa un gerente, y sobre el endpoint que más datos expone: el drill-down.

## Rol permitido — su propia obra

```
GET /api/bi/report/pdc/detail?project_id=73
```

```
hoy: 2026-07-29 | filas: 19
{'project_id': 73, 'paquete': 'Suministro ACERO DE REFUERZO', 'lote': None,
 'paso': 'Entrega de pliegos', 'fecha_fin': '2026-05-18', 'estado': 'vencido',
 'diasDesfase': 72, 'responsable': None}
```

## Rol denegado — una obra que no es suya

```
GET /api/bi/report/pdc/detail?project_id=999950
```

```json
{"error":"Acceso denegado. No tienes permiso para consultar esos proyectos."}
```

No devuelve una lista vacía —que se confundiría con «esa obra no tiene compras»— sino un error explícito.

## El proveedor no sale

Comprobado sobre el JSON completo de `/api/bi/report/pdc` y de `/api/bi/report/pdc/detail`: ninguna
respuesta contiene las cadenas `proveedor` ni `subcontratista`. Hay un test que lo fija, para que no se
reintroduzca por descuido al añadir una columna.

## Coincidencia módulo ↔ Torre, con la obra real

Con Da Porto (73) y la fecha de hoy del servidor:

```
modulo: {"vencido":8,"sem1":2,"sem2":0,"sem3":2,"sem6":0,"adelante":7,"sin_fecha":0}
torre : {"vencido":8,"sem1":2,"sem2":0,"sem3":2,"sem6":0,"adelante":7,"sin_fecha":0}
COINCIDEN (obra real Da Porto)
```

## Un hallazgo que no es de B3

`BiProjectScope::authorizedProjects()` memoiza en `$this->projects` sin incluir la sesión en la clave: una
instancia reutilizada le responde a un segundo usuario con los permisos del primero. **No es explotable
por HTTP** —cada petición construye su instancia y una petición es una sesión—, y las comprobaciones de
arriba lo confirman. Queda reportado como tarea aparte, con un test que fija el comportamiento actual y
que empezará a fallar el día que se arregle.

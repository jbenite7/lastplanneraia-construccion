---
tipo: trampa
estado: vigente
fecha: 2026-08-04
areas: [bi, rbac]
fuente: src/Controllers/Gestion/IndicadoresController.php, views/indicadores/indicadores.view.php, src/Controllers/Bi/BiViewController.php, src/Support/BiProjectScope.php, src/Security/RbacCatalog.php
resumen: "/indicadores esconde el informe con JavaScript y su controlador no comprueba el rol; /bi/* responde 403 de servidor — dos módulos hermanos con dos niveles de garantía"
---
# Indicadores oculta en el cliente; BI corta en el servidor

Los cuatro roles `G`, `S`, `SG` y `C` no deben ver el informe de indicadores
(ver [[powerbi-indicadores]]). **Dónde se cumple esa regla cambia según el módulo, y esa asimetría
es la trampa.**

| Módulo | Cómo se aplica la restricción |
|---|---|
| `/bi/*` | **Servidor, pero por rebote.** `BiViewController` no mira el rol: sólo llama a `requireAuth()` (`:52`). Quien corta es `BiProjectScope::authorizedProjects()`, que descarta todo proyecto cuyo rol no tenga `lps.indicadores.ver` (`src/Support/BiProjectScope.php:89`) — y `RbacCatalog` no se lo concede a `G`, `S`, `SG` ni `C` (`:302-338`). Al quedarse sin proyectos, `resolve()` lanza `DomainException` (`:46`) y el controlador responde `403` (`BiViewController.php:177-183`) |
| `/indicadores` | **Cliente.** `IndicadoresController` no tiene ningún control de rol; lo esconde JavaScript en la vista |

Y el orden dentro de la vista agrava el caso: `views/indicadores/indicadores.view.php:111` declara
`POWER_BI_REPORT_URL` **antes** de que `:151` decida si la oculta, así que la URL viaja en el HTML
que reciben también los cuatro roles restringidos. La respuesta es `200`.

## Cuánto vale esto, sin inflarlo

- **No es una filtración de datos privados.** Ese informe es *publish-to-web*: **público por enlace
  por diseño**, decisión registrada y aceptada en [[powerbi-indicadores]]. Quien tenga la URL lo ve,
  tenga cuenta o no.
- **Sí es una regla declarada que no se cumple donde debería.** La restricción existe como adorno
  del cliente y se salta viendo el código fuente de la página.
- **Cuidado al verificar el corte de BI** (precisado el 2026-08-06): buscar `403` o el nombre del
  rol en `BiViewController` no encuentra nada y hace parecer que `/bi/*` no restringe. Restringe,
  pero el control está a dos saltos, en el catálogo de permisos y en el filtro de proyectos. Es un
  corte real, aunque razonarlo exija leer tres archivos.
- **Y sobre todo es una inconsistencia entre hermanos.** Dos módulos que aplican la misma política
  con dos niveles de garantía distintos hacen imposible razonar sobre permisos leyendo un solo
  sitio: hay que ir a comprobar módulo por módulo.

## Cómo no caer

No supongas que un rol no ve algo porque la interfaz no se lo muestra. Comprueba dónde está el
control:

```bash
grep -n "role\|rol\|cargo\|Rbac\|403" src/Controllers/<Modulo>Controller.php
```

Si no aparece nada, la restricción es cosmética. Para cualquier dato que **no** sea público por
diseño, eso es un agujero, no un detalle.

Sin corregir a propósito: la decisión de si la restricción debe volverse real o la promesa debe
retirarse es del usuario, y está registrada en `docs/EXPERIMENTS.md`.

Mapas: [[rbac-y-rutas]] · vecina: [[powerbi-indicadores]].

---
tipo: trampa
estado: derogada
fecha: 2026-08-10
areas: [rbac, lps]
fuente: "public/js/modules/programacion_semanal/hot.js:3139, src/Controllers/Api/SemanalApiController.php:942,984, Frente 0 (Task 3, 2026-08-10)"
resumen: "Reabrir una semana esconde el botón en cliente salvo al rol A, pero el servidor solo exige lps.programacion_semanal.editar — cualquier rol con esa capacidad puede reabrir por el endpoint, y el log siempre escribe «reabierta por Admin»"
---
# Reabrir semana: el cliente y el servidor no están de acuerdo, y no está escrito en ningún sitio

> **DEROGADA el 2026-08-10, el mismo día que se escribió.** El agujero que describe está cerrado:
> `6dcec299` introdujo `src/Security/SemanalReabrirPolicy.php`, que autoriza en el **servidor** con
> la regla real del usuario —Admin y Director siempre, Residente solo hasta el fin del día de inicio
> de la semana, el resto nunca— y deniega si no puede resolver la fecha. El guard entra antes de
> mutar, y el log ya registra quién reabrió y con qué rol.
>
> **Se conserva porque la lección sigue viva** y no depende de este caso: el cliente puede esconder,
> pero **solo el servidor puede impedir**. Una restricción que solo existe en la interfaz es
> cosmética, y quien llame al endpoint directamente pasa por encima. Ver
> [[regla-solo-en-cliente-no-es-regla]].
>
> Lo que sigue describe el estado **anterior** al arreglo.

**El cliente esconde el botón de reabrir salvo al rol `A`** — `public/js/modules/programacion_semanal/hot.js:3139`
solo lo pinta para Admin. **El servidor no lo restringe así**:
`src/Controllers/Api/SemanalApiController.php:942` (`reabrir()`) solo exige la capacidad
`lps.programacion_semanal.editar`, que no es exclusiva de `A`. Cualquier rol con esa capacidad
puede reabrir la semana llamando al endpoint directamente, sin pasar por el botón que el cliente
oculta.

Y el log no ayuda a detectarlo: `SemanalApiController.php:984` escribe siempre «reabierta por
Admin», sea quien sea el rol real que hizo la llamada. Un audit trail que miente sobre quién actuó
es peor que ninguno.

**Ni la regla del cliente ni la del servidor son la que el producto quiere.** Medido preguntando al
usuario en la Task 3 del Frente 0 (2026-08-10): la regla deseada es «Admin y Director siempre,
Residente solo hasta el fin del día de inicio de la semana» — ninguna de las dos implementaciones
actuales la cumple. Quedó registrado como ICE 400 (subió de 140 al medirlo), reescrito y movido a
la tanda 1A del backlog de decisiones del Frente 0.

**Why:** cuando cliente y servidor imponen reglas distintas sobre la misma acción, la que manda de
verdad es la del servidor — el cliente solo decide qué se ve, no qué se permite. Una duda de
producto («¿quién debería poder reabrir?») puede destapar un agujero de permisos real si se mide en
vez de asumirse resuelta por la UI.

**How to apply:** no confíes en que un botón oculto en cliente sea una regla de permisos. Antes de
dar por buena una restricción de UI, verifica el endpoint que llama y compara sus capacidades
exigidas contra lo que el cliente muestra. Y si vas a auditar quién hizo una acción, comprueba que
el log registre al actor real, no un valor fijo.

Ninguna página de la wiki documentaba esta asimetría antes del pase de veracidad del 2026-08-10 que
la buscó explícitamente y no la encontró en `programacion-semanal.md`, `rbac-y-rutas.md` ni
`lps-dominio.md` — esta página lo cierra. Mapa del área: [[rbac-y-rutas]] y [[lps-dominio]].

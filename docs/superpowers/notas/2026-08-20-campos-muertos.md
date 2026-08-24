---
capa: fuente
tipo: reporte
estado: abierto
fecha: 2026-08-20
areas: [datos, lps]
fuente: medición directa sobre `programacion_semanal` en desarrollo (docker) y producción (SSH, solo lectura), más `grep` sobre `src/`, `public/js/`, `views/`, `admin/`, 2026-08-20
resumen: "Veredicto por campo para los cuatro campos de programacion_semanal al 0% de llenado: Categoria_CP, CP, alerta_crisis, reprogramaciones_semanales"
project: lps-aia
---

# Veredicto de los campos muertos — `programacion_semanal`

> Tarea 5 de F0 (higiene de datos). No borra ni cambia nada de código o esquema: deja el
> veredicto por escrito para que la decisión exista antes de que F1 construya encima.

## Medición

| Campo | Llenado en desarrollo | Llenado en producción |
|---|---|---|
| `Categoria_CP` | 0 de 5.713 | 0 de 5.896 |
| `CP` | 0 de 5.713 | 0 de 5.896 |
| `alerta_crisis` | 0 de 5.713 | 0 de 5.896 |
| `reprogramaciones_semanales` | 0 de 5.713 | 0 de 5.896 |

El 0% se confirma también en producción: no es un artefacto de datos de desarrollo sin sembrar.

## Veredicto por campo

| Campo | Quién lo lee | Quién lo escribe | Veredicto propuesto | Quién decide |
|---|---|---|---|---|
| `Categoria_CP` | `public/js/modules/programacion_semanal/hot.js:4945` (payload al guardar TNP), formulario en `views/programacion-semanal/programacion_semanal.view.php:465` ("Causa de Programación (CP)") | `src/Controllers/Api/SemanalApiController.php:1335,1362,1396` (endpoint de TNP guarda el valor si se envía) | **Dejar quieto.** Tiene circuito completo lectura↔escritura vía UI (campo obligatorio marcado con `*`), pero en la práctica nadie lo está usando: 0% de llenado real pese al circuito activo. Es un problema de adopción/proceso, no de código muerto — retirarlo rompería el formulario de TNP sin resolver la causa. | Felipe (producto/proceso): decidir si se exige o se relaja el requisito de captura en el formulario de TNP |
| `CP` | `public/js/modules/programacion_semanal/hot.js:4946`, mismo formulario (`views/.../programacion_semanal.view.php:475`, "CP (Detalle adicional)") | Mismo endpoint que `Categoria_CP` (`SemanalApiController.php:1362,1396`) | **Dejar quieto**, mismo razonamiento que `Categoria_CP`: circuito activo, adopción en cero. Es campo opcional ("Detalle adicional"), así que su 0% es más esperable que el de `Categoria_CP`. | Felipe (producto/proceso) |
| `alerta_crisis` | Uso extenso en frontend: `public/js/modules/lps_drawer.js`, `programa_general/hot.js`, `programacion_intermedia/hot.js`, `programacion_semanal/hot.js`, `views/dashboard/escalamientos.php` — pinta el ícono 🔥 y detecta "cuello de botella" | `src/Services/LpsService.php:105,114,345,354` (lo activa/desactiva LPS al declarar o resolver una crisis), `public/js/modules/lps_drawer.js:1103,1212` (UI de drawer lo escribe) | **Dejar quieto.** Es el campo con más consumo de código de los cuatro — UI y servicio de negocio (`LpsService`) lo leen y escriben activamente. El 0% en la BD refleja que **no ha ocurrido ninguna crisis marcada** en el rango medido, no que el campo esté desconectado. Retirarlo rompería el flujo de escalamiento de crisis. | Nadie: no requiere decisión, el circuito está sano y en uso |
| `reprogramaciones_semanales` | Ningún consumidor encontrado en `src/`, `public/js/`, `views/`, `admin/` | Ningún escritor encontrado — solo aparece en la definición de esquema (`admin/src/Models/Project.php:973`) | **Retirar.** Es el único de los cuatro sin ningún circuito de lectura ni escritura en runtime; solo existe como columna declarada. Candidato limpio para dropear en una tarea de esquema aparte (con el gate de Plannotator y respaldo que exige `docs/global-tables-architecture.md` — esta tarea no lo ejecuta). | Felipe, vía el gate de esquema normal (no se ejecuta aquí) |

## Notas

- `Categoria_CP` y `CP` comparten el mismo endpoint (`SemanalApiController.php`), el mismo
  formulario TNP y el mismo veredicto: tienen circuito, pero cero adopción. No son "código
  muerto" en el sentido técnico — son campos vivos sin uso real por parte de los usuarios.
- `alerta_crisis` es el caso opuesto al que se esperaba entrando a esta tarea: parecía candidato
  a dead field por su 0% de llenado, pero el grep muestra que es el campo con más superficie de
  código de los cuatro. El 0% simplemente dice "no ha habido crisis" en la ventana medida — es un
  dato de negocio, no una señal de abandono.
- `reprogramaciones_semanales` es el único candidato real a retiro de esquema. Esta tarea no lo
  ejecuta: documenta el veredicto para que F1 no construya asumiendo que existe un flujo detrás.

## Ver también

- [[2026-08-20-inventario-control-tower]] — inventario de partida de la Control Tower
- [[2026-08-20-decisiones-control-tower]] — decisiones del replanteo, mismo frente

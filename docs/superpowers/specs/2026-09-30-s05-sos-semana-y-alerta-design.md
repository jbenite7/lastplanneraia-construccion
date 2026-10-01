---
capa: fuente
tipo: spec
estado: abierto
id: S05-SOS
fecha: 2026-09-30
superficie: programa-general
rutas: ["/programa-general", "/api/lps/crisis/register"]
depende_de: [S05, T02]
version: 1.0
areas: [lps, qa, datos]
fuente: "prueba en navegador de Programa General del 2026-09-30 (memoria/arquitectura/programa-general-funciones-y-flujos.md, sección 7) y decisión de Felipe del mismo día de abrir el arreglo ya"
resumen: "Arreglo de los dos fallos críticos del SOS de Programa General: la crisis se registra en otra semana y un SOS registrado se muestra como fallo."
---

# S05 — Los dos fallos críticos del SOS

**Estado: propuesta, pendiente del visto de Felipe.** Felipe decidió el 2026-09-30 abrir el
arreglo ya; esta spec describe qué se arregla y cómo se comprueba, y no autoriza implementación
hasta su aprobación.

## Qué pasa hoy, en simple

1. **La crisis cae en otra semana.** Viendo la semana 2 de Da Porto, un SOS sobre una actividad
   quedó guardado en la semana 1. Nadie lo ve en la semana donde se declaró.
2. **Un SOS que sí se registró se muestra como fallido.** La pantalla dice «Tu sesión venció… la
   crisis no se registró», así que quien la declara cree que no pasó y puede repetirla o
   abandonarla.

Los dos afectan cómo se escala una crisis en obra. Lo demás de la prueba (17 fallos) no entra
aquí: sigue en la ficha y en su sprint de verificación.

## Causa, hasta donde está verificada

### Fallo 1: la semana

- La petición del SOS no lleva semana. `LpsTargetRequest` solo tiene `activityId`, `module`,
  `alertId` y `escalamientoId` (`src/Services/Lps/LpsTargetRequest.php`).
- El servidor la adivina con `LpsLegacyGeneralActivityAdapter::resolveWeek()`, que consulta
  `programa_consolidado` con `LIMIT 1` y **sin `ORDER BY`**
  (`src/Services/Lps/LpsLegacyGeneralActivityAdapter.php:35`). En Programa General una actividad
  tiene una fila por semana, así que la fila que devuelve es cualquiera.
- El mismo objetivo resuelto lo usan las demás operaciones del cajón (hilo, cierre), así que el
  fallo no es solo del registro.

### Fallo 2: el `alertId: 0`

- El servidor devuelve `alertId: 0` y el cliente lo rechaza porque su esquema exige un entero
  positivo (`frontend/src/shared/lps/api/crisis.ts:19`). Ese rechazo se muestra como sesión
  vencida.
- El 0 sale de `insertAlert()`, que devuelve `(int) $this->db->lastInsertId()` sin comprobarlo
  (`src/Services/Lps/LpsLegacyCrisisRepository.php:82`).
- **En la base local, `lps_escalamientos.id` no es autoincremental.** La clave primaria es
  `(project_id, id)` y la columna no tiene `AUTO_INCREMENT`. Lo medí el 2026-09-30 en
  `information_schema` de `lastplanneraia_dev` y `pruebas_snapshot`. La migración del repo sí lo
  declara (`database/migrations/001_create_global_tables.sql:460`): es un desfase entre el esquema
  del repo y la base.

## Qué se arregla

1. **La semana viaja desde la pantalla.** El cliente manda la semana que está viendo en toda
   operación sobre una actividad. El servidor comprueba que la actividad exista en esa semana y,
   si no, responde «objetivo no encontrado», igual que hoy para una actividad ajena.
2. **Sin semana, nada de adivinar al azar.** Si una llamada antigua no la manda, el servidor
   decide de forma determinista; la regla exacta la fija el plan después de inventariar quién
   llama (ver vacíos).
3. **Una alerta sin id válido es un error, no un éxito.** Si el alta no produce un id positivo,
   el servidor revierte la transacción y responde con error. Nunca más devuelve `alertId: 0`.
4. **El error de contrato no se disfraza de sesión vencida.** Si la respuesta del servidor no
   cumple su esquema, la pantalla lo dice como error del sistema, no como sesión vencida.
5. **El esquema de `lps_escalamientos` vuelve a tener `AUTO_INCREMENT`** donde falte. Es un cambio
   de esquema: va con dry-run, respaldo verificable, plan de restauración y el visto de
   producción de Felipe (`AGENTS.md`, «Arquitectura y datos»). Si producción también lo tiene
   perdido, su corrección es un paso aparte con autorización propia.

## Hechos y vacíos

| Afirmación | Estado | Fuente |
|---|---|---|
| El SOS de la semana 2 quedó en la semana 1 | Hecho | Prueba en navegador del 2026-09-30, ficha §7 |
| `resolveWeek` usa `LIMIT 1` sin `ORDER BY` | Hecho | `LpsLegacyGeneralActivityAdapter.php:35` |
| La petición no lleva semana | Hecho | `LpsTargetRequest.php` |
| `insertAlert` devuelve `lastInsertId()` sin comprobarlo | Hecho | `LpsLegacyCrisisRepository.php:82` |
| `id` sin `AUTO_INCREMENT` en la base local | Hecho, medido el 2026-09-30 | `information_schema.COLUMNS` |
| La migración del repo sí lo declara | Hecho | `001_create_global_tables.sql:460` |
| Cómo pasó el INSERT con `STRICT_TRANS_TABLES` sin valor para `id` | Vacío, pendiente 2026-09-30 | Lo reproduce el sprint con `systematic-debugging` |
| `insertProjectId` no reconoce el nombre entre comillas invertidas (`\w+`) | Por confirmar con una prueba | `src/Core/Database.php:221` |
| Si producción tiene el `AUTO_INCREMENT` | Vacío, pendiente 2026-09-30 | Lectura de solo consulta en producción, con permiso |
| Si `resolveWeek` de PI y PS tiene el mismo defecto | Vacío, pendiente 2026-09-30 | `LpsLegacyIntermediateActivityAdapter`, `LpsLegacyWeeklyActivityAdapter` |
| Quién llama hoy a la API con `modulo` y sin semana | Vacío, pendiente 2026-09-30 | Inventario del plan |
| De dónde sale el texto «Tu sesión venció» ante un error de esquema | Vacío, pendiente 2026-09-30 | `AccionesSos.tsx` y `pedir()` |

## Condición de hecho propuesta

- Pruebas PHP nuevas que fallan antes del arreglo y pasan después: semana enviada, actividad
  inexistente en esa semana y alta sin id válido.
- Pruebas del cliente: la semana viaja en la petición y un error de esquema no se muestra como
  sesión vencida.
- `docker compose exec app php scripts/run-php-tests.php --nivel=puro` y el `npm` del frontend
  con código 0.
- Prueba en navegador en la copia local de Da Porto: un SOS declarado viendo la semana 2 queda
  en la semana 2 y la pantalla confirma el registro. Escribe en la base local, así que necesita
  respaldo, `/visto-prod` y restauración al final, como en la prueba del 2026-09-30.

## Decisiones abiertas

- **Ejecutor.** Por el reparto vigente, Codex ejecuta y revisa; Claude cierra.
- **Corrección de producción.** Solo si el paso 02 confirma que allí también falta el
  `AUTO_INCREMENT`; es decisión de Felipe y va por la rutina de despliegue.

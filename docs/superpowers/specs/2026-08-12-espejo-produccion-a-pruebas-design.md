# Espejo de producción → local → pruebas (2026-08-12)

## Objetivo
Reconstruir la base de `prueba-lps` a partir de producción, porque pruebas tiene 98 filas de
`programa_consolidado` con `unique_id` NULL (proyecto Da Porto 73) que bloquean toda edición en
Programación Intermedia («Id de actividad inválido»). Producción no se modifica nunca: solo dump.

## Decisiones (grilleo 2026-08-12)
- Lo de pruebas es **casi todo** desechable; se conserva: cuentas `test.*` + dev door, el proyecto
  Da Porto de pruebas (73) y el sandbox PDC E2E.
- La base local `lastplanneraia_dev` **se pisa** con el dump de producción (con respaldo previo).
- Tras restaurar en local se **nivelan las migraciones** faltantes (clasificadas esquema→datos)
  para que a pruebas llegue el esquema que espera el código de `main`.
- Estrategia de conservación: **A — exportar y reinsertar con remapeo** de `project_id`/`user_id`
  si chocan con los de producción.

## Fases
1. **Dump de producción** (solo lectura): `mysqldump --single-transaction --routines --triggers`,
   verificar `Dump completed`, bajar, neutralizar `DEFINER` (perl documentado en
   `docs/siteground-deploy-routine.md` §3.1).
2. **Local**: respaldo verificable de `lastplanneraia_dev` → restaurar dump → migraciones
   faltantes → `scripts/diagnostico-unique-id-nulos.php` → tests canónicos + smoke en navegador.
3. **Pruebas**: exportar lo a conservar → respaldo completo de la base de pruebas en el servidor →
   restaurar la base local nivelada → reinsertar lo conservado (remapeando ids) → comparar
   `COUNT(*)` exactos → smoke: editar una celda de PI en Da Porto sin error.

## Condición de hecho
En `prueba-lps`, con datos espejo de producción más lo conservado, una edición en Programación
Intermedia guarda sin «Id de actividad inválido», y los conteos de las tablas clave cuadran contra
su origen (producción para lo espejado; export para lo conservado).

## Reglas de seguridad
- Producción: solo `mysqldump`/SELECT. Jamás DDL/DML.
- Cada base que se pisa tiene respaldo restaurable ANTES, y el dump se prueba (conteos exactos).
- `$DB_NAME` se imprime antes de todo comando destructivo (pruebas y producción comparten cuenta SSH).
- `20260712_remap_consolidado_unique_id.php` NO se ejecuta (obsoleta y destructiva).

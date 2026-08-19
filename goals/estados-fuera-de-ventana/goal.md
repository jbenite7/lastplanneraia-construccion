---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: goals/estados-fuera-de-ventana/goal.md
resumen: Que Fuera de Ventana deje de ser una etiqueta que se borra sola: los dos calculadores de estado la producen con la regla de 7+ semanas, el contrato cierra su…
---

<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: estados-fuera-de-ventana

## Fase del plan
Plan: docs/superpowers/plans/2026-08-19-estados-fuera-de-ventana.md
Fase: -
Sha verificado: ?
Presupuesto: ?

Es el frente **(A)** de la partición que aprobó el usuario: contrato y calculador, reversibles. La
migración de datos sobre los 16 proyectos es el frente **(B)** y no entra aquí.

## Objetivo
Que `Fuera de Ventana` deje de ser una etiqueta que se borra sola: los dos calculadores de estado
la producen con la regla de 7+ semanas, el contrato cierra su pregunta abierta, y la divergencia
entre ambos calculadores queda unificada. Sin tocar un solo dato guardado.

## Condición de hecho
1. `pg_calculate_status()` y `LpsService::calculateGeneralStatus()` devuelven `Fuera de Ventana`
   para toda actividad con offset de semanas **≥ 7**, y siguen devolviendo los otros siete estados
   igual que hoy.
2. Existe una prueba de caracterización que fija los **ocho** estados de **ambos** calculadores y
   que **falla si divergen entre sí** — hoy no existe ninguna prueba que los cubra.
3. El umbral de la rama de fechas nulas es el mismo en los dos, documentado cuál es el canónico.
4. `docs/design-system/ds-f1a-escala-estado.{json,md}` sin la sección «pendiente»: la decisión es
   valor persistido.
5. Un informe de diagnóstico de las 113 filas contradictorias, **sin corregirlas**.
6. Cero filas modificadas en la base.

Verificación: docker compose exec -T app php scripts/run-php-tests.php --nivel=puro

## Posture
- **No tocar `Con Alerta Restricciones`**: ni de la leyenda del PG, ni de `state-semantics.json`,
  ni de `hot.js`. La orden anterior de retirarla quedó **derogada** por decisión del usuario del
  2026-08-19, después de que este frente demostrara que sí está implementada, derivada en cliente
  desde `Estado_Restricciones` y las cinco duras.
- **No migrar ni corregir datos.** Ni un `UPDATE`. Eso es el frente (B).
- **No corregir las 113 contradictorias**: se diagnostican y se entregan.
- **No tocar `state-semantics.json`** en este frente.
- **Sin dependencias nuevas.**
- **Prefijo del frente en todo `.md` nuevo de la wiki.**

## Leer primero
- `docs/design-system/ds-f1a-escala-estado.md` — el contrato que este frente cierra.
- `src/Legacy/estado_programa_general.php` — el calculador con constantes.
- `src/Core/Lps/LpsService.php:124` — el segundo calculador, con literales.
- `decisiones/estados-consolidado-coordinadora.md` — las decisiones del usuario y la derogada.

## Archivos declarados
docs/superpowers/specs/*-estados-fuera-de-ventana-*,docs/superpowers/plans/*-estados-fuera-de-ventana-*,goals/estados-fuera-de-ventana/**,docs/design-system/ds-f1a-escala-estado.*,src/Legacy/estado_programa_general.php,src/Core/Lps/LpsService.php,tests/unit/EstadoProgramaGeneralTest.php,tests/test_estado_calculadores_paridad.php,memoria/trampas/contenedor-compartido-*,memoria/log.md

## Contención
**Este frente toca código de producto**, a diferencia de los dos anteriores. Los dos archivos de
calculador no los declara ningún otro frente vivo, pero los lee media aplicación: `LpsService`
tiene cuatro llamadores conocidos (`GeneralApiController` ×3, `SemanalApiController`) y
`pg_calculate_status` lo usa `guardar_programacion_intermedia.php` en cada guardado.

**Y no tienen ninguna prueba hoy.** Esa es la contención real: cambiar la clasificación de 65 549
filas sin red. Por eso la primera tarea del plan es escribir la caracterización del comportamiento
actual **antes** de tocar nada.

## Evidencia de la Task 3: el rojo de `--nivel=http` es preexistente

El plan mandaba parar si esa suite salía roja, con la hipótesis de que sería un consumidor que
asumía siete estados. **Se midió en vez de deducirse**, con `git stash` de los tres archivos de la
Task 3 sobre el mismo árbol:

```
sin los cambios de la Task 3 : 6 fallos
con los cambios              : 5 fallos   ← los mismos 5, ninguno nuevo
```

Los cinco constantes son ajenos al frente: `test_dev_door_http` (el candado `DEV_DOOR` está cerrado
en este entorno y su propio mensaje lo dice), `test_semanal_sanear_csrf`,
`test_bi_source_reconciliation`, `test_equipment_families_require_review` y
`test_report_processor_cic_project_scope`.

El sexto, **`test_bi_programa_general_chart_values`, oscila**: apareció solo en la corrida sin los
cambios. Ejecutado suelto **falla idéntico con y sin ellos** —mismos números,
`actual=[70.7,-4.1] expected=[66.2,-4.9]`—, así que no lo arregla ni lo rompe este frente. Se anota
porque **es el mismo test que está rojo en el CI de `main`** por el defecto de línea base que
trabaja el frente `runtime-budgets`/`elated-golick` (cohortes disjuntas vacían el baseline
contractual): que en local oscile con datos vivos encaja con ese diagnóstico y le sirve de
evidencia.

La coordinadora ratificó seguir: la razón por la que ese paso existía —descartar consumidores rotos
por el octavo estado— queda respondida por la medición, y parar por la letra habría sido proceso
por el proceso.

## El return inalcanzable, que es el argumento de por qué la caracterización va primero

`LpsService::calculateGeneralStatus` tiene **dos** `return 'Actividad Futura'`. La regla nueva se
puso entre los dos y quedó **inalcanzable**: el primero cubre el caso sin avance con inicio
posterior a la semana, que es justamente por donde sale una actividad lejana. El calculador legacy
tiene un solo return y ahí funcionó a la primera.

**La prueba de paridad lo cazó en rojo antes de que se notara en pantalla.** Si el cambio se hubiera
hecho sin la caracterización de la Task 1 —que es lo que había: cero pruebas sobre estos dos
archivos— el resultado habría sido un `Fuera de Ventana` que funciona en un calculador y no en el
otro, y los dos escriben la misma columna.

## Publicaciones
- **`aeaa7a77` — publicado el 2026-08-19** con `bash scripts/publicar.sh`. Confirmado en el paso 7:
  `git rev-parse origin/main` devuelve ese sha y no queda `ahead` ni `behind`. El gate comprobó
  cuatro cosas antes de empujar —`design-system:static`, contrato piloto PG, wiki (forma) y wiki
  (veracidad + pruebas)—, una más que por la mañana: la partición de la wiki llegó al gate mientras
  corría este frente.
- La coordinadora dejó el contenedor montando este worktree a propósito para que el invariante
  pasara sin abrir otra ventana. **Devuelto a la raíz inmediatamente después del push**, antes de
  confirmar nada, porque había una verificación visual esperando detrás.

## Cierre
Condición de hecho cumplida sobre `aeaa7a77`, con el paso 0 comprobado en cada medición:

```
$ docker compose exec -T app php scripts/run-php-tests.php --nivel=puro
RC=0   OK (45 tests, 68 assertions)

$ node --test tests/design-system/ds-f1a-escala-estado.test.mjs
RC=0   9 pass, 0 fail

$ npm run test:wiki
RC=0   sin hallazgos, 159 páginas (modo estricto)

$ git diff origin/main...HEAD | grep -icE '^\+.*(UPDATE |DELETE |INSERT )'
0
```

**Lo que este frente cambió de verdad:** `Fuera de Ventana` dejó de ser una etiqueta que se borraba
sola. Los dos calculadores la producen a partir de la séptima semana, y por primera vez existen
pruebas sobre ellos — no tenían ninguna, y de ellos depende el `Estado` de 65 549 filas.

**Lo que dejó sin tocar, a propósito:** ni un dato guardado, `Con Alerta Restricciones` en ningún
archivo, y las 113 contradictorias, que se diagnosticaron sin corregirse.

**El riesgo que hereda el frente (B), y es el único que puede destruir información:** 24 de esas 113
filas están al 100% de avance con fecha de inicio futura. Un recálculo masivo las mandaría a
`Fuera de Ventana` y **se perdería el dato de que estaban terminadas**. La consulta que las captura
está en `diagnostico-113-contradictorias.md` y hay que correrla **antes** de migrar: después ya no
hay forma de saber cuáles eran.

**Pendiente de publicar:** este commit de cierre es posterior al sha publicado —un archivo no puede
contener su propio sha— y viaja con el próximo push, junto a `5759b13d`, que quedó igual del frente
anterior.

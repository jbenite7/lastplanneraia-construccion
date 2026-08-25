---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-08-10
areas: [qa]
fuente: docs/superpowers/specs/2026-08-10-runner-tests-php-design.md
resumen: Runner para los tests PHP y su conexión al CI
---

# Runner para los tests PHP y su conexión al CI

- **Fecha:** 2026-08-10
- **Estado:** diseño aprobado
- **Fase:** 1 de 2 (la fase 2, introducir PHPUnit para tests nuevos, no empieza hasta cerrar esta)

## El problema

`tests/test_*.php` son 99 scripts PHP autoejecutables sin runner. El CI ejecuta **3**, listados a
mano en `.github/workflows/design-system.yml` (`test_dev_door_guard`, `test_dev_door_http`,
`test_global_table_safety`). Los otros 96 no los corre nadie automáticamente: un test puede llevar
meses roto sin que nada avise, y un test nuevo nace fuera del CI salvo que alguien recuerde
añadirlo a esa lista.

## Lo que se midió (2026-08-10, worktree `cranky-dhawan-aa8725` @ `13d33af3`)

Medido por código de salida, nunca por grep del texto — la trampa está documentada en
[[suite-php-rojos-preexistentes]]. Dos pasadas sobre los 99:

| Pasada | Entorno | Pasan | Fallan |
|---|---|---|---|
| A | contenedor efímero, sin BD ni HTTP | 93 | 6 |
| B | stack `docker-compose.ci.yml` con BD fixture + app viva | 71 | 28 |

Cruzando ambas:

| Grupo | Nº | Comportamiento |
|---|---|---|
| A | 67 | pasa con y sin entorno |
| B | 4 | falla sin entorno, pasa con entorno |
| C | 26 | **pasa sin BD y falla con BD** |
| D | 2 | falla siempre |

### Hallazgo 1 — el falso verde por ausencia de entorno

Los 26 del grupo C salen **exit 0 cuando no hay base de datos**. Capturan el fallo de conexión y
terminan bien. Un runner que se limitara a lanzar los tests donde caiga daría verde en 26 casos que
no han comprobado nada. Este agujero es mayor que el que motivó el encargo y condiciona el diseño:
**el runner verifica que el entorno del nivel está disponible antes de correr nada, y aborta si no
lo está.** Ausencia de entorno es un error del runner, nunca un verde.

### Hallazgo 2 — los tests «sin `exit()`» son 1, no 5

Cinco archivos no llaman a `exit()`, pero cuatro (`test_bi_forecast_contract`,
`test_design_system_components`, `test_design_system_lab_access`, `test_lps_week_edit_policy`)
señalan el fallo con `throw`, y una excepción no capturada devuelve 255. Un runner que mire el
código de salida **sí** los detecta.

El único realmente ciego es `test_pi_shared_payload_smoke.php`: no tiene ninguna aserción, imprime
`ok: true/false` por pantalla y termina en 0 pase lo que pase. Sus expectativas ya están escritas
en los rótulos de cada caso (`(debe FALLAR)`, `(debe OK)`).

### Hallazgo 3 — los 28 rojos no son código roto

Ninguno de los 28 que fallan con BD es un defecto del código de producción:

| Causa | Nº | Ejemplo |
|---|---|---|
| Datos que el fixture de CI no tiene | 20 | 14 son `test_pdc_v2_*` |
| Tablas que el fixture no crea | 4 | `test_password_reset_resultados` |
| Evidencia no versionada en `docs/qa/evidence/` | 4 | `test_goal_close_blockers_manifest` |

Piden un entorno con datos que el CI no tiene. Enriquecer el fixture hasta cubrirlos es un frente
propio, mayor que este, y chocaría con [[no-enriquecer-daporto-para-medir]]. Se declaran, no se
silencian.

## Diseño

### 1. Cada test declara el entorno que necesita

Una línea en la cabecera de cada `tests/test_*.php`:

```php
// @requiere: puro
```

Cuatro niveles, de menos a más exigente. Cada nivel incluye lo que puede honrar el anterior:

| Nivel | Necesita | Corre en CI |
|---|---|---|
| `puro` | PHP + autoload | sí, job estático |
| `db` | base de datos con el esquema del fixture | sí, job runtime |
| `http` | además la aplicación viva | sí, job runtime |
| `datos-proyecto` | datos o evidencia que el CI no tiene | no |

La etiqueta vive junto al test para que viaje con él al moverlo o copiarlo. Se asigna por **lo que
el test necesita para ser válido**, no por lo que sobrevive: los 26 del grupo C sobreviven sin BD y
aun así son `db` o `datos-proyecto`.

### 2. El runner: `scripts/run-php-tests.php`

PHP puro, ejecutado dentro del contenedor `app`. Mismo lenguaje que los tests, sin capa Node
intermedia, idéntico en local y en CI.

```bash
docker compose exec -T app php scripts/run-php-tests.php --nivel=db
```

Contrato:

1. `--nivel=X` corre todos los tests de nivel X y de los niveles por debajo. Sin `--nivel`, `puro`.
2. **Comprueba el entorno antes de correr.** Para `db` abre una conexión; para `http` pide una URL
   conocida. Si el entorno no está, sale con error explicando qué falta. Nunca da verde por ausencia.
3. Sale 0 sólo si todos los tests seleccionados pasan. Cualquier código distinto de 0 en un test es
   fallo, incluido 255 por excepción.
4. **Falla si un test no lleva etiqueta.** Un archivo nuevo sin `@requiere` rompe el runner, que es
   lo que impide repetir el problema de origen: nacer fuera del CI.
5. **Marca como sospechoso** un test que sale 0 sin haber impreso nada que respalde el verde. Red de
   seguridad frente a futuros `test_pi_shared_payload_smoke`.
6. Imprime al cerrar cuántos corrieron, cuántos pasaron y cuántos se omitieron por nivel, para que
   la salida del CI demuestre la cobertura en vez de afirmarla.

### 3. `test_pi_shared_payload_smoke.php` pasa a ser un test

Se le añaden aserciones reales usando las expectativas ya escritas en sus rótulos. Es el único
cambio a un test existente que contempla este diseño, y lo exige la condición de hecho.

### 4. Conexión al CI

En `.github/workflows/design-system.yml`:

- **`design-system-static`** (sin BD): `--nivel=puro`. Feedback rápido, sin levantar nada.
- **`design-system-runtime`** (stack ya levantado): `--nivel=http`. **Sustituye** a los tres pasos
  que hoy listan tests a mano; esos tres quedan cubiertos por el runner.

`datos-proyecto` no corre en CI. Queda declarado y se corre en local:

```bash
docker compose exec -T app php scripts/run-php-tests.php --nivel=datos-proyecto
```

### 5. Atajo de invocación

`composer.json` tiene `"scripts": {}` vacío. Se le añade `test`, para que el comando canónico no
haya que recordarlo de memoria.

## Condición de hecho

1. Un comando único ejecuta los tests PHP y devuelve el código de salida correcto: 0 sólo si todos
   los seleccionados pasan.
2. Ningún test puede pasar en silencio: el único ciego queda con aserciones, y el runner detecta
   tanto el verde sin respaldo como el test sin etiqueta.
3. El runner distingue los cuatro niveles y corre un subconjunto.
4. El CI ejecuta el runner en vez de los tres tests listados a mano, con salida real que muestre
   cuántos corren y cuántos pasan.
5. Todo verificado con salida real de comandos, no por inspección.

## Fuera de alcance

- **PHPUnit.** Es la fase 2 y no empieza hasta cerrar esta con su gate.
- **Enriquecer el fixture de CI** para que corran los 28 de `datos-proyecto`.
- **Arreglar los 28.** No son código roto; piden entorno. Quedan declarados y visibles.
- Cualquier cambio a los tests más allá de su línea `@requiere` y de las aserciones del único ciego.

## Riesgos

- **La etiqueta se asigna mal.** Un test marcado `puro` que en realidad usa BD pasaría en el job
  estático sin comprobar nada. Mitigación: la asignación se verifica corriendo cada nivel en un
  entorno que sólo ofrece ese nivel; un `puro` que necesite BD falla ahí y se ve.
- **El CI se alarga.** Pasa de 3 tests a ~71. Mitigación: los `puro` van al job estático, que no
  levanta stack; se mide el tiempo real y se reporta.
- **Los 28 declarados se vuelven invisibles.** Mitigación: el runner los cuenta en su resumen en
  cada corrida de CI, así que su número aparece en cada ejecución en vez de esconderse.

## Archivos de este goal

- Este spec: `docs/superpowers/specs/2026-08-10-runner-tests-php-design.md`
- Mapa del área: `memoria/mapas/qa-y-gates.md`
- Trampas al medir: `memoria/trampas/suite-php-rojos-preexistentes.md`

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** scripts/run-php-tests.php existe y es lo que llama el CI, como describe CLAUDE.md

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].

# Frente 0 — Higiene y decisiones: plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Desbloquear los cinco frentes siguientes del programa resolviendo lo barato que los tiene parados: el arreglo B-10 que no llegó a `main`, las ramas muertas, las 17 decisiones que el usuario debe tomar, los dos goals sin cierre y los dos residuos de la campaña de dark mode.

**Architecture:** Ocho tareas independientes salvo dos dependencias declaradas. Ninguna toca código de producto salvo la Task 1, que es un cherry-pick ya verificado más la red de pruebas que le faltaba. Las Tasks 3, 4 y 5 producen **decisiones escritas**, no código: su entregable es una disposición firme en el documento que corresponda.

**Tech Stack:** PHP 8.3 dentro de `docker compose exec app`, tests autoejecutables de `tests/test_*.php`, Node para `npm run test:wiki` y la suite estática del design system, `git` para la higiene de ramas.

**Spec:** [`2026-08-10-programa-cierre-pendientes-design.md`](../specs/2026-08-10-programa-cierre-pendientes-design.md), Frente 0 y decisiones D3, D6.

## Global Constraints

- **Docker Compose es el runtime.** Todo PHP corre con `docker compose exec app`. Nunca un PHP del host. (`AGENTS.md` §Runtime local)
- **La sesión local se abre por la puerta de servicio**, nunca por `/login`, y no se teclean credenciales: `http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E`. Cuentas habilitadas: `test.A`, `test.R`, `test.V`. (`AGENTS.md` §Seguridad)
- **Prepared statements siempre**, a través de la capa `Database`. Nada de SQL construido con datos de usuario.
- **Los datos de prueba se restauran.** Toda tarea que escriba en la base deja la base como la encontró y lo verifica, no lo supone.
- **Todo gate se entrega con una mutación que lo pone rojo, ejecutada.** No basta con que pase. (Regla heredada de F2a)
- **Todo paso que quite algo de una lista mide qué cobertura pierde**, no solo qué gana. (Regla heredada de F2a)
- **Borrar una rama exige confirmación explícita del usuario** en el momento, aunque este plan la autorice. Las ramas remotas no se tocan.
- **Commits:** autorizados, uno por tarea, atómicos y con staging selectivo. Nunca `.env` ni evidencia local.
- **Nada se declara hecho sin salida real de comando** de esta sesión.

## Hallazgos previos que cambian el plan

Tres cosas que la auditoría dio por ciertas y **la lectura del código desmintió**. Están aquí porque cada una convierte una tarea de trámite en una tarea con trabajo real:

1. **El worktree ya está limpio.** El spec mandaba resolver 33 líneas sin commitear en `tests/browser/programacion-semanal-roles-phases.mjs`. `git status --short` y `git diff HEAD` sobre ese archivo salen vacíos: entre el arranque de la auditoría y ahora, alguien lo resolvió. **La tarea desaparece**, y la Task 0 lo verifica en vez de darlo por hecho.
2. **La condición de hecho del goal de BI es imposible de cumplir tal como está escrita.** Pide «aprobación visual de la matriz de 6 modos (Mobile/Tablet/Desktop × Dark/**Linen**)», y el tema `linen` se retiró del producto el 2026-07-25 por DS-030. No se puede aprobar evidencia de un tema que no existe. Hay que redefinir la condición antes de pedir la aprobación: es la Task 5.
3. **Cerrar el goal del design system no es papeleo.** `docs/design-system/closeout-evidence.json` declara sus 15 gates en `passed`, pero su `generatedAt` es **2026-07-15** mientras el archivo dice `designSystemVersion: 1.1.0`, versión que se publicó el 2026-08-07 (`a5223a0c`). O el campo de versión se subió sin regenerar la evidencia, o el sello de tiempo miente. Es exactamente la trampa de las identidades auto-declaradas que costó siete cierres en F2a. Hay que **medirlo**: Task 6.

## File Structure

| Archivo | Responsabilidad |
|---|---|
| `src/Services/Auth/PasswordResetService.php` | Llega por cherry-pick. Devuelve `enviado` / `ignorado` / `fallido` en vez de `bool`. |
| `src/Controllers/Auth/PasswordResetController.php` | Llega por cherry-pick. Traduce el resultado a mensaje de pantalla. |
| `admin/src/Controllers/PasswordResetController.php` | Ídem, para el mini-app de Admin. |
| `tests/test_password_reset_resultados.php` | **Nuevo.** La red que le faltaba a B-10: caracteriza los tres resultados con emisor inyectado, y comprueba que no queden tokens huérfanos. |
| `docs/EXPERIMENTS.md` | Recibe la disposición firme de los 17 hallazgos `decide: usuario`. |
| `goals/bi-control-tower-gemini/goal.md` | Condición de hecho redefinida sin `linen`, y su cierre o su bloqueo explicado. |
| `goals/design-system-nucleo-gobernanza/goal.md` | Sección de cierre, o el motivo medido por el que no puede cerrarse. |
| `docs/superpowers/ramas-viejas-2026-08-03.md` | Cierre del censo: qué se borró y qué queda. |
| `memoria/goals/estado.md`, `memoria/log.md` | Operación `ingest` de la wiki. |

---

### Task 0: Verificar el punto de partida antes de tocar nada

El spec se escribió sobre una foto de hace unas horas. Esta tarea comprueba que sigue siendo cierta y **para el frente si no lo es**, en vez de arrastrar una premisa falsa por ocho tareas.

**Files:**
- Read: ninguno. Solo comandos de lectura sobre el repositorio.

**Interfaces:**
- Consumes: nada.
- Produces: la confirmación de que las cifras del spec siguen vigentes. Si alguna no lo está, la corrección va al spec **antes** de continuar.

- [ ] **Step 1: Comprobar que el worktree está limpio**

```bash
git status --short
```

Esperado: sin salida. Si aparece cualquier archivo modificado, **PARAR** y reportar al usuario qué es antes de seguir: no es tuyo y no se descarta.

- [ ] **Step 2: Re-censar el backlog**

```bash
grep -c "| abierto" docs/EXPERIMENTS.md
grep -c "abierto · decide: usuario" docs/EXPERIMENTS.md
```

Esperado: `39` y `17`. Si difieren, actualizar las cifras del spec del programa en el mismo commit que el resto de esta tarea y decirlo en el resumen.

- [ ] **Step 3: Confirmar que B-10 sigue sin estar en `main`**

```bash
git log main --oneline --grep="B-10" | head
git log --oneline main..claude/cranky-dhawan-aa8725 -- src/Services/Auth/PasswordResetService.php
```

Esperado: el primero sin salida; el segundo muestra `1af1471f`.

- [ ] **Step 4: Confirmar el runtime**

```bash
docker compose ps
docker compose exec app php -v
```

Esperado: `app`, `db` y `adminer` arriba; PHP 8.3.x. Si `app` no está arriba: `docker compose up -d db app adminer`.

- [ ] **Step 5: Sin commit.** Esta tarea no cambia el árbol salvo que el Step 2 obligue a corregir el spec; en ese caso:

```bash
git add docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design.md
git commit -m "docs(spec): el censo del backlog se re-mide al arrancar el Frente 0"
```

---

### Task 1: B-10 llega a `main` con la red de pruebas que le faltaba

El arreglo está verificado a mano por quien lo escribió, pero **no tiene ni una prueba automática**: `ls tests/ | grep -i password` no devuelve nada. Un arreglo de seguridad sin prueba se deshace solo en el siguiente refactor. Se trae y se le pone la red en el mismo movimiento.

**Files:**
- Cherry-pick: `1af1471f` — toca `src/Services/Auth/PasswordResetService.php`, `src/Controllers/Auth/PasswordResetController.php`, `admin/src/Controllers/PasswordResetController.php`, `docs/reportes/barrido-completo-2026-08-07.md`
- Create: `tests/test_password_reset_resultados.php`

**Interfaces:**
- Consumes: Task 0.
- Produces: `PasswordResetService::request(string $email, string $scope): string`, que devuelve una de tres constantes: `RESULTADO_ENVIADO` (`'enviado'`), `RESULTADO_IGNORADO` (`'ignorado'`), `RESULTADO_FALLIDO` (`'fallido'`). El constructor acepta inyección: `__construct($db = null, ?SmtpMailer $mailer = null, ?UserPasswordService $passwords = null)`. `SmtpMailer::send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): void` devuelve `void` y **lanza** cuando falla; por eso el doble de prueba se construye extendiendo `SmtpMailer` y sobrescribiendo `send()`.

- [ ] **Step 1: Traer el arreglo**

```bash
git cherry-pick 1af1471f
```

Esperado: aplica limpio. Si hay conflicto, **PARAR** y reportar: `main` avanzó sobre esos archivos y hay que resolverlo a la vista, no a ciegas.

- [ ] **Step 2: Comprobar sintaxis y análisis estático**

```bash
docker compose exec app php -l src/Services/Auth/PasswordResetService.php
docker compose exec app php -l src/Controllers/Auth/PasswordResetController.php
docker compose exec app php -l admin/src/Controllers/PasswordResetController.php
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
```

Esperado: `No syntax errors` en los tres y PHPStan sin errores nuevos.

- [ ] **Step 3: Escribir la prueba que falla**

Crear `tests/test_password_reset_resultados.php`. Sigue el patrón autoejecutable del repo (`tests/test_dev_door_guard.php`): sin runner, contador de fallos, `exit(1)` al final si hay alguno.

```php
<?php

declare(strict_types=1);

/**
 * Caracteriza los tres resultados de PasswordResetService::request() (hallazgo B-10).
 *
 * Antes devolvía `bool` y el controlador lo descartaba, así que una caída total del correo
 * se veía igual que un envío correcto. Esta red existe para que esa distinción no se pierda
 * en un refactor: si `request()` vuelve a colapsar «falló el envío» con «no hay a quién
 * enviar», el caso 2 se pone rojo.
 *
 * Ver docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design.md
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Auth\PasswordResetService;
use App\Services\Mail\SmtpMailer;

/** Emisor que siempre entrega. */
final class MailerQueEntrega extends SmtpMailer
{
    public int $enviados = 0;

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): void
    {
        $this->enviados++;
    }
}

/** Emisor que simula una caída total del relay. */
final class MailerRoto extends SmtpMailer
{
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): void
    {
        throw new \RuntimeException('relay caído (simulado)');
    }
}

$fallos = 0;
$total  = 0;

function comprobar(string $caso, string $esperado, string $obtenido): void
{
    global $fallos, $total;
    $total++;
    if ($esperado === $obtenido) {
        echo "  OK   {$caso}: {$obtenido}\n";
        return;
    }
    $fallos++;
    echo "  FALLA {$caso}: esperaba «{$esperado}», obtuvo «{$obtenido}»\n";
}

$db = Database::getInstance();

// Cuenta real y habilitada del entorno de desarrollo. Se lee de la base en vez de
// escribirse a mano para que el test no invente un usuario que no existe.
$stmt = $db->prepare(
    "SELECT correo FROM general_usuarios WHERE correo IS NOT NULL AND correo <> '' LIMIT 1"
);
$stmt->execute();
$correoRegistrado = (string) ($stmt->fetchColumn() ?: '');

if ($correoRegistrado === '') {
    echo "SALTADO: no hay ningún usuario con correo en general_usuarios.\n";
    exit(0);
}

function tokensDe(string $correo): int
{
    $db = Database::getInstance();
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM password_reset_tokens prt
         JOIN general_usuarios u ON u.id = prt.user_id
         WHERE u.correo = ?'
    );
    $stmt->execute([$correo]);
    return (int) $stmt->fetchColumn();
}

$tokensAntes = tokensDe($correoRegistrado);

echo "Caso 1 — registrado y el correo sale:\n";
$mailerOk = new MailerQueEntrega();
$servicio = new PasswordResetService($db, $mailerOk);
comprobar('resultado', PasswordResetService::RESULTADO_ENVIADO, $servicio->request($correoRegistrado, 'app'));
comprobar('se intentó enviar', '1', (string) $mailerOk->enviados);

echo "Caso 2 — registrado y el correo FALLA (el corazón de B-10):\n";
$servicioRoto = new PasswordResetService($db, new MailerRoto());
comprobar('resultado', PasswordResetService::RESULTADO_FALLIDO, $servicioRoto->request($correoRegistrado, 'app'));

echo "Caso 3 — dirección no registrada: se calla, no se distingue:\n";
$servicio3 = new PasswordResetService($db, new MailerQueEntrega());
comprobar('resultado', PasswordResetService::RESULTADO_IGNORADO, $servicio3->request('no-existe-jamas@ejemplo.invalid', 'app'));

echo "Caso 4 — formato inválido:\n";
$servicio4 = new PasswordResetService($db, new MailerQueEntrega());
comprobar('resultado', PasswordResetService::RESULTADO_IGNORADO, $servicio4->request('esto-no-es-un-correo', 'app'));

echo "Caso 5 — un envío fallido no deja el token huérfano:\n";
// El caso 1 deja un token vivo legítimo; el caso 2 no debe dejar ninguno además de ese.
comprobar('tokens tras los cuatro casos', (string) ($tokensAntes + 1), (string) tokensDe($correoRegistrado));

// Restauración: se borran los tokens que este test creó y se verifica que la cuenta vuelve
// al valor de partida. No se supone: se mide.
$limpieza = $db->prepare(
    'DELETE prt FROM password_reset_tokens prt
     JOIN general_usuarios u ON u.id = prt.user_id
     WHERE u.correo = ? AND prt.id NOT IN (SELECT * FROM (SELECT prt2.id FROM password_reset_tokens prt2 JOIN general_usuarios u2 ON u2.id = prt2.user_id WHERE u2.correo = ? ORDER BY prt2.id LIMIT ' . (int) $tokensAntes . ') t)'
);
$limpieza->execute([$correoRegistrado, $correoRegistrado]);
comprobar('base restaurada', (string) $tokensAntes, (string) tokensDe($correoRegistrado));

echo "\n{$total} comprobaciones, {$fallos} fallos.\n";
exit($fallos === 0 ? 0 : 1);
```

- [ ] **Step 4: Ejecutar la prueba**

```bash
docker compose exec app php tests/test_password_reset_resultados.php
```

Esperado: `0 fallos` y salida `0`. Si el caso 3 devuelve `fallido` en vez de `ignorado`, el orden de comprobaciones del servicio cambió y hay que mirarlo, no ajustar el test.

- [ ] **Step 5: Comprobar que la red muerde — la mutación obligatoria**

Editar `src/Services/Auth/PasswordResetService.php` y cambiar, en el `catch (\Throwable $e)`, el `return self::RESULTADO_FALLIDO;` por `return self::RESULTADO_ENVIADO;`. Eso es exactamente el defecto B-10 reintroducido.

```bash
docker compose exec app php tests/test_password_reset_resultados.php; echo "salida: $?"
```

Esperado: el **caso 2 falla** y la salida es `1`. Después, deshacer la mutación:

```bash
git checkout -- src/Services/Auth/PasswordResetService.php
docker compose exec app php tests/test_password_reset_resultados.php
```

Esperado: `0 fallos` otra vez.

- [ ] **Step 6: Commit**

```bash
git add tests/test_password_reset_resultados.php
git commit -m "test(auth): red de pruebas para los tres resultados de recuperar contrasena (B-10)"
```

El cherry-pick del Step 1 ya es su propio commit; este añade la red encima.

---

### Task 2: Higiene de ramas y referencias remotas

**Files:**
- Modify: `docs/superpowers/ramas-viejas-2026-08-03.md` (sección de cierre)

**Interfaces:**
- Consumes: Task 1 (para que `claude/cranky-dhawan-aa8725` no se borre antes de haber extraído B-10).
- Produces: repositorio local sin ramas ya integradas, y el censo cerrado por escrito. Cierra la **Task 28** del plan de la campaña de dark mode.

- [ ] **Step 1: Medir qué queda por integrar en cada rama**

```bash
for b in claude/cierre-ds-110 feat/marca-construccion claude/cranky-dhawan-aa8725; do
  echo "== $b: $(git rev-list --count main..$b) commits por delante"
  git cherry main "$b" | grep '^+' | head -5
done
```

Esperado: `claude/cierre-ds-110` y `feat/marca-construccion` en `0`. `claude/cranky-dhawan-aa8725` seguirá con commits por delante: son los 56 informes, y **eso es correcto** — D6 decidió no traerlos.

- [ ] **Step 2: Pedir confirmación al usuario antes de borrar**

Presentarle exactamente esto y esperar su respuesta: «Voy a borrar dos ramas locales que están a 0 commits de `main`, o sea que no contienen nada que `main` no tenga: `claude/cierre-ds-110` y `feat/marca-construccion`. De `claude/cranky-dhawan-aa8725` ya extraje el arreglo B-10; sus otros 56 commits son informes de estado repetidos. ¿La borro también o la dejo por si quieres conservar esos informes?»

**No borrar ninguna sin su respuesta.**

- [ ] **Step 3: Borrar las confirmadas, con `-d` y nunca con `-D`**

```bash
git branch -d claude/cierre-ds-110
git branch -d feat/marca-construccion
```

`-d` se niega a borrar una rama con contenido único. Si alguna se resiste, **PARAR**: significa que el Step 1 midió mal y tiene algo dentro.

- [ ] **Step 4: Limpiar referencias remotas muertas**

```bash
git remote prune origin --dry-run
```

Revisar la lista con el usuario si no está vacía; solo entonces:

```bash
git remote prune origin
git branch -a | cat
```

- [ ] **Step 5: Cerrar el censo por escrito**

Añadir al final de `docs/superpowers/ramas-viejas-2026-08-03.md` una sección `## Cierre — 2026-08-10` con: las ramas que quedaban vivas al abrir el Frente 0, cuáles se borraron, cuáles se conservaron y por qué, y las referencias remotas podadas. **Qué cobertura se pierde**, dicho explícitamente: al borrar una rama se pierde la posibilidad de recuperar su historia por nombre; se conserva porque sus commits están en `main` (medido en el Step 1), no porque se confíe en el censo del 2026-08-03.

- [ ] **Step 6: Commit**

```bash
git add docs/superpowers/ramas-viejas-2026-08-03.md
git commit -m "docs(ramas): cierra el censo — que se borro, que se conserva y por que"
```

---

### Task 3: La sesión de decisión de los 17 hallazgos

La tarea más valiosa del frente y la única que no puede hacerse sin el usuario. **Su entregable no es código**: es que 17 filas de `docs/EXPERIMENTS.md` pasen de `abierto · decide: usuario` a una disposición firme.

**Files:**
- Modify: `docs/EXPERIMENTS.md` (las 17 filas con `decide: usuario`)

**Interfaces:**
- Consumes: Task 0.
- Produces: 17 filas con disposición escrita. El Frente 1 no puede planificarse sin ellas.

- [ ] **Step 1: Extraer las 17 con su contexto**

```bash
grep -n "abierto · decide: usuario" docs/EXPERIMENTS.md
```

Esperado: 17 líneas. Repartidas así, por si el conteo no cuadra: **1A Seguridad y RBAC → 5** (`RBAC-B`, `RBAC-A`, `PROY-007`, `PROY-001`, `CAS-006`); **1B Cascada LPS → 4** (`C-14`, `IA-3` de 280, `IA-3` de 140, `IA-4` de 150); **1C Pulido y texto → 8** (`C-2`, `C-9`, `C-12`, `C-35`, `C-39`, `C-42`, `IA-5` de 300, `IA-5` de 125).

- [ ] **Step 2: Preparar la presentación, en simple y con recomendación**

Por cada uno de los 17, redactar cuatro líneas: **qué pasa hoy** (sin jerga), **por qué importa** para quien usa la app, **qué recomiendas** y por qué, y **cuál es la opción segura o reversible**. No presentarlos por puntuación: agruparlos por tanda (1A, 1B, 1C), porque decisiones vecinas se contaminan entre sí y decidirlas juntas es más rápido y más coherente.

Ejemplo del nivel de detalle exigido, con `PROY-001`:

> **La barra de avance del selector de proyectos es un número inventado.** Hoy `ProjectSelectorController.php:49` la rellena con `rand(0, 100)`: cada recarga da un porcentaje distinto, y es lo primero que ve todo el mundo al entrar. **Por qué importa:** presenta como dato de obra algo que no lo es; si alguien lo cita en una reunión, cita ruido. **Recomiendo retirarla** hasta que haya una métrica real que cablear — quitar algo falso es barato y reversible; cablear una métrica de avance es un trabajo con su propia definición de qué cuenta como avance. **Opción segura:** retirarla.

- [ ] **Step 3: Presentárselos al usuario con `AskUserQuestion`, por tandas**

Tres llamadas, una por tanda, con las opciones reales de cada hallazgo. Si un hallazgo admite más de dos salidas razonables, ofrecerlas todas; si solo admite «se arregla» o «se deja y este es el motivo», decirlo así.

- [ ] **Step 4: Escribir la disposición en la tabla**

Por cada uno, sustituir `abierto · decide: usuario` por la disposición decidida, en una de estas tres formas exactas, para que sean grepeables:

- `abierto · aprobado` — se arregla en el Frente 1.
- `cerrado · descartado: <motivo en una frase>` — no se arregla, y el motivo queda escrito.
- `abierto · diferido: <a qué frente o a qué condición>` — se arregla, pero no ahora.

**Ninguna fila puede quedarse muda.** Un hallazgo cerrado sin motivo es peor que uno abierto: parece resuelto.

- [ ] **Step 5: Verificar que no queda ninguno sin decidir**

```bash
grep -c "decide: usuario" docs/EXPERIMENTS.md
grep -cE "abierto · aprobado|cerrado · descartado|abierto · diferido" docs/EXPERIMENTS.md
```

Esperado: `0` en el primero, `17` en el segundo.

- [ ] **Step 6: Commit**

```bash
git add docs/EXPERIMENTS.md
git commit -m "docs(backlog): los 17 hallazgos que esperaban criterio quedan decididos"
```

---

### Task 4: Recontar el backlog tras las decisiones

Corta, pero necesaria: el Frente 1 se planifica con las cifras de después de la Task 3, no con las de antes. Sin este recuento, el plan del Frente 1 se escribiría sobre 39 hallazgos cuando puede que sean 30.

**Files:**
- Modify: `docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design.md` (tabla de tandas del Frente 1)

**Interfaces:**
- Consumes: Task 3.
- Produces: las cifras reales de las tandas 1A, 1B y 1C que el plan del Frente 1 consumirá.

- [ ] **Step 1: Recontar**

```bash
grep -c "| abierto" docs/EXPERIMENTS.md
grep -c "cerrado · descartado" docs/EXPERIMENTS.md
grep -c "abierto · diferido" docs/EXPERIMENTS.md
```

- [ ] **Step 2: Actualizar la tabla de tandas del spec** con los totales reales por tanda y el número de descartados y diferidos, dejando la cifra original entre paréntesis para que se vea qué decidió la Task 3.

- [ ] **Step 3: Commit**

```bash
git add docs/superpowers/specs/2026-08-10-programa-cierre-pendientes-design.md
git commit -m "docs(spec): el Frente 1 se dimensiona con el backlog ya decidido"
```

---

### Task 5: La condición de hecho del BI está caducada — redefinirla y desbloquear

`goals/bi-control-tower-gemini/goal.md` pide «aprobación visual explícita de la matriz de 6 modos (Mobile/Tablet/Desktop × Dark/**Linen**)». El tema `linen` se retiró del producto el 2026-07-25 por DS-030. **La condición no se puede cumplir tal como está escrita**, y por eso el goal lleva bloqueado desde entonces sin que nadie lo notara: no faltaba tiempo del usuario, faltaba una condición cumplible.

**Files:**
- Modify: `goals/bi-control-tower-gemini/goal.md` (condición de hecho y estado)
- Read: `goals/bi-control-tower-gemini/evidence/` (`cnp`, `delay`, `radar`), `goals/bi-control-tower-gemini/validation-log.md`

**Interfaces:**
- Consumes: Task 0.
- Produces: goal desbloqueado con condición cumplible, o cerrado con su motivo.

- [ ] **Step 1: Medir qué evidencia existe y en qué modos**

```bash
find goals/bi-control-tower-gemini/evidence -type f | sort
docker compose exec app php -r "echo 'ok';" >/dev/null 2>&1 || docker compose up -d app
```

Anotar, para cada captura: qué viewport y qué tema retrata. Si el nombre del archivo no lo dice, **abrirla y mirarla** — un nombre es una identidad auto-declarada, y esa es justo la trampa que costó siete cierres en F2a.

- [ ] **Step 2: Comprobar contra el contrato vigente qué modos son exigibles hoy**

```bash
grep -rn "SUPPORTED_VIEWPORTS\|REQUIRED_VIEWPORTS" scripts/ docs/design-system/ | head
grep -rn "DS-030\|DS-032" docs/design-system/*.md | head
```

Esperado: `linen` no aparece como tema válido; el viewport móvil aparece como **soportado, no requerido** (DS-032).

- [ ] **Step 3: Proponer la condición nueva al usuario**

Presentarle esto y esperar respuesta: «El goal del BI pide que apruebes seis capturas: tres tamaños × dos temas, oscuro y `linen`. Pero `linen` se borró del producto el 25 de julio, así que dos de esas seis no pueden existir y por eso el goal lleva bloqueado sin que nadie lo dijera. Tienes dos salidas: **(a)** cambiar la condición a los tres tamaños en oscuro y aprobar ahora — es lo que recomiendo, porque es lo que el producto realmente tiene; o **(b)** dejar el BI esperando al Frente 3, cuando exista el tema claro nuevo, y aprobar entonces los seis modos de verdad. La (a) desbloquea hoy y no impide revisar el claro después.»

- [ ] **Step 4: Aplicar la decisión al `goal.md`**

Si elige (a): reescribir la condición de hecho como los tres viewports en oscuro, dejar **escrito el porqué del cambio y la fecha**, y presentarle las capturas para su aprobación visual. Si aprueba, marcar el goal cerrado con la fecha y el commit.

Si elige (b): dejar el estado como bloqueado pero **con la causa corregida** — hoy dice «falta aprobación visual» y la causa real es «la condición referencia un tema retirado; se redefine cuando F3 entregue el tema claro». Añadir la dependencia explícita al Frente 3.

- [ ] **Step 5: Commit**

```bash
git add goals/bi-control-tower-gemini/goal.md
git commit -m "docs(goal): la condicion de hecho del BI referenciaba un tema retirado"
```

---

### Task 6: Los 15 gates del design system — medir antes de cerrar

El goal `design-system-nucleo-gobernanza` exige que «los quince gates exactos de `closeout-evidence.json` tengan evidencia **fresca** y estado `passed`». Los quince dicen `passed`, pero el archivo tiene `generatedAt: 2026-07-15` y `designSystemVersion: 1.1.0`, versión publicada el 2026-08-07. **Fresca no es.** O se mide de nuevo, o el goal no se cierra: declararlo cerrado sobre evidencia de hace 26 días es exactamente el defecto que este repo ya pagó siete veces.

**Files:**
- Read: `docs/design-system/closeout-evidence.json`, `goals/design-system-nucleo-gobernanza/goal.md`, `facts.md`, `validation-log.md`
- Modify: `goals/design-system-nucleo-gobernanza/goal.md` (sección de cierre o de bloqueo medido)

**Interfaces:**
- Consumes: Task 0.
- Produces: el goal cerrado con evidencia fresca, o con la lista exacta de qué gates no pasan y por qué.

- [ ] **Step 1: Dejar por escrito la discrepancia antes de tocarla**

```bash
python3 -c "
import json; d=json.load(open('docs/design-system/closeout-evidence.json'))
print('declara version:', d['designSystemVersion'], '| generado:', d['generatedAt'])
print('gates:', len(d['gates']), '| passed:', sum(1 for g in d['gates'] if g['status']=='passed'))
"
git log -1 --format='%h %cd %s' --date=short a5223a0c
```

Esperado: `1.1.0` declarada, generada el `2026-07-15`, 15/15 `passed`, y la 1.1.0 publicada el `2026-08-07`. **Tres semanas de diferencia**: la evidencia es anterior a la versión que dice avalar.

- [ ] **Step 2: Correr los gates que sí son ejecutables hoy**

```bash
npm run test:design-system:static
docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G
docker compose exec app php tests/test_global_table_safety.php
docker compose exec app php tests/test_global_table_reconciliation.php
```

Esperado: la suite estática en 8/8 (según el cierre de la 1.1.0), PHPStan sin errores y los dos de tablas globales en `OK`. Anotar el resultado real de cada uno, con su hora.

- [ ] **Step 3: Clasificar los 15 gates en tres cubos**

Para cada gate de `closeout-evidence.json`, decidir con evidencia de esta sesión: **(a) verificado hoy** — se acaba de correr y pasa; **(b) no ejecutable en esta sesión** — depende de un fixture, de datos productivos o de una aprobación humana; **(c) caducado** — su recibo apunta a un árbol que ya no existe. Escribir la clasificación completa, gate a gate, en la sección nueva del `goal.md`.

- [ ] **Step 4: Cerrar o no cerrar, y decirlo sin adornos**

Si los 15 caen en (a): añadir al `goal.md` una sección `## Cierre — 2026-08-10` con la evidencia y las horas, y marcarlo cerrado.

Si alguno cae en (b) o (c): **no cerrar**. Escribir en su lugar una sección `## Por qué sigue abierto — 2026-08-10` con la lista exacta de los gates que faltan, qué haría falta para cerrarlos y a qué frente pertenece ese trabajo. Un goal honestamente abierto vale más que uno cerrado en falso, y este programa se apoya en el mapa de estado para todo lo demás.

- [ ] **Step 5: No regenerar `closeout-evidence.json` para forzar un verde**

Está prohibido por `AGENTS.md` §Verificación y es la tentación obvia de esta tarea. Si el archivo debe regenerarse, es trabajo del frente que corresponda y con el usuario avisado, no un efecto colateral de un cierre documental.

- [ ] **Step 6: Commit**

```bash
git add goals/design-system-nucleo-gobernanza/goal.md
git commit -m "docs(goal): los 15 gates del DS clasificados con evidencia de hoy"
```

---

### Task 7: Los dos residuos de la campaña de dark mode

La campaña quedó en 34/38. La Task 28 la cierra la Task 2 de este plan. Queda la **Task 31**, de la que aquí se ejecuta todo **menos su Step 2**: la revisión en frío con `steve-jobs-design-review` se hace al cerrar el Frente 1, sobre la cascada ya arreglada. Revisarla ahora sería revisar defectos que ya están censados y a punto de repararse.

**Files:**
- Modify: `docs/DESIGN-AUDIT.md` (las 20 filas `pendiente (Task N)`), `docs/IMPROVE-APP-PLAN.md` (cierre de campaña), `docs/superpowers/barrido-diseno-2026-08-03.md` (pasada final)
- Create: `docs/PRODUCT.md` — solo si la revisión del Frente 1 lo alimenta; si no, **se difiere y se dice**

**Interfaces:**
- Consumes: Task 3 (la disposición de las 54 decisiones necesita saber qué se decidió de los 17).
- Produces: la campaña de dark mode cerrada salvo su revisión final, que queda enganchada al Frente 1.

- [ ] **Step 1: Refrescar las 20 filas de `docs/DESIGN-AUDIT.md`**

```bash
grep -c "pendiente (Task" docs/DESIGN-AUDIT.md
```

Cada fila `pendiente (Task N)` se cambia por su disposición real, cruzándola contra `docs/EXPERIMENTS.md` ya decidido y contra los commits de la campaña. La tabla se creó a mitad de campaña y nadie más la sincroniza: si no se hace aquí, queda mintiendo indefinidamente.

- [ ] **Step 2: Disposición final de las 54 decisiones**

Cada una marcada como ejecutada (con su hash), descartada (con su motivo) o diferida (con su destino). Cruzar contra la Task 3: varias de las 54 son las mismas que los 17 `decide: usuario`, y deben decir lo mismo en los dos sitios.

- [ ] **Step 3: Cerrar `docs/IMPROVE-APP-PLAN.md` hasta donde llega**

Las fases 1–6 ya están en `done`. La fase 9 pasa de `pending` a `pending — enganchada al cierre del Frente 1`, con la referencia a este plan. **No** se marca `done`: no se ha hecho.

- [ ] **Step 4: Anotar qué queda fuera y por qué**

En el mismo `IMPROVE-APP-PLAN.md`, una línea: `docs/PRODUCT.md` no se crea aquí porque su contenido (`## Outcome Roadmap`) sale de la revisión de la fase 9, que aún no ocurrió. Crear el archivo vacío ahora sería un placeholder.

- [ ] **Step 5: Commit**

```bash
git add docs/DESIGN-AUDIT.md docs/IMPROVE-APP-PLAN.md docs/superpowers/barrido-diseno-2026-08-03.md
git commit -m "docs(campana): las 20 filas del audit y las 54 decisiones quedan al dia"
```

---

### Task 8: Wiki e informe al día, y verificación del frente

**Files:**
- Modify: `memoria/goals/estado.md`, `memoria/log.md`, y las páginas de `memoria/` que la operación `ingest` toque
- Modify: `docs/reportes/estado-desarrollo.html`

**Interfaces:**
- Consumes: Tasks 1 a 7. Es la última.
- Produces: el mapa de estado del repositorio coincidiendo con la realidad, y la condición de hecho del Frente 0 verificada.

- [ ] **Step 1: Leer el procedimiento antes de escribir en la wiki**

```bash
cat docs/wiki-operacion.md
```

Es obligatorio: la wiki tiene frontmatter, trece áreas cerradas y reglas de forma que el lint comprueba.

- [ ] **Step 2: Operación `ingest` sobre `memoria/goals/estado.md`**

Su corte es del 2026-08-06 y no refleja: el cierre de la 1.1.0, las tres fases cerradas de reapertura móvil, ni lo que decidan las Tasks 5 y 6 de este plan. Actualizar las tablas de abiertos, cerrados y absorbidos con lo medido, y anexar la línea correspondiente a `memoria/log.md`.

- [ ] **Step 3: Escribir la lección de este frente, si la hay**

Dos candidatas medidas, ambas del mismo tipo — **una condición de hecho puede caducar sin que nadie lo note**: la del BI referenciaba un tema retirado hace tres semanas, y la del design system se apoya en evidencia anterior a la versión que avala. Si el patrón se sostiene, es una nota de `memoria/trampas/`, con enlace desde los dos goals.

- [ ] **Step 4: Regenerar el informe de estado**

`docs/reportes/estado-desarrollo.html` tiene corte del 2026-08-07 y no ve los ~40 commits de design system del 08 y el 09, ni nada de este frente. Actualizarlo o, si su generación no está automatizada, **decir en el resumen que quedó desactualizado y por qué** en vez de dejarlo mintiendo en silencio.

- [ ] **Step 5: Lint de la wiki**

```bash
npm run test:wiki
```

Esperado: verde. Vigilar el contador de commits desde el último pase de `veracidad`: sale en rojo por encima de 40 y este frente añade varios.

- [ ] **Step 6: Verificación de la condición de hecho del frente**

```bash
git status --short
git log main --oneline --grep="B-10" | head -2
docker compose exec app php tests/test_password_reset_resultados.php
grep -c "decide: usuario" docs/EXPERIMENTS.md
npm run test:wiki
npm run test:design-system:static
```

Esperado: worktree limpio; B-10 en `main`; la prueba en `0 fallos`; `0` hallazgos sin decidir; wiki verde; suite estática 8/8.

- [ ] **Step 7: Commit**

```bash
git add memoria/ docs/reportes/estado-desarrollo.html
git commit -m "docs(wiki): ingest del Frente 0 — estado de goals y leccion de las condiciones caducadas"
```

- [ ] **Step 8: Gate 1 — informe al usuario y parada**

Reportar: qué se verificó con qué comando y qué resultado, qué quedó fuera y por qué, qué datos se tocaron y se restauraron, y la recomendación priorizada para el Frente 1 con las cifras reales del backlog tras la Task 4. **Parar aquí**: el Frente 1 no arranca sin aprobación (D7).

---

## Autorrevisión de este plan

**Cobertura del spec:** los ocho puntos del Frente 0 tienen tarea. El del worktree sucio se convirtió en el Step 1 de la Task 0 porque el árbol ya está limpio, medido. B-10 → Task 1. Ramas → Task 2. Las 17 decisiones → Task 3. BI → Task 5. Design system → Task 6. Task 28 → Task 2 Step 5. Task 31 → Task 7, sin su Step 2. Wiki e informe → Task 8.

**Tres premisas del spec que este plan corrige, medidas:** el worktree ya está limpio; la condición de hecho del BI es incumplible por referenciar `linen`, retirado el 2026-07-25; y cerrar el goal del design system exige medir, no redactar, porque su evidencia es del 2026-07-15 y avala una versión del 2026-08-07. Las tres están arriba, en «Hallazgos previos que cambian el plan», y las dos últimas se llevan a la wiki en la Task 8 Step 3.

**Dos tareas añadidas que el spec no tenía:** la Task 0, porque un frente que arranca sobre una foto de hace horas debe comprobarla; y la Task 4, porque el Frente 1 no puede dimensionarse con las cifras de antes de las decisiones.

**Consistencia de tipos:** `PasswordResetService::request()` devuelve `string`, y las tres constantes usadas en el test (`RESULTADO_ENVIADO`, `RESULTADO_IGNORADO`, `RESULTADO_FALLIDO`) son las que declara el servicio en el commit `1af1471f`. El doble de prueba extiende `SmtpMailer` y respeta su firma exacta, `send(string, string, string, string, string = ''): void`, que devuelve `void` y lanza al fallar.

**Dependencias entre tareas:** Task 1 → Task 2 (no borrar la rama antes de extraer B-10). Task 3 → Task 4 y → Task 7 Step 2. Task 8 consume todas. El resto es independiente y puede reordenarse.

**Dónde puede pararse este plan legítimamente:** Task 0 Step 1 si el worktree tiene algo ajeno; Task 1 Step 1 si el cherry-pick entra en conflicto; Task 2 Step 3 si `git branch -d` se resiste. Los tres son «PARAR y reportar», no «resolver a ojo».

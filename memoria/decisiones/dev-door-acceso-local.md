---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-07-30
areas: [docker, rbac, qa]
fuente: memoria-claude
origen: lps-aia-dev-door-acceso-local
resumen: "en lps-aia la sesión local se abre SIEMPRE por /dev/entrar, nunca tecleando credenciales ni pidiéndole el login al usuario"
---
En `lps-aia`, para obtener una sesión autenticada en local se usa **siempre** la puerta de
servicio, nunca `/login`:

```
http://localhost:8081/dev/entrar?u=test.R&p=PDC%20Sandbox%20E2E
```

`u` ∈ {`test.A` (rol A), `test.R` (rol R), `test.V` (rol V)}; `test.C` y `test.D` son cuentas
sembradas que existen, pero cuáles quedan habilitadas depende de `DEV_DOOR_USERS` en `.env` —
**configuración local, no versionada, que varía por máquina**. **Corregido el 2026-08-10:** en esta
máquina hoy `DEV_DOOR_USERS=test.A,test.R,test.V,test.C,test.D` incluye las cinco (`test.D` se
añadió ese mismo día para que el test del rol D pudiera correr por la puerta de servicio); no
asumas que solo tres están activas sin mirar el `.env` real. Sin `p` aterriza en `/proyectos`. El
rol que queda en sesión es el **real** de `project_members`, así que sirve para cubrir "un rol
permitido y uno denegado" del contrato de `AGENTS.md`.

**Why:** el usuario pidió esto explícitamente (2026-07-30) tras constatar que yo no puedo teclear
credenciales y que el panel del navegador pierde la cookie cada 60-90 s ([[sesion-cae-en-el-panel]]),
lo que convertía cada QA en pedirle el login una y otra vez.

**How to apply:** ir directo a `/dev/entrar` al empezar cualquier verificación en navegador; no
proponer login manual ni pedirle credenciales al usuario. Si redirige a `/login` o da 404, la puerta
está cerrada: revisar `DEV_DOOR=1` y `DEV_DOOR_USERS` en `.env`. Editar `APP_ENV` en `.env` **no**
la cierra bajo Docker (el contenedor inyecta la variable y `createImmutable` no la pisa); para
cerrarla, `DEV_DOOR=0`.

Diseño en `docs/superpowers/specs/2026-07-30-dev-door-design.md`; candado en `src/Core/DevDoor.php`,
cubierto por `tests/test_dev_door_guard.php`. Para trastear conviene el proyecto sintético
`PDC Sandbox E2E` (990100), no Da Porto ([[no-enriquecer-daporto-para-medir]]).

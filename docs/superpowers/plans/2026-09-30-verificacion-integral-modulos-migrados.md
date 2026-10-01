---
capa: fuente
tipo: plan
estado: abierto
fecha: 2026-09-30
areas: [qa, lps, arquitectura]
fuente: docs/superpowers/plans/2026-09-30-verificacion-integral-modulos-migrados.md
resumen: "Sprint de verificación integral y ficha en la wiki para los módulos ya migrados cuyos planes están cerrados: S01 login, S02 recuperar clave, S03 restablecer clave y S04 selector de proyectos"
---

# Verificación integral de los módulos ya migrados (S01 a S04)

> Propuesta del 2026-09-30, pendiente del visto de Felipe; se exige solo cuando el protocolo
> `docs/qa/protocolo-verificacion-integral.md` pase a vigente.

**Por qué es un plan aparte.** Los planes de S01, S02, S03 y S04 están cerrados. Añadirles
tareas hacía que `TASKS.md` las contara como hechas, y reabrirlos hacía que contara como
pendientes tareas viejas que ya se hicieron. Decisión de Felipe del 2026-09-30: un plan aparte,
que deja intactos los cuatro cierres. Programa General (S05), el shell (T01) y el contexto LPS
(T02) llevan sus tareas en sus propios planes, que siguen abiertos.

**Orden.** Primera tanda de la cola del protocolo (los ya migrados primero), después de cerrar lo
pendiente de Programa General. Cada módulo: primero el sprint y después su ficha.

## Task 1: Sprint de verificación integral de S01 Login

- [ ] Mapa de S01 en tres frentes (pantalla, servidor y base de datos, reglas y flujos) sobre `/` y `/login`, cada objeto con `archivo:línea`.
- [ ] Entorno: rama servida con `LPS_CODE_ROOT`, `.env` por enlace duro hecho por Felipe, dependencias dentro del contenedor y respaldo de la base de desarrollo antes de escribir.
- [ ] Prueba de lectura en navegador de cada objeto: consola y red sin errores, dos temas, 1920, 1180 y 390 px, teclado y foco.
- [ ] Prueba de escritura de cada acción, una a la vez, con foto antes y después y restauración; lo que no se deshaga desde la aplicación se consulta a Felipe antes.
- [ ] Roles: una cuenta con permiso y una sin él, en pantalla y directo a la API.
- [ ] Cierre: restauración dirigida (con `/visto-prod` de Felipe), contenedor devuelto a la raíz y hallazgos clasificados.

## Task 2: Ficha de S01 en la wiki

- [ ] Escribir `memoria/arquitectura/login-funciones-y-flujos.md` con las secciones de [[programa-general-funciones-y-flujos]].
- [ ] Enlazarla desde [[autenticacion]] y anotar su línea en `memoria/log.md`.
- [ ] `npm run test:wiki:forma` con código de salida 0.

## Task 3: Sprint de verificación integral de S02 Recuperar clave

- [ ] Mapa de S02 en tres frentes sobre `/password/forgot`, cada objeto con `archivo:línea`.
- [ ] Entorno, prueba de lectura, prueba de escritura con restauración, roles y cierre, como en la Task 1. El correo de recuperación se prueba sin enviar a buzones reales.

## Task 4: Ficha de S02 en la wiki

- [ ] Escribir `memoria/arquitectura/recuperar-clave-funciones-y-flujos.md`, enlazarla desde [[autenticacion]] y anotar su línea en `memoria/log.md`.
- [ ] `npm run test:wiki:forma` con código de salida 0.

## Task 5: Sprint de verificación integral de S03 Restablecer clave

- [ ] Mapa de S03 en tres frentes sobre `/password/reset`, cada objeto con `archivo:línea`.
- [ ] Entorno, prueba de lectura, prueba de escritura con restauración, roles y cierre, como en la Task 1. La clave de la cuenta de prueba se restaura al terminar.

## Task 6: Ficha de S03 en la wiki

- [ ] Escribir `memoria/arquitectura/restablecer-clave-funciones-y-flujos.md`, enlazarla desde [[autenticacion]] y anotar su línea en `memoria/log.md`.
- [ ] `npm run test:wiki:forma` con código de salida 0.

## Task 7: Sprint de verificación integral de S04 Selector de proyectos

- [ ] Mapa de S04 en tres frentes sobre `/proyectos`, cada objeto con `archivo:línea`.
- [ ] Entorno, prueba de lectura, prueba de escritura con restauración, roles y cierre, como en la Task 1.

## Task 8: Ficha de S04 en la wiki

- [ ] Escribir `memoria/arquitectura/selector-proyectos-funciones-y-flujos.md`, enlazarla desde [[selector-de-proyectos]] y anotar su línea en `memoria/log.md`.
- [ ] `npm run test:wiki:forma` con código de salida 0.

## Cierre

Pendiente. El avance se lee de esta sección y del historial de git, no de las casillas.

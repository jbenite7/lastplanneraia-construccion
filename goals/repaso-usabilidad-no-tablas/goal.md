---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-03
areas: [proceso]
fuente: goals/repaso-usabilidad-no-tablas/goal.md
resumen: Ejecutar el hallazgo H-08 que cierre-dark-mode-y-tablas dejó diferido: revisar la usabilidad de las superficies que no son tablas, en los ejes que ningún…
---

# Goal — Repaso de usabilidad de superficies ajenas a tablas (H-08)

## Objetivo

Ejecutar el hallazgo **H-08** que `cierre-dark-mode-y-tablas` dejó diferido: revisar la usabilidad
de las superficies que no son tablas, en los ejes que ningún script mide (estados vacíos,
retroalimentación, jerarquía, copia, flujo y consistencia entre superficies hermanas).

Es un trabajo de **diagnóstico**. No se modifica código: la decisión de qué se arregla es del
usuario, con el inventario delante, igual que en la fase G0 del goal anterior.

## Condición de hecho

- [x] Inventario real de superficies establecido desde `public/index.php` y `admin/public/index.php`,
      con el número cubierto sobre el número total declarado explícitamente.
- [x] Recorrido en el navegador contra el contenedor, a 1180×820 y dark.
- [x] Ejes automáticos re-medidos una vez para no reportar como hallazgo algo que ya está bien.
- [x] `inventario-usabilidad.md` con un hallazgo por fila: superficie, tipo, descripción, severidad
      y captura.
- [x] Recomendación priorizada al cierre.
- [x] Ninguna modificación de código de producción.

**Cerrado el 2026-08-03.** 39 hallazgos (15 altas, 15 medias, 9 bajas). El usuario aprobó atacar
**altas y medias: 30 de 39**. Las tres decisiones abiertas quedaron resueltas y **ninguna se ejecuta
aquí** — cada una genera trabajo propio (ver `inventario-usabilidad.md` §7):

- `/indicadores` conserva el embebido, con estados y encuadre de «contenido externo».
- `/dashboard` tendrá panel de inicio; **absorbe H-24** (las escrituras automáticas al entrar).
- La puerta de servicio se extiende a `admin/`, con spec propio por tocar autenticación.

## Alcance verificado

Las cifras del goal anterior («31 superficies de la app más 14 de admin») **se comprobaron y son
correctas**, pese a la advertencia del encuadre. Detalle en `inventario-usabilidad.md` §1.

**Cubiertas: 26 de 45.** Las 14 superficies internas de `admin/` quedaron fuera por una razón dura,
no por falta de tiempo: la puerta de servicio (`/dev/entrar`) solo abre sesión en la app principal,
`admin/` exige su propio `/admin/login`, y las reglas del repo prohíben teclear credenciales o pedir
que una persona entre. Ver §1 para la lista exacta de lo no cubierto.

## Archivos de este goal

- [inventario-usabilidad.md](inventario-usabilidad.md) — el entregable.
- `evidence/` — capturas a 1180×820 dark de cada superficie recorrida.
- [Estado de los goals](../../memoria/goals/estado.md)
- [Goal anterior: cierre-dark-mode-y-tablas](../cierre-dark-mode-y-tablas/goal.md)

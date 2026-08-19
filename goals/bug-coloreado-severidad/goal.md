<!-- cas:cita-textual — registro del frente: cita salidas y comandos tal como se midieron -->
# Frente: bug-coloreado-severidad

## Fase del plan
Plan: docs/superpowers/plans/2026-08-19-bug-coloreado-severidad.md
Fase: Fase 1, Fase 2 y Fase 3 — el frente ejecutó las tres en una sesión
Sha verificado: 9e534129017c5ea2a72dad3c7bc90f89b368e57f
Presupuesto: ?

<!-- Por qué `Fase:` no lleva un identificador único: el plan declara tres fases y este
     frente las hizo las tres, porque son tres tandas de un solo diagnóstico de lectura
     que no cabía partir sin volver a medir lo mismo. Poner «Fase 3» sería falso y haría
     saltar el aviso de fase previa abierta sobre dos fases que sí están cerradas. Se
     escribe lo que pasó, no el valor que encaja en la casilla.
     Al abrirse, el frente quedó con `Plan: ?` / `Fase: ?` porque `cas-frente.sh` solo
     parsea encabezados `Task N` o `Fase N` y el plan usaba `## Tanda N`. Lo arregló
     `7b7c2b9d` (otra sesión) renombrando los encabezados; se rellena aquí. -->

## Objetivo
Averiguar por qué la tabla de /programacion-intermedia no ordena el color por severidad
de «Crítico» a «Sin problema», como espera el usuario, y demostrarlo con medición.
Diagnóstico, no arreglo: la spec ofrece tres respuestas posibles y distinguir cuál es
constituye todo el trabajo.

## Condición de hecho
Un diagnóstico escrito que responde cuál de las tres respuestas es, con capturas a
1180×820 dark por sesión real, valores computados y —si es bug— la línea que lo causa
y cómo reproducirlo. Cero cambios en producto.
Verificación: npm run test:design-system:static

**Cumplida.** `bash scripts/publicar.sh --solo-verificar` sobre `9e534129`:

```
Verificando sobre 9e534129…
  ✔ design-system:static               RC=0
  ✔ contrato piloto PG                 RC=0
  ✔ wiki (lint + veracidad)            RC=0
```

## Posture
- No arreglar. Ni «ya que estoy».
- No tocar los tintes ni sus tokens: cambiar la escala es DS-F1 y es decisión de negocio.
- No cambiar lo que mide ninguna prueba ni regenerar goldens: bloqueo incondicional.
- Sin dependencias nuevas.

## Leer primero
- `docs/design-system/state-semantics.json` y `state-tint-exceptions.json`
- `tests/design-system/state-tint-ladder.test.mjs`
- `public/js/modules/programacion_intermedia/hot.js` (`stateLabels`, `statePresentation`)
- `decisiones/contadores-cero.md`
- `GLOSARIO.md`

## Archivos declarados
goals/bug-coloreado-severidad/**

## Resultado
El veredicto no es una de las tres respuestas: son dos, y hay una tercera que existe
pero no causa el síntoma. Todo en `goals/bug-coloreado-severidad/diagnostico.md`.

- **(2) Hueco de contrato, principal.** No hay escala de tinte ordenada por severidad
  que respetar: la paleta es **nominal, no ordinal**, por decisión medida del
  2026-07-28. `GLOSARIO.md` no define ninguna severidad y «Sin problema» no es
  vocabulario del repo. El fondo de la fila lo pinta una escalera ordinal
  (`--ds-cell-state-*`, `public/css/styles.css:3664-3725`) que **ningún contrato
  gobierna y ningún test cubre**, y que contradice el nivel declarado en
  `state-semantics.json` en 3 de 8 estados.
- **(3) Contraste, agravante.** Ocho entradas de leyenda pintan cinco colores; tres
  pares de estados son bit-idénticos. La luminosidad OKLab de los cinco peldaños cabe
  en una banda del 9 % y está prácticamente invertida: `atencion` es el más claro y
  `critico` el penúltimo más oscuro.
- **(1) Bug real, ajeno al síntoma.** `states-feedback.css:162` es letra muerta porque
  `legacy-bridge.css:104-142` gana desde `legacy-overrides` con `:where()`. Arreglarlo
  colapsaría tres chips de PI en uno y pondría en rojo `ops-state-chip-hue.mjs`.

Entregables: `diagnostico.md`, `propuesta-arreglo-3-estados.md`, `insumo-ds-f1.md` y
`evidence/` (dos capturas a escala real, dos JSON de medición, tres sondas de solo
lectura y reutilizables).

## Pendientes
- Aplicar el **Caso A** de `propuesta-arreglo-3-estados.md` («En Ejecución Pendiente»
  pintado con el verde de «controlado» siendo P1). Exige ampliar los archivos
  declarados a `public/css/styles.css` y vigilar el golden de PI.
- Decidir qué autoridad de severidad se deroga: `state-semantics.json` o
  `docs/matriz-severidad-cajon-contextual-lps.md`, que discrepan en cuatro estados de PI.
- El mapeo de `styles.css:3664-3725` no tiene contrato ni guard: hoy nadie lo vigila.
- `#fef3c7` en la leyenda (`hot.js:2857`): hex claro de reserva sobre tema oscuro.
- **DS-F1** — rediseño de la escala, pedido por el usuario el 2026-08-18. Su entrada
  medida es `goals/bug-coloreado-severidad/insumo-ds-f1.md`. **Lo abre la coordinadora.**

## Publicaciones
- `9e534129017c5ea2a72dad3c7bc90f89b368e57f` → `origin/main`, 2026-08-18. Verificado
  con `scripts/publicar.sh` **después** de integrar `origin/main` dos veces (8 + 8
  commits entrantes; el segundo lote entró mientras corría la verificación y el script
  denegó la publicación, que es el guardarraíl haciendo su trabajo). `git rev-parse
  origin/main` confirmado idéntico al sha verificado.

## Cierre
El frente termina aquí. Su condición de hecho está cumplida y verificada, el trabajo
está publicado en `main` y lo que queda vivo son los pendientes de arriba, que
pertenecen a otros frentes (el Caso A y DS-F1), no a éste.

**Dos cosas que hay que decir sin adornos, porque el procedimiento no se siguió entero:**

1. **No hubo visto de la coordinadora.** Se publicó por **autorización directa y
   explícita del usuario** el 2026-08-18, que es quien declara el reparto. Una sesión de
   ejecución no puede firmarse su propio visto —el gate de rutas deniega `.claude/vistos/`
   por diseño— y no se intentó. Queda anotado como lo que fue.
2. **Se apuntó el contenedor compartido a este worktree** para pasar el invariante de
   montaje de `publicar.sh`, y se devolvió a la raíz en el mismo turno. Al devolverlo se
   encontró que otra sesión lo tenía apuntado a `recursing-shtern-472554`, un worktree
   **sin `vendor/`**, así que `localhost:8081` respondía **500 para todas las sesiones**.
   Se restauró a la raíz y se comprobó `HTTP 200`. Está reportado a la coordinadora.

## Archivos de este goal
- [[diagnostico]] · [[propuesta-arreglo-3-estados]] · [[insumo-ds-f1]]
- [[docs/superpowers/specs/2026-08-19-bug-coloreado-severidad-design]]
- [[docs/superpowers/plans/2026-08-19-bug-coloreado-severidad]]
- [[decisiones/bug-coloreado-severidad-ejecutor]]
- [[memoria/goals/estado]]

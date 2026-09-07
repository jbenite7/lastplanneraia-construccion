---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-09-07
areas: [qa, proceso]
fuente: .github/workflows/ci.yml, PR #7 #8 #9 #13 #14 (dependabot), corridas 33937050578 / 33187868372 / 32791271432 / 33070931277 / 33070944252, AGENTS.md (regla del 2026-09-07)
resumen: "Cuatro PR llegaron con veredicto de agosto sobre un CI que ya no existía, y GitHub los mostraba igual que a uno medido hoy: un verde tiene fecha, y a veces no hay verde sino ausencia de medición"
---
# Un verde caduca, y a veces ni siquiera existe

[[un-verde-solo-vale-para-el-arbol-donde-se-midio]] dice que un resultado depende del **árbol** donde
se midió. Esta es la otra mitad: depende también del **momento**, y de si de verdad se midió. Las
tres formas de verde falso se ven idénticas en la interfaz de GitHub, y las tres aparecieron en
cuatro días.

## Las tres caras

**1. El paso que se sella solo.** Todos los gates del job `design-system-runtime` llevan
`continue-on-error: true` a propósito (`ci.yml`, P2 Tarea 2 del 2026-08-24: sin eso, el primero en
romperse cancelaba los demás y escondía la deuda de los otros). El efecto colateral es que **cada
paso muestra su palomita aunque su gate haya fallado**. El veredicto real vive en las variables `G_*`
del paso «Summarize gate results», y solo ahí. Medido el 2026-09-04: una corrida en `failure` tenía
once de doce gates en verde, y otra en `success` los tenía todos — el color no distingue esos dos
casos, la tabla sí.

**2. El veredicto de otra época.** Los cinco PR de dependabot abiertos el 2026-09-07 traían checks de
entre el 24 y el 28 de agosto. Dos en rojo, dos en verde, uno reciente. Los dos rojos
(`33187868372`, `33070931277`) eran de los días en que `G_FULL_APP_FLOW`,
`G_RUNTIME_BUDGET_CHECK` y `G_KEYBOARD_REFLOW_EVIDENCE` estaban caídos por causas ajenas a esos PR
—arregladas el 4 y el 5 de septiembre—, así que **su rojo no hablaba de ellos**. Y los dos verdes
(`32791271432`, `33070944252`) son de un CI **que ya no existe**: sus jobs se llaman
`design-system-runtime` a secas, sin sufijo de tema, porque la matriz por tema entró el 2026-08-28.
Tras pedir `@dependabot rebase`, los cuatro corrieron contra `main` de hoy y dieron trece de trece en
los dos temas. Ninguno de los cuatro veredictos anteriores decía nada útil.

**3. El check que nadie hizo.** `ci.yml:9-18` lleva `paths-ignore: ['memoria/**', '*.md']`, así que
un PR que solo toca esta wiki o un `.md` de raíz **no dispara CI**. `gh pr checks` responde
`no checks reported` y GitHub marca el PR `CLEAN` — que ahí **no significa «pasó», significa «no
había nada que pasar»**. Se descubrió el 2026-09-07 con el PR #35, que era el que escribía la regla
de quién mergea: al aplicarla a sí misma cayó en un caso que ella no cubría.

## Por qué se cuela

Las tres tienen el mismo mecanismo: **el indicador que se mira no es el que mide**. La palomita del
paso no es el gate, el estado del PR no es la corrida, y `CLEAN` no es un resultado sino la ausencia
de uno. Y ninguna de las tres deja rastro cuando engaña: un verde falso no produce un fallo, produce
un frente que parecía cerrado.

Se agrava con el tiempo. Un PR abierto tres semanas conserva su check antiguo con la misma cara que
el día que se hizo; nada en la interfaz envejece un veredicto ni avisa de que el CI que lo produjo ya
cambió.

## Cómo evitarlo

- **Antes de creerle a un verde, pregúntale tres cosas: qué midió, de qué árbol y cuándo.** Si
  cualquiera de las tres no se puede contestar mirando, no es evidencia.
- **En este repo el veredicto se lee de las variables `G_*` del paso «Summarize gate results»**,
  nunca del color del job ni de su `conclusion`. Está fijado como regla en `AGENTS.md` desde el
  2026-09-07.
- **Un PR viejo se revalida antes de mergearlo**, aunque su check esté verde. En los de dependabot
  basta comentar `@dependabot rebase`; en los propios, integrar `main` y volver a correr. El coste
  son diez minutos y evita meter a `main` algo que nadie probó contra el árbol de hoy.
- **`CLEAN` sin checks no es aprobación.** Comprobar si el workflow siquiera corre para esos
  archivos (`paths-ignore`) antes de declarar cualquier condición de hecho que dependa del CI — el
  PR #35 declaró un `design-system-static: success` que era imposible, por no mirar eso primero.
- **Un rojo viejo tampoco acusa.** Antes de atribuirle el fallo al cambio, mirar qué más estaba roto
  en el repo esa semana: dos de los cinco PR arrastraban un rojo que no era suyo.

Relacionado: [[un-verde-solo-vale-para-el-arbol-donde-se-midio]],
[[condicion-de-hecho-caduca-sin-aviso]], [[branch-preexisting-red-gates]],
[[gate-solo-cuenta-elementos-no-los-lee]]. Mapa del área: [[qa-y-gates]].

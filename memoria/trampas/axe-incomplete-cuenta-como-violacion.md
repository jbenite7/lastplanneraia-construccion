---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-03
areas: [qa, design-system]
fuente: sesion
resumen: "El arnés de accesibilidad mete los `incomplete` de axe en la misma lista que las `violations`, y sobre fondos translúcidos axe siempre devuelve incomplete: rojos falsos garantizados"
---
`tests/browser/support/accessibility.mjs:36` construye la lista de hallazgos así:

```js
[['violations', 'violation'],
 ['incomplete', 'incomplete']]
```

Las dos colecciones se aplanan juntas y reciben el mismo trato. Un **`incomplete`** de axe
significa *«no pude comprobarlo»*, no *«está mal»* — pero bloquea igual que una violación probada.

**Por qué muerde en esta aplicación en particular:** axe no puede calcular contraste cuando el
fondo es translúcido, porque no sabe qué hay detrás. Y las superficies de este repositorio son
translúcidas por diseño: `--ds-color-surface-dark` es `rgba(28, 36, 31, 0.92)`. O sea que axe va a
devolver `incomplete` **sistemáticamente**, y cada superficie nueva que se meta en el carril de
accesibilidad nace con rojos que no corresponden a ningún defecto.

Medido el 2026-08-03 en `/programa-general`: los nueve `incomplete` que reportaba axe sobre celdas
`.pdc-header` no eran defecto. Con la sonda de `tests/browser/support/contrast.mjs` —que **sí**
compone alfa sobre los ancestros— los 55 nodos de esa familia dan entre **13,82 y 15,27:1**, con
cero por debajo del mínimo AA de 4,5. Triplican lo exigido.

**Why:** un guard que no distingue «no medible» de «mal medido» enseña a la gente a ignorar sus
rojos, y entonces deja de vigilar aunque siga en verde o en rojo. Es el mismo daño que un gate que
nunca falla, por el camino contrario.

**How to apply:** mientras el arnés no separe las dos categorías, un `incomplete` se resuelve
**midiendo con `contrast.mjs` y registrando la excepción con la cifra dentro**, en
`docs/design-system/a11y-exceptions.json`. Ese archivo exige `owner`, `reason`, `milestone` y
`expiresAt`, y rechaza comodines y excepciones caducadas — está bien diseñado, úsalo. Lo que no
vale es sacar la prueba del pipeline ni bajar el umbral.

**Pendiente, y es decisión de repositorio:** que `resultEntries` reporte los `incomplete` sin
bloquear, o los mande a un carril propio. Cambia la política de accesibilidad de todo el
repositorio, así que no se hace de paso dentro de otro trabajo.

Emparentada con [[gate-estatico-no-ve-tokens-rotos]] y
[[guard-valida-declaracion-contra-si-misma]]: las tres son formas de que un guard diga algo
distinto de lo que el lector cree que dice.

---
capa: fuente
tipo: reporte
estado: abierto
fecha: 2026-08-20
areas: [bi, design-system]
fuente: medición del código (`bi-spa.js`, `ControlTowerService.php`, `BiViewController.php`, `views/bi/`) contra la spec del replanteo, bajo el método `antes-del-almuerzo`, 2026-08-20
resumen: "Propuesta que resuelve la contradicción D52/D11 de la spec del replanteo: dos lienzos como composiciones de hojas compartidas cuestan ~1,05 veces uno; reparto hoja por hoja, trato de Curva S y Proveedores, y el bus factor que la spec no trata"
project: lps-aia
---

# Sostenibilidad de los lienzos por audiencia

> Propuesta a [[2026-08-20-replanteo-control-tower-design]], sección 18 punto 1. **No edita la
> spec**; la edita solo `sesion-40aa70aa-db1`. Las 66 decisiones están en
> [[2026-08-20-decisiones-control-tower]].

## Recomendación en una frase

**Salida 1: cada lienzo es un subconjunto de hojas, y las hojas compartidas se construyen una sola
vez y se montan en los dos.** Medido contra el código, el segundo lienzo cuesta cerca del 5 % del
primero, porque en esta base **la unidad de construcción ya es la hoja y el lienzo no es más que
navegación**. No hay que revisar D52 ni D11: hay que precisar qué significa «lienzo» para que las dos
dejen de chocar.

**Qué NO haría:** no abriría la puerta de entrada compartida (salida 2) ni recortaría hojas
(salida 3). La primera mete Responsables delante de gerencia, que es exactamente la trampa de
captura que D47 prohíbe; la segunda revierte una decisión de Felipe sin que el costo la justifique,
porque el costo que la motivaba no existe una vez medido.

## Las preguntas del grilleo y lo que asumí

Esta sesión corrió sin Felipe al frente. Las preguntas que le habría hecho de a una, con la
respuesta que asumí para poder entregar. **Cada supuesto es reversible sin rehacer el análisis.**

| # | Pregunta | Supuesto tomado | Si la respuesta es otra |
|---|---|---|---|
| 1 | ¿Gerencia necesita ver Responsables persona por persona? | **No.** D46 («nunca todos ven a todos») y D47 (ayuda, no evaluación) ya lo dicen | Responsables se monta también en gerencia, con el filtro de servidor de D46. El costo no cambia |
| 2 | ¿Una hoja compartida puede verse distinta según quién entra? | **No.** Una hoja, un diseño. Lo que una audiencia no necesita baja a desglose | Aparece duplicación real: cada «modo» es una presentación más que mantener. Es la única respuesta que sube el costo |
| 3 | ¿Cómo se elige el lienzo al entrar? | **Por rol de `project_members`**, normalizado con `RbacService::normalizeRole()`: A y D ven gerencia; R, C y los demás ven obra; Admin puede cambiar | Si se elige a mano, hace falta un conmutador y una preferencia guardada. Trabajo pequeño, pero es trabajo |
| 4 | ¿Hoy alguien distinto a Felipe ha hecho un deploy o un cambio de métrica? | **Nadie** | Si sí, la mitad de la sección de bus factor ya está resuelta y sobra |

## Dónde está la contradicción de verdad

D52 y D11 no chocan por lo que deciden sino por lo que **no definen**: qué es un lienzo. Hay dos
lecturas:

- **Lienzo = producto aparte**, con su código, sus vistas y su ciclo. Bajo esta lectura, dos lienzos
  son dos tableros, y la advertencia de D52 («triplica construcción y mantenimiento») es cierta.
- **Lienzo = composición**: una puerta de entrada, una hoja de aterrizaje y una lista de hojas
  montadas. Bajo esta lectura, la hoja es el producto y el lienzo es navegación.

El método respalda la segunda. Su regla «un solo lienzo, una sola audiencia» (sección 6) es una regla
de **pantalla**: que lo que una persona ve al abrir responda a una decisión suya y no a la de otro.
No es una regla de repositorios. Y la sección 7 del mismo método prohíbe construir lo que no se
puede mantener. Las dos se cumplen a la vez solo con la lectura de composición.

Lo que D52 rechazó —«las mismas hojas con puerta distinta»— sigue rechazado: gerencia y obra **no
ven las mismas hojas**, y ninguna aterriza en una hoja que no es suya.

## El costo, medido

### Lo que hay hoy

| Pieza | Tamaño | Cómo se organiza |
|---|---|---|
| `public/js/modules/bi-spa.js` | 4.199 líneas, 285 funciones | **2.416 líneas (57 %) son motor compartido**: gráficas, drill-downs, tablas accesibles, filtros, chips. El resto se indexa por `report_key`, nunca por audiencia |
| `render<Hoja>` en ese archivo | Overview 26 · Programa General 52 (más ~1.200 de drill-downs propios) · Curva S 5 · Intermedia 21 · Semanal 23 · Plan de Compras 102 · Proveedores 15 · Responsables 16 | Cada hoja es una función que recibe el JSON de su `report_key` |
| `src/Services/ControlTowerService.php` | 3.693 líneas, 157 métodos | Scorecard, gráficas y tablas se despachan con `match ($reportKey)`. **No existe una rama por audiencia** |
| `views/bi/control-tower.php` | 718 líneas | Ocho `<section id="view-…">` en un solo archivo, todas presentes siempre |
| `views/bi/_nav.php` | 32 líneas | Ocho pestañas fijas, sin filtro por rol. **Esta es la puerta de entrada hoy** |
| `BiViewController.php` | 195 líneas | Ocho acciones que llaman al mismo `renderView($reportKey, 'control-tower')` |

Con el catálogo ejecutable de la sección 6, el cálculo ya se comparte por contrato: una métrica, una
declaración, un ejecutor. Así que la pregunta «qué queda duplicado» se reduce a **presentación**, y
la presentación está atada a la hoja, no al lienzo.

### Qué cuesta cada salida

La unidad de medida es «qué hay que tocar cuando cambia una métrica o una hoja», que es el costo del
mes cuatro, no del mes uno.

| Salida | Qué se construye de más | Costo al cambiar una métrica | Costo al cambiar una hoja | Veredicto |
|---|---|---|---|---|
| **1. Subconjuntos, hojas compartidas montadas en los dos** | Un mapa `audiencia → [hojas, hoja de aterrizaje]` (~40 líneas de PHP), `_nav.php` leyendo ese mapa, el `renderView` decidiendo qué secciones emite. Nada nuevo en JS si T1 ya parte por hoja | **1×** (catálogo) | **1×** (la hoja vive una vez) | **≈ 1,05× un lienzo** |
| 2. Puerta de entrada compartida (revisar D52) | Lo mismo que la 1 menos el mapa: las ocho pestañas para todos con otra hoja de aterrizaje | 1× | 1× | ≈ 1,02×, pero viola D46/D47 y mezcla audiencias en la misma barra |
| 3. Menos hojas por lienzo (revisar D11) | Menos hojas en total | 1× | 1× | Ahorra solo si se **apagan** hojas, y D11 dice que ninguna se apaga. Montar menos hojas en un lienzo no ahorra nada frente a la salida 1 |

Lo que sí triplica el costo, y lo único que hay que prohibir en la spec, es la **variante por
audiencia dentro de una hoja compartida** («Curva S modo gerencia» y «Curva S modo obra»). Ahí cada
cambio se hace dos veces. La regla: una hoja tiene un diseño; lo que una audiencia no necesita baja a
desglose o se resuelve con los filtros que ya existen (`supports_multi_project`, que distingue ver
una obra de ver todas).

### Lo que T1 cambia y lo que no

Partir `bi-spa.js` por hoja (T1) es lo que hace que el segundo lienzo cueste casi nada **en JS**: el
cascarón carga la lista de módulos del lienzo activo y cada módulo ignora quién mira. Pero T1 no toca
el PHP. El despacho por `match ($reportKey)` en un servicio de 3.693 líneas sigue siendo una sola
persona leyendo un solo archivo. **T1 debería tener un gemelo en el servicio** —una clase por
`report_key` detrás de una interfaz común—, no por los lienzos sino por el bus factor de abajo.

## Reparto hoja por hoja

Criterio: la decisión y el dueño que la sección 8 ya declara, pasados por el test de la decisión del
método («si esta cifra cambia de la noche a la mañana, ¿quién actúa antes del almuerzo?»). Una hoja
va en un lienzo solo si **alguien de esa audiencia actúa desde ella**. Si la audiencia solo necesita
*saber*, le llega como fila del panorama del Resumen, no como hoja montada.

| Hoja | Decisión declarada | Quién actúa | Gerencia | Obra | Por qué |
|---|---|---|---|---|---|
| 8.1 Resumen Ejecutivo | D17: en qué obra meterse esta semana | Gerencia llama a un director | **Aterrizaje** | — | Es la única hoja que compara obras. La obra no actúa desde ella |
| 8.2 Programa General | D21: reordenar la ventana de seis semanas · riesgo de la fecha de entrega · valor ganado | Director (reordena) y gerencia (evalúa la fecha) | Sí | Sí | **Compartida.** Las tres decisiones son de la misma hoja y del mismo titular P50. El desglose de actividades es del director; gerencia no lo abre |
| 8.3 Programación Intermedia | Liberar hoy la restricción que mata el compromiso en tres semanas | Director y responsables | — | **Aterrizaje** | Gerencia no libera restricciones. Su señal (huérfanas, vencidas) ya viaja en la fila de obra del panorama |
| 8.4 Programación Semanal | D35: preparar el comité del lunes (principal) | Director y residente | — | Sí | D35 lista «gerencia compara entre obras» como secundaria: esa comparación es el panorama del Resumen, no esta hoja |
| 8.5 Curva S | D40: rendición hacia arriba y hacia afuera · gatillo de replanificación | Gerencia rinde; el director replanifica | Sí | Sí | **Compartida.** Ver abajo |
| 8.6 Plan de Compras | D42: destrabar el paso vencido · cobertura del presupuesto · anticipar qué falta | Compras y director | — | Sí | «Gerencia vigila cobertura» es una cifra, no una acción: va como columna del panorama. Si al medir resulta que gerencia sí destraba pasos, se monta también, sin costo |
| 8.7 Proveedores | D45: a quién no volver a contratar · a quién apretar esta semana | Gerencia y comité (contratar); director (apretar) | Sí | Sí | **Compartida.** Ver abajo |
| 8.8 Responsables | D47: ver quién necesita ayuda | El jefe directo | — | Sí | D46: cada quien la suya, el jefe ve su equipo. Gerencia viéndola convierte la hoja en evaluación de desempeño y envenena la captura (trampa 2 del método). **Es la hoja que decide entre la salida 1 y la 2** |

Resultado: **gerencia, cuatro hojas** (Resumen, Programa General, Curva S, Proveedores). **Obra,
siete** (Programa General, Intermedia, Semanal, Curva S, Plan de Compras, Proveedores, Responsables).
**Tres compartidas.** Las ocho de D11 viven, cada una en al menos un lienzo; ninguna se apaga.

La asimetría no es un error: gerencia decide pocas cosas, grandes y semanales; la obra decide muchas,
chicas y diarias. Un lienzo de gerencia con siete hojas sería el museo de métricas que la sección 0
del método prohíbe.

## Curva S y Proveedores

Son la objeción de la sección 18 y la respuesta es la misma para las dos: **una hoja, un diseño,
montada en los dos lienzos, sin modo por audiencia.** Lo que cambia entre audiencias no es la
pantalla sino el **alcance de datos**, y eso ya lo resuelve el filtro de proyecto:

- **Curva S.** Gerencia la abre multi-obra para rendir; el director la abre en su obra para ver si la
  brecha pasó el umbral de D40 y hay que replanificar. Es el mismo lienzo de D41 —teórica, real,
  proyección con banda— con `supports_multi_project` decidiendo si se ve una curva o varias. El
  umbral de replanificación (abierto 18.2) es un dato del catálogo, no una rama de código. Lo único
  que no se comparte es el **aterrizaje**: nadie aterriza en Curva S; se llega desde el Resumen
  (gerencia) o desde Programa General (obra).
- **Proveedores.** «A quién no volver a contratar» (gerencia, comité) y «a quién apretar esta
  semana» (director) son dos acciones sobre **la misma tabla ordenada por la misma calificación**.
  La diferencia es la ventana: gerencia mira el histórico del contratista entre obras; el director,
  la semana en curso en su obra. Eso es un filtro de período más el de proyecto, no dos hojas. La
  regla de D44 (el integral no se publica con cuatro componentes vacíos) aplica igual para los dos, y
  hacerla dos veces sería el primer caso de duplicación que el mes cuatro cobraría.

Si en la práctica una de las dos audiencias pide algo que el filtro no resuelve, la respuesta es un
**desglose** nuevo dentro de la hoja, no una copia de la hoja. Eso es lo que la spec debe dejar
escrito para que no se decida a las carreras.

## Qué se rompe si Felipe falta dos semanas

La spec no lo trata y el riesgo «tres lienzos con una sola persona» de la sección 16 apunta a la
mitigación equivocada: colapsar lienzos no baja el bus factor, porque el bus factor no está en los
lienzos. Está aquí:

| Qué | Se rompe | Sigue andando |
|---|---|---|
| **La app y la Torre en producción** | — | Sí. No dependen de nadie mientras nada cambie. El interruptor de la Torre (desplegado el 2026-08-20) permite apagarla desde Admin sin tocar código, y Power BI sigue vivo hasta F5 |
| **Un cambio de métrica** (fórmula, corte, denominador) | Hoy sí: vive como SQL en un servicio de 3.693 líneas que solo Felipe lee | Con la sección 6 cumplida, es editar una declaración de catálogo. Es la mitigación más grande del replanteo y no está nombrada como tal |
| **Un dato mal calculado que alguien ve en comité** | Sí: nadie más sabe distinguir si es el dato, la vista o el catálogo | La trazabilidad de 6.3 («de dónde sale esto») le da a quien mire una pista sin leer código. Sirve más como documentación viva que como función de usuario |
| **El correo de F4** | Si falla, nadie lo arregla y nadie lo nota: la señal deja de llegar en silencio | Empezar a mano, como manda la sección 4 del método, tiene aquí un segundo valor: una ausencia de dos semanas se nota porque el correo no llegó |
| **Deploy de un arreglo urgente** | Sí, si nadie más lo ha hecho nunca (supuesto 4) | `docs/siteground-deploy-routine.md` existe; lo que no existe es alguien que la haya seguido |
| **Power BI retirado (F5) y la Torre caída** | **Sin vuelta atrás.** Es el único escenario en que la ausencia cuesta datos de la operación | Mientras F5 no se ejecute, el informe viejo es el plan B |

De ahí salen tres condiciones que la spec no tiene y debería:

1. **F5 no se ejecuta hasta que una persona distinta a Felipe haya hecho, de punta a punta y
   documentado, un deploy y un cambio de métrica por catálogo.** No es burocracia: es la prueba de que
   existe plan B antes de quemar el viejo.
2. **T1 se extiende al servicio**: una clase por `report_key`. No por los lienzos sino porque un
   archivo de 3.693 líneas no lo lee nadie en dos semanas de ausencia.
3. **Un runbook de una página por hoja** —qué decide, de dónde salen sus cifras, qué apagar si falla—
   que se escribe al cerrar la fase que construye la hoja, no al final. El interruptor ya existe; lo
   que falta es que alguien sepa cuándo usarlo.

Y una honestidad más: **dos lienzos o uno no cambia nada de esta tabla.** El bus factor de la Torre
es el mismo con un lienzo que con tres. Por eso la salida 1 se puede tomar sin culpa.

## Ajustes propuestos a la spec

| Sección | Cambio concreto | Por qué |
|---|---|---|
| **18, punto 1** | Reemplazar el punto completo por la redacción de abajo y pasarlo a «resuelto» | Es lo que desbloquea la planificación de F1 en adelante |
| **5 (Alcance)** o nueva **5.x «Lienzos»** | Definir lienzo como **composición**: puerta de entrada por rol, hoja de aterrizaje y lista de hojas montadas. La hoja es la unidad de construcción; el lienzo, de navegación. Incluir la tabla del reparto (gerencia 4, obra 7, compartidas 3) | Sin la definición, D52 y D11 vuelven a chocar en la siguiente spec derivada |
| **8 (cabecera)** | Añadir a la declaración de cada hoja un campo «**Lienzos:** gerencia · obra · ambos» y «**Aterrizaje de:** …» | Hace explícito lo que hoy se infiere del dueño |
| **8.5 y 8.7** | Añadir: «Montada en los dos lienzos **sin variante por audiencia**. El alcance lo decide el filtro de proyecto y período. Una necesidad nueva de una audiencia es un desglose, no una copia» | Es el único punto donde el mes cuatro cobraría duplicación |
| **8.8** | Añadir: «No se monta en el lienzo de gerencia (D46, D47)» | Es la hoja que descarta la puerta de entrada compartida; conviene que quede escrito el porqué |
| **12 (Diseño visual)**, último punto | «Un lienzo por audiencia (D52): gerencia y obra, **entendidos como composiciones de hojas compartidas, no como productos aparte**. Regla dura: una hoja, un diseño» | Alinea la regla visual con la definición |
| **13, F1** | Añadir a T1: «**y su gemelo en el servicio**: una clase por `report_key` detrás de una interfaz común» | Bus factor; y hace que F2–F5 toquen un archivo por hoja, no uno de 3.693 líneas |
| **13, F5** | Añadir condición de hecho: «**y una persona distinta a Felipe ha hecho, documentado, un deploy y un cambio de métrica por catálogo**» | Sin plan B humano no se retira el plan B técnico |
| **16 (Riesgos)**, fila «Tres lienzos con una sola persona detrás» | Reemplazar la mitigación por: «Medido el 2026-08-20: el segundo lienzo cuesta ≈5 % del primero porque la unidad es la hoja. El riesgo real es el bus factor, tratado en 16.x» y añadir fila «**Felipe ausente dos semanas**» con las tres condiciones de arriba | La mitigación actual (colapsar lienzos) no reduce el riesgo que dice mitigar |
| **17 (Lo que no se construye)** | Añadir: «Variantes por audiencia dentro de una hoja compartida» | Es la única forma de duplicación que esta arquitectura permite por descuido |

### Redacción exacta que reemplaza la sección 18, punto 1

> 1. **Resuelto el 2026-08-20 (ver [[2026-08-20-sostenibilidad-lienzos]]).** D52 y D11 no chocan:
>    un **lienzo** es una composición —puerta de entrada por rol, hoja de aterrizaje y lista de hojas
>    montadas—, y la **hoja** es la unidad de construcción. Gerencia monta cuatro hojas (Resumen
>    Ejecutivo como aterrizaje, Programa General, Curva S, Proveedores); obra monta siete
>    (Programación Intermedia como aterrizaje, Programa General, Semanal, Curva S, Plan de Compras,
>    Proveedores, Responsables). Las ocho hojas de D11 viven, cada una en al menos un lienzo.
>    Programa General, Curva S y Proveedores se construyen **una vez** y se montan en los dos, **sin
>    variante por audiencia**: el alcance lo deciden los filtros de proyecto y período, y una
>    necesidad nueva de una audiencia es un desglose, no una copia. Responsables no se monta en
>    gerencia (D46, D47). Medido contra el código, el segundo lienzo cuesta cerca del 5 % del
>    primero; lo que triplicaría el costo —y queda prohibido en 17— es la variante por audiencia
>    dentro de una hoja compartida. El costo que D52 advertía no está en los lienzos sino en el bus
>    factor, que se trata en 16.

## Lo que este reporte no hizo

- No habló con Felipe: los cuatro supuestos de arriba están sin confirmar.
- No midió tiempo de desarrollo en horas; midió líneas, funciones y puntos de despacho. Es una
  medida de mantenimiento, no de construcción.
- No refactorizó nada. `bi-spa.js` sigue en 4.199 líneas y el servicio en 3.693.

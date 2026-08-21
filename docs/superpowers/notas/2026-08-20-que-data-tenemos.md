---
capa: fuente
tipo: nota-de-trabajo
estado: vigente
fecha: 2026-08-20
areas: [bi, datos, pdc]
fuente: medición directa contra la base de desarrollo y contra producción, 2026-08-20
resumen: "Qué data existe de verdad para sostener la Control Tower: llenado real por campo, las dos generaciones del plan de compras, y dos errores de medición corregidos"
project: lps-aia
---

# Qué data tenemos de verdad

> Medido el 2026-08-20 contra `lastplanneraia_dev` (local) y contra producción por SSH, en solo
> lectura. **No se trajo ninguna copia de producción.** Los porcentajes de llenado son señal
> confiable de qué campos se usan; los conteos absolutos de dev pueden diferir de producción.

## Dos errores de medición, corregidos

Quedan escritos porque son el error que un tablero puede cometer en grande.

1. **Conté los ceros como campos vacíos.** Filtrar `PAC <> ''` borra de la cuenta todos los
   compromisos incumplidos —que son el dato que importa— y hace parecer que el campo no se registra.
2. **Usé el denominador equivocado.** El PAC solo aplica a actividades **comprometidas**; la causa de
   no cumplimiento, solo a las comprometidas **que no se cumplieron**. Medido sobre el total del
   programa, una captura sana parece abandono.

Corrección de Felipe. Con el denominador correcto, la conclusión se invierte.

## La captura está sana

| Medición | Resultado |
|---|---|
| PAC registrado sobre comprometidas | **92,3%** (2.230 de 2.416) |
| Causa de no cumplimiento sobre comprometidas incumplidas | **89,4%** (862 de 964) |
| Causa de no programación sobre no comprometidas | **90,1%** (2.970 de 3.297) |
| Responsable AIA en el programa | 91,4% |
| Subcontratista en el programa | 80,3% |

**No hay problema de captura en programación semanal.** La hipótesis de «primero arreglar la
captura» queda descartada para este módulo.

## Volumen disponible (base de desarrollo)

| Objeto | Filas | Nota |
|---|---|---|
| `programa_consolidado` | 65.633 | 6 obras con historia real; la mayor con 28 semanas |
| `bi_pi_restricciones` | 258.807 | vista: 5 restricciones duras por actividad-semana |
| `programacion_semanal` / `bi_ps_compromisos` | 5.713 | |
| `bi_riesgos` | 51.583 | 36 proyectos |
| `bi_curva_s_duracion` | 176 | 36 proyectos |
| `bi_cic_contratistas` | 323 | |
| `bi_cip_responsables` | **1** | Ver «defectos detectados» |
| `pi_shared_constraints` / links | 163 / 4.274 | |

## El análisis de restricciones: el cero es el hallazgo

Patrón por actividad-semana, sobre las cinco restricciones duras (45.600 actividades-semana):

| Patrón | Cantidad | % |
|---|---|---|
| **Ninguna tocada** | 31.396 | **68,9%** |
| Mixto (análisis real) | 9.182 | 20,1% |
| Todas listas | 5.022 | 11,0% |

**El patrón mixto demuestra que el cero significa «no se analizó», no «no aplica»**: cuando alguien
abre el análisis, marca unas restricciones y deja otras. Si el cero fuera «no aplica», lo mixto sería
la norma.

### La prueba predictiva, que salió débil

| Grupo | Compromisos | PAC |
|---|---|---|
| Actividades **sin analizar** | 459 | 53,2% |
| Actividades **analizadas** | 1.601 | 57,5% |

Cuatro puntos de diferencia, en la dirección esperada, sobre una muestra parcial.
**No alcanza para sostener en comité que analizar restricciones sube el cumplimiento.**

Por eso el cero entra a la Torre **como adherencia al método** —«el 69% de las actividades entró a la
semana sin análisis de restricciones»— que se sostiene sola y no necesita correlación, y **la lectura
predictiva se rotula aparte, como estimación con su nivel de certeza** (decisión de Felipe).

## El plan de compras: dos generaciones, obras distintas

### Modelo nuevo (PDC v2) — Da Porto está avanzado

Proyecto 73 · Da Porto, en producción:

| Objeto | Filas |
|---|---|
| `pdc_presupuesto_items` | 523 |
| `pdc_insumo_paquete` | 278 insumos en **58 paquetes de contratación** |
| `pdc_insumo_actividades` | 820 |
| `pdc_insumo_vinculos` | 396 |

**Los 278 están confirmados a mano**, 208 por decisión humana directa, con nombre y fecha del
2026-08-19. Es trabajo real y reciente.

**Lo que no existe en ninguna obra:** `pdc_plan_paquete`, `pdc_plan_paso`, `pdc_subpaquete`,
`pdc_paquete_frente`, `pdc_proyecto_pasos` — todas en cero. **El v2 llegó a empaquetar insumos y se
detuvo antes de ponerles calendario.**

### Modelo viejo (PDC v1) — 409 planes vivos y huérfanos

La tabla `pdc` tiene **409 planes completos** en tres obras: Prueba (27, 136), Optimización
Aeropuerto JMC (68, 162) y Milán Campestre Torre 19 (74, 111). **Da Porto no está.**

Trae lo que al v2 le falta: fechas planeadas y reales de cada paso (recibo de propuestas, cuadros
comparativos, legalización, fabricación, insumos en obra, inicio), proveedor adjudicado, número de
contrato, pólizas, y valores de presupuesto, primera negociación, adjudicado, anticipo, reclamado y
devoluciones.

**El código del v1 se eliminó del repositorio el 2026-08-04.** Esos datos están vivos en producción
y sin aplicación que los lea. Catálogos asociados que siguen ahí: `general_paquetes_contratacion`
(221 paquetes tipo), `general_pasos_contratacion` (9 pasos), `general_pdc_familias` (83).

**Ninguna obra tiene las dos generaciones.**

## Proveedores: el integral se publica sin sus componentes

Sobre 323 filas de `bi_cic_contratistas`, cuántas tienen valor mayor que cero:

| Componente | Peso declarado | Con valor |
|---|---|---|
| PAC | 30% | 229 (71%) |
| Calidad | 20% | **5 (1,5%)** |
| Social-Ambiental | 20% | **23 (7%)** |
| SST | 20% | **6 (1,9%)** |
| Administración | 10% | **6 (1,9%)** |
| **Cal_Integral** | — | **171 (53%)** |

Confirma D44 con datos: **el integral se está calculando y publicando hoy con cuatro de sus cinco
componentes en cero.** Es, en la práctica, el PAC con otro nombre.

## El catálogo de causas sí atribuye responsabilidad

Lo que en Power BI parecían duplicados son tres causas distintas:

| Causa | Veces |
|---|---|
| Actividad predecesora incompleta / no ejecutada **(obra)** | 502 |
| Actividad predecesora incompleta / no ejecutada **(subcontratista)** | 297 |
| Actividad predecesora incompleta / no ejecutada *(sin atribuir)* | 224 |

**La gráfica trunca el texto justo donde está la atribución de culpa.** El dato más político del
tablero existe y la visualización lo borra. La tercera variante, sin sufijo, sí es deuda de catálogo.

## Defectos detectados, para verificar

1. **`bi_cip_responsables` devuelve 1 fila** mientras `Responsable_AIA` está lleno en 5.223 filas del
   programa. Apunta a defecto de la vista, no a falta de datos.
2. **`medir_productividad` está en 0% de llenado** y el radar pinta un eje de Productividad. O sale de
   otra fuente —y entonces el catálogo miente sobre su propio origen, justo lo que D5 viene a matar—
   o el eje muestra algo que no es lo que dice.
3. **Campos muertos:** `Categoria_CP`, `CP`, `alerta_crisis`, `reprogramaciones_semanales`, todos en
   0%. Decidir si se retiran o si alguien debería estar llenándolos.
4. **Mojibake en los datos**: aparecen «Diseńos» y «Programación» mal codificados en el catálogo de
   causas.

## Valor ganado: lo que el dato sostiene

`programa_consolidado` **no tiene columna de valor, precio ni peso**; `cantidad_ppto` está en 223
filas. **No se puede ponderar el avance por plata desde el programa.**

El presupuesto existe (`pdc_presupuesto_items`) pero solo en **dos proyectos**: 27 y 73, 523 ítems
cada uno. La tabla `pdc` del modelo viejo sí trae valores adjudicados, para sus tres obras.

Esto acota D27 más de lo que la spec suponía: el desempeño de cronograma en plata **solo es
calculable donde hay presupuesto cargado**, hoy dos obras, y cruzando insumos con actividades.

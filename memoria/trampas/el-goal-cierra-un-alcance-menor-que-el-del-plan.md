---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-25
areas: [proceso]
tags: [leer-antes-de-tocar]
fuente: "frente «el estado real de los 127 planes y specs», 2026-08-25; goals/biblia-t5-lectura/goal.md, docs/superpowers/plans/2026-08-04-biblia-t5-lectura.md"
resumen: "Un `goal.md` con `## Cierre` puede estar cerrando menos de lo que su plan mandaba, y el cruce por slug no ve la diferencia"
---

# El goal cierra, pero cierra un alcance menor que el del plan

**El síntoma.** Cruzas los planes con los goals por slug, encuentras que el goal tiene `## Cierre`
con contenido, y concluyes que el plan está hecho. El goal dice **HECHO** y trae su tabla de
verificación con salida real.

**Lo que parece.** Que el trabajo del plan se completó y alguien lo firmó.

**Lo que es.** Que se completó **el alcance del goal**, que puede ser más pequeño que el del plan.
El goal es la promesa que la sesión ejecutora se hizo a sí misma; el plan es lo que se había
diseñado. Cuando divergen, el goal gana en silencio: es el que se escribe al final.

**El caso medido.** `biblia-t5-lectura` tiene el goal cerrado como HECHO y su **Task 3 entera sin
ejecutar** — manda crear `e2e/tests/biblia/lectura.spec.mjs`, y el archivo no existe mientras sus
cuatro hermanos de otras tandas (`cascada-lps`, `pdc`, `soporte`, `transversal`) sí. Lo agravan
dos detalles:

1. El plan **preveía** que la prueba pudiera no escribirse —si `DEV_DOOR_USERS` no tiene cuenta
   con rol restringido— pero exigía entonces «documenta esa limitación en el spec y en el
   documento de escenarios». `docs/flujos/lectura-bi.md` lista cuatro pendientes y **ninguno es
   este**. La salida autorizada no se tomó.
2. El goal justifica la ausencia con una razón que el plan no dice: «este goal no exigía prueba
   ejecutable propia». El plan sí la exige — es una Task completa.

**Y no era un caso aislado, que es lo que lo hace trampa.** Al verificar los 127 documentos
aparecieron las tres tandas de la biblia con el mismo defecto:

| Tanda | El plan manda | Existe |
|---|---|---|
| `biblia-t3` | 3 documentos de `docs/flujos/` + prueba | 1 (`compras-v2.md`) |
| `biblia-t4` | 5 documentos | 1 (`soporte.md`), escalamientos entero sin hacer |
| `biblia-t5` | 2 documentos + prueba | 1 (`lectura-bi.md`), sin prueba |

**Cómo se sale.** No preguntes «¿el goal está cerrado?». Pregunta **«¿queda alguna Task entera sin
ejecutar?»**, y compruébalo contra el árbol:

```bash
# los artefactos que el plan manda crear, ¿existen?
grep -n 'Create:' <plan>.md | sed -E 's/.*`([^`]+)`.*/\1/' | while read -r f; do
  test -e "$f" && echo "OK    $f" || echo "FALTA $f"
done
```

Un archivo que falta no siempre es un fallo —consolidar dos documentos en uno es una decisión de
alcance legítima—, pero **una Task entera sin rastro sí lo es**, salvo que esté escrito por qué.

**El contraste que fija la regla.** `control-tower-f0-higiene-datos` también dejó una Task sin
ejecutar (el retiro de tablas del PDC v1) y **sí cierra**: su plan admitía esa salida —«las tablas
están retiradas, **o hay constancia escrita de por qué no**», línea 526— y la constancia existe,
en `docs/superpowers/notas/2026-08-20-retiro-pdc-v1.md:127-131`, con decisión de Felipe.

> **La diferencia entre los dos no es cuánto se hizo: es si quedó escrito por qué no se hizo el
> resto.** Un plan puede cerrar con trabajo pendiente. Lo que no puede es cerrar callándolo.

**Cuánto costó.** Nada de golpe, y ahí está el problema: costó **la fiabilidad de un atajo**. La
señal «goal cerrado ⇒ plan hecho» cubría 33 de 127 documentos y habría ahorrado la cuarta parte
del frente. Validada en 5 casos al azar, acertó 4. Aplicada a los 33 con esa tasa habría escrito
unas **seis afirmaciones falsas de `cerrado`**, que es una afirmación fuerte y no un cajón de
sastre. Se degradó a indicio —sirve para decidir a quién mirar, no para sellar— y el lote manual
pasó de 92 a 127.

**La regla que sobrevive al caso.** Una señal automática puede decidir **a quién mirar**, nunca
**qué escribir**. Y una señal se valida en muestra **antes** de confiarle un lote — y si falla,
se degrada, no se ajusta para que acierte: elegir el criterio por su resultado es fabricar la
evidencia por otro camino.

Relacionadas: [[el-tipo-de-una-fuente-lo-dedujo-un-script]] · [[el-trabajo-hecho-no-vuelve-solo-al-documento]] · [[condicion-de-hecho-caduca-sin-aviso]]

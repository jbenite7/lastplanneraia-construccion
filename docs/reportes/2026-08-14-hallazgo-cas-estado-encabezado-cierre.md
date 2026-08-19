---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-14
areas: [proceso]
fuente: docs/reportes/2026-08-14-hallazgo-cas-estado-encabezado-cierre.md
resumen: Hallazgo para coordinating-agent-sessions: cas-estado.sh no ve los cierres de otro repo
---

# Hallazgo para `coordinating-agent-sessions`: `cas-estado.sh` no ve los cierres de otro repo

**Origen:** sesión de `lps-aia`, 2026-08-14. Medido sobre `lps-aia@ef49b6a0` con el script en
`coordinating-agent-sessions@f5c069d`.
**Estado allá:** no registrado. Comprobado que ni `decisiones/` ni los `goals/` de ese repo
mencionan la variante del encabezado.
**Para qué sirve este archivo:** que quien lo arregle no tenga que volver a buscar la causa. Está
escrito para copiarse tal cual a la cola de ese repo.

## El síntoma

`cas-estado.sh`, corrido contra `lps-aia`, devuelve **RC=0** y lista **42** frentes: **4** con dato
real y **38** con «sin rama resoluble · ❓ no medible». De esas 38, **20 son metas cerradas** —y el
mapa las pinta **`ENCOLADA`**, que el propio script define como «hay contrato y ninguna sesión lo
está trabajando». Es lo contrario de lo que pasó, afirmado en verde.

## La causa, localizada

`cas_cierre()` resuelve el cierre así:

```sh
if cas_tiene_seccion "$g" '## Cierre'; then echo si; else echo no; fi
```

y `cas_tiene_seccion` (en `scripts/lib.sh`) compara con **igualdad exacta**:

```awk
$0 == s { hallada = 1 }
```

`lps-aia` escribe ese encabezado **con sufijo**. Reparto real de sus 42 metas:

| Encabezado | Metas |
|---|---|
| `## Cierre formal` | 19 |
| `## Cierre formal (2026-08-07)` | 1 |
| `## Cierre` | 2 |

## La correlación que cierra el caso

Las **2** metas con el encabezado literal —`ci-en-verde` y `css-presupuesto-57kb`— son
**exactamente las 2** que el mapa pinta `CERRADA`. 2 de 2, sin excepciones en ninguna dirección.

**Por qué no se había visto:** en `coordinating-agent-sessions` **24 de 24** metas usan el literal
exacto, así que allí la función acierta siempre. El instrumento está validado solo contra su propia
casa; en cuanto mira los goals de otro repo con otra costumbre de encabezado, **deja de ver los
cierres sin decir que dejó de verlos**.

## Arreglo propuesto, ya comprobado en aislado

Sustituir la igualdad por un prefijo. Medido sobre los 42 goals de `lps-aia`, sin tocar el otro
repo, ejecutando las dos variantes del `awk` por separado:

| Comparación | Cierres detectados |
|---|---|
| `$0 == s` (hoy) | 2 |
| `index($0, s) == 1` (propuesta) | 22 |

Quedarían **18** filas sin dato —metas sin sección de cierre y sin rama—, y eso es correcto: son
metas que nadie empezó. Lo que no es correcto es que salgan en verde.

**Cuidado al aplicarlo:** `cas_tiene_seccion` es compartida y la consumen otras derivaciones
(subió a `lib.sh` desde `cas-frente.sh` en `6316cda`). Un prefijo hace que `## Cierre` case también
con `## Cierre formal`, que es lo que se busca, pero conviene revisar si algún consumidor depende de
la exactitud para distinguir dos secciones cuyo nombre empieza igual.

## Orden recomendado: primero el código de salida, después el prefijo

El arreglo del prefijo **no debe ir primero**. Mientras `cas-estado.sh` devuelva `RC=0` sin poder
medir, cualquier arreglo posterior seguirá siendo **indistinguible de un fallo silencioso** —
incluido este. Un instrumento que no puede medir tiene que decirlo con un código de salida distinto
de cero; si no, el siguiente defecto se descubrirá igual de tarde y por casualidad.

## Lo que se descartó, y por qué

**Normalizar los encabezados de `lps-aia`** para que el script los vea. Sería doblar 20 archivos
fuente ante la rigidez de un instrumento. Y hay un argumento del propio script: un comentario de
`cas-estado.sh` (junto a la rama sin rama resoluble) ya deja escrito que una meta **cerrada** no
debe pintarse como encolada, citando el caso medido de `portabilidad`. La corrección le toca a quien
mide, no a lo medido.

## Cómo reproducirlo

```sh
cd <ruta-a-lps-aia>
bash <ruta-a-coordinating-agent-sessions>/scripts/cas-estado.sh; echo "RC=$?"
grep -h '^## Cierre' goals/*/goal.md | sort | uniq -c
```

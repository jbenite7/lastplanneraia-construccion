# Decisiones pendientes — frente semana-fija-visual

<!-- Una entrada por decisión, con estos campos:
**Qué se decide** · **Qué se midió** (con sha) · **Opciones reales** · **Recomendación** ·
**Qué quedó saltado** -->

## Cierre — el frente hizo lo que debía, y encontró más de lo que buscaba

- **Causa arreglada en el escenario, no en la imagen.** `POST /context/week` fija la semana; la
  ruta se verificó **aislada y antes** de construir encima (`Semana 3` → POST → `Semana 1`).
- **Diff explicado por entero**, y ninguno de los dos cambios era de este frente: `b647499d` (el
  botón pasa a acción primaria) y `db8a1e6b` (etiqueta en minúscula, del frente de vocabulario,
  que además corre los chips de su fila).
- **Goldens recapturados con aprobación explícita del usuario**, que vio antes/después/diff.
  Procedencia en `docs/design-system/manifests/programacion-intermedia.goldens.md`.

### Dos cosas que no se buscaban y quedan escritas

1. **El rojo permanente estaba tapando alarmas reales.** Esos dos cambios llevaban desde el
   2026-08-07 sin retratarse y nadie lo vio. La conclusión del usuario se queda corta: una alarma
   que suena siempre no solo se ignora, **oculta las que suenan debajo**.
2. **No vale cualquier mutación.** La primera —alargar «Seleccionar visibles»— falló a 1180×820
   (1649 px) y **pasó** a 1440×900, porque en ese ancho ese botón es el último de su fila y no
   arrastra nada detrás: se queda bajo `maxDiffPixels: 100`. Con una sola resolución en rojo se
   habría dado por demostrado algo que en la otra no lo estaba. La válida se hizo sobre una
   etiqueta de la leyenda, que arrastra su fila en ambos anchos: **2 failed**, 4031 px.

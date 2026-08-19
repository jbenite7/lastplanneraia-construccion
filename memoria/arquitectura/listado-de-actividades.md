---
capa: wiki
tipo: modulo
estado: derogada
fecha: 2026-08-03
areas: [pdc, arquitectura]
tags: [generado]
fuente: public/index.php
resumen: "Listado de Actividades (PDC v1): era el CRUD de actividades sobre el que se armaba el PDC v1; eliminado del repositorio el 2026-08-04"
---
# Listado de Actividades (PDC v1)

> [!warning] Derogada el 2026-08-04
> El PDC v1 se **eliminó del repositorio**: rutas, controladores, servicios, vistas, JS/CSS,
> pruebas y 18 tablas. Esta página se conserva como registro histórico, no describe código
> vivo. El sucesor es [[plan-de-compras]] (`/plan-compras`).


**Qué resolvía (histórico).** Era el CRUD de actividades del proyecto — la versión anterior al PDC
v2 de [[plan-de-compras]]. **Corregido el 2026-08-10:** el cuerpo seguía en presente pese al banner
de derogación de arriba; se eliminó junto con el resto del PDC v1 el 2026-08-04, y el PDC v2 no
depende de este código. Ver [[pdc]] y [[pdc-v2|docs/pdc-v2.md]] para el mapa completo de dónde
encaja cada versión.

**Dónde encaja.** En el flujo del Plan de Compras. Ver [[flujo-pdc]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

**Vaciado a mano el 2026-08-10** (patrón de `panel-admin.md`): el script no toca este bloque
porque el módulo `listado-de-actividades` ya no está declarado en
`scripts/wiki-arquitectura.modulos.mjs` — sus rutas se eliminaron con el PDC v1 y el generador no
tiene de dónde extraerlas. El bloque de abajo llevaba desde el 2026-08-04 listando rutas,
controladores, servicios y tablas que ya no existen, pese al banner de derogación en rojo arriba.

<!-- generado:inicio -->
### Rutas
_Ninguno: código eliminado con el PDC v1 el 2026-08-04._

### Controladores
_Ninguno: código eliminado con el PDC v1 el 2026-08-04._

### Servicios
_Ninguno: código eliminado con el PDC v1 el 2026-08-04._

### Tablas
_Ninguno: código eliminado con el PDC v1 el 2026-08-04._

### Quién puede
_Ninguno: código eliminado con el PDC v1 el 2026-08-04._
<!-- generado:fin -->

---
tipo: modulo
estado: derogada
fecha: 2026-08-03
areas: [pdc, arquitectura]
fuente: public/index.php
resumen: "Contratos (PDC v1): vinculaba actividades con paquetes de contratación usando el motor semi-auto; eliminado del repositorio el 2026-08-04"
---
# Contratos y definición semiautomática

> [!warning] Derogada el 2026-08-04
> El PDC v1 se **eliminó del repositorio**: rutas, controladores, servicios, vistas, JS/CSS,
> pruebas y 18 tablas. Esta página se conserva como registro histórico, no describe código
> vivo. El sucesor es [[plan-de-compras]] (`/plan-compras`).


**Qué resolvía (histórico).** Unía cada actividad del proyecto con su paquete de contratación
(Suministro, Mano de Obra o Suministro e Instalación) para saber quién era responsable de
comprarla o ejecutarla. Usaba el mismo motor semiautomático (`auto/preview`, `auto/apply`,
`auto/undo`) que [[listado-de-actividades]] y el PDC v1 — todo eliminado el 2026-08-04. **Corregido
el 2026-08-10:** el cuerpo seguía en presente pese al banner de derogación de arriba.

**Dónde encaja.** En el flujo del Plan de Compras. Ver [[flujo-pdc]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

**Nota del manifiesto (histórica, corregida el 2026-08-10).** Reparto de criterio que aplicaba
mientras el código existía: /api/pdc/auto/* se atribuía a Contratos por ser el contrato
auto/preview·apply·undo·feedback·metrics que define contratos; el resto de /api/pdc/* quedaba en
Listado de Actividades. Los dos archivos que citaba como verificación
(`src/Controllers/Api/PdcAutoGenerateController.php` y `src/Services/SemiAutoService.php`) **ya no
existen**: se eliminaron con el PDC v1 el 2026-08-04. No los uses como instrucción de
verificación.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

**Vaciado a mano el 2026-08-10** (patrón de `panel-admin.md`): el script no toca este bloque
porque el módulo `contratos` ya no está declarado en `scripts/wiki-arquitectura.modulos.mjs` — sus
rutas se eliminaron con el PDC v1 y el generador no tiene de dónde extraerlas. El bloque de abajo
llevaba desde el 2026-08-04 listando rutas, controladores, servicios y tablas que ya no existen,
pese al banner de derogación en rojo arriba.

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

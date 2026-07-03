# Contratos - matriz funcional de escenarios

Estado: contrato funcional aprobado para implementacion.
Alcance: `/contratos/` y su entrega hacia `/pdc/` cuando aplica.

Este documento fija los escenarios operativos antes de crear o modificar tests.

## 1. Contrato de alcance

Dentro de alcance:

- Definir modalidad contractual por actividad: `SI`, `MO`, `S`, `OC`.
- Definir hasta 5 paquetes por modalidad.
- Definir recursos asociados por paquete.
- Definir la cantidad de contratos por cada paquete.
- Guardar trazabilidad semanal de cambios relevantes.
- Entregar a `/pdc/` los datos que ese modulo ya sabe consumir para fechas, duraciones y recalculo.
- Respetar decisiones manuales frente a sugerencias semi-auto.

Fuera de alcance de `/contratos/`:

- Proveedores disponibles.
- Cambio de proveedor por calidad.
- Renombrar o mantener catalogos de proveedores.
- Adjudicacion, polizas, valores, negociaciones y seguimiento del proveedor. Eso vive en `/pdc/`.

## 2. Reglas aceptadas

- `SI` es exclusivo: si una actividad usa suministro e instalacion, no se combina con `MO`, `S` ni `OC`.
- `MO`, `S` y `OC` pueden combinarse.
- El usuario decide cuantas contrataciones se requieren por paquete; el motor no debe inventarlo.
- Si se llenan los 5 paquetes de una modalidad, la UI muestra alerta pequena de posible sobreplaneacion.
- Si falta familia confiable, el semi-auto no aplica solo; envia a revision.
- Si existe familia pero no paquete confiable, se revisa o se corrige manualmente; no se inventan paquetes de baja confianza.
- Si falta duracion contractual, la actividad queda incompleta y se abre submodal para definir las 7 duraciones.
- Cambios de fecha de inicio deben advertir impacto y dejar a `/pdc/` recalcular con su flujo existente.
- Una decision manual confirmada no puede ser reemplazada automaticamente por semi-auto.
- Undo semi-auto solo restaura campos de `/contratos/`: modalidad, paquetes, recursos, cantidades, confianza y marca de auto-definir.
- Roles sin permiso de edicion deben quedar bloqueados por UI y API.
- En proyectos de preconstruccion, `/contratos/` no debe estar disponible por menu, URL directa ni API.

## 3. Trazabilidad semanal

La trazabilidad requerida no es auditoria contractual completa. Es una bitacora para saber en que semana cambio algo.

Campos a trazar:

- `actividadInicio`.
- `fechaInicio`.
- `tipoContrato`.
- Paquetes por modalidad.
- Recursos por modalidad.
- Cantidad de contratos por paquete.
- Semana activa al momento del cambio.
- Usuario que ejecuta el cambio cuando este disponible en sesion.

Las validaciones de "mismo paquete en la misma semana" o "mismo paquete en semanas distintas" son tecnicas: deben probar aislamiento por semana y proyecto, pero no son escenarios funcionales del usuario.

## 4. Matriz de 20 escenarios

| # | Escenario | Resultado esperado |
|---:|---|---|
| 1 | Se requieren 2 contratos de mano de obra para estructura en concreto | En el paquete `MO` correspondiente se captura cantidad `2`; PDC recibe dos contratos asociados o el desglose equivalente que ya soporta |
| 2 | Una actividad requiere `MO` y `S` separados | La UI permite marcar `MO` y `S`, definir paquetes/recursos/cantidades por separado y guardar ambas modalidades |
| 3 | Una actividad requiere `SI` | `SI` bloquea `MO`, `S` y `OC`; al guardar se conserva solo `SI` y sus paquetes |
| 4 | Una actividad cambia de `SI` a `MO + S` | La UI advierte limpieza de campos incompatibles y guarda la nueva combinacion sin residuos de `SI` |
| 5 | Una actividad agrega `OC` sobre `MO` existente | `OC` se agrega sin borrar `MO`; cada paquete conserva su cantidad |
| 6 | Un paquete necesita mas de un contrato | El stepper permite entero mayor que 1 y se guarda por paquete, no como valor global de la actividad |
| 7 | Un paquete se deja sin cantidad | La UI normaliza a `1` cuando hay paquete, o lo marca incompleto si el campo queda invalido |
| 8 | El usuario cambia `actividadInicio` | Se registra semana del cambio y se actualiza el dato que alimenta fechas relacionadas |
| 9 | El usuario cambia `fechaInicio` | Se registra semana del cambio, se muestra aviso de impacto y `/pdc/` recalcula con su flujo existente |
| 10 | Falta duracion de una familia/paquete contractual | Se abre submodal de duraciones y no se deja como contrato completo hasta definir las 7 duraciones |
| 11 | Se definen las 7 duraciones faltantes | Se guardan en `general_dias_procesos_contratacion` y quedan disponibles para `/pdc/` |
| 12 | Semi-auto propone una modalidad con confianza alta | Puede mostrarse como lista para aplicar, sin saltarse la revision visual del asistente |
| 13 | Semi-auto no encuentra familia confiable | No aplica cambios; queda en revision/no recomendado |
| 14 | Semi-auto encuentra familia pero no paquete confiable | No inventa paquete; pide revision o correccion manual |
| 15 | El usuario ya habia confirmado manualmente una actividad | Semi-auto puede mostrar propuesta, pero no preselecciona ni sobreescribe la decision manual |
| 16 | El usuario limpia contratos de una actividad con dependencias externas | Se advierte el impacto y solo se limpian campos propios de `/contratos/` |
| 17 | Undo despues de aplicar semi-auto | Restaura modalidad, paquetes, recursos, cantidades, confianza y marca auto-definir previos |
| 18 | Usuario sin permiso intenta editar | Botones/campos quedan bloqueados y la API rechaza el guardado |
| 19 | Proyecto de preconstruccion intenta abrir `/contratos/` | No aparece en navegacion, URL directa queda bloqueada y APIs no devuelven flujo operativo |
| 20 | Usuario llena los 5 paquetes de una modalidad | Se permite guardar, pero se muestra alerta pequena de posible sobreplaneacion |

## 5. Evidencia minima esperada

- Tests PHP enfocados para guardado, permisos, trazabilidad y reglas de modalidad.
- Playwright para guardar, recargar y verificar cantidades por paquete.
- Caso de cambio de `fechaInicio` que confirme aviso y conexion con flujo existente de `/pdc/`.
- Snapshot antes/despues de datos para trazabilidad semanal.
- Captura o recording de la UI de cantidades y submodal de duraciones.

## 6. Brechas actuales a cerrar

- La UI actual tiene 5 filas por modalidad, pero no captura cantidad por paquete.
- El dato `numeroSubcontratos` es global por actividad y no alcanza para el escenario de cantidad por paquete.
- El guardado actual crea duraciones por defecto cuando faltan; el contrato funcional requiere submodal y decision explicita.
- La trazabilidad semanal de campos de `/contratos/` debe quedar consultable por semana.
- La proteccion de preconstruccion debe cubrir UI, URL directa y APIs.

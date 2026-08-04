# PDC v2 · Fase B2 (primera mitad) — Semáforos y look-ahead de contratación — Design

- **Fecha:** 2026-07-29
- **Ola:** 1 (lo que el comité comprometió antes del lanzamiento)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** Comité Semanal de Innovación del 2026-07-29 — petición explícita y primera en la lista
  de Tomás Trujillo. Transcripción en `goals/pdc-preparar-b1/evidence/comite-2026-07-29.md`.
- **Depende de:** B1 (`fecha_real` por paso, `SeguimientoService`) y responsable-como-usuario —
  **ambos ya en `main`** (`f7cef87`, `5ee2e49`, `9f2790c`, `a4d0c75`, `20260728_pdc_v2_responsable_usuario.sql`).
- **Estado:** **implementado y en `main`** (fila 1 del tablero, `60f8bfe`). La pestaña «Vencimientos» y el semáforo del plan existen; la clasificación vive en `SeguimientoService` y la consumen también B3 y el flujo de caja.

## Problema

El plan de compras ya sabe **cuándo debería** ocurrir cada paso de contratación de cada paquete (A4) y
**cuándo ocurrió** de verdad (B1). Nadie puede preguntarle todavía lo único que importa un lunes por la
mañana: **qué se me está venciendo**.

Textual del comité:

> «Yo quiero ver que está en elaboración de pliegos y es un poco como que está vencido […] Pliegos:
> paquetes vencidos, paquetes que tengo que tener a una semana, 2 semanas, 3 semanas, 6 semanas. O sea,
> los indicadores los quiero desde esos pasos previos.»

Y la respuesta que lo hace barato: «la información está, no es sino volverla reporte».

## Por qué esto ES la fase B2

El roadmap define B2 como «semáforos (al día / próximo a vencer / vencido) contra fechas derivadas». La
petición del comité es la misma regla —fecha programada contra hoy— presentada como lista de trabajo en
vez de como color en una celda. Se escribe **una sola vez**: un cálculo, dos presentaciones.

La otra mitad de B2 (re-matching al reprogramar) va en su propio spec:
`2026-07-29-rematching-reprogramacion-design.md`.

## Decisiones cerradas en el grilleo

| Decisión | Valor | Por qué |
|---|---|---|
| Dónde vive | **Pestaña nueva en el submódulo Seguimiento** | Reusa datos que ya están en pantalla; no toca BI. Llega dentro de la semana comprometida |
| La fila | **Un paso pendiente de un paquete** | Es literal lo que se pidió. Un paquete aparece varias veces si tiene varios pasos abiertos — y esconder eso es justo lo que oculta los atrasos |
| Cortes | **Vencido · 1 · 2 · 3 · 6 semanas** | Los que nombró el dueño del producto, sin inventar otros |
| Torre de Control | **Después, no ahora** | Se declara como fase B3 en su propio spec. La pestaña es el día 1 |
| Responsable | **Columna de primera clase** | Ya existe `responsable_user_id`; sin él el tablero no responde «quién» |

## Alcance

### Entra

- Pestaña **«Vencimientos»** dentro de Seguimiento, junto a las que ya existen.
- Una fila por **paso pendiente** (`pdc_plan_paso` con `fecha_real IS NULL`) de un paquete del proyecto
  activo, con: paquete · paso · fecha programada · responsable · días de desfase.
- Agrupación por **estado de vencimiento**, calculado contra la fecha de hoy:

  | Estado | Regla |
  |---|---|
  | Vencido | `fecha_fin < hoy` |
  | Vence en 1 semana | `hoy <= fecha_fin < hoy + 7d` |
  | Vence en 2 semanas | `hoy + 7d <= fecha_fin < hoy + 14d` |
  | Vence en 3 semanas | `hoy + 14d <= fecha_fin < hoy + 21d` |
  | Vence en 6 semanas | `hoy + 21d <= fecha_fin < hoy + 42d` |
  | Más adelante | `fecha_fin >= hoy + 42d` — se cuenta, no se lista |

- **Filtro por paso**: ver solo «elaboración de pliegos», solo «recepción de propuestas», etc. Es la forma
  en que se pidió («los indicadores los quiero desde esos pasos previos»).
- Filtro por **responsable**, incluido «los míos».
- El **semáforo en el plan**: la misma clasificación pintada en la vista de plan que ya existe, para que
  el color y la lista nunca puedan contradecirse.

### No entra

- Notificaciones, correos o recordatorios.
- Recalcular o reprogramar nada: el tablero **solo lee**.
- Torre de Control / Power BI (fase B3).
- Pasos ya cumplidos: un paso con `fecha_real` desaparece del tablero aunque se haya cumplido tarde. El
  atraso histórico se ve en Seguimiento, que es su sitio.

## Arquitectura

Sin tablas nuevas y sin migraciones.

- **Backend:** un método en `src/Services/Pdc/SeguimientoService.php` que devuelve los pasos pendientes
  del proyecto con su clasificación ya resuelta. La regla vive **en el servicio, no en la SPA**, para que
  la pestaña y el semáforo del plan no puedan divergir.
- **Endpoint:** `GET /plan-compras/api/seguimiento/vencimientos`, con los filtros como parámetros. RBAC:
  la misma capacidad de lectura que ya protege Seguimiento.
- **Frontend:** vista nueva en `pdc-app/`, dentro de la navegación de Seguimiento, con la tabla
  agrupada. Reusa la rejilla y los tokens que ya usan las otras pestañas — sin componentes nuevos ni
  colores propios.

**La fecha de hoy la pone el servidor**, no el navegador: dos usuarios en husos distintos deben ver el
mismo vencido.

## Condición de hecho

Medido en la app real contra Da Porto, consola sin errores:

1. La pestaña lista los pasos pendientes agrupados en los seis cortes, y los conteos por corte suman
   exactamente el total de pasos pendientes del proyecto.
2. Un paso con fecha programada de ayer aparece en **Vencido** con su desfase en días; al registrarle
   `fecha_real`, desaparece del tablero.
3. Filtrar por un paso concreto deja solo las filas de ese paso; filtrar por responsable, solo las suyas.
4. El color del semáforo en el plan coincide, paso a paso, con el corte del tablero.
5. Un usuario sin la capacidad de lectura de Seguimiento recibe 403 en el endpoint.
6. Regresión: Vitest, `npm run build`, PHPStan sin errores nuevos, y los e2e `pdc-v2-*` en verde.

## Riesgos

- **Los proyectos con muchos paquetes generan muchas filas.** Da Porto tiene 96 paquetes que generan
  proceso × hasta 9 pasos. El corte «más adelante» se cuenta pero no se lista precisamente por eso; si
  aun así la tabla pesa, se pagina — no se recorta en silencio.
- **Un paquete sin fechas programadas no puede vencer.** Los 25 paquetes sin `duracion_ref` quedarían
  invisibles en el tablero. Se resuelven en `2026-07-29-cierre-prelanzamiento-pdc-design.md`, y hasta
  entonces el tablero debe **decir cuántos paquetes no está mirando**, en vez de callarlo.

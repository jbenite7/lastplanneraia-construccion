# Objetivo — Resolver los 15 hallazgos de la revisión del módulo

Que el módulo de Plan de Compras deje de estorbar a quien lo usa: tablas que no recortan ni
esconden nada, decisiones que se pueden deshacer, y una navegación que no obliga a bajar rodando
para descubrir que había más.

## De dónde sale

De un recorrido del dueño del producto por el módulo en el navegador (2026-07-28, proyecto
Da Porto), que dejó **15 hallazgos** registrados en [`hallazgos.md`](hallazgos.md) sin resolver
ninguno sobre la marcha.

Van desde fricción menor —texto recortado, doble clic para editar— hasta dos vacíos serios:
**amarrar un paquete a un frente del cronograma era una decisión sin retorno**, y no había forma de
elegir qué versión del presupuesto rige.

## El entendimiento compartido

[`facts.md`](facts.md) — **30 hechos verificables**, 28 con prueba automática, agrupados por el plan
que los cumple.

**Tres se corrigieron después de explorar el código, porque sus premisas eran falsas:**

- **f11** — se pedía conservar el responsable al desamarrar. Al mirar el código apareció que
  **cambiar de frente ya lo borra hoy, en silencio**. El hecho ahora cubre las dos operaciones: es
  un arreglo de un fallo existente, no solo una función nueva.
- **f16** — se pedía bloquear el cambio de versión oficial si había trabajo hecho. Resultó que las
  asignaciones a paquete y el plan de fechas **no dependen de la versión**: sobreviven solos. Se
  cambió a avisar en vez de bloquear, porque bloquear protegería de un peligro inexistente.
- **f26** — se pedía que el módulo dejara de tener barra propia y usara la lateral. **No es
  implementable**: la barra lateral no admite anidamiento y las rutas con almohadilla no llegan al
  servidor. Se reescribió al patrón que ya usa Control Tower — una entrada en la lateral, pestañas
  dentro.

## Los planes

Partido en tres, en este orden:

1. **[Arreglos de tabla](plan-1-tablas.md)** — ajuste de línea, anchos al contenido, un clic para
   editar, señalar lo pendiente, un solo botón de propuestas, y decir qué hace «Recalcular».
   Todo visible, sin tocar la base. **Su primera tarea no es visible**: crear el sitio común de las
   tablas, porque hoy el tema está duplicado seis veces y la función de formatear dinero también,
   con divergencias reales.
2. **[Lo que falta poder hacer](plan-2-funcionalidad.md)** — desamarrar, cambiar de frente sin
   perder al responsable, elegir la versión oficial, selector de nivel, y los puentes desde el
   historial. Toca base de datos y reglas.
3. **[Rediseño de navegación](plan-3-navegacion.md)** — meter el módulo en el shell del sistema de
   diseño y acabar con las tablas apiladas. Es el más grande y va último porque toca todo lo demás.

## Condición de terminado

Los 30 hechos de `facts.md` cumplidos y verificados, con las suites en verde: PHP en 0 fallos,
Vitest en verde, el indicador del motor de paquetes intacto en 7 diferencias, y PHPStan nivel 6
sin errores.

Cada plan es entregable por separado: terminar el 1 ya mejora el módulo aunque el 2 y el 3 no
hayan empezado.

# PDC v2 — Subpaquetes: del paquete de preconstrucción al contrato real de la obra — Design

- **Fecha:** 2026-07-29
- **Ola:** 3 (lo grande)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** Comité del 2026-07-29 — la observación más de fondo de toda la reunión.
- **Estado:** aprobado en grilleo, pendiente de plan. **Requiere su propio grilleo antes de implementar:**
  cambia la forma del modelo de datos.

## Problema

El plan de compras se arma en preconstrucción y con paquetes grandes: 35 paquetes cubren el 86,8 % del
presupuesto de Da Porto. Eso es lo correcto para preconstrucción. Pero no es lo que la obra contrata.
Textual, mirando el paquete de pisos:

> «Aquí había porcelanato, porcelanato, tableta gres, porcelanato, cerámica blanca. Es muy probable que la
> obra no haga un solo subcontrato de suministro, porque posiblemente no son el mismo proveedor, no son la
> misma marca, no son el mismo estilo. Pero para efectos de un plan de compras de etapa de
> preconstrucción, a mí me sirve: son grandes paquetes de contratación.»

Y lo que debería permitir:

> «Que esto empiece a hablarle a la oficina técnica: este paquete que voy a contratar con estas fechas, si
> va esto, si va esto, este no, este crea el nuevo paquete de contratación […] y quien esté manipulando
> esto diga: eso lo puedo contratar en 2 meses, tírelo para dentro de 2 meses; o eso lo necesito ya.»

Hoy el paquete es plano: o se contrata entero o no existe.

## Decisiones cerradas en el grilleo

| Decisión | Valor |
|---|---|
| Forma | **Subpaquetes dentro del paquete grande.** El paquete sombrilla se conserva y por dentro tiene lotes |
| Quién manda | **El subpaquete.** Cada uno tiene su proceso de contratación y sus fechas; el grande **resume** rango y avance |
| El maestro | El paquete grande sigue viniendo del maestro global; los subpaquetes son **de esa obra** y no lo actualizan |

Descartadas: sacar insumos a paquetes nuevos independientes (rompe la lectura de preconstrucción en 35
paquetes) y duplicar el paquete (deja dos con el mismo nombre y ensucia el histórico del que aprende el
motor).

## Consecuencias, dichas de frente

Esto **no es una función más**: añade un nivel de jerarquía y toca casi todo lo construido.

- El plan de fechas pasa a calcularse por subpaquete.
- El tablero de vencimientos trabaja con subpaquetes — es donde de verdad se contrata.
- El seguimiento (fechas reales por paso) cuelga del subpaquete.
- La cobertura y los porcentajes tienen que decidir qué cuentan: paquetes o subpaquetes.
- El motor de sugerencias sigue trabajando a nivel de paquete grande; **no** aprende de subpaquetes,
  porque son casuística de obra.

Por eso lleva grilleo propio antes de implementarse, y por eso está en la Ola 3.

## Alcance

### Entra

- Un paquete puede partirse en **N subpaquetes**; los insumos del paquete se reparten entre ellos.
- Un paquete sin partir **sigue funcionando exactamente como hoy** — sin subpaquetes de una sola fila
  creados por compatibilidad. Cero regresión para las obras que no lo usen.
- Cada subpaquete: nombre, insumos, modalidad, fechas propias, responsable propio, proceso de
  contratación propio.
- El paquete sombrilla muestra el rango de fechas que abarcan sus subpaquetes y su avance agregado.
- Los insumos que no se asignan a ningún subpaquete se quedan en el paquete, que sigue siendo contratable.

### No entra

- Subpaquetes de subpaquetes.
- Que los subpaquetes suban al maestro global.
- Repartir un mismo insumo entre dos subpaquetes: un insumo, un destino — la regla que sostiene todo el
  módulo desde A3.

## Condición de hecho

1. Partir «Pisos» en tres subpaquetes por material, darle a cada uno su fecha, y ver los tres en el plan
   con sus fechas distintas.
2. El paquete sombrilla muestra el rango que abarcan y el avance agregado, y coincide con la suma.
3. El tablero de vencimientos lista subpaquetes, no el sombrilla, para los paquetes partidos.
4. Un proyecto sin ningún paquete partido produce **exactamente el mismo plan** que antes del cambio,
   comparado fila a fila.
5. Los porcentajes de cobertura siguen sumando 100 % y está escrito qué unidad cuentan.
6. Ningún subpaquete aparece en el catálogo global.
7. Regresión completa: Vitest, PHP, PHPStan, build y los e2e `pdc-v2-*`.

## Riesgos

- **El punto 4 es el que hay que defender.** Es fácil que un nivel de jerarquía nuevo cambie sutilmente
  los números de las obras que no lo usan. Se compara contra una captura del plan tomada antes.
- **La cobertura se vuelve ambigua**: ¿11 de 96 paquetes o 11 de 130 subpaquetes? Hay que elegir una y
  decirla en pantalla, no dejar que cada vista decida.
- **Es el cambio más grande que le queda al módulo.** Si la Ola 3 tiene que recortarse, este es el que
  debe empezar, no el que debe hacerse a medias.

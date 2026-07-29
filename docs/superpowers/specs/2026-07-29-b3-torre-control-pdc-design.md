# PDC v2 · Fase B3 — El plan de compras en la Torre de Control — Design

- **Fecha:** 2026-07-29
- **Ola:** 3 (lo grande)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** roadmap maestro (fase B3) + decisión del comité del 2026-07-29 de llevar los indicadores a
  BI **después** de tener la pestaña dentro del módulo.
- **Depende de:** `2026-07-29-b2-semaforos-lookahead-design.md` (la pestaña es el día 1; esto es el día 2).
- **Estado:** aprobado en grilleo, pendiente de plan. **Requiere grilleo propio:** el roadmap ya dice que
  su alcance exacto se especifica en su propio brainstorming.

## Problema

La pestaña de vencimientos de la Ola 1 responde «qué se me vence **en esta obra**». Falta la pregunta de
gerencia técnica: **cómo van todas las obras**. Esa es la Torre de Control, que ya existe en
`/bi/control-tower`.

## El obstáculo que hay que resolver primero

El BI actual va por **Power BI publish-to-web**: público, sin filtro por proyecto y sin API de JavaScript.
Está bien para indicadores agregados que no son sensibles; **no sirve** para datos de contratación por
obra, que dicen con quién se está negociando y por cuánto.

Por eso este spec no puede empezar por «qué gráficas». Tiene que empezar por **dónde vive el dato**:

- ¿Power BI Embedded (con identidad y filtro por proyecto), que estaba anotado como pendiente?
- ¿O indicadores servidos por la propia aplicación dentro de la Torre de Control, como ya se hace en otras
  partes del producto?

Esa decisión es la primera pregunta del grilleo de B3, y condiciona todo lo demás.

## Alcance previsto (a confirmar en su grilleo)

Los indicadores que el roadmap ya nombra, más lo que pidió el comité:

- **Cobertura de asignación** — cuánto del presupuesto tiene destino, por valor y por conteo. Los dos
  números juntos: cada uno cuenta media verdad.
- **Paquetes vencidos y en riesgo** por obra — el agregado del tablero de la Ola 1.
- **Avance de contratación** — cuántos paquetes han pasado cada paso.
- **Carga por responsable**, que en la pestaña se descartó como fila pero a nivel de gerencia sí es la
  pregunta correcta: quién está sobrecargado.

## Regla que hereda de la Ola 1

La clasificación de vencimiento **no se reimplementa aquí**. Vive en `SeguimientoService` y la Torre de
Control la consume. Dos definiciones de «vencido» en la misma empresa es peor que no tener ninguna.

## Condición de hecho (preliminar)

1. Está decidido y escrito dónde vive el dato, con el problema de privacidad resuelto explícitamente.
2. Ninguna obra ve datos de contratación de otra obra sin permiso, verificado con un rol permitido y uno
   denegado.
3. Los números del tablero por obra y los de la Torre de Control coinciden para la misma obra y el mismo
   día.
4. Los indicadores cargan con el volumen real de las obras activas.

## Riesgos

- **Publicar contratación en un tablero público sería un incidente**, no un bug. El punto 1 de la
  condición de hecho existe para que eso se decida antes de construir, no después de publicar.
- **Es la fase más fácil de adelantar mal.** Con la pestaña funcionando, la tentación es copiar consultas
  al BI y tener «algo» rápido. La regla del cálculo único está para impedirlo.

---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-02
areas: [rbac, bi]
fuente: goals/bi-control-tower-gemini/goal.md
resumen: Implementación y validación del dashboard de la Torre de Control BI (Programa General, Radar de Productividad/Eficiencia/PAC y Cronograma de Avance).
---

# Goal — BI Control Tower / Programa General (Radar y Cronograma)

**Slug:** `bi-control-tower-gemini`
**Estado:** BLOQUEADO — y **la causa escrita hasta el 2026-08-10 era incorrecta**. Decía «falta
aprobación visual», como si el bloqueo fuera falta de tiempo del usuario. La causa real es que **la
condición de hecho no se podía cumplir**: pide aprobar seis modos, tres de ellos del tema `linen`,
retirado del producto el 2026-07-25 por DS-030. Nadie puede aprobar capturas de un tema que ya no
existe. El goal llevaba mes y medio esperando algo imposible.

## Objetivo

Implementación y validación del dashboard de la Torre de Control BI (Programa General, Radar de
Productividad/Eficiencia/PAC y Cronograma de Avance).

## Condición de hecho

Aprobación visual explícita por parte del usuario de la matriz de 6 modos
(Mobile/Tablet/Desktop × Dark/Linen) e integración mediante commit atómico.

**Sigue vigente por decisión del usuario del 2026-08-10**, con una corrección: los tres modos
claros ya no son de `linen` sino del **tema claro nuevo** que construye la fase F3 del goal
[[goals/reapertura-movil-y-tema-claro/goal|reapertura-movil-y-tema-claro]].

Se le ofrecieron tres salidas —redefinir a tres tamaños en oscuro y aprobar hoy, reducir a solo
escritorio oscuro, o esperar— y eligió **esperar**. El motivo es sólido: la matriz de seis modos
existe porque este dashboard debe leerse en ambos temas, y aprobar solo la mitad convertiría el
control en un trámite. Las 24 capturas actuales, incluidas las de `linen`, quedan como
**procedencia histórica**: documentan cómo se veía, no avalan nada.

## Lo que falta

- **Bloqueado por dependencia, no por olvido:** el tema claro no existe. Se desbloquea cuando la
  fase F3 de `reapertura-movil-y-tema-claro` entregue la paleta clara y su conmutador.
- Recapturar los tres modos claros contra el tema nuevo, y revisar que los tres oscuros sigan
  vigentes tras los cambios acumulados desde julio.
- Entonces sí, aprobación visual de los seis y commit atómico.

**Lo que NO falta:** la evidencia automatizada está completa y en verde desde julio — 33 pruebas de
navegador, 101 de fuentes de datos, 17 de reconciliación, PHPStan y la suite estática. Este goal no
espera trabajo técnico; espera un tema que aún no se ha construido.

---

## Archivos de este goal

[[goals/bi-control-tower-gemini/validation-log|validation-log.md]]

Estado y relación con los demás goals: [[estado|Estado de los goals]].

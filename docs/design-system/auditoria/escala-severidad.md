---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-08-18
areas: [design-system]
fuente: docs/design-system/auditoria/escala-severidad.md
resumen: Esto no fija la escala del producto. Fijar vocabulario y escalas es DS-F1, y arranca con brainstorming con el usuario. Lo que declara este archivo es con qué…
---

# La escala con la que se clasifica aquí

**Esto no fija la escala del producto.** Fijar vocabulario y escalas es **DS-F1**, y arranca con
brainstorming con el usuario. Lo que declara este archivo es **con qué regla se está clasificando
en DS-F0**, para que el inventario sea reinterpretable después: si DS-F1 decide otra escala, se
puede remapear entrada por entrada porque cada hallazgo lleva escrito **por qué** cayó en su nivel,
no solo el nivel.

## Los cinco niveles

La cascada del entregable es «Crítico → Sin problema». Se numera 4→0 para que coincida con la
escala de Nielsen que ya usa `docs/DESIGN-AUDIT.md`, y así las 88 entradas de aquel registro entran
sin traducción.

| Nivel | Nombre | Qué lo define **en esta auditoría** |
|---|---|---|
| 4 | `critico` | Rompe una regla innegociable del contrato **en una superficie declarada `pilot`**, o deja el sistema sin poder detectarlo (un gate que mide otra cosa, un token que el CSS resuelve a nada), o rompe accesibilidad **nivel A** |
| 3 | `mayor` | Rompe una regla innegociable en superficie no migrada, o es deuda **estructural** que impide migrar: un vendor entero fuera del sistema, la cascada invertida, un archivo fuera de toda capa |
| 2 | `menor` | Desviación local **con equivalente ya existente** en el sistema (el token está, no se usa), o accesibilidad **AA de geometría** (target táctil, contraste al filo) |
| 1 | `cosmetico` | Inconsistencia sin efecto funcional ni de contraste: una tilde, un tooltip redundante, un estado vacío duplicado |
| 0 | `sin-problema` | **Medido y conforme.** Se registra a propósito: un inventario que solo lista deuda no dice dónde NO hay deuda, y esa mitad del mapa es la que DS-F1 necesita para saber qué ya funciona |

## Las tres reglas que evitan que la escala se estire

1. **La severidad la fija el contrato, no el esfuerzo de arreglarlo.** Un hallazgo trivial de
   reparar puede ser crítico, y uno costoso puede ser cosmético. El coste va en `salidaConocida`,
   nunca en el nivel.
2. **`pilot` sube un escalón.** La misma desviación es `critico` en un módulo que declara estar
   migrado y `mayor` en uno que declara no estarlo — porque en el primero, además del defecto, hay
   una declaración que no se cumple.
3. **Lo que no se pudo medir no baja de nivel: se marca.** Un hallazgo con
   `bloqueadoPor: "runtime-budgets-al-ci"` conserva su severidad estimada y dice que está
   estimada. Rebajarlo por no poder medirlo lo escondería.

## Lo que la escala deliberadamente NO decide

- **Qué se repara primero.** Eso es priorización y depende del contrato de DS-F1.
- **Qué es excepción legítima.** Un `!important` en un adaptador de vendor puede ser la única
  salida correcta; aquí se cuenta y se describe su contexto, y quién decide si es excepción es el
  contrato, no el auditor.
- **Cuánta deuda es aceptable.** No hay umbrales en este directorio.

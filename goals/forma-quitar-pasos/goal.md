---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/forma-quitar-pasos/goal.md
resumen: Andamiaje del frente forma-quitar-pasos: el goal.md se creo y su objetivo nunca se escribio.
---

# Frente: forma-quitar-pasos

## Objetivo
Reducir los pasos del recorrido del usuario en `/programacion-intermedia`, con la regla de que el
frente no cierra sin haber eliminado algo.

## Condición de hecho
<!-- qué comando, con qué salida, prueba que el frente terminó -->

## Archivos declarados
views/errors,public/index.php,docs/decisiones-pendientes.md

## Contención
<!-- archivo · commits hoy · quién más lo declara -->

## Cadena de herramientas
<!-- ids del arsenal, máx 8, una línea de porqué cada uno -->

## Cierre

**Cerrado como «medir y repartir»**, según la resolución de `D-FORMA-1` del 2026-08-11. Verificado
hoy:

```
$ ls -d goals/contadores-cero goals/vocabulario-estados-cascada
goals/contadores-cero  goals/vocabulario-estados-cascada
$ grep -rn 'allowedMethods' public/index.php views/errors/
(sin resultados — el codigo muerto se retiro)
```

Tres de los cuatro candidatos salieron a frentes propios con spec y plan. **El cuarto no se toca, y
ese es el hallazgo:** el backlog describía el selector de semana como «dos menús que rinden las
mismas semanas», el frente midió cuatro flyouts más el de cabecera, y **también se quedó corto** —
son **tres cosas distintas que se parecen por fuera**: la pastilla que dice *dónde estás*, un flyout
*por módulo* con las semanas de ese módulo, y el de *gestión*, que es el único con «+ Nueva semana».

**La regla «no cierra sin eliminar algo» estuvo a punto de causar daño.** Los cuatro candidatos eran
decisión de usuario o de navegación, y quitar «algo» solo para cumplirla habría convertido una
decisión de producto en un trámite. Se resolvió repartiendo, no sacrificando.

---
capa: wiki
tipo: decision
estado: vigente
fecha: 2026-10-01
areas: [deploy, proceso]
fuente: sesion
resumen: "lps-aia no se despliega a producción hasta terminar todos los specs y planes de la migración a React; cerrar un sprint o fusionar un PR no abre la pregunta del deploy"
---
**No hay despliegue a producción hasta que estén terminados todos los specs y planes de la migración
a React.** Felipe lo enunció en el chat el 2026-10-01, al cierre del sprint del SOS (PR #100), cuando
una sesión listó el deploy como «pendiente de tu decisión». La fecha original de la decisión es
pendiente: no estaba escrita en el repo antes de esta nota.

**Why:** el producto se publica entero, no módulo por módulo. Un deploy a mitad de la migración
llevaría a la obra un sistema mezclado entre pantallas React y legadas. Además, cada cierre que trata
el deploy como decisión abierta le cuesta a Felipe releer lo que ya decidió.

**How to apply:**
- Al cerrar un sprint o fusionar un PR, **no** menciones el deploy como pendiente ni como siguiente
  paso. Fusionar en `main` es el final del ciclo mientras dure la migración.
- La rutina de [[docs/siteground-deploy-routine]] y [[produccion-deploy]] siguen vigentes para cuando
  llegue el momento; esta nota solo dice **cuándo**, no cómo.
- Solo se reabre si Felipe lo saca, o cuando el último spec y el último plan de la migración a React
  estén cerrados. Que un arreglo urgente pida excepción es una decisión de Felipe, no de la sesión.

Vecinos: [[entorno-y-despliegue]] · [[repo-publico-por-ci]].

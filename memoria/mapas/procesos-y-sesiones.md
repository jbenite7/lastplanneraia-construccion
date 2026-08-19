---
capa: wiki
tipo: mapa
estado: vigente
fecha: 2026-08-19
areas: [proceso]
fuente: AGENTS.md §Publicación, las trampas del área
resumen: "Cómo se trabaja aquí: el gate de cierre de nueve pasos, la coordinación entre sesiones y las formas en que un verde miente"
---

# Mapa · Procesos y sesiones

## Qué manda

[[AGENTS|AGENTS.md]] §Publicación — **el gate de cierre de frente es bloqueante**: un frente no
está cerrado cuando su trabajo funciona, sino cuando funciona **y está en el remoto**.

## La idea que ordena el área

**Publicar es invocar `bash scripts/publicar.sh`**, no encadenar comandos. Un gate solo gobierna si
puede **impedir** la publicación, y encadenado detrás de la verificación ya se ejecutó. El script
existe porque esa regla vivía solo en la prosa y tres sesiones distintas la incumplieron en tres
jornadas seguidas — ver [[publicar-sh-el-gate-ejecutable]].

**El paso que más se salta y más caro sale es el 5: re-verificar DESPUÉS de integrar.** Traer
trabajo ajeno puede romper un verde propio sin tocar tu diff, y quien hizo el trabajo no lo ve.

## Dónde está el estado

- [[estado]] — el estado real de los goals, leído de cada `goal.md`.
- [[cola-de-pendientes]] — fuente única de pendientes.
- [[registro-de-trabajo]] — catálogo del trabajo fechado, spec con su plan.

## Trampas — las formas en que un verde miente

- [[el-codigo-de-salida-se-pierde-en-la-tuberia]] — encadenar convierte un gate en decoración.
- [[verificas-un-arbol-y-publicas-otro]] — el paso 5 y el 6 sobre árboles distintos.
- [[publicar-sh-se-aisla-y-se-rompe-en-la-raiz]] — el aislamiento dejó al gate sin contenedor.
- [[condicion-de-hecho-caduca-sin-aviso]] — dos goals bloqueados por una condición ya obsoleta.
- [[el-archivo-que-tocas-puede-tener-un-contrato]] — mejorar el CI puso un gate en rojo.
- [[baseline-de-una-muestra-congela-el-atipico]] — medir una vez y llamarlo línea base.
- [[el-contador-no-mide-el-archivo]] — el recuento sube y baja sin que cambie lo contado.
- [[mutar-el-supuesto-no-solo-las-entradas]] — una aserción que pasa las tres mutaciones y no mide.
- [[una-decision-escrita-no-llega-sola-al-codigo]] — decidir no es implementar.
- [[autoria-por-coincidencia-de-hora]] — la hora de actividad no prueba autoría.
- [[path-with-space-esm-guard-noop]] — el repo vive en una ruta con espacio.

## Vecinos

[[worktrees]] para el aislamiento · [[qa-y-gates]] para qué suite creer ·
[[design-system]] para el programa de cuatro fases.

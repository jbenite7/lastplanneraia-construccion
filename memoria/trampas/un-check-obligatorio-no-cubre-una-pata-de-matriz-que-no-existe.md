---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-09-29
areas: [qa, proceso]
fuente: "gh api repos/jbenite7/lastplanneraia-construccion/branches/main (protected: false) y .../branches/main/protection (Branch not protected), .../rules/branches/main ([]), consultados el 2026-09-29"
resumen: "Hoy main no exige ningún check, pero si algún día se activan, un job omitido cuenta como éxito y una pata de matriz que no se crea no existe: exigirla por nombre bloquearía los PR"
---
# Un check obligatorio no cubre una pata de matriz que no existe

**Estado hoy (medido el 2026-09-29).** `main` **no está protegido**: la API devuelve `protected: false`,
el endpoint de protección responde «Branch not protected» y la lista de reglas que aplican a la rama
está vacía. Esta trampa es latente: no hay ningún check obligatorio que se pueda romper.

**El síntoma, si se activaran.** Con el CI por carriles, la matriz de temas se calcula (`["light"]` o
`["light","dark"]`). Un PR que no toque CSS, vistas ni bundle no crea la pata `dark`. Un check
obligatorio llamado `design-system-runtime (dark)` se quedaría esperando un reporte que nunca llega.

**Lo que parece.** Que «omitido» y «no existe» son lo mismo. Un job que existe pero se omite por un
`if` cuenta como éxito para GitHub. Una pata de matriz que no se generó no existe, y GitHub no la puede
dar por pasada. Es el comportamiento documentado; no se reprodujo aquí porque no hay protección con la
que probarlo.

**Cómo se sale.** Si Felipe activa checks obligatorios, que exija el job `cambios` y **no** las patas de
la matriz por nombre. Y con `cambios` en rojo, `design-system-static` y `design-system-runtime` quedan
omitidos, que para GitHub cuenta como éxito; por eso `cambios` es el que debe ser obligatorio.

**Cuánto costó.** Nada todavía. Se anota porque la primera versión de esta comprobación, el 2026-09-28,
se hizo contra un nombre de repositorio equivocado y dio un 404 que se leyó como «sin evidencia»; ver
[[un-404-de-github-puede-ser-el-nombre-del-repo]].

Relacionadas: [[qa-y-gates]] · [[un-verde-caduca-y-a-veces-no-existe]] ·
[[docs/superpowers/specs/2026-09-28-ci-por-carriles-design|spec del CI por carriles]]

## Design System consumer gate

- [ ] La superficie y su manifiesto están declarados en `docs/design-system/manifests/`.
- [ ] El cambio consume tokens `--ds-*`/`--aia-*` y primitivas `aia-*`; no añade valores visuales locales.
- [ ] No se añadieron estilos inline, CDN, skins de vendors ni excepciones sin revisión.
- [ ] Se ejecutó `$impeccable audit <superficie>` y el detector correspondiente.
- [ ] Pasan contrato estático, Axe, consola/red, foco y targets de `44px`.
- [ ] Se adjunta QA visual desktop dark en `1180x820` y `1440x900`.
- [ ] La revisión manual de accesibilidad está documentada.
- [ ] Goldens solo se actualizaron con aprobación visual explícita.
- [ ] Los archivos protegidos de Programa General permanecen sin cambios.

## Cierre de frente (`AGENTS.md` §Publicación)

- [ ] **Verificación citada**: los comandos que prueban la condición de hecho y su salida real
  (RC en su propia línea) están en la descripción de este PR, no solo "pasa".
- [ ] **Spec actualizada**: si este frente tiene una spec en `docs/superpowers/specs/`, quedó
  actualizada en este mismo PR (condición de hecho, decisiones consumidas, lo que cambió de
  alcance). Un frente que no la actualizó no está cerrado.
- [ ] **Bitácora al día**: si este frente tiene un plan con `## Bitácora del piloto` (o
  equivalente) en `docs/superpowers/plans/`, sus paradas y decisiones están anotadas hasta el
  último commit de este PR, no solo hasta donde empezó la sesión.

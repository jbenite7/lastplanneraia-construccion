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

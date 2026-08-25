---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-24
areas: [deploy, proceso]
fuente: "Revisión de espacio-cuenta-siteground el 2026-08-24; error publicado en 0a79d905 y corregido el mismo día"
resumen: "Grepear los `Host` de ~/.ssh/config sin resolver sus Include da un negativo falso: los alias de SiteGround existen y viven en un archivo incluido"
---
# Grepear los `Host` de un `ssh_config` no resuelve sus `Include`

**El síntoma.** Se comprueba si existe un alias SSH y sale que no:

```
$ grep -i "^Host " ~/.ssh/config
Host *
```

Un solo `Host *` y nada más. La lectura obvia: no hay alias configurados en esta máquina.

**Lo que parece.** Que la configuración se perdió — y en este repo hay una explicación a mano que
encaja demasiado bien: la mudanza del 2026-08-18 dejó otros rastros iguales, como el `.env`
enlazado a una ruta del disco viejo que documenta `CLAUDE.md`. La conclusión falsa llega envuelta
en una causa plausible.

**Lo que es.** `~/.ssh/config` empieza con **doce líneas `Include`**, y los alias viven en uno de
los archivos incluidos:

```
Include /Volumes/Crucial X6/Developer/colima/data/ssh_config    (×12, duplicadas)
Include ~/.ssh/config.d/recovered-aliases                        ← aquí están
Host *
    UserKnownHostsFile ...
```

En `~/.ssh/config.d/recovered-aliases` están los cinco: `siteground-produccion-lastplanner`,
`siteground-pruebas-lastplanner`, `siteground-pruebas`, `siteground-produccion` y
`hetzner-vps-openclaw`. Las dos llaves que declaran (`lps_siteground_deploy`,
`siteground_pruebas_id_ed25519`) existen, y la conexión funciona.

**Cómo se sale.** No greper la lista: **preguntarle a `ssh`**, que sí resuelve los `Include`.

```bash
ssh -G siteground-pruebas-lastplanner | head -3   # hostname/user/port resueltos
ssh -o BatchMode=yes -o ConnectTimeout=12 siteground-pruebas-lastplanner 'echo CONECTA'
```

`ssh -G` imprime la configuración efectiva para ese destino sin conectarse, y `BatchMode=yes` evita
que una prueba se quede colgada pidiendo passphrase. Si de verdad hace falta leer los archivos,
resuélvelos antes: `grep -h "^Host " ~/.ssh/config ~/.ssh/config.d/* 2>/dev/null`.

**Cuánto costó.** El 2026-08-24 esta lectura produjo una conclusión falsa —«no hay acceso al
servidor, los frentes C y D son imposibles desde aquí»— que **llegó a publicarse en `main`**
(`0a79d905`) en tres documentos. La desmintió Felipe en una línea: «busca los aliases, que ya
existen». Al conectarse resultó que **dos de los tres frentes que se daban por bloqueados ya
estaban hechos**: el D ejecutado en producción y el B verificable en vivo. La corrección está en el
`## Estado medido` de
[[docs/superpowers/specs/2026-08-18-espacio-cuenta-siteground-design]].

**Lo que hace a esta trampa peligrosa no es el comando**, es que el negativo falso era **cómodo**:
explicaba el estado parcial de una spec sin exigir más trabajo, y encajaba con una causa ya
conocida del repo. Un resultado que confirma lo que uno esperaba merece la comprobación extra, no
menos.

Relacionadas: [[el-trabajo-hecho-no-vuelve-solo-al-documento]] ·
[[siteground-sin-tunel-ssh]] · [[el-contador-no-mide-el-archivo]]

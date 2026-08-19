---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-07-30
areas: [deploy]
fuente: memoria-claude
origen: lps-aia-siteground-sin-tunel-ssh
resumen: SiteGround prohíbe el reenvío de puertos SSH, así que no hay forma de ver prueba-lps ni producción como si fueran locales
---
En la cuenta SSH de SiteGround (`siteground-pruebas-lastplanner` y la de producción, que **es la
misma cuenta**), cualquier `ssh -L` muere con:

```
channel 2: open failed: administratively prohibited: open failed
```

Medido el 2026-07-30 contra los puertos 80 y 443, con `localhost` y con `127.0.0.1` en el extremo
remoto, en modo `-v` para confirmar que el rechazo viene del servidor. Las opciones de la llave
listan `port-forwarding`, así que **la llave no es el problema y mirar ahí despista**.

Consecuencia práctica: **no hay forma de navegar `prueba-lps` como si fuera local**, lo que deja sin
vía la puerta de servicio [[dev-door-acceso-local]] en ese host — su candado exige
`REMOTE_ADDR` local, y con razón. El QA en navegador contra pruebas sigue necesitando login normal.

Lo que sí funciona, y basta para smokes automatizados:

```bash
ssh siteground-pruebas-lastplanner
curl -s -k -c /tmp/cj.txt -H 'Host: prueba-lps.lastplanneraia.com' \
  'https://127.0.0.1/dev/entrar?u=test.R&p=Da%20Porto'
```

El puerto 80 dentro del servidor responde 301; el 443 sirve la app. Hace falta la cabecera `Host`
porque hay varios sitios en la misma máquina.

Registrado también en `docs/siteground-deploy-routine.md` §Supuestos.

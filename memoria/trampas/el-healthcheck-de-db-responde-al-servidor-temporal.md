---
tipo: trampa
estado: vigente
fecha: 2026-08-18
areas: [docker, qa, datos]
fuente: corrida del gate semanal-roles-phases en el stack aislado, logs de `db`
resumen: el healthcheck de `db` se pone verde contra el servidor temporal que MySQL usa para cargar las semillas, así que `app` arranca y hay una ventana de ~8 s sin base en la que todo falla a la vez
---
`docker-compose.yml` arranca `app` con `depends_on: db: condition: service_healthy`, y el
healthcheck es `mysqladmin ping -h localhost`. La trampa es **a qué servidor le está haciendo ping**.

La imagen oficial de MySQL, cuando hay semillas en `/docker-entrypoint-initdb.d/`, levanta un
**servidor temporal** para cargarlas, lo para, y solo entonces arranca el definitivo. El temporal
escucha en el socket local —en los logs aparece como `ready for connections … port: 0`—, así que
**el `ping` del healthcheck lo encuentra y da verde mientras las semillas siguen cargando**.
`app` arranca ahí. Después el temporal se para y el definitivo tarda en subir.

Medido el 2026-08-18 en el stack aislado, leyendo `docker compose logs db`:

```
15:06:43  ready for connections … port: 0     <- temporal; el healthcheck ya da verde
15:07:15  Stopping temporary server
15:07:23  ready for connections … port: 3306  <- el de verdad, 8 s después
```

**Ocho segundos sin base.** El primer recibo del gate `semanal-roles-phases`, lanzado justo tras
`up -d`, salió **rojo con 13 de 13 casos caídos en 8.072 ms** — la anchura exacta de la ventana. El
segundo, con las mismas entradas, pasó en 46 s. Un intermitente que parece del producto y es del
arranque.

**Por qué `curl /login` no protege:** esa página responde **200 sin tocar la base**, así que el
bucle de espera del workflow la da por buena en plena ventana. Comprobar la app no es comprobar la
base.

**Cómo esperar de verdad** — preguntarle a la base por un dato que solo existe tras las semillas:

```bash
until docker compose -p "$COMPOSE_PROJECT_NAME" -f docker-compose.yml -f docker-compose.ci.yml \
  exec -T db mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e \
  "SELECT COUNT(*) FROM lastplanneraia_ci.usuarios;" 2>/dev/null | grep -qE '^[1-9]'; do sleep 2; done
```

En el workflow de CI el gate no está expuesto: corre después de varios pasos largos y la ventana
quedó atrás hace rato. Quien sí se la come es **quien levanta el stack en local y mide acto
seguido**, que es lo que hace la receta de arranque de los planes.

Ver también [[exec-en-contenedor-vivo-corre-el-repo-ajeno]] y [[captura-playwright-miente]]: las tres
son la misma familia — evidencia recogida antes de que el sistema esté donde uno cree que está.

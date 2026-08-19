---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-18
areas: [rbac, arquitectura]
fuente: sesión del 2026-08-18, salida del modal de cambio obligatorio de contraseña
resumen: la sesión a medias del cambio obligatorio de contraseña no se limpia con `/logout`, porque esa ruta no es pública y el middleware exige `$_SESSION['usuario']`, que en ese estado no existe
---
Cuando una cuenta trae `force_password_change = 1`, `LoginController::login()` **no** crea sesión
completa: deja `usuario_temp` + `must_change_password` y devuelve a `/login`, donde la vista abre
un modal bloqueante. Es un estado intermedio, ni anónimo ni autenticado, y ahí está la trampa:

**`/logout` no sirve para salir de él.** No figura en `$publicRoutes` de `public/index.php`, así
que la petición pasa antes por `SessionMiddleware::check()`, que exige `$_SESSION['usuario']` —la
que justamente no se creó— y redirige a `/login` **sin destruir nada**. La bandera sobrevive, y el
modal se reabre en cada carga. El usuario queda encerrado: la única salida era completar el cambio
de contraseña o borrar la cookie a mano.

Se arregló el 2026-08-18 (`667a6b21`) con una ruta pública propia, `/login/cancelar` →
`LoginController::cancelPasswordChange()`, y un botón «Volver al inicio de sesión» en el modal.
Dos detalles del arreglo que conviene no deshacer:

- **Solo destruye la sesión si hay cambio pendiente.** Una ruta pública que cierre sesión sin
  condición es un vector de CSRF: bastaría un `<img src="/login/cancelar">` para echar a cualquier
  usuario autenticado. Con la condición, a quien no tiene el cambio pendiente solo lo redirige.
- **Bajo mantenimiento devuelve a la ruta oculta**, no a `/login`, que ahí muestra la página de
  mantenimiento: destruir la sesión se lleva por delante `maintenance_bypass`.

**Lo general, que vale más que el caso:** en este repo, una ruta que deba funcionar *sin* sesión
completa —salir, cancelar, recuperar— tiene que estar en `$publicRoutes`. Si no, el middleware la
convierte en una redirección silenciosa a `/login` y el efecto que esperabas nunca ocurre.

**Corregido el 2026-08-18.** Aquí decía que al medirlo había salido otro hallazgo aparte —que con
SweetAlert2 11.4.24 la tecla Escape no cierra **ningún** diálogo de la aplicación, ni con
`allowEscapeKey: true` ni con un evento sintético—. **Era falso, y lo eran las dos mediciones que
lo sostenían.** Con Playwright contra este mismo contenedor, Escape cierra el diálogo en `/login`
—incluida la forma exacta de este modal con el flag en `true`— y en `/programacion-semanal`. Las
dos medidas fallaron por su instrumento, no por la aplicación: la pulsación real se hizo en el
panel Browser integrado, que tiene el reloj de animaciones congelado
([[panel-browser-no-anima]] — reincidencia, la página ya describía este mismo síntoma con
SweetAlert2 desde el 2026-08-06), y el evento sintético se lanzó sobre `document`, donde
SweetAlert2 no escucha ([[evento-sintetico-no-alcanza-al-popup]]).

El modal sigue con `allowEscapeKey: false`, pero por el motivo real, ya escrito en la vista: un
Escape accidental cae en el `.then()` como `dismiss` y navega a `/login/cancelar`, que destruye la
sesión pendiente; descartaría la contraseña a medio teclear y además cerraría la sesión. No es
trampa de teclado, porque el botón «Volver al inicio de sesión» es alcanzable con Tab.

Ver también [[dev-door-acceso-local]], la otra ruta que vive fuera del middleware, y
[[un-if-de-autorizacion-no-es-toda-la-autorizacion]], que es la misma pregunta —quién comprueba
qué, y dónde— vista desde el otro lado.

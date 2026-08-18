---
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

Al medirlo salió otro hallazgo aparte, que **no** es de este flujo: con SweetAlert2 11.4.24 la
tecla Escape no cierra **ningún** diálogo de la aplicación, ni con `allowEscapeKey: true` ni con un
evento sintético. Por eso el modal deja el flag en `false` con el hallazgo escrito al lado, y la
salida real es el botón. Queda como trabajo con sesión propia.

Ver también [[dev-door-acceso-local]], la otra ruta que vive fuera del middleware, y
[[un-if-de-autorizacion-no-es-toda-la-autorizacion]], que es la misma pregunta —quién comprueba
qué, y dónde— vista desde el otro lado.

---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-13
areas: [design-system, bi]
fuente: src/View/Components/DesignSystemComponent.php:392, verificacion en navegador del 2026-08-13
resumen: quitar un item del sidebar rompe con 500 las pantallas de ese modulo, porque DesignSystemComponent lanza excepcion si el destino activo no esta entre los items pintados
---
`DesignSystemComponent` (`src/View/Components/DesignSystemComponent.php:392`) lanza
`InvalidArgumentException("unknown active sidebar destination: …")` cuando el destino
declarado como activo no aparece entre los items que acaba de pintar. Consecuencia: **si
ocultas un item del sidebar, las pantallas de ese modulo dejan de renderizar** —error 500—
para quien todavia pueda entrar en ellas, porque son justo las que declaran ese destino
como activo.

Medido el 2026-08-13 al ocultar el Control Tower
([[control-tower-oculto-mientras-se-desarrolla]]): las ocho vistas `/bi/*` cayeron para
Admin, que es precisamente el unico que debia poder abrirlas.

**La regla ya existia y el item BI se la saltaba.** `views/partials/shell_sidebar.php:65`
dice «Nunca ocultar el modulo en el que el usuario ya esta», y la aplica al resto de
modulos con `array_diff` contra el activo. El item BI se decidia con un ternario aparte
sobre `BiAccessComponent::canAccess()`, ajeno a esa regla. La cura fue pintarlo tambien
cuando `$shellActive === 'control-tower'`.

**Lo que lo destapo, y por que casi se escapa:** la comprobacion por `curl` de la sesion
recien abierta daba 200, porque el fallo **solo aparece si la sesion paso antes por un
modulo**. Ademas el log de Apache registra **200** aunque el cuerpo sea la pagina de error,
porque la excepcion se convierte en pagina despues de empezar a emitir. Dos motivos para no
fiarse del codigo HTTP: hay que mirar el cuerpo, y hay que navegar como navega una persona.
Emparejada con [[el-dom-dice-que-existe-no-que-se-ve]] y
[[valor-declarado-no-es-valor-computado]].

**Anexo del 2026-08-13, misma jornada, otra trampa de medicion.** Al verificar el
despliegue por `curl`, pedir `/programacion-semanal` **sin proyecto en sesion** devuelve
**302 a `/proyectos`**: sin `-L`, lo que se guarda es el cuerpo vacio de la redireccion, y
cualquier `grep -c` sobre el da 0. Ese 0 parece «no hay accesos BI» y en realidad es «no
hay pagina». Se cazo cuando el 0 aparecio justo despues de un cambio que debia producir lo
contrario. Para medir presencia o ausencia en una ruta protegida hacen falta las dos
cosas: seguir la redireccion (`-L`) y **tener proyecto en sesion** (`p=<Proyecto_Proceso>`
en la puerta dev); y conviene imprimir el `<title>` para saber que pagina se midio.

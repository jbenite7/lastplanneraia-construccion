/**
 * El contenido de la ayuda de cada pantalla, versionado al lado de la pantalla que describe.
 *
 * Vive aquí y no en la base de datos por una razón de mantenimiento: cambiar una pantalla y su
 * ayuda tiene que ser un solo cambio y una sola revisión. Una ayuda que miente es peor que
 * ninguna, y la única defensa contra eso es que las dos cosas viajen juntas.
 *
 * Es un objeto de datos y no JSX para poder verificarlo: `ayuda.test.ts` comprueba que ninguna
 * pantalla se queda sin ayuda y que ninguna deja una de las tres preguntas en blanco. Con JSX no
 * se puede afirmar eso.
 *
 * Las ocho pantallas se enumeran a mano —no se derivan de `PANTALLAS` de `navegacion.ts`— porque
 * «Pasos de contratación» está fuera de la barra a propósito y aun así necesita ayuda.
 *
 * Dos cosas que NO se escriben aquí, y conviene saber por qué antes de añadirlas:
 *
 * 1. **Lo que la pantalla ya dice en el momento.** Un mensaje que aparece cuando pasa la cosa
 *    llega mejor que el mismo texto guardado detrás de un botón, y mantener dos copias
 *    sincronizadas es trabajo que nadie hace. Aplica a la advertencia de método del flujo de caja
 *    (la sirve el servidor y viaja dentro del CSV) y al motivo por el que el desplegable de
 *    frentes está vacío (`motivoSinAnclas()`). La ayuda los señala; no los reescribe.
 * 2. **Lo que el lector no puede ver en la pantalla.** Describir un dato que existe por dentro
 *    pero no se pinta es la forma más rápida de que la ayuda deje de creerse.
 */

export type PantallaAyuda =
  | 'importar' | 'maestro' | 'presupuesto' | 'comparar'
  | 'paquetes' | 'plan' | 'pasos' | 'seguimiento'

export const PANTALLAS_AYUDA: PantallaAyuda[] = [
  'importar', 'maestro', 'presupuesto', 'comparar',
  'paquetes', 'plan', 'pasos', 'seguimiento',
]

/** Un trozo de la pantalla que merece explicación propia dentro del mismo panel. */
export type ApartadoAyuda = { etiqueta: string; texto: string }

export type ContenidoAyuda = {
  titulo: string
  /** Qué hace esta pantalla. */
  queHace: string
  /** Qué tengo que hacer yo aquí. Uno por acción, en el orden en que se hacen. */
  queHagoYo: string[]
  /** Qué pasa después. */
  quePasaDespues: string
  /** Las pestañas o zonas que necesitan una línea propia. Vacío si la pantalla no las tiene. */
  apartados: ApartadoAyuda[]
}

export const AYUDAS: Record<PantallaAyuda, ContenidoAyuda> = {
  importar: {
    titulo: 'Cargar presupuesto',
    queHace:
      'Trae el presupuesto de la obra desde el Excel del software de presupuestos y lo guarda como '
      + 'una versión. Todo lo demás del módulo cuelga de aquí: sin presupuesto cargado, las otras '
      + 'pantallas no tienen con qué trabajar.',
    queHagoYo: [
      'Sube el Excel del presupuesto. Tiene que traer la hoja «Presupuesto» y pesar menos de 10 MB.',
      'Revisa la previsualización antes de confirmar: es la última oportunidad de ver qué va a entrar.',
      'Si ya habías cargado un presupuesto antes, lee el impacto sobre el trabajo ya hecho.',
      'Confirma. La versión queda guardada y pasa a ser la activa.',
    ],
    quePasaDespues:
      'La obra queda con una versión activa del presupuesto, y las pantallas de Maestro, '
      + 'Presupuesto y Paquetes empiezan a mostrar sus datos. Las versiones anteriores no se '
      + 'borran: quedan en el historial y puedes volver a activar una, o comparar dos en la '
      + 'pantalla de Comparar.',
    apartados: [
      {
        etiqueta: 'Impacto sobre el trabajo ya hecho',
        texto:
          'Si la obra ya tenía presupuesto, aquí se te dice qué le pasa a lo que ya habías '
          + 'armado —los insumos que vinculaste en el Maestro y los paquetes que armaste— antes de '
          + 'que confirmes. Léelo entero: es el único momento en que puedes echarte atrás sin '
          + 'perder nada. Si algo no cuadra, cancela y revisa el Excel en lugar de confirmar y '
          + 'arreglar después.',
      },
      {
        etiqueta: 'Historial de versiones',
        texto:
          'Cada carga queda guardada con su fecha. Puedes volver a activar una versión anterior, y '
          + 'el sistema te avisará también en ese caso de qué se ve afectado.',
      },
    ],
  },

  maestro: {
    titulo: 'Maestro de insumos',
    queHace:
      'Conecta cada insumo que viene en el Excel de la obra con el catálogo único de insumos de la '
      + 'empresa. El Excel de cada obra escribe los nombres a su manera; el catálogo es el que hace '
      + 'que «cemento gris» de una obra y de otra sean el mismo insumo.',
    queHagoYo: [
      'Abre «Pendientes por vincular»: son los insumos del presupuesto que todavía no están en el catálogo.',
      'Haz doble clic en uno para vincularlo a un insumo del catálogo que ya exista.',
      'Si de verdad es nuevo, créalo en el catálogo. Piensa antes: crear un duplicado es el error caro aquí.',
      'Repite hasta que la lista de pendientes quede vacía.',
    ],
    quePasaDespues:
      'Con los insumos vinculados, la pantalla de Paquetes puede agrupar bien y los informes de la '
      + 'empresa pueden sumar la misma cosa entre obras distintas. Mientras queden pendientes, esos '
      + 'insumos siguen apareciendo en el presupuesto de la obra pero no se pueden comparar con '
      + 'nada.',
    apartados: [
      {
        etiqueta: 'Pendientes por vincular',
        texto:
          'La cola de trabajo de esta pantalla. Un clic selecciona; un doble clic abre la ventana '
          + 'para vincular. El sistema te propone parecidos, pero la decisión es tuya.',
      },
      {
        etiqueta: 'Catálogo global',
        texto:
          'El catálogo completo de la empresa, de solo consulta. Búscalo aquí antes de crear un '
          + 'insumo nuevo: casi siempre ya está, escrito de otra forma.',
      },
      {
        etiqueta: 'Importar SINCO',
        texto:
          'Trae el catálogo desde el Excel exportado de SINCO, con la hoja «Maestro Insumos». Se '
          + 'hace de vez en cuando y afecta a toda la empresa, no solo a tu obra. Ojo con un efecto '
          + 'que no es obvio: al entrar insumos nuevos al catálogo, algunos de tus pendientes '
          + 'encuentran solos el insumo que estaban esperando. Vuelve a mirar «Pendientes por '
          + 'vincular» después de una carga, porque la lista puede haber bajado sin que tú hicieras '
          + 'nada.',
      },
    ],
  },

  presupuesto: {
    titulo: 'Presupuesto',
    queHace:
      'Muestra el presupuesto activo de la obra tal como quedó al cargarlo, por capítulos y '
      + 'actividades, y señala lo que conviene mirar dos veces antes de seguir.',
    queHagoYo: [
      'Elige hasta qué nivel de detalle quieres ver, o haz clic en una fila para abrirla.',
      'Atiende los avisos: actividades sin cantidad, insumos en cero y partidas globales.',
      'Si un aviso indica un error del Excel, corrígelo allí y vuelve a cargar el presupuesto.',
    ],
    quePasaDespues:
      'Esta pantalla no cambia nada: es para mirar y decidir. Lo que corrijas aquí se corrige en el '
      + 'Excel y entra volviendo a cargar el presupuesto. Cuando el presupuesto te cuadre, el paso '
      + 'siguiente es agrupar sus insumos en la pantalla de Paquetes.',
    apartados: [
      {
        etiqueta: 'Avisos del presupuesto',
        texto:
          'Una actividad sin cantidad o un insumo en cero casi siempre es un descuido del Excel, y '
          + 'arrastra el error a todo lo que venga después. Una partida global grande no es un '
          + 'error, pero es dinero que no se puede repartir por actividad: conviene saber cuánto es.',
      },
      {
        etiqueta: 'Qué cuenta cada cifra',
        texto:
          'Cada total dice de qué está hecho. Si dos cifras de la pantalla no coinciden, es porque '
          + 'cuentan cosas distintas: lee lo que declara cada una antes de dar por buena una resta.',
      },
    ],
  },

  comparar: {
    titulo: 'Comparativo de versiones',
    queHace:
      'Pone dos versiones del presupuesto una al lado de la otra y muestra qué cambió: qué subió, '
      + 'qué bajó y en qué actividades e insumos.',
    queHagoYo: [
      'Elige las dos versiones que quieres comparar. Necesitas al menos dos cargadas.',
      'Mira primero por actividad para ubicar dónde está el cambio, y luego abre por insumo.',
    ],
    quePasaDespues:
      'Sirve para explicar y para sustentar. No cambia el presupuesto ni la versión activa: es solo '
      + 'consulta. Si lo que ves aquí te hace querer cambiar de versión, eso se hace desde Cargar '
      + 'presupuesto.',
    apartados: [
      {
        etiqueta: 'Cómo leer la diferencia',
        texto:
          'La diferencia que se muestra es lo que subió menos lo que bajó. Una diferencia pequeña '
          + 'puede esconder un sobrecosto grande compensado por un ahorro grande: abre el detalle '
          + 'antes de concluir que «casi no cambió».',
      },
    ],
  },

  paquetes: {
    titulo: 'Paquetes de contratación',
    queHace:
      'Agrupa los insumos del presupuesto en paquetes de contratación: los conjuntos de cosas que '
      + 'se van a contratar juntas, con un mismo tercero. Es la traducción del presupuesto, que está '
      + 'ordenado como se construye, al orden en que se compra.',
    queHagoYo: [
      'Empieza por «Insumos distintos» y asigna cada insumo a un paquete, o márcalo como omitido si no se contrata.',
      'Si no sabes por dónde arrancar, usa el asistente paso a paso: propone agrupaciones y tú decides.',
      'Apunta a que no quede valor sin destino. La meta es 100% asignado u omitido.',
    ],
    quePasaDespues:
      'Cada paquete que genere un proceso de contratación pasa a la pantalla de Plan, donde recibe '
      + 'fechas según el frente de obra al que sirva. Un insumo sin paquete no llega nunca al plan, '
      + 'y por lo tanto nadie va a recordar comprarlo a tiempo.',
    apartados: [
      {
        etiqueta: 'Insumos distintos',
        texto:
          'La lista de trabajo: cada insumo del presupuesto una sola vez, con su valor. De aquí se '
          + 'manda a un paquete o se omite.',
      },
      {
        etiqueta: 'Asistente paso a paso',
        texto:
          'Propone agrupaciones a partir de lo que ya se hizo en otras obras. Acierta con frecuencia '
          + 'y se equivoca a veces: revisa lo que propone en lugar de aceptarlo en bloque.',
      },
      {
        etiqueta: 'Paquetes con insumos',
        texto:
          'El resultado: qué paquetes existen y qué entró en cada uno. Úsalo para comprobar que un '
          + 'paquete no quedó con cosas que no se contratan juntas.',
      },
    ],
  },

  plan: {
    titulo: 'Plan de compras',
    queHace:
      'Dice qué hay que empezar a contratar y cuándo. Toma cada paquete, mira el frente de obra al '
      + 'que sirve, cuenta hacia atrás los días que se tarda en contratar y calcula la fecha en que '
      + 'hay que arrancar el proceso para llegar a tiempo.',
    queHagoYo: [
      'Mira la pestaña «Plan»: lo vencido va primero. Es tu lista de esta semana.',
      'Abre una fila para ver sus pasos y quién responde de cada uno.',
      'Si hay paquetes en «Sin frente», amárralos a un nodo del cronograma: sin frente no hay fecha.',
      'Si hay algo en «Pendientes de calcular» o en «Desfases», resuélvelo antes de fiarte de las fechas.',
    ],
    quePasaDespues:
      'Las fechas alimentan la pantalla de Seguimiento, que es donde se marca lo que ya ocurrió y se '
      + 've qué se vence. Si el cronograma de la obra se mueve, este plan queda desactualizado hasta '
      + 'que lo recalcules: por eso existe la pestaña de Desfases.',
    apartados: [
      {
        etiqueta: 'Sin frente',
        texto:
          'Paquetes que van a generar un proceso de contratación pero no están amarrados a ningún '
          + 'frente del cronograma. Sin ese amarre no hay de dónde sacar una fecha, así que estos '
          + 'paquetes no aparecen en el plan y nadie los va a ver venir. Es la lista que hay que '
          + 'dejar vacía. Si al abrir el desplegable no te ofrece ningún frente, la propia pantalla '
          + 'te dice ahí mismo cuál es la causa: hazle caso a ese mensaje.',
      },
      {
        etiqueta: 'Pendientes de calcular',
        texto:
          'Ya tienen frente, pero el plan todavía no se ha recalculado con ese amarre. Aparecen aquí '
          + 'y no en «Plan» hasta que recalcules.',
      },
      {
        etiqueta: 'Desfases',
        texto:
          'Cuando alguien mueve un frente en el cronograma, las fechas que este plan calculó dejan '
          + 'de corresponder. Aquí se ve cuáles. Puedes ver el cambio propuesto antes de aplicarlo: '
          + 'mirarlo no toca nada. «Aplicar» sí mueve las fechas del plan, así que revisa el detalle '
          + 'antes, porque afecta a lo que otras personas ya tenían previsto. Lo que ya ocurrió y '
          + 'quedó registrado no se pierde al recalcular.',
      },
    ],
  },

  pasos: {
    titulo: 'Pasos del proceso de contratación',
    queHace:
      'Define, para esta obra, qué pasos tiene un proceso de contratación y cuántos días toma cada '
      + 'uno. Es de donde el plan saca el tiempo que hay que contar hacia atrás desde la fecha en que '
      + 'se necesita el material.',
    queHagoYo: [
      'Revisa los pasos que trae por defecto y ajústalos a como se contrata en esta obra.',
      'Si otra obra ya tiene una configuración que te sirve, cópiala y ajusta desde ahí.',
      'Ajusta las duraciones que se te queden cortas o largas según lo que pasa de verdad.',
    ],
    quePasaDespues:
      'Cada cambio aquí cambia las fechas de arranque de todo el plan de la obra, así que hay que '
      + 'recalcular para verlo. Todo cambio queda registrado con su fecha y su autor, y se puede '
      + 'volver a una configuración anterior.',
    apartados: [
      {
        etiqueta: 'Copiar la configuración de otra obra',
        texto:
          'Trae los pasos y las duraciones de otra obra. Es una copia de una vez, no un vínculo: si '
          + 'la otra obra cambia después, esta no se entera. La pantalla te enseña qué va a traer '
          + 'antes de traerlo, y te avisa si la obra de origen está a medio configurar.',
      },
      {
        etiqueta: 'Duraciones del catálogo de la empresa',
        texto:
          'Los días que la empresa tiene medidos para cada tipo de paquete. Se pueden ajustar solo '
          + 'en las filas que esta obra usa, y el cambio es de la empresa, no solo tuyo. Un paquete '
          + 'del que la empresa todavía no tiene una duración medida no aparece en esta lista: '
          + 'recibe fechas por el promedio de los de su tipo, y no hay ningún número que editar '
          + 'hasta que alguien mida ese proceso.',
      },
      {
        etiqueta: 'Historial',
        texto:
          'Cada cambio de configuración queda anotado y no se borra. Volver a una versión anterior '
          + 'también deja rastro.',
      },
    ],
  },

  seguimiento: {
    titulo: 'Seguimiento del plan de compras',
    queHace:
      'Es la pantalla del día a día: registra lo que ya ocurrió de cada proceso de contratación, '
      + 'muestra qué se vence y estima cuánto dinero va a salir por mes.',
    queHagoYo: [
      'Entra por «Vencimientos» y mira qué se te vence: es la pregunta de la mañana.',
      'Marca en «Paquetes» los pasos que ya se cumplieron, con su fecha real.',
      'Si un paso se atrasó, márcalo igual con la fecha en que de verdad pasó, no con la planeada.',
    ],
    quePasaDespues:
      'Lo que registras aquí queda guardado y no lo borra un recálculo del plan: lo que ya ocurrió, '
      + 'ocurrió. La clasificación de vencimientos se mueve a medida que marcas, y la curva de '
      + 'desembolsos se recalcula sola cada vez que la pides.',
    apartados: [
      {
        etiqueta: 'Vencimientos',
        texto:
          'Qué se vence, por paso y por responsable. Lo vencido primero, luego lo que vence pronto. '
          + 'Coincide con lo que marca la pantalla de Plan, porque las dos cuentan igual.',
      },
      {
        etiqueta: 'Paquetes',
        texto:
          'El detalle paso a paso de cada paquete, y donde se marca lo cumplido con su fecha real.',
      },
      {
        etiqueta: 'Flujo de caja',
        texto:
          'Estima cuánto dinero sale por mes según el plan. Antes de sacar esta tabla de aquí —una '
          + 'foto, un archivo, una diapositiva— lee el aviso que la pantalla muestra encima de ella '
          + 'y llévalo contigo: dice con qué método está hecha y qué no tiene en cuenta. La misma '
          + 'frase viaja dentro del archivo que exportas. Sin ella, alguien la va a leer como una '
          + 'promesa de tesorería, y no lo es.',
      },
    ],
  },
}

export function ayudaDe(id: PantallaAyuda): ContenidoAyuda {
  const contenido = AYUDAS[id]
  // Falla fuerte y temprano: una pantalla sin ayuda es un incumplimiento de la regla de proceso,
  // no un caso a tolerar con un panel vacío que el usuario no sabría interpretar.
  if (!contenido) throw new Error(`No hay ayuda escrita para la pantalla «${id}»`)
  return contenido
}

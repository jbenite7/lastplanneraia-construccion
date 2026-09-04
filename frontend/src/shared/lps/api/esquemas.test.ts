import {
  EsquemaAcciones,
  EsquemaComentarioRaiz,
  EsquemaCrisisAlert,
  EsquemaTarget,
} from './esquemas';

// --- target: unión discriminada por kind -----------------------------------

test('un target de actividad válido pasa sin alertId', () => {
  const resultado = EsquemaTarget.safeParse({ kind: 'activity', activityId: 3, module: 'PG', week: 1 });
  expect(resultado.success).toBe(true);
});

test('un target de alerta válido exige alertId', () => {
  const resultado = EsquemaTarget.safeParse({ kind: 'alert', activityId: 3, module: 'PG', week: 1, alertId: 7 });
  expect(resultado.success).toBe(true);
});

test('un target de actividad con un alertId sobrante no rompe: se ignora, no se exige', () => {
  // kind:'activity' nunca produce alertId (targetToArray sólo lo añade si isAlert(),
  // LpsApiController.php:288-302), pero la política "no .strict()" tolera la clave si apareciera.
  const resultado = EsquemaTarget.safeParse({ kind: 'activity', activityId: 3, module: 'PG', week: 1, alertId: 7 });
  expect(resultado.success).toBe(true);
});

test('un target de alerta sin alertId se rechaza', () => {
  const resultado = EsquemaTarget.safeParse({ kind: 'alert', activityId: 3, module: 'PG', week: 1 });
  expect(resultado.success).toBe(false);
});

test('un módulo fuera de PG/PI/PS se rechaza — LpsTargetResolver sólo acepta esos tres', () => {
  const resultado = EsquemaTarget.safeParse({ kind: 'activity', activityId: 3, module: 'ZZ', week: 1 });
  expect(resultado.success).toBe(false);
});

test('un kind fuera de activity/alert se rechaza', () => {
  const resultado = EsquemaTarget.safeParse({ kind: 'otro', activityId: 3, module: 'PG', week: 1 });
  expect(resultado.success).toBe(false);
});

// --- acciones -----------------------------------------------------------

test('acciones con actorWriteBlock fuera del enum cerrado se rechaza', () => {
  const resultado = EsquemaAcciones.safeParse({
    read: true,
    comment: false,
    notifyNext: true,
    close: false,
    actorWriteBlock: 'algo_inventado',
  });
  expect(resultado.success).toBe(false);
});

test('acciones válidas con los tres valores documentados de actorWriteBlock', () => {
  for (const valor of ['none', 'forbidden', 'profile_required']) {
    const resultado = EsquemaAcciones.safeParse({
      read: true,
      comment: true,
      notifyNext: true,
      close: true,
      actorWriteBlock: valor,
    });
    expect(resultado.success).toBe(true);
  }
});

// --- crisisAlert ----------------------------------------------------------

test('crisisAlert exige level numérico, nunca null — LpsTarget::forAlert() siempre lo recibe', () => {
  const resultado = EsquemaCrisisAlert.safeParse({ id: 1, active: true, level: null });
  expect(resultado.success).toBe(false);
});

test('crisisAlert válido', () => {
  const resultado = EsquemaCrisisAlert.safeParse({ id: 1, active: true, level: 3 });
  expect(resultado.success).toBe(true);
});

// --- comentario raíz: un solo nivel de anidación ---------------------------

test('un comentario raíz con menciones null y respuestas vacías es válido', () => {
  const resultado = EsquemaComentarioRaiz.safeParse({
    id: 10,
    comentario: 'texto',
    created_at: '2026-08-31 09:00:00',
    autor_nombre: 'Ana',
    autor_cargo: 'Residente',
    menciones: null,
    respuestas: [],
  });
  expect(resultado.success).toBe(true);
});

test('una respuesta anidada con su propio "respuestas" (reply-a-reply) igual pasa: la clave sobrante se ignora', () => {
  // El presenter nunca produce esto (buildTree sólo anida un nivel), pero el esquema no debe
  // fallar por una clave adicional — eso es justamente la postura "no .strict()".
  const resultado = EsquemaComentarioRaiz.safeParse({
    id: 10,
    comentario: 'texto',
    created_at: '2026-08-31 09:00:00',
    autor_nombre: null,
    autor_cargo: null,
    menciones: { roles: ['A', 'R'] },
    respuestas: [
      {
        id: 11,
        comentario: 'respuesta',
        created_at: '2026-08-31 09:01:00',
        autor_nombre: null,
        autor_cargo: null,
        menciones: null,
        respuestas: [], // sobrante en una respuesta: se ignora, no rompe
      },
    ],
  });
  expect(resultado.success).toBe(true);
});

test('un comentario que trae usuario_id (forma legada) no rompe la forma React: la clave se ignora', () => {
  const resultado = EsquemaComentarioRaiz.safeParse({
    id: 10,
    comentario: 'texto',
    created_at: '2026-08-31 09:00:00',
    autor_nombre: 'Ana',
    autor_cargo: 'Residente',
    menciones: null,
    usuario_id: 42,
    respuestas: [],
  });
  expect(resultado.success).toBe(true);
});

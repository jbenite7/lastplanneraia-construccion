# Contrato de gobierno global

Este contrato hace del repositorio la autoridad del Design System AIA. El
catálogo, los manifiestos, las decisiones, los tokens, los componentes y sus
pruebas ejecutables prevalecen sobre soluciones locales o prompts de módulo.

## Cambio compartido

- Todo cambio de API, token o variante usa SemVer y actualiza el changelog.
- Una familia cambia primero en el laboratorio y conserva evidencia aprobada.
- Cada consumidor afectado se declara en su manifiesto y pasa su smoke.
- Programa General es el único piloto autorizado durante Sprint 00.
- Una revisión local del diff inspecciona alcance, pruebas, evidencia y cambios
  ajenos antes del commit si cambia el núcleo.

## Límites operativos

- El cierre local se realiza sin push y sin deploy.
- No se activa branch protection durante Sprint 00.
- No se permite introducir un framework frontend ni actualizar vendors.
- La deuda nueva o una excepción vencida bloquean el cambio.

## Autoridad de publicación

Solo una versión cuyos contratos, auditor, axe, goldens y piloto estén verdes
puede proponerse para publicación. La aprobación humana consolidada precede al
staging y al commit; la existencia del workflow no equivale a un check remoto.

## Tarea posterior de enforcement remoto

- `DS-GOV-001`
- Owner: Felipe.
- Trigger: después del primer push autorizado de `1.0.0`.
- Resultado: activar los required checks del workflow de Design System y
  documentar su protección remota.
- Límite: no se ejecuta durante Sprint 00 y no autoriza push, deploy ni branch
  protection por sí misma.

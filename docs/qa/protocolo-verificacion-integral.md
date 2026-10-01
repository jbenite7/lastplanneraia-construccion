---
capa: fuente
tipo: contrato
estado: vigente
fecha: 2026-09-30
areas: [qa, lps, arquitectura]
fuente: docs/qa/protocolo-verificacion-integral.md
resumen: "Protocolo común de verificación integral por módulo: mapa, prueba y depuración en navegador, restauración y ficha en la wiki. Vigente desde el 2026-09-30 por aprobación de Felipe en el chat."
---

# Protocolo de verificación integral por módulo

**Estado: vigente desde el 2026-09-30.** Felipe lo aprobó ese día en el chat. Desde entonces es
requisito de cierre de cada módulo de la migración: un módulo no se da por cerrado sin su sprint
de verificación integral y su ficha en la wiki.

Pedido de Felipe del 2026-09-30: replicar en cada módulo lo que se hizo con Programa General
(mapa completo, prueba y depuración en navegador de todas sus funciones, lógica de negocio y
flujos) y dejar en la wiki, por cada módulo, submódulo y objeto, sus funciones, flujos y
protocolos. El primer caso, y modelo, es [[programa-general-funciones-y-flujos]].

## Alcance

Cada spec de la migración a React (S01 a S27 y T01 a T03) gana una sección «Verificación integral
y ficha en la wiki» y su plan gana dos tareas finales:

- **Sprint de verificación integral** del módulo, con este protocolo.
- **Ficha en la wiki** del módulo: `memoria/arquitectura/<superficie>-funciones-y-flujos.md`, con la
  estructura de la ficha de Programa General.

«Objeto» incluye pantallas, submódulos, tablas, columnas, modales, diálogos, popups, tooltips,
menús, el riel lateral, botones, enlaces, campos, chips, avisos y estados de carga, vacío y error.

## Cola (decisión de Felipe del 2026-09-30: los ya migrados primero)

1. S05 Programa General: mapa y prueba hechos el 2026-09-30; queda la restauración y el sprint
   de corrección de los fallos encontrados.
2. T01 shell, T02 contexto LPS, S04 selector de proyectos, S01 login, S02 recuperar clave y S03
   restablecer clave: ya en React.
3. El resto, en el orden de la migración, como requisito de cierre de cada módulo: el módulo no se
   da por cerrado hasta tener su sprint verificado y su ficha.

## Los seis pasos

### 1. Mapa (solo lectura)
Tres frentes en paralelo, cada uno citando `archivo:línea`:
- **Pantalla:** cada objeto con su texto visible, qué hace, su manejador, la llamada al servidor,
  sus validaciones y mensajes, y cuándo aparece, se oculta o se deshabilita (rol, permiso,
  estado, semana, tema, ancho), más los atajos de teclado.
- **Servidor y base de datos:** cada endpoint con su controlador, permisos y CSRF, entradas,
  tablas y columnas que lee y escribe, efectos sobre otros módulos y semanas, errores, y qué
  pruebas lo cubren.
- **Reglas y flujos:** cada regla con su fuente y si el código la cumple, la contradice o no la
  implementa; cada flujo de punta a punta con precondiciones, resultado, datos que cambian y qué
  puede fallar; y las brechas de pruebas.

### 2. Entorno
- Servir la rama del módulo con `LPS_CODE_ROOT`; `.env` por enlace duro hecho por Felipe
  ([[env-enlazado-se-rompe-dentro-del-contenedor]]); `composer install` dentro del contenedor.
- Recargar las hojas sin caché antes de medir ([[recarga-normal-sirve-la-hoja-css-vieja]]).
- **Respaldo** de la base de desarrollo antes de cualquier escritura (`mysqldump`, solo lectura),
  guardado en el disco Crucial X6.
- Elegir con Felipe el proyecto donde se escribe.

### 3. Prueba de lectura
Cada objeto del mapa en el navegador: carga sin errores de consola ni de red; ambos temas;
1920, 1180 y 390 px; teclado y foco. Cada hallazgo del mapa marcado «a verificar» se confirma o
se descarta con evidencia.

### 4. Prueba de escritura, una acción a la vez
Foto de los datos antes → acción desde la pantalla con un clic real → foto después →
comparación campo por campo → restauración por la misma API cuando se pueda. Lo que no se pueda
deshacer desde la aplicación se pregunta a Felipe antes de ejecutarlo y se anota para la
restauración final.

### 5. Roles
Al menos una cuenta con permiso y una sin él (`test.A`, `test.R`, `test.V` por la puerta de
servicio), cada una intentando lo que no le toca, en la pantalla y directo a la API.

### 6. Cierre
- **Restauración dirigida** de lo que dejó la prueba: escritura SQL en la base local, exige el
  `/visto-prod` de Felipe. Se prefiere a recargar el respaldo completo, que borraría lo que otras
  sesiones escribieron mientras tanto.
- Contenedor devuelto a la raíz del repo.
- **Ficha en la wiki** del módulo, con lint estricto en verde.
- **Hallazgos clasificados:** bloqueantes a un sprint de corrección; deuda u oportunidad a
  `TASKS.md`. Cada uno con su evidencia.

## Evidencia mínima por módulo

- Ficha en `memoria/arquitectura/`, enlazada desde la página del módulo y con su línea en
  `memoria/log.md`.
- Lista de hallazgos con gravedad, confirmados en navegador o marcados como no verificados.
- Registro de la restauración y de lo que se conservó a propósito.

## Lo que este protocolo no cambia

No autoriza escrituras en producción, despliegues, merges ni push: siguen siendo de Felipe. No
reemplaza las pruebas automáticas ni los gates del CI; los complementa con lo que solo se ve en
un navegador con datos reales.

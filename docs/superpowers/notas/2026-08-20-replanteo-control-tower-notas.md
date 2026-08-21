---
capa: fuente
tipo: nota-de-trabajo
estado: en-curso
fecha: 2026-08-20
areas: [bi, rbac, design-system]
fuente: brainstorming con Felipe, 2026-08-20
resumen: "Notas vivas del replanteo de la Control Tower: hallazgos del informe Power BI actual y decisiones de Felipe"
---

# Replanteo de la Control Tower — notas de trabajo

> Nota viva del brainstorming. No es contrato. Alimenta la spec que se escribirá al cerrar.

## Decisiones de Felipe (2026-08-20)

1. **Qué no sirve hoy:** no cuenta una historia · no se confía en las cifras · se ve y se siente mal.
   No marcó «nadie la usa para decidir» — hoy está oculta del menú.
2. **La desconfianza, en concreto:** no se sabe de dónde sale el número · cada quien lo calcula
   distinto · el dato llega tarde o incompleto.
3. **Audiencia:** tres, con caminos distintos — gerencia, obra, y socio/cliente/dueño.
4. **Orden de ataque:** primero el cimiento — una sola definición por métrica.
5. **El cimiento es la ley, no el papel:** la definición manda el cálculo; imposible que pantalla y
   documento discrepen.
6. **Dato incompleto:** se muestra la cifra y se declara de qué se está parando
   («8 de 12 obras, corte al viernes»).
7. **Punto de partida del inventario de métricas:** el informe Power BI que ya usa la gerencia,
   no la lista de 19 del código.

## Estado del código (verificado el 2026-08-20)

- Catálogo de 19 métricas en `src/Services/Bi/MetricDictionaryService.php`: definición, fórmula,
  fuente, grano, política de corte, limitaciones, versión. **Descriptivo, no ejecutable** — cada
  endpoint calcula por su cuenta. Nada obliga a que coincidan.
- `LineageService` lo expone por reporte vía `/api/bi/lineage`.
- Existen y están conectados por `ControlTowerService`: `StorytellingService`, `ForecastService`,
  `RiskScoringService`, `ActionRecommendationService`. ~2.000 líneas de maquinaria narrativa que no
  llega a la pantalla.
- La pantalla es un solo archivo: `public/js/modules/bi-spa.js`, 4.199 líneas.
- 8 vistas `/bi/*` y ~19 endpoints `/api/bi/*`.

## El informe Power BI actual — recorrido del 2026-08-20

`constructoraia.sharepoint.com/sites/TablerosAIA/SitePages/Last-Planner-AIA.aspx`
Fuente declarada: «Last Planner AIA Web App». Actualización diaria. Corte visto: 11/08/2026, semana 10.
Cuatro páginas, una obra a la vez: **Programa General · Última Semana · Liberación de Restricciones · Proveedores**.

### Métricas vistas

- **Programa General:** % Avance de Obra (17,98% contra 76,21%) · % Cumplimiento Cronograma (23,59%) ·
  Días de Retraso/Adelanto (88) · radar de tres ejes (% Actividades Comprometidas, % Ejecutado,
  % Cantidades Comprometidas) · tres tarjetas (24% / 24% / 20,17%) · Causas de No Programación ·
  Causas de No Cumplimiento · tabla de cumplimiento semanal por responsable.
- **Última Semana:** % Avance Semanal (4,84% contra 17,79%) · % Cumplimiento Plan Semanal (27,21%) ·
  # Actividades sin Programar (90) · mismo radar · tabla con Total Tareas Críticas / No Comprometida /
  No Cumplida / Atrasada.
- **Liberación de Restricciones:** Estado de Restricciones por Semana (barras por semanas para
  iniciar; 612 + 247 en «0 ya debió iniciar») · Liberación General (671 liberadas 66,57%, 318 sin
  gestionar 31,55%, 19 en proceso 1,88%) · Pareto de Restricciones No Liberadas (Actividad
  Predecesora 100, Materiales 77, Mano de Obra 62, Equipos 58, Diseños 18) · Número de Actividades
  Afectadas (91 / 4 / 10).
- **Proveedores:** Calificación Integral con pesos declarados — PAC 30%, Calidad 20%,
  Social-Ambiental 20%, SST 20%, Administración 10% · Aprobación de Proveedores · Promedio de
  Calificaciones Integrales. Filtros: Proyecto, NIT-Proveedor, Alcance, Tipo, Rango de Fechas.

### Hallazgos

- **Dos motores para la misma cifra.** El informe se alimenta de esta aplicación pero recalcula en
  Power BI; la Control Tower calcula otra vez en el servidor. Es el «cada quien lo calcula distinto»
  hecho arquitectura.
- **El filtro no gobierna toda la página.** Al filtrar por la causa «Otra» (3 actividades), las tres
  tarjetas pasaron a 100% / 100% / 40,73% y la tabla bajó a 3 actividades, pero % Avance de Obra,
  % Cumplimiento Cronograma y Días de Retraso **no se movieron**. Media pantalla habla de 3
  actividades y la otra media de la obra entera, sin señal de cuál es cuál.
- **Todo en rojo.** Avance, cumplimiento, retraso y las tres tarjetas laterales. Cuando todo grita,
  nada avisa.
- **Promete «Analice, Detecte, Actúe» y solo cumple el primero.** Muestra el qué; no el porqué ni el
  qué hacer.
- **Radar ilegible** en estado normal: el área queda diminuta contra la escala.
- **«88 Días de Retraso / Adelanto»**: el signo es ambiguo, la etiqueta no dice de qué lado está.
- **Tooltips:** no se disparan con hover inyectado desde esta sesión. Sí existen — ver la sección de
  abajo, con las capturas que aportó Felipe. El clic sí funciona y filtra.

### Proveedores — la calificación integral que no es integral (visto 2026-08-20, obra Optimización Aeropuerto JMC)

La tabla declara pesos: PAC 30%, Calidad 20%, Social-Ambiental 20%, SST 20%, Administración 10%.
**Solo la columna PAC trae datos**; las otras cuatro están vacías. Aun así se muestra un Total de
55,08% rotulado «Calificación Integral», y la gráfica «Promedio Calificaciones Integrales» sale en
blanco. Un proveedor con 100% en PAC y sin información en calidad, SST y ambiental se ve igual de
bien que uno completo.

Es el caso más claro del problema de fondo: el cálculo no está malo — la **etiqueta promete más de
lo que el dato sostiene**. Cualquier cimiento debe impedir que una métrica se publique como completa
cuando sus componentes no llegaron.

### Los tooltips sí existen y son páginas enteras (Felipe los mostró, 2026-08-20)

Corrección a la nota de arriba: el informe **sí** tiene tooltips, y no son textos — son páginas
emergentes de Power BI con visuales completas. No se disparan con hover inyectado desde esta sesión;
los capturó Felipe.

- **Sobre el medidor «% Avance de Obra» se despliega la Curva S de Cronograma**: % Ejecutado Semanal
  en barras, Curva S Teórica (roja) y Curva S Real (verde), de junio a septiembre de 2026.
- **Sobre «Causas de No Programación»** (184, 96,84%) se abre el desglose por causa:
  Restricciones habilitantes no cumplidas 182 (98,91%), y dos entradas de «Actividad predecesora
  incompleta / n…» de 1 (0,54%) cada una.
- **Sobre un segmento de «Causas de No Cumplimiento»** (14, 46,67%) se abre:
  Personal insuficiente (subcontratista), 14 (100%).

### Hallazgos que salen de ahí

- **La visual más valiosa está en el lugar más frágil.** La curva S responde «cómo vamos y hacia
  dónde», y solo la ve quien pase el mouse, en su propio computador, uno a la vez. En comité
  proyectado no existe; en captura tampoco; en pantalla táctil, menos.
- **Hay un nivel completo de detalle solo alcanzable por hover**: el paso de causa a subcausa. Es el
  «por qué» que la Guía de Acción promete, escondido tras un gesto que no se puede tocar ni
  compartir.
- **Categorías duplicadas o indistinguibles**: «Actividad predecesora incompleta / n…» aparece dos
  veces, con colores y conteos distintos y el texto truncado. Problema del catálogo de causas, no de
  la gráfica.
- **La Torre de Control ya tiene la curva S como pantalla propia** (`/bi/curva-s`). Lo que en Power
  BI está escondido, en la aplicación ya es de primer nivel: no hay que inventarlo, hay que decidir
  cuál manda.

## Dictado de Felipe — pendiente

<!-- Aquí van las anotaciones de lo que Felipe cuente sobre los tooltips y lo que la gerencia mira -->

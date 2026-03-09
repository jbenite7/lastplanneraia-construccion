# GLOSARIO DE TÉRMINOS - AIA LAST PLANNER

Este documento constituye la fuente oficial de términos técnicos y de negocio para el proyecto. Facilita la alineación entre el equipo de desarrollo, los residentes de obra y la dirección estratégica.

---

## 🏗️ I. Arquitectura Técnica y Entorno

1.  **Legacy (Construcción)**: Directorio base (`/construccion/`) con el código fuente original (PHP procedural) que gestiona la lógica histórica del proyecto.
2.  **Modern MVC (Src)**: Nueva estructura en `/src/` que implementa el patrón Modelo-Vista-Controlador para mejorar la mantenibilidad y escalabilidad.
3.  **Database (Singleton)**: Clase centralizada que garantiza una única instancia de conexión a la base de datos, optimizando el uso de recursos del servidor.
4.  **Handsontable**: Grilla interactiva de JavaScript que permite la edición masiva de datos con una experiencia similar a Excel.
5.  **Docker**: Plataforma de contenedores que estandariza el entorno de ejecución, asegurando consistencia entre desarrollo y producción.
6.  **Adminer**: Herramienta ligera de gestión de base de datos accesible vía web para realizar consultas y ajustes rápidos.
7.  **PHP 8.3/8.4**: Últimas versiones del lenguaje utilizadas en el proyecto para aprovechar mejoras en rendimiento, tipado y seguridad.
8.  **Mobile First**: Filosofía de diseño que prioriza la funcionalidad y estética en dispositivos móviles antes de adaptar a pantallas grandes.
9.  **Antigravity**: Asistente de IA y marco de trabajo enfocado en la excelencia técnica y proactividad en el código.
10. **Router**: Componente encargado de interpretar las URLs de navegación y delegar el control al controlador correspondiente.
11. **Front Controller**: Punto de entrada único del sistema (`public/index.php`) que procesa todas las solicitudes antes del enrutamiento.
12. **Service Layer**: Capa de abstracción donde reside la lógica de negocio pura, separándola de los controladores y las vistas.
13. **Core (Namespace)**: Conjunto de clases y utilidades fundamentales que sirven de base para todos los módulos del sistema.
14. **rbac_guard**: Middleware de seguridad que valida en tiempo real si el usuario tiene los permisos necesarios para acceder a un recurso.
15. **Autosave**: Característica de persistencia inmediata que guarda los cambios del usuario automáticamente en la base de datos sin recargar la página.
16. **Dual View**: Capacidad de las interfaces para alternar entre una tabla completa (Desktop) y una vista de tarjetas táctiles (Móvil).
17. **SQL Filter**: Capa de protección y saneamiento de entradas aplicada para prevenir vulnerabilidades de Inyección SQL.
18. **Composer**: Herramienta de gestión de dependencias para PHP que administra las librerías externas del proyecto.
19. **DB Prefix**: Prefijo dinámico utilizado en las consultas para identificar y conectar con la base de datos específica de cada proyecto.
20. **Singleton Pattern**: Patrón de diseño que restringe la instanciación de una clase a un solo objeto (común en la conexión a DB).
21. **Ajax Interface**: Tecnología que permite la comunicación asíncrona entre el navegador y el servidor para actualizar contenidos parcialmente.
22. **View Logic**: Separación estricta entre la presentación de datos y la lógica de procesamiento para facilitar cambios visuales.
23. **API Logic**: Estructura de endpoints diseñada para la comunicación eficiente entre diferentes componentes del sistema o servicios externos.
24. **Environment Variables (.env)**: Archivo de configuración centralizado para almacenar credenciales y parámetros sensibles del entorno.
25. **Log Activity**: Sistema de auditoría que registra las acciones críticas realizadas por los usuarios para trazabilidad y seguridad.

---

## 📊 II. Módulos Last Planner & Programación

26. **LPS (Last Planner System)**: Sistema del Último Planificador; metodología enmarcada en Lean Construction que prioriza compromisos reales.
27. **PAC (Porcentaje de Actividades Completadas)**: Indicador clave (PPC) que mide la eficacia del cumplimiento del plan semanal.
28. **CNC (Causas de No Cumplimiento)**: Categorización de las razones técnicas o logísticas por las cuales una actividad no se terminó.
29. **Look-Ahead (6 Semanas)**: Ventana de planificación intermedia utilizada para identificar y remover obstáculos con antelación.
30. **Actividad Liberada**: Tarea que tiene sus 7 recursos de liberación resueltos y está lista para ser ejecutada al 100%.
31. **CALIFICACIÓN INTEGRAL DE CONTRATISTAS (CIC)**: Evaluación métrica del desempeño y cumplimiento de los contratistas en el proyecto.
32. **7 Recursos de Liberación**: Diseños, Materiales, Mano de Obra, Equipos, Procedimiento Constructivo, Actividad Predecesora y Modelación BIM.
33. **Restricciones**: Obstáculos de cualquier índole (recursos faltantes) que impiden que una actividad sea considerada "liberada".
34. **Liberación de Restricciones**: Proceso sistemático de asegurar la disponibilidad de los 7 recursos previos para dar vía libre a la ejecución.
35. **CNP (Causas de No Programación)**: Razones por las que una actividad del Look-Ahead no pudo pasar al plan de la semana.
36. **Programación Semanal**: Conjunto de compromisos de ejecución detallados asumidos por los Last Planners para los próximos 7 días.
37. **Programa General**: Cronograma maestro que define la ruta principal del proyecto y los hitos contractuales.
38. **Hito (Milestone)**: Punto de control u objetivo notable que marca un logro significativo, sin duración propia necesariamente.
39. **Variabilidad**: Factores inciertos (clima, fallos de equipo) que afectan la estabilidad y confiabilidad del flujo de trabajo.
40. **Plan Estabilizado**: Estado de la programación donde se ha reducido la variabilidad al mínimo mediante la liberación de restricciones.
41. **Promesa Confiable**: Compromiso asumido por una persona tras verificar que cuenta con todos los recursos para cumplirlo.
42. **Frente de Obra**: División física o geográfica del proyecto utilizada para organizar y asignar recursos de manera eficiente.
43. **Sectorización**: Lógica de división del proyecto (pisos, torres, sectores) para facilitar el control y la programación.
44. **Last Planner (Rol)**: Persona con autoridad sobre los recursos que asume el compromiso final de ejecución (Maestro/Capataz).
45. **PPC (Percent Plan Complete)**: Métrica técnica equivalente al PAC; indicador de la confiabilidad del sistema de planificación.
46. **Flujo de Trabajo (Workflow)**: Secuencia estandarizada de estados y aprobaciones por la que transita una tarea desde su planeación hasta su cierre.
47. **Actualización Semanal**: Ritual de cierre de semana donde se reportan avances, se analizan CNC y se planifica la semana siguiente.
48. **Fecha de Inicio Original**: Fecha establecida contractualmente o en el cronograma base para el comienzo de una actividad.
49. **Fecha de Inicio Real**: Momento exacto en el que se iniciaron las labores físicas de una tarea en la obra.
50. **Actividad Predecesora**: Tarea cuyo cumplimiento o estado es requisito indispensable para el inicio de la actividad siguiente.

---

## 📝 III. Contratos, Compras y PDC

51. **Contrato**: Acuerdo formal que rige la relación técnica, económica y legal entre AIA y un tercero (proveedor/contratista).
52. **Paquete de Contratación**: Agrupación lógica de diversos ítems de obra que se adjudican bajo un mismo contrato.
53. **SI (Suministro e Instalación)**: Modalidad contractual que obliga al tercero a proveer el material y ejecutar su instalación.
54. **S (Suministro)**: Contrato enfocado exclusivamente en la entrega de materiales o insumos en sitio, sin mano de obra de instalación.
55. **MO (Mano de Obra)**: Contrato destinado únicamente a la ejecución física, donde los materiales son provistos por AIA u otro tercero.
56. **Adicional (Contratos)**: Modificación al contrato original que incrementa su alcance físico o su valor económico por necesidades de obra.
57. **Retención de Garantía**: Porcentaje de los pagos parciales que AIA retiene para asegurar la calidad final y la estabilidad de lo construido.
58. **PDC (Plan de Compras)**: Cronograma estratégico de adquisiciones sincronizado con las necesidades de flujo de la obra.
59. **Pestaña de Gestión**: Interfaz administrativa donde se configuran los parámetros base, roles y prefijos de cada proyecto.
60. **Ítem de Pago**: Unidad mínima de medida (ej: m2 de muro) utilizada para cuantificar el avance y procesar los pagos.
61. **Subcontratista**: Empresa o persona externa que asume la ejecución de paquetes específicos bajo supervisión del Residente.
62. **Orden de Servicio**: Autorización técnica rápida para la ejecución de tareas menores o específicas que no requieren un contrato complejo.
63. **Amortización**: Descuento progresivo que se aplica a los pagos de avance para devolver el valor del anticipo recibido.
64. **Anticipo**: Capital inicial entregado al contratista para facilitar la movilización de recursos e inicio de labores.
65. **Acta de Recibo**: Documento técnico que formaliza que un trabajo ha sido terminado y recibido a satisfacción por AIA.
66. **Cruce de Inventarios**: Proceso de validación contable y física entre los materiales comprados y los instalados realmente.
67. **Balance de Contrato**: Informe de estado que resume el valor total, los pagos realizados, saldos y adicionales aprobados.
68. **Vigencia**: Margen de tiempo estipulado en el contrato durante el cual las obligaciones y derechos son exigibles.
69. **Garantía**: Compromiso legal del contratista sobre la durabilidad y correcto funcionamiento de los trabajos ejecutados.
70. **Cláusula Penal**: Sanción económica o administrativa estipulada para casos de incumplimiento de los términos contractuales.

---

## 🔑 IV. Roles, Seguridad y Control de Cambios

71. **ODC (Orden de Cambio)**: Documento oficial para registrar y formalizar cualquier modificación técnica o económica al proyecto.
72. **Prioridad (Cambios)**: Nivel de urgencia (Baja, Media, Alta) asignado a una solicitud de cambio para su procesamiento.
73. **Tipo de Cambio (Costos)**: Modificación que impacta directamente el presupuesto del proyecto, requiriendo análisis financiero.
74. **Tipo de Cambio (Cronograma)**: Modificación que altera las fechas de hitos o la fecha final de entrega del proyecto.
75. **Aprobación de Cambio**: Flujo de estados (Pendiente, Revisado, Aprobado, Rechazado) por el que pasa una orden de cambio.
76. **Cargo (Compañía)**: Posición formal del empleado dentro de la organización AIA (ej: Gerente, Coordinador).
77. **Rol (Proyecto)**: Función específica y nivel de permisos asignado a un usuario en una obra particular (ej: Residente de la Torre A).
78. **Administrador (A)**: Rol con acceso total al sistema, creación de proyectos, gestión de usuarios y configuraciones globales.
79. **Director (D)**: Perfil de alta gerencia enfocado en la supervisión de indicadores y aprobación de cambios estratégicos.
80. **Residente (R)**: Rol operativo responsable de actualizar la programación diaria/semanal y reportar avances en sitio.
81. **SST (S)**: Responsable de Seguridad y Salud en el Trabajo; gestiona permisos de riesgo y recursos de protección.
82. **Ambiental (G)**: Perfil encargado del cumplimiento de la normativa ambiental y la gestión de residuos del proyecto.
83. **Oficina Técnica (OT)**: Rol dedicado a la gestión de presupuestos, control de costos, suministros y órdenes de cambio.
84. **Visualizador (V)**: Acceso de solo lectura orientado a auditoría, clientes o entes de control que no deben modificar datos.
85. **Inactividad de Sesión**: Seguridad que desconecta al usuario tras 1 hora de inactividad para proteger la integridad de los datos.

---

## 🚀 V. Otros / Metodologías Complementarias

86. **Manual de Marca**: Estándares visuales de AIA aplicados rigurosamente en la interfaz (colores corporativos, tipografía, logos).
87. **I.W.E (Inventario de Trabajo Ejecutable)**: Conjunto de tareas con restricciones resueltas que sirven de "colchón" si falla el plan principal.
88. **Constraint Log**: Registro dinámico donde se listan, asignan responsables y se rastrean todos los obstáculos detectados en el proyecto.
89. **RCA (Root Cause Analysis)**: Análisis de Causa Raíz; proceso profundo aplicado a las CNC recurrentes para evitar su repetición.
90. **Habilitación de Tareas**: Fase de preparación previa a la liberación, donde se analizan los flujos de materiales de largo plazo.
91. **Ciclo Constructivo**: Secuencia de actividades técnicas que se repiten rítmicamente (ej: encofrado, acero, concreto, fraguado).
92. **KPI de Gestión**: Indicadores que miden la eficiencia de los procesos administrativos y de soporte a la obra.
93. **Lecciones Aprendidas**: Repositorio de conocimientos derivados de experiencias previas que optimizan la planeación futura.
94. **Análisis de Capacidad**: Evaluación de si la mano de obra y equipos disponibles son suficientes para ejecutar el plan propuesto.
95. **Estabilidad del Flujo**: Indicador de qué tan continuo es el avance del trabajo, evitando los "pare y siga" en la obra.
96. **Desperdicio de Proceso**: Aquellas actividades que consumen tiempo y dinero sin mover el avance físico (ej: sobreproducción).
97. **Control de Avance Físico**: Metodología de medición directa en campo para validar la veracidad de los reportes en el sistema.
98. **Indicador de Restricciones**: Métrica que evalúa el éxito del equipo en resolver trabas antes de que lleguen a la ventana semanal.
99. **Dashboard Metodológico**: Visualización dinámica de los indicadores LPS para facilitar la toma de decisiones gerenciales.
100. **ROADMAP**: Documento vivo que proyecta el desarrollo técnico y las nuevas funcionalidades planificadas para el software.

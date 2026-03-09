# Plan de Implementación: Sistema de Alertas Tempranas AIA +CERTEZA

Este plan detalla la construcción del motor de alertas predictivas, desde la infraestructura base hasta la integración con IA.

## User Review Required

> [!IMPORTANT]
> **Esquema de Base de Datos**: Se propone la creación de una nueva tabla `system_notifications` para persistir las alertas.
> **Hooks en Handsontable**: Las alertas de sobreasignación se implementarán directamente en el frontend (`hot.js`) para feedback inmediato.

---

## Proposed Changes

### [Infraestructura & Backend]

#### [NEW] [create_notifications_table.sql](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/database/migrations/20260304_create_notifications_table.sql)
- Definición de la tabla `system_notifications`:
  - `id` (INT, PK)
  - `user_id` (VARCHAR) - Destinatario
  - `type` (ENUM: 'alert', 'warning', 'info')
  - `category` (ENUM: 'restriction', 'performance', 'capacity', 'ゾンビ')
  - `message` (TEXT)
  - `metadata` (JSON) - Para guardar IDs de actividades, proyectos, etc.
  - `is_read` (BOOLEAN)
  - `created_at` (TIMESTAMP)

#### [NEW] [NotificationService.php](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/src/Services/NotificationService.php)
- Servicio encargado de:
  - `push(userId, type, message, metadata)`: Insertar alertas en DB.
  - `getUnread(userId)`: Recuperar alertas para el Navbar.
  - `markAsRead(alertId)`: Gestión de estado.

#### [MODIFY] [ReportProcessor.php](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/src/Services/ReportProcessor.php)
- Integrar el trigger de **Auditoría de Silencios**:
  - Al procesar reportes, si una actividad programada no tiene cambio en `ejecutado` en 48h, invocar a `NotificationService`.

---

### [Motores de Lógica (Corto/Mediano Plazo)]

#### [NEW] [AlertEngine.php](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/src/Services/AlertEngine.php)
- Motor central para:
  - **Detección Zombie**: Script que corre tras cada carga de datos comparando snapshots de avance.
  - **Cálculo de Score 360**: Query que une `lps_ppc_history` con `calificacion_integral_contratista`.
  - **Shadow PPC**: Lógica de resta de restricciones sobre el PPC bruto.

#### [MODIFY] [listar_programacion_semanal.php](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/construccion/api/listar_programacion_semanal.php)
- Inyectar el cálculo de la **Proyección Inercial** y **Capacidad Teórica** en el JSON de salida para que Handsontable pueda renderizar los avisos de sobre-promesa.

---

### [Frontend & UI]

#### [MODIFY] [hot.js](file:///Users/juanfelipebenitezramos/last-planner-aia-legacy-permisos/public/js/hot.js) (O archivo equivalente de Handsontable)
- **Renderers OKLCH**: Implementar clases CSS dinámicas para los semáforos de 15/7/5/2 días.
- **Hook beforeChange**: Validar que el nuevo compromiso del subcontratista no supere su capacidad operativa calculada.
- **Top Bar Widget**: Implementar el mini-conteo de "Lo Urgente de Hoy" en el Navbar.

---

## Verification Plan

### Automated Tests
1. **Unidad**: Pruebas para `NotificationService::push()` y `markAsRead()`.
   - Comando: `docker-compose exec app php vendor/bin/phpunit tests/NotificationServiceTest.php`
2. **Integración**: Script para simular una "Actividad Zombie" y verificar que se genera la alerta.
   - Comando: `docker-compose exec app php scripts/test_zombie_detection.php`

### Manual Verification
1. **Checklist "Gate Zero"**:
   - Intentar confirmar una semana con restricciones abiertas.
   - Verificar que aparezca el modal de advertencia con la lista completa de bloqueadores.
2. **Visualización de Semáforos**:
   - Cambiar la fecha de una restricción a 48h del compromiso.
   - Verificar si la celda cambia a Rojo Vibrante (OKLCH).
3. **Notificación Push**:
   - Forzar una notificación desde Adminer y verificar que el contador de la campana en el Navbar se actualice sin recargar la página.

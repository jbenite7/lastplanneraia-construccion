---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-03-25
fuente: docs/20260325_general_feature_flags.md
resumen: Esquema SQL de referencia de la tabla general_feature_flags, que gobierna los interruptores de funcionalidad.
---

## SQL de referencia

```sql
CREATE TABLE IF NOT EXISTS `general_feature_flags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `flag_key` varchar(100) NOT NULL,
  `flag_value` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_flag_key` (`flag_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `general_feature_flags` (`flag_key`, `flag_value`, `description`, `updated_by`)
VALUES (
  'console_logs_enabled',
  0,
  'Controla la visualizacion global de console.log en el frontend.',
  'deploy'
)
ON DUPLICATE KEY UPDATE
  `flag_value` = VALUES(`flag_value`),
  `description` = VALUES(`description`),
  `updated_by` = VALUES(`updated_by`);
```

Nota: el código también crea esta tabla automáticamente con `CREATE TABLE IF NOT EXISTS` si aún no existe, para evitar que el switch falle en el primer deploy.

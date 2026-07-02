-- Cierra la separacion del catalogo:
-- `general_pdc_familias` queda sin aliases conocidos ni elementos contractuales
-- que ya tienen representacion en tablas dedicadas.

UPDATE `general_pdc_familias` f
JOIN `general_pdc_family_aliases` a ON a.alias_family_id = f.id AND a.activa = 1
SET f.activa = 0
WHERE COALESCE(f.activa, 1) = 1;

UPDATE `general_pdc_familias` f
JOIN `general_pdc_contractual_elements` e
  ON e.nombre COLLATE utf8mb4_unicode_ci = f.nombre COLLATE utf8mb4_unicode_ci
 AND e.activa = 1
SET f.activa = 0
WHERE COALESCE(f.activa, 1) = 1;

UPDATE `general_pdc_activity_rules` r
JOIN `general_pdc_familias` f ON f.id = r.familia_id
SET r.activa = 0
WHERE COALESCE(f.activa, 1) = 0
  AND r.activa = 1;

SET @ci_fixture_timestamp = '2026-01-01 00:00:00';

UPDATE `general_pdc_familias`
SET `created_at` = @ci_fixture_timestamp, `updated_at` = @ci_fixture_timestamp;
UPDATE `general_pdc_activity_rules`
SET `created_at` = @ci_fixture_timestamp;
UPDATE `general_pdc_paquete_aliases`
SET `created_at` = @ci_fixture_timestamp;
UPDATE `general_pdc_family_contract_options`
SET `created_at` = @ci_fixture_timestamp, `updated_at` = @ci_fixture_timestamp;
UPDATE `general_pdc_family_contract_option_items`
SET `created_at` = @ci_fixture_timestamp;
UPDATE `general_pdc_project_family_strategy`
SET `created_at` = @ci_fixture_timestamp, `updated_at` = @ci_fixture_timestamp;
UPDATE `general_dias_defaults_categoria`
SET `created_at` = @ci_fixture_timestamp, `updated_at` = @ci_fixture_timestamp;
UPDATE `general_pdc_family_aliases`
SET `created_at` = @ci_fixture_timestamp, `updated_at` = @ci_fixture_timestamp;
UPDATE `general_pdc_contractual_elements`
SET `created_at` = @ci_fixture_timestamp, `updated_at` = @ci_fixture_timestamp;
UPDATE `general_pdc_family_rule_audit`
SET `created_at` = @ci_fixture_timestamp;

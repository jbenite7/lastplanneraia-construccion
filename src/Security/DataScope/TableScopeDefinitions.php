<?php

declare(strict_types=1);

namespace App\Security\DataScope;

final class TableScopeDefinitions
{
    public const IDENTITY = [
        'general_proyectos_procesos',
        'general_usuarios',
        'password_history',
        'password_reset_tokens',
        'project_members',
        'rbac_permissions',
        'rbac_role_permissions',
        'rbac_roles',
        'system_notifications',
    ];

    public const SYSTEM = [
        'bi_lineage',
        'event_dictionary',
        'general_auditoria_acciones',
        'general_cnc',
        'general_codigos_actividades',
        'general_costos_cuadrillas',
        'general_cuadrillas_tipicas',
        'general_curvas_pdc_apr',
        'general_dias_defaults_categoria',
        'general_dias_procesos_contratacion',
        'general_feature_flags',
        'general_flags',
        'general_maestro_insumos',
        'general_matching_config',
        'general_paquetes_contratacion',
        'general_pasos_contratacion',
        'general_pdc_activity_rules',
        'general_pdc_chapter_category_map',
        'general_pdc_contractual_elements',
        'general_pdc_familias',
        'general_pdc_family_aliases',
        'general_pdc_family_contract_option_items',
        'general_pdc_family_contract_options',
        'general_pdc_family_rule_audit',
        'general_pdc_paquete_aliases',
        'general_rama_frente',
        'notification_types',
        'role_intelligence',
        'role_notification_defaults',
        'v_pdc_inventory',
    ];
}

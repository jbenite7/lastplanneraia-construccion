<?php

namespace App\Security;

class RbacCatalog
{
    public const DEFAULT_ROLE = 'C';
    public const PERM_AUTO_DEFINIR_CONTRATOS = 'lps.contratos.auto_definir';

    public static function roleAliases(): array
    {
        return [
            'P' => 'D',
            'U' => 'V',
            'RESIDENTE DE OBRA' => 'R',
            'DIRECTOR DE OBRA' => 'D',
            'PROFESIONAL DCV' => 'DCV',
            'PROFESIONAL SST' => 'S',
            'PROFESIONAL AMBIENTAL' => 'G',
            'PROFESIONAL SST + GSA' => 'SG',
            'SUBCONTRATISTA' => 'C',
            'VISUALIZADOR' => 'V',
        ];
    }

    public static function getRoleName(string $code): string
    {
        $names = [
            'A' => 'Administrador',
            'D' => 'Director de Obra',
            'R' => 'Residente de Obra',
            'DCV' => 'Profesional DCV',
            'OT' => 'Oficina Técnica / Compras',
            'G' => 'Ambiental',
            'S' => 'Seguridad SST',
            'SG' => 'SST + Ambiental',
            'C' => 'Subcontratista',
            'V' => 'Visualizador',
        ];

        return $names[$code] ?? 'Desconocido';
    }

    public static function canonicalRoles(): array
    {
        return ['A', 'D', 'R', 'DCV', 'OT', 'G', 'S', 'SG', 'C', 'V'];
    }

    public static function permissionDefinitions(): array
    {
        return [
            ['key' => 'admin.panel.acceso', 'module' => 'admin', 'action' => 'acceso', 'description' => 'Acceso al panel admin'],
            ['key' => 'admin.usuarios.gestionar', 'module' => 'admin', 'action' => 'gestionar_usuarios', 'description' => 'Gestion de usuarios'],
            ['key' => 'admin.proyectos.gestionar', 'module' => 'admin', 'action' => 'gestionar_proyectos', 'description' => 'Gestion de proyectos'],
            ['key' => 'admin.miembros.gestionar', 'module' => 'admin', 'action' => 'gestionar_miembros', 'description' => 'Gestion de miembros'],
            ['key' => 'admin.permisos.gestionar', 'module' => 'admin', 'action' => 'gestionar_permisos', 'description' => 'Gestion de permisos'],
            ['key' => 'admin.auditoria.ver', 'module' => 'admin', 'action' => 'ver_auditoria', 'description' => 'Ver auditoria'],
            ['key' => 'admin.matching.config.editar', 'module' => 'admin', 'action' => 'editar_config_matching', 'description' => 'Editar configuracion de matching semantico'],

            ['key' => 'lps.programa_general.ver', 'module' => 'lps', 'action' => 'programa_general_ver', 'description' => 'Ver programa general'],
            ['key' => 'lps.programa_general.editar', 'module' => 'lps', 'action' => 'programa_general_editar', 'description' => 'Editar programa general'],
            ['key' => 'lps.programa_general_actualizar.ver', 'module' => 'lps', 'action' => 'programa_general_actualizar_ver', 'description' => 'Ver programa general actualizar'],
            ['key' => 'lps.programa_general_actualizar.editar', 'module' => 'lps', 'action' => 'programa_general_actualizar_editar', 'description' => 'Editar programa general actualizar'],
            ['key' => 'lps.programacion_intermedia.ver', 'module' => 'lps', 'action' => 'programacion_intermedia_ver', 'description' => 'Ver programacion intermedia'],
            ['key' => 'lps.programacion_intermedia.editar', 'module' => 'lps', 'action' => 'programacion_intermedia_editar', 'description' => 'Editar programacion intermedia'],
            ['key' => 'lps.programacion_semanal.ver', 'module' => 'lps', 'action' => 'programacion_semanal_ver', 'description' => 'Ver programacion semanal'],
            ['key' => 'lps.programacion_semanal.editar', 'module' => 'lps', 'action' => 'programacion_semanal_editar', 'description' => 'Editar programacion semanal'],
            ['key' => 'lps.cic.ver', 'module' => 'lps', 'action' => 'cic_ver', 'description' => 'Ver CIC'],
            ['key' => 'lps.cic.editar', 'module' => 'lps', 'action' => 'cic_editar', 'description' => 'Editar CIC'],
            ['key' => 'lps.cnc.ver', 'module' => 'lps', 'action' => 'cnc_ver', 'description' => 'Ver CNC'],
            ['key' => 'lps.cnc.editar', 'module' => 'lps', 'action' => 'cnc_editar', 'description' => 'Editar CNC'],
            ['key' => 'lps.cnp.ver', 'module' => 'lps', 'action' => 'cnp_ver', 'description' => 'Ver CNP'],
            ['key' => 'lps.cnp.editar', 'module' => 'lps', 'action' => 'cnp_editar', 'description' => 'Editar CNP'],
            ['key' => 'lps.listado_actividades.ver', 'module' => 'lps', 'action' => 'listado_actividades_ver', 'description' => 'Ver listado de actividades'],
            ['key' => 'lps.listado_actividades.editar', 'module' => 'lps', 'action' => 'listado_actividades_editar', 'description' => 'Editar listado de actividades'],
            ['key' => 'lps.contratos.ver', 'module' => 'lps', 'action' => 'contratos_ver', 'description' => 'Ver contratos'],
            ['key' => 'lps.contratos.editar', 'module' => 'lps', 'action' => 'contratos_editar', 'description' => 'Editar contratos'],
            ['key' => 'lps.contratos.auto_definir', 'module' => 'lps', 'action' => 'contratos_auto_definir', 'description' => 'Auto-definir contratos con preview y confianza'],
            ['key' => 'lps.pdc.ver', 'module' => 'lps', 'action' => 'pdc_ver', 'description' => 'Ver PDC'],
            ['key' => 'lps.pdc.editar', 'module' => 'lps', 'action' => 'pdc_editar', 'description' => 'Editar PDC'],
            ['key' => 'lps.pdc.auto_generar', 'module' => 'lps', 'action' => 'pdc_auto_generar', 'description' => 'Auto-generar PDC desde el programa general'],
            ['key' => 'lps.control_cambios.ver', 'module' => 'lps', 'action' => 'control_cambios_ver', 'description' => 'Ver control de cambios'],
            ['key' => 'lps.control_cambios.editar', 'module' => 'lps', 'action' => 'control_cambios_editar', 'description' => 'Editar control de cambios'],
            ['key' => 'lps.paquetes_contratacion.ver', 'module' => 'lps', 'action' => 'paquetes_contratacion_ver', 'description' => 'Ver paquetes de contratacion'],
            ['key' => 'lps.paquetes_contratacion.editar', 'module' => 'lps', 'action' => 'paquetes_contratacion_editar', 'description' => 'Editar paquetes de contratacion'],
            ['key' => 'lps.profesionales.ver', 'module' => 'lps', 'action' => 'profesionales_ver', 'description' => 'Ver profesionales'],
            ['key' => 'lps.profesionales.editar', 'module' => 'lps', 'action' => 'profesionales_editar', 'description' => 'Editar profesionales'],
            ['key' => 'lps.subcontratistas.ver', 'module' => 'lps', 'action' => 'subcontratistas_ver', 'description' => 'Ver subcontratistas'],
            ['key' => 'lps.subcontratistas.editar', 'module' => 'lps', 'action' => 'subcontratistas_editar', 'description' => 'Editar subcontratistas'],
            ['key' => 'lps.indicadores.ver', 'module' => 'lps', 'action' => 'indicadores_ver', 'description' => 'Ver indicadores'],
            ['key' => 'lps.reportes.generar', 'module' => 'lps', 'action' => 'reportes_generar', 'description' => 'Generar reportes'],
            ['key' => 'lps.semana.crear', 'module' => 'lps', 'action' => 'semana_crear', 'description' => 'Crear nuevas semanas en el proyecto'],
            ['key' => 'lps.semana.eliminar', 'module' => 'lps', 'action' => 'semana_eliminar', 'description' => 'Eliminar semanas del proyecto'],

            ['key' => 'notificaciones.resumen_semanal', 'module' => 'notificaciones', 'action' => 'resumen_semanal', 'description' => 'Recibir resumen semanal'],
            ['key' => 'notificaciones.cic_especialidad', 'module' => 'notificaciones', 'action' => 'cic_especialidad', 'description' => 'Recibir alertas CIC por especialidad'],
            ['key' => 'notificaciones.lps_operativas', 'module' => 'notificaciones', 'action' => 'lps_operativas', 'description' => 'Recibir alertas operativas LPS'],
            ['key' => 'notificaciones.gestion_pdc_contratos', 'module' => 'notificaciones', 'action' => 'gestion_pdc_contratos', 'description' => 'Recibir alertas de gestion PDC y contratos'],
            ['key' => 'notificaciones.ejecutivas', 'module' => 'notificaciones', 'action' => 'ejecutivas', 'description' => 'Recibir alertas ejecutivas'],
            ['key' => 'notificaciones.admin_seguridad', 'module' => 'notificaciones', 'action' => 'admin_seguridad', 'description' => 'Recibir alertas de administracion y seguridad'],
            ['key' => 'notificaciones.preferencias_personales', 'module' => 'notificaciones', 'action' => 'preferencias_personales', 'description' => 'Gestionar preferencias personales de notificacion'],
        ];
    }

    public static function permissionKeys(): array
    {
        return array_column(self::permissionDefinitions(), 'key');
    }

    public static function fallbackPermissionsByRole(): array
    {
        $allRead = [
            'lps.programa_general.ver',
            'lps.programa_general_actualizar.ver',
            'lps.programacion_intermedia.ver',
            'lps.programacion_semanal.ver',
            'lps.cic.ver',
            'lps.cnc.ver',
            'lps.cnp.ver',
                'lps.listado_actividades.ver',
                'lps.listado_actividades.editar',
                'lps.contratos.ver',
                'lps.contratos.editar',
                'lps.pdc.ver',
                'lps.pdc.editar',
                'lps.pdc.auto_generar',
            'lps.control_cambios.ver',
            'lps.paquetes_contratacion.ver',
            'lps.profesionales.ver',
            'lps.subcontratistas.ver',
            'lps.indicadores.ver',
            'lps.reportes.generar',
        ];

        $allWrite = [
            'lps.programa_general.editar',
            'lps.programa_general_actualizar.editar',
            'lps.programacion_intermedia.editar',
            'lps.programacion_semanal.editar',
            'lps.cic.editar',
            'lps.cnc.editar',
            'lps.cnp.editar',
            'lps.listado_actividades.editar',
            'lps.contratos.editar',
            'lps.pdc.editar',
            'lps.pdc.auto_generar',
            'lps.control_cambios.editar',
            'lps.paquetes_contratacion.editar',
            'lps.profesionales.editar',
            'lps.subcontratistas.editar',
        ];

        return [
            'A' => ['*'],

            // Director funcional: control total operativo, sin administracion de sistema.
            'D' => array_merge(
                $allRead,
                $allWrite,
                [
                    'lps.contratos.auto_definir',
                    'lps.semana.crear',
                    'lps.semana.eliminar',
                    'notificaciones.resumen_semanal',
                    'notificaciones.cic_especialidad',
                    'notificaciones.lps_operativas',
                    'notificaciones.gestion_pdc_contratos',
                    'notificaciones.ejecutivas',
                    'notificaciones.preferencias_personales',
                ],
            ),

            // Residente: edita LPS y Listado/Contratos/PDC completos.
            'R' => [
                'lps.semana.crear',
                'lps.semana.eliminar',
                'lps.programa_general.ver',
                'lps.programa_general.editar',
                'lps.programa_general_actualizar.ver',
                'lps.programa_general_actualizar.editar',
                'lps.programacion_intermedia.ver',
                'lps.programacion_intermedia.editar',
                'lps.programacion_semanal.ver',
                'lps.programacion_semanal.editar',
                'lps.cic.ver',
                'lps.cic.editar',
                'lps.cnc.ver',
                'lps.cnc.editar',
                'lps.cnp.ver',
                'lps.cnp.editar',
                'lps.control_cambios.ver',
                'lps.control_cambios.editar',
                'lps.listado_actividades.ver',
                'lps.listado_actividades.editar',
                'lps.contratos.ver',
                'lps.contratos.editar',
                'lps.pdc.ver',
                'lps.pdc.editar',
                'lps.pdc.auto_generar',
                'lps.paquetes_contratacion.ver',
                'lps.profesionales.ver',
                'lps.subcontratistas.ver',
                'lps.subcontratistas.editar',
                'lps.indicadores.ver',
                'lps.reportes.generar',
                'notificaciones.resumen_semanal',
                'notificaciones.lps_operativas',
                'notificaciones.gestion_pdc_contratos',
                'notificaciones.preferencias_personales',
            ],
            'DCV' => [
                'lps.semana.crear',
                'lps.programa_general.ver',
                'lps.programa_general.editar',
                'lps.programa_general_actualizar.ver',
                'lps.programa_general_actualizar.editar',
                'lps.programacion_intermedia.ver',
                'lps.programacion_intermedia.editar',
                'lps.programacion_semanal.ver',
                'lps.programacion_semanal.editar',
                'lps.cic.ver',
                'lps.cic.editar',
                'lps.cnc.ver',
                'lps.cnc.editar',
                'lps.cnp.ver',
                'lps.cnp.editar',
                'lps.control_cambios.ver',
                'lps.control_cambios.editar',
                'lps.listado_actividades.ver',
                'lps.contratos.ver',
                'lps.pdc.ver',
                'lps.paquetes_contratacion.ver',
                'lps.profesionales.ver',
                'lps.subcontratistas.ver',
                'lps.subcontratistas.editar',
                'lps.indicadores.ver',
                'lps.reportes.generar',
                'notificaciones.resumen_semanal',
                'notificaciones.lps_operativas',
                'notificaciones.gestion_pdc_contratos',
                'notificaciones.preferencias_personales',
            ],

            // Oficina tecnica: solo edita Listado/Contratos/PDC; lo demas lectura.
            'OT' => [
                'lps.contratos.auto_definir',
                'lps.semana.crear',
                'lps.semana.eliminar',
                'lps.programa_general.ver',
                'lps.programa_general_actualizar.ver',
                'lps.programacion_intermedia.ver',
                'lps.programacion_semanal.ver',
                'lps.cic.ver',
                'lps.cnc.ver',
                'lps.cnp.ver',
                'lps.control_cambios.ver',
                'lps.paquetes_contratacion.ver',
                'lps.profesionales.ver',
                'lps.subcontratistas.ver',
                'lps.indicadores.ver',
                'lps.listado_actividades.ver',
                'lps.listado_actividades.editar',
                'lps.contratos.ver',
                'lps.contratos.editar',
                'lps.pdc.ver',
                'lps.pdc.editar',
                'lps.pdc.auto_generar',
                'lps.reportes.generar',
                'notificaciones.resumen_semanal',
                'notificaciones.lps_operativas',
                'notificaciones.gestion_pdc_contratos',
                'notificaciones.preferencias_personales',
            ],

            // SST, Ambiental y mixto: solo CIC.
            'G' => [
                'lps.programa_general.ver',
                'lps.programacion_intermedia.ver',
                'lps.programacion_semanal.ver',
                'lps.cic.ver',
                'lps.cic.editar',
                'notificaciones.resumen_semanal',
                'notificaciones.cic_especialidad',
                'notificaciones.preferencias_personales',
            ],
            'S' => [
                'lps.programa_general.ver',
                'lps.programacion_intermedia.ver',
                'lps.programacion_semanal.ver',
                'lps.cic.ver',
                'lps.cic.editar',
                'notificaciones.resumen_semanal',
                'notificaciones.cic_especialidad',
                'notificaciones.preferencias_personales',
            ],
            'SG' => [
                'lps.programa_general.ver',
                'lps.programacion_intermedia.ver',
                'lps.programacion_semanal.ver',
                'lps.cic.ver',
                'lps.cic.editar',
                'notificaciones.resumen_semanal',
                'notificaciones.cic_especialidad',
                'notificaciones.preferencias_personales',
            ],

            // Subcontratista: sin acciones in-app por ahora.
            'C' => [
                'notificaciones.resumen_semanal',
                'notificaciones.cic_especialidad',
                'notificaciones.preferencias_personales',
            ],

            // Rol legacy: solo lectura.
            'V' => array_merge(
                $allRead,
                [
                    'notificaciones.resumen_semanal',
                    'notificaciones.lps_operativas',
                    'notificaciones.preferencias_personales',
                ],
            ),
        ];
    }

    public static function eventDictionary(): array
    {
        return [
            'seguridad.auth' => [
                'login_exitoso' => ['modulo_legacy' => 'Seguridad', 'accion_legacy' => 'LOGIN'],
                'login_fallido' => ['modulo_legacy' => 'Seguridad', 'accion_legacy' => 'LOGIN_FALLIDO'],
                'logout' => ['modulo_legacy' => 'Seguridad', 'accion_legacy' => 'LOGOUT'],
            ],
            'seguridad.proyecto' => [
                'acceso' => ['modulo_legacy' => 'Login', 'accion_legacy' => 'ACCESO_PROYECTO'],
            ],
            'seguridad.autorizacion' => [
                'denegada' => ['modulo_legacy' => 'Seguridad', 'accion_legacy' => 'AUTORIZACION_DENEGADA'],
            ],
            'rbac.usuario' => [
                'crear' => ['modulo_legacy' => 'Usuarios', 'accion_legacy' => 'CREAR'],
                'modificar' => ['modulo_legacy' => 'Usuarios', 'accion_legacy' => 'MODIFICAR'],
                'eliminar' => ['modulo_legacy' => 'Usuarios', 'accion_legacy' => 'ELIMINAR'],
            ],
            'rbac.miembro_proyecto' => [
                'agregar' => ['modulo_legacy' => 'Miembros', 'accion_legacy' => 'CREAR'],
                'actualizar_rol' => ['modulo_legacy' => 'Miembros', 'accion_legacy' => 'MODIFICAR'],
                'remover' => ['modulo_legacy' => 'Miembros', 'accion_legacy' => 'ELIMINAR'],
            ],
            'rbac.permisos' => [
                'actualizar_rol' => ['modulo_legacy' => 'Seguridad', 'accion_legacy' => 'RBAC_ACTUALIZAR_ROL'],
                'migracion_p_a_d' => ['modulo_legacy' => 'Sistema', 'accion_legacy' => 'MIGRACION_P_A_D'],
            ],
            'proyecto.lifecycle' => [
                'crear' => ['modulo_legacy' => 'Proyectos', 'accion_legacy' => 'CREAR'],
                'modificar' => ['modulo_legacy' => 'Proyectos', 'accion_legacy' => 'MODIFICAR'],
                'eliminar' => ['modulo_legacy' => 'Proyectos', 'accion_legacy' => 'ELIMINAR'],
                'estado' => ['modulo_legacy' => 'Proyectos', 'accion_legacy' => 'ESTADO'],
                'backup' => ['modulo_legacy' => 'Proyectos', 'accion_legacy' => 'BACKUP'],
            ],
            'lps.listado_actividades' => [
                'crear' => ['modulo_legacy' => 'ListadoActividades', 'accion_legacy' => 'CREAR'],
                'modificar' => ['modulo_legacy' => 'ListadoActividades', 'accion_legacy' => 'MODIFICAR'],
                'eliminar' => ['modulo_legacy' => 'ListadoActividades', 'accion_legacy' => 'ELIMINAR'],
                'importar' => ['modulo_legacy' => 'ListadoActividades', 'accion_legacy' => 'IMPORTAR'],
            ],
            'lps.contratos' => [
                'modificar' => ['modulo_legacy' => 'Contratos', 'accion_legacy' => 'MODIFICAR'],
                'crear_dias_proceso' => ['modulo_legacy' => 'Contratos', 'accion_legacy' => 'CREAR_DIAS_PROCESO'],
            ],
            'lps.pdc' => [
                'crear' => ['modulo_legacy' => 'PDC', 'accion_legacy' => 'CREAR_ACTIVIDAD'],
                'modificar' => ['modulo_legacy' => 'PDC', 'accion_legacy' => 'MODIFICAR_ACTIVIDAD'],
                'eliminar' => ['modulo_legacy' => 'PDC', 'accion_legacy' => 'ELIMINAR_ACTIVIDAD'],
                'adjudicar' => ['modulo_legacy' => 'PDC', 'accion_legacy' => 'ADJUDICAR_PDC'],
                'modificar_celda' => ['modulo_legacy' => 'PDC', 'accion_legacy' => 'MODIFICAR_CELDA'],
            ],
            'lps.programacion_intermedia' => [
                'modificar' => ['modulo_legacy' => 'ProgramacionIntermedia', 'accion_legacy' => 'MODIFICAR'],
                'shared_restriction_apply' => ['modulo_legacy' => 'ProgramacionIntermedia', 'accion_legacy' => 'SHARED_RESTRICTION_APPLY'],
            ],
            'lps.programacion_semanal' => [
                'modificar' => ['modulo_legacy' => 'ProgramacionSemanal', 'accion_legacy' => 'MODIFICAR'],
            ],
            'lps.cic' => [
                'modificar' => ['modulo_legacy' => 'ProgramacionSemanal', 'accion_legacy' => 'MODIFICAR_CIC'],
            ],
            'lps.cnc' => [
                'modificar' => ['modulo_legacy' => 'ProgramacionSemanal', 'accion_legacy' => 'MODIFICAR_CNC'],
            ],
            'lps.cnp' => [
                'modificar' => ['modulo_legacy' => 'ProgramacionSemanal', 'accion_legacy' => 'MODIFICAR_CNP'],
            ],
            'lps.control_cambios' => [
                'crear' => ['modulo_legacy' => 'ControlCambios', 'accion_legacy' => 'CREAR'],
                'modificar' => ['modulo_legacy' => 'ControlCambios', 'accion_legacy' => 'MODIFICAR'],
                'eliminar' => ['modulo_legacy' => 'ControlCambios', 'accion_legacy' => 'ELIMINAR'],
                'nueva_semana' => ['modulo_legacy' => 'ControlCambios', 'accion_legacy' => 'NUEVA_SEMANA'],
                'eliminar_semana' => ['modulo_legacy' => 'ControlCambios', 'accion_legacy' => 'ELIMINAR_SEMANA'],
            ],
            'sistema.reportes' => [
                'generar_curva_s' => ['modulo_legacy' => 'Sistema', 'accion_legacy' => 'GENERAR_CURVA_S'],
                'generar_curva_s_pdc' => ['modulo_legacy' => 'Sistema', 'accion_legacy' => 'GENERAR_CURVA_S_PDC'],
                'generar_curva_sb' => ['modulo_legacy' => 'Sistema', 'accion_legacy' => 'GENERAR_CURVA_SB'],
                'generar_reporte_gral' => ['modulo_legacy' => 'Sistema', 'accion_legacy' => 'GENERAR_REPORTE_GRAL'],
                'generar_reporte_pdc' => ['modulo_legacy' => 'Sistema', 'accion_legacy' => 'GENERAR_REPORTE_PDC'],
                'generar_restricciones_gral' => ['modulo_legacy' => 'Sistema', 'accion_legacy' => 'GENERAR_RESTRICCIONES_GRAL'],
            ],
            'sistema.procesos' => [
                'autoprogramar' => ['modulo_legacy' => 'Sistema', 'accion_legacy' => 'AUTOPROGRAMAR'],
                'actualizar_pdc_nueva_semana' => ['modulo_legacy' => 'Sistema', 'accion_legacy' => 'PDC_ACTUALIZAR'],
                'limpieza' => ['modulo_legacy' => 'Sistema', 'accion_legacy' => 'LIMPIEZA'],
            ],
            'notificaciones.planeadas' => [
                'resumen_semanal' => ['modulo_legacy' => 'Notificaciones', 'accion_legacy' => 'PLAN_RESUMEN_SEMANAL'],
                'cic_especialidad' => ['modulo_legacy' => 'Notificaciones', 'accion_legacy' => 'PLAN_CIC_ESPECIALIDAD'],
                'lps_operativas' => ['modulo_legacy' => 'Notificaciones', 'accion_legacy' => 'PLAN_LPS_OPERATIVAS'],
                'gestion_pdc_contratos' => ['modulo_legacy' => 'Notificaciones', 'accion_legacy' => 'PLAN_GESTION_PDC_CONTRATOS'],
                'ejecutivas' => ['modulo_legacy' => 'Notificaciones', 'accion_legacy' => 'PLAN_EJECUTIVAS'],
                'admin_seguridad' => ['modulo_legacy' => 'Notificaciones', 'accion_legacy' => 'PLAN_ADMIN_SEGURIDAD'],
                'preferencias_personales' => ['modulo_legacy' => 'Notificaciones', 'accion_legacy' => 'PLAN_PREFERENCIAS_PERSONALES'],
            ],
            'notificaciones.envio' => [
                'enviada' => ['modulo_legacy' => 'Notificaciones', 'accion_legacy' => 'ENVIADA'],
                'fallida' => ['modulo_legacy' => 'Notificaciones', 'accion_legacy' => 'FALLIDA'],
            ],
        ];
    }
}

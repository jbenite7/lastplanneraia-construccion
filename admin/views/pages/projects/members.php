<?php use Admin\Core\RoleManager;

?>
<div class="row">
    <!-- Listado de Miembros Actuales -->
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users-cog mr-2"></i> Miembros Asignados</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-valign-middle">
                    <thead>
                        <tr>
                            <th>Nombre / Usuario</th>
                            <th>Cargo</th>
                            <th class="text-center">Rol del Proyecto</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No hay miembros asignados a este proyecto todavía.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($member['nombre']); ?></strong><br>
                                        <small class="text-muted">@<?php echo htmlspecialchars($member['usuario']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['cargo']); ?></td>
                                    <td class="text-center">
                                        <?php
                                            $roleName = RoleManager::getRoleName($member['role']);
                                $roleColor = RoleManager::getRoleColor($member['role']);
                                ?>
                                        <span class="badge bg-<?php echo $roleColor; ?> p-2" title="<?php echo $member['role']; ?>" style="min-width: 120px; display: inline-block;">
                                            <?php echo htmlspecialchars($roleName); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger remove-member" 
                                                data-id="<?php echo $member['user_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($member['nombre']); ?>">
                                            <i class="fas fa-user-minus"></i> Quitar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Panel para añadir miembros -->
    <div class="col-md-4">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i> Añadir Miembro</h3>
            </div>
            <div class="card-body">
                <form action="/admin/proyectos/miembros/añadir" method="POST" id="addMemberForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="project_id" value="<?php echo $project['Id']; ?>">
                    
                    <div class="form-group">
                        <label for="user_id">Seleccionar Usuario</label>
                        <select name="user_id" id="user_id" class="form-control select2" required>
                            <option value="">-- Buscar por nombre o usuario --</option>
                            <?php foreach ($availableUsers as $user): ?>
                                <option value="<?php echo $user['id']; ?>" data-cargo="<?php echo htmlspecialchars($user['cargo'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($user['nombre']); ?> (@<?php echo htmlspecialchars($user['usuario']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="cargoSuggestion" class="mt-1 small text-info" style="display:none;">
                            <i class="fas fa-lightbulb"></i> Cargo detectado: <span id="cargoText"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role">Rol en el Proyecto</label>
                        <select name="role" id="role" class="form-control select2">
                            <?php foreach ($roles as $code => $info): ?>
                                <option value="<?php echo $code; ?>">
                                    <?php echo $code; ?> - <?php echo htmlspecialchars($info['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" id="roleDescription">
                            <?php echo htmlspecialchars($roles['A']['description']); ?>
                        </small>
                    </div>

                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-check-circle"></i> Asignar al Proyecto
                    </button>
                </form>
            </div>
            <div class="card-footer small text-muted">
                <i class="fas fa-info-circle"></i> Solo se muestran usuarios que aún no pertenecen a este proyecto.
            </div>
        </div>
        
        <a href="/admin/proyectos" class="btn btn-default btn-block shadow-sm">
            <i class="fas fa-arrow-left"></i> Volver al Listado de Proyectos
        </a>
    </div>
</div>

<!-- Formulario oculto para eliminar miembros -->
<form id="removeMemberForm" action="/admin/proyectos/miembros/quitar" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="project_id" value="<?php echo $project['Id']; ?>">
    <input type="hidden" name="user_id" id="removeUserId">
</form>

<script>
$(function() {
    // Diccionario de roles para JS (para las descripciones)
    const rolesInfo = <?php echo json_encode($roles); ?>;

    // Inicializar Select2
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4'
        });
    }

    /**
     * Motor de Limpieza de Cargos (Blindaje contra variaciones)
     */
    function cleanCargo(text) {
        if (!text) return "";
        
        let c = text.toLowerCase();
        
        // 1. Quitar acentos
        c = c.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        
        // 2. Normalizar géneros (Directora -> Director, etc)
        c = c.replace(/\bdirectora\b/g, "director")
             .replace(/\bcoordinadora\b/g, "coordinador")
             .replace(/\bresidenta\b/g, "residente");
             
        // 3. Quitar conectores y artículos
        c = c.replace(/\b(de|del|la|el|y|en|con)\b/g, "");
        
        // 4. Limpiar caracteres especiales y espacios extra
        c = c.replace(/[^a-z0-9 ]/g, " ");
        c = c.replace(/\s+/g, " ").trim();
        
        return c;
    }

    // --- Lógica de Sugerencia Inteligente ---
    $('#user_id').on('select2:select change', function(e) {
        const selectedOption = $(this).find('option:selected');
        const cargoOriginal = selectedOption.data('cargo');
        
        if (cargoOriginal) {
            const cargoLimpio = cleanCargo(cargoOriginal);
            $('#cargoText').text(cargoOriginal); // Mostrar original para el humano
            $('#cargoSuggestion').show();
            
            // Consultar al servidor usando el cargo limpio
            $.getJSON('/admin/proyectos/sugerir-rol', { cargo: cargoLimpio }, function(data) {
                if (data.role) {
                    $('#role').val(data.role).trigger('change');
                    if (window.AIA && window.AIA.Notice) window.AIA.Notice.info('Sugerencia inteligente aplicada');
                }
            });
        } else {
            $('#role').val('V').trigger('change');
            $('#cargoSuggestion').hide();
        }
    });

    // Actualizar descripción del rol al cambiar el select
    $('#role').on('change', function() {
        const code = $(this).val();
        if (rolesInfo[code]) {
            $('#roleDescription').text(rolesInfo[code].description);
        }
    });

    /**
     * Función normalizadora para comparar strings sin acentos ni mayúsculas
     */
    function normalize(text) {
        return text.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }

    function suggestRole(cargo) {
        if (!cargo) return null;
        const c = normalize(cargo);
        
        // 1. Prioridad: Especialidades (SST, Compras, DCV, Planeación)
        if (c.includes('sst') && c.includes('ambiental')) return 'SG';
        if (c.includes('sst')) return 'S';
        if (c.includes('ambiental')) return 'G';
        if (c.includes('costo') || c.includes('compra') || c.includes('licitaci') || c.includes('aprovisionamiento')) return 'OT';
        if (c.includes('dcv')) return 'DCV';
        if (c.includes('planeaci') || c.includes('programaci') || c.includes('control')) return 'D';
        
        // 2. Jerarquía General (Solo si no es de una especialidad anterior)
        if (c.includes('director') || c.includes('gerente') || c.includes('jefe')) return 'A';
        if (c.includes('residente')) return 'R';
        if (c.includes('interventor') || c.includes('coordinador') || c.includes('invitado') || c.includes('vp')) return 'V';
        
        return null;
    }

    // Confirmación para quitar miembro
    $('.remove-member').on('click', function() {
        var userId = $(this).data('id');
        var userName = $(this).data('name');

        Swal.fire({
            title: '¿Quitar miembro?',
            text: "El usuario '" + userName + "' ya no tendrá acceso a este proyecto.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, quitar acceso',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $('#removeUserId').val(userId);
                $('#removeMemberForm').submit();
            }
        });
    });

    // Notificaciones
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        const action = urlParams.get('success');
        if (action === 'member_added' && window.AIA && window.AIA.Notice) window.AIA.Notice.success('Miembro asignado con éxito');
        if (action === 'member_removed' && window.AIA && window.AIA.Notice) window.AIA.Notice.success('Se ha revocado el acceso al miembro');
        window.history.replaceState({}, document.title, window.location.pathname + "?id=<?php echo $project['Id']; ?>");
    }
});
</script>

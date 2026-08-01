<?php
$h = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statusBadge = static function (array $status) use ($h): string {
    $key = (string) ($status['status_key'] ?? 'needs_decision');
    $class = match ($key) {
        'creates_activities' => 'badge-success',
        'managed_in_contracts' => 'badge-warning',
        'alias_of' => 'badge-info',
        'do_not_use' => 'badge-secondary',
        default => 'badge-danger',
    };

    return '<span class="badge ' . $class . '">' . $h($status['label'] ?? 'Necesita decisión') . '</span>';
};
$statusDetail = static function (array $status) use ($h): string {
    $parts = array_filter([
        (string) ($status['reason'] ?? ''),
        (string) ($status['next_action'] ?? ''),
        (string) ($status['package_hint'] ?? ''),
        (string) ($status['canonical_family'] ?? ''),
    ]);

    return $parts === [] ? '' : '<small class="text-muted d-block">' . $h(implode(' · ', $parts)) . '</small>';
};
?>

<?php if (!empty($flash_success)): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle mr-2"></i><?php echo $h($flash_success); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
  </div>
<?php endif; ?>

<?php if (!empty($flash_error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $h($flash_error); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
  </div>
<?php endif; ?>

<div class="card aia-panel aia-panel--elevated aia-panel aia-panel--elevated">
  <div class="card-body d-flex flex-wrap align-items-center">
    <strong class="mr-3">Exportar catálogo</strong>
    <a class="btn btn-sm btn-outline-success mr-2" href="/admin/matching/family-catalog/export?type=families"><i class="fas fa-download mr-1"></i>Familias</a>
    <a class="btn btn-sm btn-outline-info mr-2" href="/admin/matching/family-catalog/export?type=aliases"><i class="fas fa-download mr-1"></i>Aliases</a>
    <a class="btn btn-sm btn-outline-warning mr-2" href="/admin/matching/family-catalog/export?type=contractual"><i class="fas fa-download mr-1"></i>Contratos</a>
    <a class="btn btn-sm btn-outline-secondary" href="/admin/matching/family-catalog/export?type=rules"><i class="fas fa-download mr-1"></i>Reglas</a>
  </div>
</div>

<div class="row">
  <div class="col-lg-4">
    <div class="card aia-panel aia-panel--elevated card-outline card-success">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Familias operativas</h3></div>
      <form method="POST" action="/admin/matching/family-catalog/family">
        <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
        <div class="card-body">
          <div class="form-group">
            <label>Código</label>
            <input class="form-control" name="codigo" placeholder="PISOS_ENCHAPES" required>
          </div>
          <div class="form-group">
            <label>Nombre</label>
            <input class="form-control" name="nombre" placeholder="Pisos y Enchapes" required>
          </div>
          <div class="form-row">
            <div class="form-group col-7">
              <label>Categoría</label>
              <input class="form-control" name="categoria" value="GENERAL">
            </div>
            <div class="form-group col-5">
              <label>Orden</label>
              <input class="form-control" type="number" name="orden" value="999">
            </div>
          </div>
          <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" id="familyActive" name="activa" checked>
            <label class="custom-control-label" for="familyActive">Activa para nuevas propuestas</label>
          </div>
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="familyReview" name="siempre_revision">
            <label class="custom-control-label" for="familyReview">Requiere revisión humana</label>
          </div>
        </div>
        <div class="card-footer">
          <button class="btn btn-success" type="submit"><i class="fas fa-save mr-1"></i>Guardar familia</button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card aia-panel aia-panel--elevated card-outline card-info">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-random mr-2"></i>Aliases</h3></div>
      <form method="POST" action="/admin/matching/family-catalog/alias">
        <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
        <div class="card-body">
          <div class="form-group">
            <label>Alias detectado</label>
            <input class="form-control" name="alias_nombre" placeholder="Red RCI" required>
          </div>
          <div class="form-group">
            <label>Familia canónica</label>
            <select class="form-control" name="familia_id" required>
              <?php foreach ($families as $family): ?>
                <?php if ((int) ($family['activa'] ?? 1) !== 1) { continue; } ?>
                <option value="<?php echo (int) $family['id']; ?>"><?php echo $h($family['nombre']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Fuente</label>
            <input class="form-control" name="fuente" value="admin">
          </div>
          <div class="form-group">
            <label>Notas</label>
            <textarea class="form-control" name="notas" rows="2"></textarea>
          </div>
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="aliasActive" name="activa" checked>
            <label class="custom-control-label" for="aliasActive">Alias activo</label>
          </div>
        </div>
        <div class="card-footer">
          <button class="btn btn-info" type="submit"><i class="fas fa-save mr-1"></i>Guardar alias</button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card aia-panel aia-panel--elevated card-outline card-warning">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-file-contract mr-2"></i>Elementos contractuales</h3></div>
      <form method="POST" action="/admin/matching/family-catalog/contractual">
        <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
        <div class="card-body">
          <div class="form-group">
            <label>Nombre</label>
            <input class="form-control" name="nombre" placeholder="Acero de Refuerzo" required>
          </div>
          <div class="form-row">
            <div class="form-group col-6">
              <label>Tipo</label>
              <select class="form-control" name="tipo_paquete" required>
                <option>Suministro</option>
                <option>Mano de Obra</option>
                <option>Suministro e Instalación</option>
                <option>Orden de Compra</option>
              </select>
            </div>
            <div class="form-group col-6">
              <label>Paquete</label>
              <input class="form-control" name="paquete_nombre" placeholder="ACERO DE REFUERZO" required>
            </div>
          </div>
          <div class="form-group">
            <label>Familia relacionada</label>
            <select class="form-control" name="familia_id">
              <option value="">Sin relación directa</option>
              <?php foreach ($families as $family): ?>
                <?php if ((int) ($family['activa'] ?? 1) !== 1) { continue; } ?>
                <option value="<?php echo (int) $family['id']; ?>"><?php echo $h($family['nombre']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="contractActive" name="activa" checked>
            <label class="custom-control-label" for="contractActive">Disponible para Contratos</label>
          </div>
        </div>
        <div class="card-footer">
          <button class="btn btn-warning" type="submit"><i class="fas fa-save mr-1"></i>Guardar elemento</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="card aia-panel aia-panel--elevated card-outline card-primary">
  <div class="card-header"><h3 class="card-title"><i class="fas fa-box-open mr-2"></i>Crear opción contractual guiada</h3></div>
  <form method="POST" action="/admin/matching/family-catalog/contract-option">
    <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
    <div class="card-body">
      <div class="form-row">
        <div class="form-group col-lg-4">
          <label>Familia</label>
          <select class="form-control" name="familia_id" required>
            <?php foreach ($families as $family): ?>
              <option value="<?php echo (int) $family['id']; ?>"><?php echo $h($family['nombre']); ?> · <?php echo $h($family['catalog_status']['label'] ?? ''); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-lg-3">
          <label>Modalidad</label>
          <select class="form-control" name="tipo_contrato" required>
            <option value="2">Suministro e Instalación</option>
            <option value="3">Suministro</option>
            <option value="4">Mano de Obra</option>
            <option value="5">Orden de Compra</option>
            <option value="1">MO/S separados</option>
            <option value="6">Equipos</option>
          </select>
        </div>
        <div class="form-group col-lg-3">
          <label>Tipo visible</label>
          <input class="form-control" name="tipo_paquete" value="Suministro e Instalación" required>
        </div>
        <div class="form-group col-lg-2">
          <label>Cantidad por defecto</label>
          <input class="form-control" type="number" min="1" name="cantidad_default" value="1" required>
        </div>
      </div>
      <div class="form-group">
        <label>Paquetes</label>
        <textarea class="form-control" name="paquetes" rows="3" placeholder="PISOS Y ENCHAPES&#10;MORTERO DE NIVELACION" required></textarea>
      </div>
      <div class="form-row">
        <div class="form-group col-md-2"><label>Elaboración</label><input class="form-control" type="number" min="0" name="dias_elaboracion" value="8"></div>
        <div class="form-group col-md-2"><label>Entrega</label><input class="form-control" type="number" min="0" name="dias_entrega" value="10"></div>
        <div class="form-group col-md-2"><label>Recibo</label><input class="form-control" type="number" min="0" name="dias_recibo" value="1"></div>
        <div class="form-group col-md-2"><label>Cuadros</label><input class="form-control" type="number" min="0" name="dias_cuadros" value="10"></div>
        <div class="form-group col-md-2"><label>Legalización</label><input class="form-control" type="number" min="0" name="dias_legalizacion" value="10"></div>
        <div class="form-group col-md-1"><label>Fabric.</label><input class="form-control" type="number" min="0" name="dias_fabricacion" value="0"></div>
        <div class="form-group col-md-1"><label>Insumos</label><input class="form-control" type="number" min="0" name="dias_insumos" value="0"></div>
      </div>
      <p class="text-muted mb-0">Usa esto cuando una familia ya existe, pero el asistente indica que faltan paquetes de Contratos.</p>
    </div>
    <div class="card-footer">
      <button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Crear opción contractual</button>
    </div>
  </form>
</div>

<div class="row">
  <div class="col-lg-7">
    <div class="card aia-panel aia-panel--elevated aia-panel aia-panel--elevated">
      <div class="card-header"><h3 class="card-title">Catálogo actual</h3></div>
      <div class="card-body table-responsive p-0 admin-table-scroll--sm">
        <table class="table table-sm table-hover mb-0">
          <thead><tr><th>Familia</th><th>Categoría</th><th>Reglas</th><th>Aliases</th><th>Contratos</th><th>Estado</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($families as $family): ?>
              <?php $rowImpact = array_values(array_filter($impact, static fn($item) => (int) $item['id'] === (int) $family['id']))[0] ?? []; ?>
              <?php $status = $family['catalog_status'] ?? []; ?>
              <tr>
                <td><strong><?php echo $h($family['nombre']); ?></strong><br><small class="text-muted"><?php echo $h($family['codigo']); ?></small></td>
                <td><?php echo $h($family['categoria']); ?></td>
                <td><?php echo (int) ($rowImpact['reglas'] ?? 0); ?></td>
                <td><?php echo (int) ($rowImpact['aliases'] ?? 0); ?></td>
                <td><?php echo (int) ($rowImpact['elementos_contractuales'] ?? 0); ?></td>
                <td><?php echo $statusBadge($status); ?><?php echo $statusDetail($status); ?></td>
                <td>
                  <?php if ((int) ($family['activa'] ?? 1) !== 1): ?>
                    <form method="POST" action="/admin/matching/family-catalog/approve">
                      <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
                      <input type="hidden" name="type" value="family">
                      <input type="hidden" name="id" value="<?php echo (int) $family['id']; ?>">
                      <button class="btn btn-xs btn-outline-success" type="submit">Aprobar</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card aia-panel aia-panel--elevated aia-panel aia-panel--elevated">
      <div class="card-header"><h3 class="card-title">Reporte completo del catálogo</h3></div>
      <div class="card-body border-bottom">
        <span class="mr-2"><?php echo $statusBadge(['status_key' => 'creates_activities', 'label' => 'Crea actividades']); ?></span>
        <span class="mr-2"><?php echo $statusBadge(['status_key' => 'managed_in_contracts', 'label' => 'Se gestiona en Contratos']); ?></span>
        <span class="mr-2"><?php echo $statusBadge(['status_key' => 'alias_of', 'label' => 'Es otro nombre de...']); ?></span>
        <span class="mr-2"><?php echo $statusBadge(['status_key' => 'needs_decision', 'label' => 'Necesita decisión']); ?></span>
        <span><?php echo $statusBadge(['status_key' => 'do_not_use', 'label' => 'No usar']); ?></span>
      </div>
      <div class="card-body table-responsive p-0 admin-table-scroll--xs">
        <table class="table table-sm table-hover mb-0">
          <thead><tr><th>Elemento</th><th>Tipo</th><th>Estado</th><th>Motivo</th><th>Siguiente acción</th></tr></thead>
          <tbody>
            <?php foreach (($catalogReport ?? []) as $reportRow): ?>
              <?php $status = $reportRow['status'] ?? []; ?>
              <tr>
                <td><strong><?php echo $h($reportRow['item'] ?? ''); ?></strong><br><small class="text-muted"><?php echo $h($reportRow['code'] ?? ''); ?></small></td>
                <td><?php echo $h($reportRow['type'] ?? ''); ?></td>
                <td><?php echo $statusBadge($status); ?></td>
                <td><?php echo $h($status['reason'] ?? ''); ?><?php if (!empty($status['package_hint'])): ?><small class="text-muted d-block"><?php echo $h($status['package_hint']); ?></small><?php endif; ?><?php if (!empty($status['canonical_family'])): ?><small class="text-muted d-block"><?php echo $h($status['canonical_family']); ?></small><?php endif; ?></td>
                <td><?php echo $h($status['next_action'] ?? ''); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card aia-panel aia-panel--elevated aia-panel aia-panel--elevated">
      <div class="card-header"><h3 class="card-title">Importar catálogo</h3></div>
      <form method="POST" action="/admin/matching/family-catalog/import">
        <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
        <div class="card-body">
          <div class="form-group">
            <label>Tipo</label>
            <select class="form-control" name="type">
              <option value="families">Familias</option>
              <option value="aliases">Aliases</option>
              <option value="contractual">Elementos contractuales</option>
            </select>
          </div>
          <div class="form-group">
            <label>CSV</label>
            <textarea class="form-control" name="csv" rows="5" placeholder="codigo,nombre,categoria,orden,siempre_revision,activa"></textarea>
          </div>
          <p class="text-muted mb-0">Las filas importadas pueden quedar inactivas para revisión y aprobarse después.</p>
        </div>
        <div class="card-footer">
          <button class="btn btn-outline-primary" type="submit"><i class="fas fa-file-import mr-1"></i>Importar CSV</button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card aia-panel aia-panel--elevated card-outline card-danger">
      <div class="card-header"><h3 class="card-title">Decisiones pendientes</h3></div>
      <div class="card-body">
        <?php if (empty($pendingDecisions)): ?>
          <p class="text-muted mb-0">No hay familias pendientes de decisión.</p>
        <?php else: ?>
          <p class="text-muted">Define si estas filas siguen en Listado o pasan a Contratos.</p>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead><tr><th>Familia</th><th>Categoría</th><th>Reglas</th><th>Decisión</th></tr></thead>
              <tbody>
                <?php foreach ($pendingDecisions as $pending): ?>
                  <tr>
                    <td><strong><?php echo $h($pending['nombre']); ?></strong></td>
                    <td><?php echo $h($pending['categoria']); ?></td>
                    <td><?php echo (int) ($pending['reglas_activas'] ?? 0); ?></td>
                    <td>
                      <form class="mb-2" method="POST" action="/admin/matching/family-catalog/resolve-decision">
                        <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
                        <input type="hidden" name="familia_id" value="<?php echo (int) $pending['id']; ?>">
                        <input type="hidden" name="decision" value="keep_listado">
                        <input type="hidden" name="motivo" value="Confirmada como familia operativa.">
                        <button class="btn btn-xs btn-outline-success" type="submit">Mantener en Listado</button>
                      </form>
                      <form method="POST" action="/admin/matching/family-catalog/resolve-decision">
                        <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
                        <input type="hidden" name="familia_id" value="<?php echo (int) $pending['id']; ?>">
                        <input type="hidden" name="decision" value="move_contracts">
                        <input type="hidden" name="motivo" value="Definida como compra, recurso o paquete contractual.">
                        <select class="form-control form-control-sm mb-1" name="tipo_paquete" required>
                          <option>Suministro</option>
                          <option>Mano de Obra</option>
                          <option>Suministro e Instalación</option>
                          <option>Orden de Compra</option>
                          <option>Equipos</option>
                        </select>
                        <input class="form-control form-control-sm mb-1" name="paquete_nombre" value="<?php echo $h(mb_strtoupper((string) $pending['nombre'], 'UTF-8')); ?>" required>
                        <button class="btn btn-xs btn-outline-warning" type="submit">Pasar a Contratos</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card aia-panel aia-panel--elevated aia-panel aia-panel--elevated">
      <div class="card-header"><h3 class="card-title">Impacto y Auditoría</h3></div>
      <div class="card-body">
        <h6>Aliases activos</h6>
        <ul class="list-unstyled">
          <?php foreach (array_slice($aliases, 0, 8) as $alias): ?>
            <?php $status = $alias['catalog_status'] ?? []; ?>
            <li><?php echo $statusBadge($status); ?> <?php echo $h($alias['alias_nombre']); ?> → <strong><?php echo $h($alias['familia_nombre']); ?></strong><?php echo $statusDetail($status); ?></li>
          <?php endforeach; ?>
        </ul>

        <h6 class="mt-3">Elementos contractuales</h6>
        <ul class="list-unstyled">
          <?php foreach (array_slice($contractualElements, 0, 8) as $element): ?>
            <?php $status = $element['catalog_status'] ?? []; ?>
            <li><?php echo $statusBadge($status); ?> <?php echo $h($element['nombre']); ?> → <?php echo $h($element['tipo_paquete']); ?><?php echo $statusDetail($status); ?></li>
          <?php endforeach; ?>
        </ul>

        <h6 class="mt-3">Auditoría</h6>
        <?php if (empty($audit)): ?>
          <p class="text-muted mb-0">Aún no hay cambios manuales registrados en este catálogo.</p>
        <?php else: ?>
          <ul class="list-unstyled">
            <?php foreach ($audit as $event): ?>
              <li><small class="text-muted"><?php echo $h($event['fecha']); ?></small><br><?php echo $h($event['accion']); ?> · <?php echo $h($event['descripcion']); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <div class="card aia-panel aia-panel--elevated aia-panel aia-panel--elevated">
      <div class="card-header"><h3 class="card-title">Reglas de detección</h3></div>
      <form method="POST" action="/admin/matching/family-catalog/rule">
        <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
        <div class="card-body">
          <div class="form-group">
            <label>Regla</label>
            <select class="form-control" name="rule_id" required>
              <?php foreach ($rules as $rule): ?>
                <option value="<?php echo (int) $rule['id']; ?>">#<?php echo (int) $rule['id']; ?> · <?php echo $h($rule['familia_nombre']); ?> · <?php echo $h(mb_substr((string) $rule['patron_regex'], 0, 70)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Familia correcta</label>
            <select class="form-control" name="familia_id" required>
              <?php foreach ($families as $family): ?>
                <?php if ((int) ($family['activa'] ?? 1) !== 1) { continue; } ?>
                <option value="<?php echo (int) $family['id']; ?>"><?php echo $h($family['nombre']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Motivo</label>
            <input class="form-control" name="motivo" placeholder="Corrección por validación humana">
          </div>
          <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="ruleActive" name="activa" checked>
            <label class="custom-control-label" for="ruleActive">Regla activa después de guardar</label>
          </div>
        </div>
        <div class="card-footer">
          <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-exchange-alt mr-1"></i>Reasignar regla</button>
        </div>
      </form>
    </div>
  </div>
</div>

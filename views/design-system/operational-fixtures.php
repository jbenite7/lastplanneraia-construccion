<?php
$familyOperationalFixtures = array_values(array_filter(
    $operationalFixtures,
    static fn(array $fixture): bool => $fixture['family'] === $family['id']
));
$stateLabels = [
    'default' => 'Predeterminado', 'searching' => 'Buscando', 'empty' => 'Sin resultados',
    'error' => 'Error recuperable', 'success' => 'Completado', 'loading' => 'Cargando',
    'invalid' => 'Datos inválidos', 'expired' => 'Sesión expirada', 'changing' => 'Cambiando',
    'creating' => 'Creando', 'deleting' => 'Eliminando', 'editing' => 'Editando',
    'validating' => 'Validando', 'saving' => 'Guardando', 'reverted' => 'Revertido',
    'sorting' => 'Ordenando', 'unread' => 'No leídas', 'crisis' => 'Crisis',
    'simulation' => 'Simulación', 'closing' => 'Cerrando', 'analyzing' => 'Analizando',
    'reviewing' => 'Revisando', 'applying' => 'Aplicando', 'partial' => 'Aplicación parcial',
    'undone' => 'Deshecho', 'auditing' => 'Auditando', 'confirming' => 'Confirmación',
    'filtering' => 'Filtrando', 'drilldown' => 'Detalle', 'open' => 'Abierto',
    'readonly' => 'Solo lectura',
];
?>
<?php if ($familyOperationalFixtures !== []): ?>
<section class="ds-operational-suite" data-operational-suite="<?= htmlspecialchars($family['id'], ENT_QUOTES, 'UTF-8') ?>" aria-labelledby="operational-suite-<?= htmlspecialchars($family['id'], ENT_QUOTES, 'UTF-8') ?>">
    <header class="ds-operational-suite__head">
        <div>
            <h3 id="operational-suite-<?= htmlspecialchars($family['id'], ENT_QUOTES, 'UTF-8') ?>">Contratos operativos P1/P2</h3>
            <p>Comportamientos reales de la app representados con los componentes canónicos de esta familia.</p>
        </div>
        <span class="aia-chip"><?= count($familyOperationalFixtures) ?> <?= count($familyOperationalFixtures) === 1 ? 'objeto' : 'objetos' ?></span>
    </header>
    <div class="ds-operational-suite__list">
    <?php foreach ($familyOperationalFixtures as $fixture): ?>
        <?php $fixtureId = (string) $fixture['id']; ?>
        <article class="ds-operational-fixture ds-operational-fixture--<?= htmlspecialchars($fixture['topology'], ENT_QUOTES, 'UTF-8') ?>" data-operational-fixture="<?= htmlspecialchars($fixtureId, ENT_QUOTES, 'UTF-8') ?>" data-contract-state="default">
            <header class="ds-operational-fixture__head">
                <div>
                    <div class="ds-operational-fixture__meta">
                        <span class="aia-chip aia-chip--info"><?= htmlspecialchars($fixture['priority'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= htmlspecialchars(str_replace('-', ' ', $fixture['topology']), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <h4><?= htmlspecialchars($fixture['label'], ENT_QUOTES, 'UTF-8') ?></h4>
                    <p><?= htmlspecialchars($fixture['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <p class="ds-operational-fixture__consumers"><strong>Consumidores</strong><span><?= htmlspecialchars(implode(' · ', $fixture['consumers']), ENT_QUOTES, 'UTF-8') ?></span></p>
            </header>
            <div class="ds-operational-fixture__states" role="group" aria-label="Estados de <?= htmlspecialchars($fixture['label'], ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($fixture['states'] as $index => $state): ?>
                    <button class="aia-btn aia-btn--secondary" type="button" data-contract-state-action="<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"><?= htmlspecialchars($stateLabels[$state] ?? $state, ENT_QUOTES, 'UTF-8') ?></button>
                <?php endforeach; ?>
            </div>
            <p class="ds-operational-fixture__status" role="status" aria-live="polite" data-operational-state-output>Estado actual: Predeterminado</p>
            <div class="ds-operational-fixture__preview">
            <?php switch ($fixtureId):
                case 'project-selector': ?>
                    <form class="ds-fixture-toolbar" role="search" data-project-search>
                        <label class="aia-field" for="fixture-project-search"><span>Buscar proyecto</span><input class="aia-input" id="fixture-project-search" type="search" placeholder="Nombre, ciudad o código"></label>
                        <button class="aia-btn" type="submit">Buscar</button>
                    </form>
                    <div class="ds-project-list" role="group" aria-label="Proyectos de ejemplo">
                        <button type="button" class="ds-project-row" data-project-item data-search-value="nuevo edificio corporativo bogota"><strong>Nuevo Edificio Corporativo</strong><span>Bogotá · Construcción</span><span>Rol: Planificador</span></button>
                        <button type="button" class="ds-project-row" data-project-item data-search-value="da porto cartagena"><strong>Da Porto</strong><span>Cartagena · Inmobiliario</span><span>Rol: Residente</span></button>
                    </div>
                    <p class="aia-feedback aia-feedback--info" data-project-empty hidden>No hay proyectos que coincidan. Solicita acceso o limpia la búsqueda.</p>
                <?php break;
                case 'auth-credentials': ?>
                    <form class="ds-auth-fixture" aria-label="Acceso de ejemplo">
                        <label class="aia-field" for="fixture-auth-user"><span>Usuario</span><input class="aia-input" id="fixture-auth-user" autocomplete="username" value="residente.aia"></label>
                        <label class="aia-field" for="fixture-auth-password"><span>Contraseña</span><span class="ds-input-action"><input class="aia-input" id="fixture-auth-password" type="password" autocomplete="current-password" value="segura123"><button class="aia-btn aia-btn--secondary" type="button" data-fixture-action="toggle-password" aria-controls="fixture-auth-password" aria-pressed="false">Mostrar</button></span></label>
                        <div class="ds-fixture-actions"><button class="aia-btn" type="submit">Ingresar</button><button class="aia-btn aia-btn--secondary" type="button">Recuperar acceso</button></div>
                    </form>
                <?php break;
                case 'context-week': ?>
                    <div class="ds-context-contract" role="group" aria-label="Contexto operativo">
                        <label class="aia-field"><span>Proyecto</span><select class="aia-select"><option>Nuevo Edificio Corporativo</option><option>Da Porto</option></select></label>
                        <label class="aia-field"><span>Módulo</span><select class="aia-select"><option>Programa General</option><option>Programación Intermedia</option></select></label>
                        <label class="aia-field"><span>Semana</span><select class="aia-select"><option>Semana 29 · actual</option><option>Semana 28</option></select></label>
                        <div class="ds-fixture-actions"><button class="aia-btn" type="button" data-fixture-action="create-week">Crear semana</button><button class="aia-btn aia-btn--critical" type="button" data-fixture-action="delete-week">Eliminar semana</button></div>
                    </div>
                <?php break;
                case 'editable-grid': ?>
                    <div class="ds-grid-workbench">
                        <div class="ds-fixture-toolbar"><span class="aia-chip aia-chip--warning">2 cambios sin aplicar</span><div class="ds-fixture-actions"><button class="aia-btn aia-btn--secondary" type="button" data-fixture-action="undo-grid">Revertir</button><button class="aia-btn" type="button" data-fixture-action="save-grid">Guardar cambios</button></div></div>
                        <div class="handsontable ds-editable-grid" tabindex="0" role="region" aria-label="Grilla editable de actividades"><table class="htCore"><thead><tr><th scope="col">Actividad</th><th scope="col">Avance</th><th scope="col">Estado</th><th scope="col">Acción</th></tr></thead><tbody><tr><th scope="row">Losa N1</th><td><label class="aia-visually-hidden" for="grid-progress-1">Avance Losa N1</label><input id="grid-progress-1" class="aia-input" type="number" min="0" max="100" value="65" data-grid-editor></td><td><select class="aia-select" aria-label="Estado Losa N1" data-grid-editor><option>En ejecución</option><option>En riesgo</option></select></td><td><button class="aia-btn aia-btn--secondary" type="button">Ver detalle</button></td></tr><tr><th scope="row">Redes norte</th><td><label class="aia-visually-hidden" for="grid-progress-2">Avance Redes norte</label><input id="grid-progress-2" class="aia-input" type="number" min="0" max="100" value="20" data-grid-editor></td><td><select class="aia-select" aria-label="Estado Redes norte" data-grid-editor><option>En riesgo</option><option>Bloqueado</option></select></td><td><button class="aia-btn aia-btn--secondary" type="button">Ver detalle</button></td></tr></tbody></table></div>
                    </div>
                <?php break;
                case 'datatables-legacy': ?>
                    <div class="dataTables_wrapper ds-datatable" data-datatable-fixture>
                        <div class="ds-fixture-toolbar"><label class="aia-field" for="datatable-search"><span>Filtrar tabla</span><input class="aia-input" id="datatable-search" type="search"></label><div class="ds-fixture-actions"><button class="aia-btn aia-btn--secondary" type="button">Exportar</button><button class="aia-btn aia-btn--secondary" type="button">Columnas</button></div></div>
                        <div class="ds-table-viewport" tabindex="0" role="region" aria-label="Resultados DataTables"><table><thead><tr><th scope="col"><button type="button" data-fixture-action="sort-table" aria-label="Ordenar por actividad">Actividad</button></th><th scope="col">Responsable</th><th scope="col">Estado</th><th scope="col">Acción</th></tr></thead><tbody data-sortable-body><tr><th scope="row">Cimentación</th><td>Ana Torres</td><td>A tiempo</td><td><button class="aia-btn aia-btn--secondary" type="button">Abrir</button></td></tr><tr><th scope="row">Redes hidrosanitarias</th><td>Carlos Ruiz</td><td>En riesgo</td><td><button class="aia-btn aia-btn--secondary" type="button">Abrir</button></td></tr></tbody></table></div>
                        <nav class="aia-pagination" aria-label="Páginas de resultados"><button type="button" aria-label="Página anterior" disabled>Anterior</button><button type="button" aria-label="Página 1" aria-current="page" data-fixture-action="paginate-table" data-page="1">1</button><button type="button" aria-label="Página 2" data-fixture-action="paginate-table" data-page="2">2</button><button type="button" aria-label="Página siguiente" data-fixture-action="paginate-table" data-page="2">Siguiente</button></nav>
                    </div>
                <?php break;
                case 'notifications-center': ?>
                    <div class="ds-notification-contract">
                        <button class="aia-btn aia-btn--secondary" type="button" data-fixture-action="toggle-notifications" aria-expanded="true" aria-controls="fixture-notifications-panel">Notificaciones <span class="aia-chip aia-chip--warning" data-notification-count>3</span></button>
                        <section id="fixture-notifications-panel" class="ds-notification-panel" aria-label="Notificaciones recientes"><ul><li><strong>Restricción próxima a vencer</strong><span>Redes norte · hoy</span><button type="button" data-fixture-action="mark-read">Marcar como leída</button></li><li><strong>Semana publicada</strong><span>Programación semanal · hace 12 min</span><button type="button" data-fixture-action="mark-read">Marcar como leída</button></li></ul></section>
                    </div>
                <?php break;
                case 'lps-context-drawer': ?>
                    <div class="ds-drawer-contract">
                        <button class="aia-btn" type="button" data-fixture-action="open-drawer" aria-expanded="true" aria-controls="fixture-lps-drawer">Abrir conversación LPS</button>
                        <aside id="fixture-lps-drawer" class="ds-lps-drawer" aria-labelledby="fixture-lps-drawer-title"><header><div><h4 id="fixture-lps-drawer-title">Losa N1 · hilo operativo</h4><p>3 respuestas · modo seguimiento</p></div><button class="aia-btn aia-btn--secondary" type="button" data-fixture-action="close-drawer">Cerrar</button></header><ol><li><strong>Residente</strong><p>El acero llega hoy a las 15:00.</p></li><li><strong>Planificador</strong><p>Se mantiene el compromiso condicionado.</p></li></ol><label class="aia-field" for="fixture-drawer-reply"><span>Responder</span><textarea class="aia-textarea" id="fixture-drawer-reply"></textarea></label><div class="ds-fixture-actions"><button class="aia-btn" type="button">Enviar respuesta</button><button class="aia-btn aia-btn--critical" type="button">Cerrar con justificación</button></div></aside>
                    </div>
                <?php break;
                case 'admin-operations': ?>
                    <div class="ds-admin-contract">
                        <nav aria-label="Áreas administrativas"><a href="#admin-audit" aria-current="page">Auditoría</a><a href="#admin-backups">Respaldos</a><a href="#admin-import">Importación</a></nav>
                        <section id="admin-audit"><div class="ds-fixture-toolbar"><div><h4>Operaciones recientes</h4><p>Datos de ejemplo para revisar trazabilidad.</p></div><button class="aia-btn aia-btn--secondary" type="button">Exportar auditoría</button></div><div class="ds-table-viewport" tabindex="0"><table><thead><tr><th scope="col">Operación</th><th scope="col">Responsable</th><th scope="col">Resultado</th><th scope="col">Control</th></tr></thead><tbody><tr><th scope="row">Importar catálogo</th><td>Administrador</td><td>Completado</td><td><button class="aia-btn aia-btn--secondary" type="button">Ver log</button></td></tr><tr><th scope="row">Restaurar respaldo</th><td>Administrador</td><td>Pendiente</td><td><button class="aia-btn aia-btn--critical" type="button" data-fixture-action="confirm-admin">Revisar impacto</button></td></tr></tbody></table></div></section>
                    </div>
                <?php break;
                case 'bi-runtime-drilldown': ?>
                    <div class="ds-bi-runtime">
                        <form class="ds-fixture-toolbar" aria-label="Filtros BI"><label class="aia-field"><span>Proyecto</span><select class="aia-select"><option>Nuevo Edificio Corporativo</option></select></label><label class="aia-field"><span>Corte</span><select class="aia-select"><option>Semana 29</option><option>Semana 28</option></select></label><button class="aia-btn" type="submit">Actualizar</button></form>
                        <div class="ds-bi-runtime__layout"><figure><figcaption><h4>Restricciones por estado</h4><p>Datos de ejemplo; selecciona una barra para ver el detalle.</p></figcaption><svg viewBox="0 0 320 120" role="img" aria-label="12 restricciones abiertas, 7 en gestión y 4 resueltas"><g><rect x="24" y="20" width="132" height="20" rx="4"></rect><rect x="24" y="52" width="77" height="20" rx="4"></rect><rect x="24" y="84" width="44" height="20" rx="4"></rect></g><g><text x="164" y="35">12 abiertas</text><text x="109" y="67">7 en gestión</text><text x="76" y="99">4 resueltas</text></g></svg></figure><section><h4>Detalle · abiertas</h4><div class="ds-table-viewport" tabindex="0"><table><thead><tr><th scope="col">Restricción</th><th scope="col">Vence</th></tr></thead><tbody><tr><th scope="row">Diseño de redes</th><td>Hoy</td></tr><tr><th scope="row">Entrega de acero</th><td>Mañana</td></tr><tr data-bi-extra-row hidden><th scope="row">Permiso de izaje</th><td>3 días</td></tr></tbody></table></div><button class="aia-btn aia-btn--secondary" type="button" data-fixture-action="load-more-bi">Cargar más</button></section></div>
                    </div>
                <?php break;
                case 'tom-select-advanced': ?>
                    <div class="ds-tom-contract">
                        <div class="aia-field">
                            <span id="fixture-tom-label">Responsables</span>
                            <div class="ts-wrapper multi">
                                <div class="ts-control" role="combobox" aria-labelledby="fixture-tom-label" aria-expanded="true" aria-controls="fixture-tom-options">
                                    <span class="item" data-value="ana">Ana Torres <button type="button" aria-label="Quitar Ana Torres" data-fixture-action="remove-tom-option">Quitar</button></span>
                                    <span class="item" data-value="carlos" hidden>Carlos Ruiz · Director <button type="button" aria-label="Quitar Carlos Ruiz · Director" data-fixture-action="remove-tom-option" data-tom-value="carlos">Quitar</button></span>
                                    <span class="item" data-value="laura" hidden>Laura Gómez · Oficina Técnica <button type="button" aria-label="Quitar Laura Gómez · Oficina Técnica" data-fixture-action="remove-tom-option" data-tom-value="laura">Quitar</button></span>
                                    <input id="fixture-tom-search" type="search" aria-label="Buscar responsable" placeholder="Buscar responsable" data-tom-search>
                                </div>
                                <div class="ts-dropdown" id="fixture-tom-options"><div role="listbox" aria-labelledby="fixture-tom-label"><button type="button" role="option" aria-selected="false" data-fixture-action="add-tom-option" data-tom-value="carlos">Carlos Ruiz · Director</button><button type="button" role="option" aria-selected="false" data-fixture-action="add-tom-option" data-tom-value="laura">Laura Gómez · Oficina Técnica</button></div></div>
                            </div>
                        </div>
                        <div class="ds-fixture-actions"><button class="aia-btn aia-btn--secondary" type="button" data-fixture-action="clear-tom">Limpiar</button><button class="aia-btn" type="button">Confirmar selección</button></div>
                    </div>
                <?php break;
                case 'enriched-datepicker': ?>
                    <div class="ds-datepicker-contract"><label class="aia-field" for="fixture-date"><span>Fecha de vencimiento</span><span class="ds-input-action"><input class="aia-input" id="fixture-date" value="24/07/2026" aria-describedby="fixture-date-help"><button class="aia-btn aia-btn--secondary" type="button" data-fixture-action="toggle-calendar" aria-expanded="true" aria-controls="fixture-calendar">Abrir calendario</button></span><small class="aia-helper" id="fixture-date-help">Formato local: día/mes/año. Rango permitido: julio a septiembre de 2026.</small></label><section class="ds-calendar" id="fixture-calendar" aria-label="Julio de 2026"><header><button type="button" aria-label="Mes anterior">Anterior</button><h4>Julio de 2026</h4><button type="button" aria-label="Mes siguiente">Siguiente</button></header><table role="grid"><thead><tr><th scope="col">Lu</th><th scope="col">Ma</th><th scope="col">Mi</th><th scope="col">Ju</th><th scope="col">Vi</th></tr></thead><tbody><tr><td><button type="button">20</button></td><td><button type="button">21</button></td><td><button type="button">22</button></td><td><button type="button">23</button></td><td><button type="button" aria-current="date">24</button></td></tr><tr><td><button type="button">27</button></td><td><button type="button">28</button></td><td><button type="button">29</button></td><td><button type="button">30</button></td><td><button type="button">31</button></td></tr></tbody></table></section><div class="ds-fixture-actions"><button class="aia-btn aia-btn--secondary" type="button" data-fixture-action="clear-date">Limpiar</button><button class="aia-btn" type="button">Aplicar fecha</button></div></div>
                <?php break;
            endswitch; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

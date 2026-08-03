<?php
// views/dashboard/escalamientos.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Escalamientos AIA</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.10.1/jquery-ui.js"></script>
    <?= \App\View\Components\DesignSystemHeadComponent::renderForModule('escalamientos') ?>
    <link rel="stylesheet" href="/css/escalamientos.css?v=<?= urlencode((string) (@filemtime(dirname(__DIR__, 2) . '/public/css/escalamientos.css') ?: 'esc1')) ?>" />
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260711foundation5" charset="utf-8"></script>
    
    <!-- El CSS del drawer llega vía aia-design-system.css (layer vendor); el link crudo duplicaba la cascada. -->
</head>
<body class="esc-page">

    <div class="lps-dashboard-container">
        <div class="dashboard-header">
            <div class="dashboard-header__row">
                <div>
                    <h1>Dashboard Corporativo de Escalamientos AIA</h1>
                    <p>Monitoreo en tiempo real de frentes de obra bloqueados y progresión jerárquica de crisis.</p>
                </div>
                <a href="/programa-general" class="aia-btn esc-back-link">
                    ← Volver a Planificación
                </a>
            </div>
        </div>

        <?php
        // Agrupar crisis por nivel
        $columnas = [
            // `clase` viaja a la columna, no a la cabecera: el acento de nivel
            // se declara una vez ahi y lo heredan cabecera y tarjetas. Antes
            // cada tarjeta lo recibia por `style="--card-accent: …"`, un estilo
            // inline que ninguna hoja podia alcanzar.
            2 => ['titulo' => 'Director de Obra', 'clase' => 'col-director', 'items' => []],
            3 => ['titulo' => 'Coordinador Integración', 'clase' => 'col-coordinador', 'items' => []],
            4 => ['titulo' => 'Gerente de Construcción', 'clase' => 'col-gerente-c', 'items' => []],
            5 => ['titulo' => 'Gerente General', 'clase' => 'col-gerente-g', 'items' => []],
        ];

foreach ($crisis as $c) {
    $nivel = (int) $c['nivel_actual'];
    // Si el nivel es 1 (Residente), lo mostramos en la columna del Director ya que el Director es el superior inmediato del Residente
    $colNivel = $nivel === 1 ? 2 : $nivel;
    if (isset($columnas[$colNivel])) {
        $columnas[$colNivel]['items'][] = $c;
    }
}
?>

        <div class="kanban-board">
            <?php foreach ($columnas as $nivelId => $col): ?>
                <div class="kanban-column <?= $col['clase'] ?>">
                    <div class="column-header">
                        <h2><?= htmlspecialchars($col['titulo']) ?></h2>
                        <span class="column-counter"><?= count($col['items']) ?></span>
                    </div>

                    <?php if (empty($col['items'])): ?>
                        <div class="no-crisis-placeholder">
                            ✅ Sin crisis en este nivel
                        </div>
                    <?php else: ?>
                        <?php foreach ($col['items'] as $item): ?>
                            <div class="kanban-card"
                                 onclick="openLpsDrawer(<?= htmlspecialchars(json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)">
                                
                                <div class="card-meta">
                                    <span>Semana <?= htmlspecialchars($item['semana']) ?></span>
                                    <span>#<?= htmlspecialchars($item['unique_id'] ?? $item['consecutivo_en_programa']) ?></span>
                                </div>
                                
                                <h3 class="card-title">🔥 <?= htmlspecialchars($item['actividad_nombre'] ?? 'Actividad sin nombre') ?></h3>
                                
                                <div class="card-restriction">
                                    <strong>Bloqueo:</strong> <?= htmlspecialchars($item['restriccion_desc'] ?? 'Restricciones Abiertas') ?>
                                </div>
                                
                                <div class="card-footer">
                                    <span>Resp: <strong><?= htmlspecialchars($item['subcontratista'] ?? 'AIA') ?></strong></span>
                                    <span class="badge-trigger"><?= htmlspecialchars($item['trigger_origen']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Incluir Drawer Lateral Unificado -->
    <?php include PROJECT_ROOT . '/views/partials/drawer_unificado.php'; ?>

    <!-- Contexto PHP para JS -->
    <input type="hidden" id="baseDatos_PHP" value="<?= htmlspecialchars($dbName) ?>" />
    <input type="hidden" id="semana_PHP" value="<?= htmlspecialchars($_SESSION['semana'] ?? 0) ?>" />
    <input type="hidden" id="permiso_canonico" value="<?= htmlspecialchars($_SESSION['permiso'] ?? '') ?>" />

    <!-- Scripts de Terceros y Utilidades -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    
    <!-- Script del Drawer -->
    <script src="/js/modules/lps_drawer.js?v=20260722shell1"></script>

    <script>
        let currentSelectedCardData = null;

        // Este dashboard no tiene malla: sus tarjetas abren el drawer directamente. El adaptador
        // solo declara lo que de verdad sabe hacer —escribir una fila— y no finge ser una
        // instancia de Handsontable. El drawer consulta por capacidad y se adapta.
        const gridlessAdapter = {
            setDataAtRowProp: function(index, prop, value) {
                if (prop === 'alerta_crisis' && parseInt(value, 10) === 0) {
                    // Si se mitiga, recargar el dashboard
                    window.location.reload();
                }
            }
        };

        $(document).ready(function() {
            // Inicializar el Drawer sin malla, con el adaptador de escritura
            LPSContextualDrawer.init(gridlessAdapter, 'dashboard', {});
        });

        function openLpsDrawer(itemData) {
            // Adaptar los nombres de los atributos de la base de datos al formato esperado por el Drawer
            currentSelectedCardData = {
                unique_id: itemData.unique_id || itemData.consecutivo_en_programa,
                Consecutivo: itemData.unique_id || itemData.consecutivo_en_programa,
                id: itemData.id,
                Actividad: itemData.actividad_nombre,
                Subcontratista: itemData.subcontratista || 'AIA',
                Restriccion: itemData.restriccion_desc || 'Ninguna',
                Ruta_Critica: 1, // Todas las crisis del dashboard son Ruta Crítica P1
                alerta_crisis: 1,
                nivel_actual: itemData.nivel_actual,
                alerta_id: itemData.id,
                // El modulo en que nacio la crisis: el dashboard las reune de los tres, asi que sin
                // este dato el escalamiento quedaria registrado contra el modulo equivocado.
                modulo: itemData.modulo
            };

            // Abrir el drawer pasándole el rowData emulado
            LPSContextualDrawer.updateContext(currentSelectedCardData, 'dashboard');
        }
    </script>
</body>
</html>

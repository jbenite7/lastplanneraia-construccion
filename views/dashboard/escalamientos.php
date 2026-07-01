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
    <script type="text/javascript" src="/js/linksComunesHead2.js?v=20260521" charset="utf-8"></script>
    
    <!-- CSS 2026 Moderno e Interfaz Mobile-First -->
    <style>
        :root {
            --color-director: oklch(0.60 0.16 230);
            --color-coordinador: oklch(0.55 0.20 280);
            --color-gerente-c: oklch(0.65 0.18 45);
            --color-gerente-g: oklch(0.62 0.22 18);
            
            --bg-card: rgba(255, 255, 255, 0.85);
            --border-card: rgba(0, 0, 0, 0.06);
            --text-main: oklch(0.25 0.04 120);
        }

        body {
            font-family: 'Outfit', 'Inter', sans-serif;
            background: radial-gradient(circle at top right, oklch(0.98 0.01 150), oklch(0.96 0.02 120));
            margin: 0;
            padding: 0;
            color: var(--text-main);
            min-height: 100vh;
        }

        .lps-dashboard-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid rgba(26, 60, 42, 0.1);
            padding-bottom: 16px;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 1.8rem;
            color: oklch(0.30 0.08 140);
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .dashboard-header p {
            margin: 0;
            font-size: 0.9rem;
            color: oklch(0.50 0.03 140);
        }

        /* Grid Kanban Jerárquico - Mobile First */
        .kanban-board {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .kanban-board {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1200px) {
            .kanban-board {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Columnas */
        .kanban-column {
            background: rgba(26, 60, 42, 0.03);
            border-radius: 16px;
            padding: 16px;
            border: 1px solid rgba(26, 60, 42, 0.06);
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 400px;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.02);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .kanban-column:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(26, 60, 42, 0.04);
        }

        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 2px solid currentColor;
            margin-bottom: 8px;
        }

        .column-header h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .column-counter {
            font-size: 0.8rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            background: currentColor;
            color: #ffffff !important;
        }

        /* Tarjeta de Crisis */
        .kanban-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .kanban-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--card-accent, oklch(0.5 0.1 140));
        }

        .kanban-card:hover {
            transform: scale(1.02) translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border-color: rgba(26, 60, 42, 0.15);
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            font-weight: 700;
            color: oklch(0.50 0.02 120);
        }

        .card-title {
            margin: 0;
            font-size: 0.92rem;
            font-weight: 800;
            color: oklch(0.20 0.04 140);
            line-height: 1.3;
        }

        .card-restriction {
            font-size: 0.8rem;
            color: oklch(0.40 0.08 30);
            background: oklch(0.97 0.01 45);
            padding: 6px 8px;
            border-radius: 6px;
            border-left: 2px solid oklch(0.65 0.18 45);
            margin: 2px 0;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.72rem;
            margin-top: 4px;
            border-top: 1px solid rgba(0,0,0,0.04);
            padding-top: 8px;
        }

        .badge-trigger {
            background: rgba(0,0,0,0.05);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        /* Clases de colores según nivel */
        .col-director { color: var(--color-director); }
        .col-coordinador { color: var(--color-coordinador); }
        .col-gerente-c { color: var(--color-gerente-c); }
        .col-gerente-g { color: var(--color-gerente-g); }

        /* Estilo sutil de Drawers */
        .lps-drawer-open-btn {
            background: none;
            border: none;
            color: oklch(0.5 0.1 140);
            font-weight: 700;
            font-size: 0.75rem;
            cursor: pointer;
            padding: 0;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .lps-drawer-open-btn:hover {
            text-decoration: underline;
        }

        .no-crisis-placeholder {
            text-align: center;
            color: oklch(0.6 0.02 140);
            font-size: 0.82rem;
            padding: 30px 10px;
            border: 1px dashed rgba(26, 60, 42, 0.1);
            border-radius: 8px;
            background: rgba(255,255,255,0.4);
        }
    </style>
    
    <!-- CSS del drawer que ya creamos en el Ciclo 2 -->
    <link rel="stylesheet" href="/css/handsontable-module.css?v=20260522d" />
</head>
<body class="pi-page">

    <div class="lps-dashboard-container">
        <div class="dashboard-header">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h1>Dashboard Corporativo de Escalamientos AIA</h1>
                    <p>Monitoreo en tiempo real de frentes de obra bloqueados y progresión jerárquica de crisis.</p>
                </div>
                <a href="/programa-general" style="text-decoration: none; padding: 10px 16px; background: oklch(0.35 0.08 140); color: #fff; border-radius: 8px; font-weight: 700; font-size: 0.85rem; box-shadow: 0 4px 12px rgba(26, 60, 42, 0.15);">
                    ← Volver a Planificación
                </a>
            </div>
        </div>

        <?php
        // Agrupar crisis por nivel
        $columnas = [
            2 => ['titulo' => 'Director de Obra', 'clase' => 'col-director', 'color' => 'var(--color-director)', 'items' => []],
            3 => ['titulo' => 'Coordinador Integración', 'clase' => 'col-coordinador', 'color' => 'var(--color-coordinador)', 'items' => []],
            4 => ['titulo' => 'Gerente de Construcción', 'clase' => 'col-gerente-c', 'color' => 'var(--color-gerente-c)', 'items' => []],
            5 => ['titulo' => 'Gerente General', 'clase' => 'col-gerente-g', 'color' => 'var(--color-gerente-g)', 'items' => []],
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
                <div class="kanban-column">
                    <div class="column-header <?= $col['clase'] ?>">
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
                                 style="--card-accent: <?= $col['color'] ?>" 
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
    <script src="/js/modules/lps_drawer.js?v=20260522c"></script>

    <script>
        let currentSelectedCardData = null;

        // Adaptador dummy para emular Handsontable en el dashboard
        const dummyHot = {
            getSourceDataAtRow: function(index) {
                return currentSelectedCardData;
            },
            setDataAtRowProp: function(index, prop, value) {
                if (prop === 'alerta_crisis' && parseInt(value, 10) === 0) {
                    // Si se mitiga, recargar el dashboard
                    window.location.reload();
                }
            }
        };

        $(document).ready(function() {
            // Inicializar el Drawer con el dummyHot y el contexto
            LPSContextualDrawer.init(dummyHot, 'dashboard', {});
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
                alerta_id: itemData.id
            };

            // Abrir el drawer pasándole el rowData emulado
            LPSContextualDrawer.updateContext(currentSelectedCardData, 'dashboard');
        }
    </script>
</body>
</html>

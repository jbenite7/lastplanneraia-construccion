<?php
/**
 * Shell de la isla React de la Torre de Control — hoja Intermedia, etapa piloto (Task 6).
 *
 * Se sirve SOLO cuando CT_PILOTO=1 en .env y para la hoja Intermedia
 * (BiViewController::intermedia()); las otras 7 hojas siguen sirviéndose por
 * views/bi/_layout.php + bi-spa.js sin importar el valor de la bandera (D55: primero se
 * reconstruye, solo después se retira la hoja vieja).
 *
 * Variables inyectadas por BiViewController::renderCtPiloto(): $bootstrapJson (JSON seguro),
 * $assetVersion (cache-busting del bundle ct-app), $tokensVersion (cache-busting de
 * tokens.css). Mismo patrón que el shell de Plan de Compras v2
 * (views/plan-compras/app.view.php) — el bundle NO se edita a mano, lo genera
 * `npm run build` desde `ct-app/` sobre public/ct-app/.
 */
?>
<!DOCTYPE html>
<html lang="es" data-aia-theme="dark">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Torre de Control — Prog. Intermedia (piloto) — Last Planner AIA</title>
	<?php require dirname(__DIR__) . '/partials/head_brand.php'; ?>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/theme-bootstrap.js') ?>
	<link rel="stylesheet" href="/css/tokens.css?v=<?php echo (int) $tokensVersion; ?>">
	<?= \App\View\Components\DesignSystemHeadComponent::renderStylesheet('/css/aia-design-system.css') ?>
	<link rel="stylesheet" href="/ct-app/assets/ct.css?v=<?php echo (int) $assetVersion; ?>">
</head>
<body class="aia-shell aia-shell--sidebar">
	<?php $shellMuestraSemana = false; // El piloto no reusa el chip de semana del BI viejo: Task 7 decide su propio contexto. ?>
	<?php require PROJECT_ROOT . '/views/partials/shell_sidebar.php'; ?>

	<main id="root"></main>
	<script>
		window.__CT_BOOTSTRAP__ = <?php echo $bootstrapJson; ?>;
		window.__AIA_SHELL_SIDEBAR__ = true;
	</script>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/theme.js') ?>
	<script type="module" src="/ct-app/assets/ct.js?v=<?php echo (int) $assetVersion; ?>"></script>
</body>
</html>

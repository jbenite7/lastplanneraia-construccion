<?php
/**
 * Shell de la isla React — Plan de Compras v2.
 *
 * Variables: $bootstrapJson (JSON seguro), $assetVersion (int cache-busting del bundle),
 * $tokensVersion (int cache-busting propio de tokens.css), $shellActive (id del ítem del sidebar).
 * El bundle NO se edita a mano: lo genera `npm run build` desde `pdc-app/` sobre public/pdc-app/.
 *
 * Desde la revisión de UX de julio de 2026 la página vive dentro del shell con barra lateral, como
 * el resto de módulos. Mismo patrón que Control Tower (views/bi/_layout.php): la lateral aporta UNA
 * entrada al módulo y la navegación entre las seis pantallas se queda dentro, como pestañas. No
 * puede ser de otra forma — la lateral no admite anidamiento, y las rutas de la SPA van tras
 * almohadilla, así que el servidor no llega a saber en qué pantalla está el usuario.
 */
?>
<!DOCTYPE html>
<html lang="es" data-aia-theme="dark">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Plan de Compras — Last Planner AIA</title>
	<link rel="icon" href="/favicon.ico">
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/theme-bootstrap.js') ?>
	<link rel="stylesheet" href="/css/tokens.css?v=<?php echo (int) $tokensVersion; ?>">
	<?= \App\View\Components\DesignSystemHeadComponent::renderStylesheet('/css/aia-design-system.css') ?>
	<link rel="stylesheet" href="/pdc-app/assets/pdc.css?v=<?php echo (int) $assetVersion; ?>">
</head>
<body class="aia-shell aia-shell--sidebar">
	<?php require PROJECT_ROOT . '/views/partials/shell_sidebar.php'; ?>

	<div id="root"></div>
	<script>
		window.__PDC_BOOTSTRAP__ = <?php echo $bootstrapJson; ?>;
		window.__AIA_SHELL_SIDEBAR__ = true;
	</script>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/sidebar_navigation.js') ?>
	<?php /* theme.js es el que publica `window.AiaDesignSystem` y conserva el tema elegido; sin él
	         el gate del shell se queda esperando ese global y la página no comparte el conmutador
	         de tema con el resto de módulos. */ ?>
	<?= \App\View\Components\DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/theme.js') ?>
	<script type="module" src="/pdc-app/assets/pdc.js?v=<?php echo (int) $assetVersion; ?>"></script>
</body>
</html>

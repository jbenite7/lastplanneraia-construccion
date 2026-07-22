<?php
/**
 * Shell de la isla React — Plan de Compras v2.
 * Variables: $bootstrapJson (JSON seguro), $assetVersion (int cache-busting).
 * El bundle NO se edita aquí: se compila en el repo `plan-de-compras`
 * (npm run sync) y llega a public/pdc-app/.
 */
?>
<!DOCTYPE html>
<html lang="es" data-aia-theme="dark">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Plan de Compras — Last Planner AIA</title>
	<link rel="icon" href="/favicon.ico">
	<link rel="stylesheet" href="/css/tokens.css?v=<?php echo (int) $assetVersion; ?>">
	<link rel="stylesheet" href="/pdc-app/assets/pdc.css?v=<?php echo (int) $assetVersion; ?>">
</head>
<body>
	<div id="root"></div>
	<script>
		window.__PDC_BOOTSTRAP__ = <?php echo $bootstrapJson; ?>;
	</script>
	<script type="module" src="/pdc-app/assets/pdc.js?v=<?php echo (int) $assetVersion; ?>"></script>
</body>
</html>

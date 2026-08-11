<?php
/**
 * Página de error dentro del producto.
 *
 * Una sola plantilla para 404, 405 y 500. La disposición pedía «dos plantillas»;
 * parametrizar una sirve lo mismo con menos código, y este frente va de quitar,
 * no de multiplicar archivos.
 *
 * Antes, los tres errores devolvían texto pelado —`404 Not Found` son 13 bytes
 * sin una sola etiqueta— y eso echaba al usuario FUERA del producto: sin marca,
 * sin tema, sin ruta de vuelta. Era la única fila bloqueante del roadmap del
 * 2026-08-05.
 *
 * DOS DECISIONES DE COSTE, las dos para no tocar ningún baseline:
 *
 * 1. Usa `render()`, el agregador, y NO `renderForModule()`. Un módulo nuevo
 *    exige manifiesto con escenarios y capturas doradas fijadas por sha256, y
 *    eso es tocar el baseline visual, que necesita aprobación propia.
 * 2. NO trae hoja de estilos propia. Se compone solo con primitivas que el
 *    contrato ya publica —`.aia-empty`, `.aia-title`, `.aia-btn`,
 *    `.aia-btn--secondary`, todas en `design-system/core.css`, que el agregador
 *    ya entrega—. Una hoja nueva habría añadido un `<link>` sin capa y con él
 *    una entrada al inventario de `unlayered-delivery`.
 *
 * Se pinta con sesión y sin ella: un 404 puede caer con la sesión caducada, que
 * es justo cuando el usuario más necesita el enlace de vuelta.
 *
 * Variables esperadas: $codigo (int), $titulo (string), $mensaje (string).
 */

$codigo = isset($codigo) ? (int) $codigo : 500;
$titulo = isset($titulo) ? (string) $titulo : 'Algo se rompió de nuestro lado';
$mensaje = isset($mensaje) ? (string) $mensaje : 'El error quedó registrado. Puedes volver e intentarlo otra vez.';

$haySesion = !empty($_SESSION['usuario']);
$hayProyecto = !empty($_SESSION['project_id']);
$esc = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php require dirname(__DIR__) . '/partials/head_brand.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $esc((string) $codigo) ?> · Last Planner AIA</title>
    <?= \App\View\Components\DesignSystemHeadComponent::render() ?>
</head>
<body class="hold-transition aia-shell">
<main class="aia-empty aia-empty--page" role="main">
    <div>
        <p class="aia-title"><?= $esc($titulo) ?></p>
        <p><?= $esc($mensaje) ?></p>
        <p><?= $esc((string) $codigo) ?></p>

        <nav aria-label="Salidas">
            <?php if ($hayProyecto): ?>
                <a class="aia-btn" href="/programacion-semanal">Volver a Programación Semanal</a>
                <a class="aia-btn aia-btn--secondary" href="/proyectos">Cambiar de proyecto</a>
            <?php elseif ($haySesion): ?>
                <a class="aia-btn" href="/proyectos">Ir a tus proyectos</a>
            <?php else: ?>
                <a class="aia-btn" href="/login">Iniciar sesión</a>
            <?php endif; ?>
        </nav>
    </div>
</main>
</body>
</html>

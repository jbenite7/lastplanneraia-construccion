<?php

declare(strict_types=1);
// @requiere: puro

/**
 * Verifica que las interpolaciones dentro de atributos `title="${...}"` en
 * public/js/modules/bi-spa.js usen escapeAttr (que escapa comillas dobles), no escapeHtml
 * a secas (que no las escapa). Un `escapeHtml` puro en contexto de atributo permite XSS
 * almacenado vía una causa/observación guardada en `programacion_semanal` que contenga
 * `" onmouseover="alert(1)`.
 *
 * Hallazgo bloqueante de la revisión final de la Fase 0 de higiene de datos (Control Tower).
 */

$fallos = 0;
$total = 0;

function comprobar(string $descripcion, bool $condicion): void
{
    global $fallos, $total;
    $total++;
    if ($condicion) {
        echo "PASA: {$descripcion}\n";
    } else {
        $fallos++;
        echo "FALLA: {$descripcion}\n";
    }
}

$ruta = __DIR__ . '/../public/js/modules/bi-spa.js';
$contenido = file_get_contents($ruta);

if ($contenido === false) {
    echo "FAIL: no se pudo leer {$ruta}\n";
    exit(1);
}

// 1. Toda interpolación dentro de title="${...}" debe usar escapeAttr, nunca escapeHtml a secas.
preg_match_all('/title="\$\{([^}]*)\}"/', $contenido, $coincidencias);
comprobar('el archivo contiene al menos una interpolación title="${...}"', count($coincidencias[1]) > 0);

foreach ($coincidencias[1] as $i => $expresion) {
    $usaEscapeAttr = (bool) preg_match('/\bescapeAttr\s*\(/', $expresion)
        || (bool) preg_match('/\battrText\b/', $expresion);
    comprobar("interpolación #{$i} en title=\"\${...}\" usa escapeAttr (expresión: {$expresion})", $usaEscapeAttr);

    $usaEscapeHtmlDirecto = (bool) preg_match('/\bescapeHtml\s*\(/', $expresion);
    comprobar("interpolación #{$i} en title=\"\${...}\" NO usa escapeHtml directo (expresión: {$expresion})", !$usaEscapeHtmlDirecto);
}

// 2. escapeAttr debe existir y su cuerpo debe escapar comillas dobles.
comprobar('el archivo define function escapeAttr', (bool) preg_match('/function\s+escapeAttr\s*\(/', $contenido));

if (preg_match('/function\s+escapeAttr\s*\([^)]*\)\s*\{([^}]*)\}/', $contenido, $cuerpo)) {
    $cuerpoFn = $cuerpo[1];
    comprobar('escapeAttr delega en escapeHtml', (bool) preg_match('/escapeHtml\s*\(/', $cuerpoFn));
    comprobar(
        'escapeAttr escapa comillas dobles (patrón /"/g reemplazado por &quot;)',
        (bool) preg_match('/replace\s*\(\s*\/"\/g\s*,\s*[\'"]&quot;[\'"]\s*\)/', $cuerpoFn)
    );
} else {
    comprobar('se pudo extraer el cuerpo de escapeAttr', false);
}

echo "\n";

if ($fallos > 0) {
    echo "FAIL: {$fallos} de {$total} comprobaciones fallaron\n";
    exit(1);
}

echo "OK: {$total} comprobaciones pasaron\n";
exit(0);

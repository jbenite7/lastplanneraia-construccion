<?php

// Configuración para PHP CS Fixer
// Documentación: https://cs.symfony.com/

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor'); // Excluimos la carpeta de dependencias

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true, // Estándar base de codificación para PHP
        'array_syntax' => ['syntax' => 'short'], // Usa la sintaxis corta de arrays: [] en lugar de array()
        'ordered_imports' => ['sort_algorithm' => 'alpha'], // Ordena los 'use' statements alfabéticamente
        'no_unused_imports' => true, // Elimina los 'use' statements que no se utilizan
        'trailing_comma_in_multiline' => true, // Añade una coma al final en arrays multilínea para diffs más limpios
        'phpdoc_scalar' => true, // Anotaciones de tipo escalares deben ser 'bool', 'int', etc., no 'boolean', 'integer'
        'phpdoc_single_line_var_spacing' => true, // Espaciado consistente en anotaciones @var
        'phpdoc_trim' => true, // Elimina espacios en blanco innecesarios en los phpdocs
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => ['=>' => null]
        ], // Asegura que haya un espacio alrededor de los operadores binarios
        'blank_line_before_statement' => [
            'statements' => ['return']
        ], // Añade una línea en blanco antes de un 'return' para mayor legibilidad
    ])
    ->setFinder($finder);

<?php

$replacements = [
    'App\Modules\Usuarios\Infrastructure\Models\User' => 'App\Modules\Usuarios\Infrastructure\Models\User',
    'App\Modules\Usuarios\Infrastructure\Models\Alumno' => 'App\Modules\Usuarios\Infrastructure\Models\Alumno',
    'App\Modules\Usuarios\Infrastructure\Models\Docente' => 'App\Modules\Usuarios\Infrastructure\Models\Docente',
    'App\Modules\Usuarios\Infrastructure\Models\Padre' => 'App\Modules\Usuarios\Infrastructure\Models\Padre',
    'App\Modules\Usuarios\Infrastructure\Models\Administrativo' => 'App\Modules\Usuarios\Infrastructure\Models\Administrativo',
    'App\Modules\Usuarios\Presentation\Controllers\AccountController' => 'App\Modules\Usuarios\Presentation\Controllers\AccountController',
    'App\Modules\Usuarios\Presentation\Controllers\FamilyLinkController' => 'App\Modules\Usuarios\Presentation\Controllers\FamilyLinkController',
];

$directory = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

foreach ($regex as $file) {
    $path = $file[0];
    if (strpos($path, '/vendor/') !== false || strpos($path, '/storage/') !== false) {
        continue;
    }

    $content = file_get_contents($path);
    $changed = false;

    foreach ($replacements as $old => $new) {
        if (strpos($content, $old) !== false) {
            $content = str_replace($old, $new, $content);
            $changed = true;
        }
    }

    if ($changed) {
        file_put_contents($path, $content);
        echo "Updated $path\n";
    }
}

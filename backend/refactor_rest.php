<?php

$map = [
    'CargaAcademica' => 'Academico',
    'Curso' => 'Academico',
    'EventoCalendario' => 'Academico',
    'Grado' => 'Academico',
    'Matricula' => 'Academico',
    'Nota' => 'Academico',
    'PeriodoAcademico' => 'Academico',
    'ReporteAcademico' => 'Academico',
    'Seccion' => 'Academico',
    'Material' => 'Materiales',
    'Horario' => 'Horarios',
    'Comunicado' => 'Comunicados',
    'ComunicadoLectura' => 'Comunicados',
    'Notificacion' => 'Notificaciones',
];

$baseDir = __DIR__;
$modelsDir = $baseDir . '/app/Models';
$modulesDir = $baseDir . '/app/Modules';

function updateReferences($baseDir, $model, $module) {
    $oldNamespace = "App\\Models\\$model";
    $newNamespace = "App\\Modules\\$module\\Infrastructure\\Models\\$model";
    
    $directories = [
        $baseDir . '/app',
        $baseDir . '/database',
        $baseDir . '/tests',
        $baseDir . '/routes',
    ];

    foreach ($directories as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $modified = false;

                // 1. If it has `use App\Models\Model;`, replace it
                if (strpos($content, "use $oldNamespace;") !== false) {
                    $content = str_replace("use $oldNamespace;", "use $newNamespace;", $content);
                    $modified = true;
                }
                
                // 2. If it relies on same namespace (App\Models) and uses the model WITHOUT import
                // e.g. public function alumno(): BelongsTo { return $this->belongsTo(Alumno::class); }
                // Since we are moving ALL models, if a file has `namespace App\Models;` and uses `$model::class`,
                // wait, if we process this file AFTER it was moved, its namespace is now `App\Modules\...`
                // Let's just blindly add use statements if we see `$model::class` or `$model ` and no use statement
                // Actually, a simpler way is to replace `namespace App\Models;` in the models being moved.

                // 3. Fully qualified replacements: `App\Models\Model::class` -> `App\Modules\...\Model::class`
                if (strpos($content, $oldNamespace) !== false) {
                    $content = str_replace($oldNamespace, $newNamespace, $content);
                    $modified = true;
                }
                
                // 4. In factories, we might have missing use statements because we moved the model out of App\Models.
                // We'll just append `use $newNamespace;` after `namespace Database\Factories;` if the model is used.
                if (strpos($content, 'namespace Database\Factories;') !== false) {
                    if (preg_match("/\b$model(::| )/", $content) && strpos($content, "use $newNamespace;") === false) {
                        $content = str_replace("namespace Database\Factories;\n", "namespace Database\Factories;\n\nuse $newNamespace;\n", $content);
                        $modified = true;
                    }
                }
                
                // 5. Same for tests, models, controllers that lost their sibling.
                if (preg_match('/namespace App\\\(Models|Http\\\Controllers|Policies|Modules\\\.*);/', $content, $matches)) {
                    if (preg_match("/\b$model::class/", $content) && strpos($content, "use $newNamespace;") === false) {
                        $content = str_replace($matches[0], $matches[0] . "\n\nuse $newNamespace;", $content);
                        $modified = true;
                    }
                }

                if ($modified) {
                    file_put_contents($file->getPathname(), $content);
                }
            }
        }
    }
}

foreach ($map as $model => $module) {
    $sourcePath = "$modelsDir/$model.php";
    $targetDir = "$modulesDir/$module/Infrastructure/Models";
    $targetPath = "$targetDir/$model.php";

    if (file_exists($sourcePath)) {
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // 1. Move file
        rename($sourcePath, $targetPath);
        echo "Moved $model to $module\n";
        
        // 2. Update namespace in the moved file
        $content = file_get_contents($targetPath);
        $content = str_replace("namespace App\\Models;", "namespace App\\Modules\\$module\\Infrastructure\\Models;", $content);
        file_put_contents($targetPath, $content);
        
        // 3. Update references in all other files
        updateReferences($baseDir, $model, $module);
    }
}

// Ensure Alumno, Docente, Padre, User, Administrativo are correctly imported in all files that use them.
// We missed doing this fully in the previous refactoring.
$missingMap = [
    'Alumno' => 'Usuarios',
    'Docente' => 'Usuarios',
    'Padre' => 'Usuarios',
    'User' => 'Usuarios',
    'Administrativo' => 'Usuarios',
];
foreach ($missingMap as $model => $module) {
    updateReferences($baseDir, $model, $module);
}

echo "Done!\n";

<?php

$baseDir = __DIR__;
$appDir = $baseDir . '/app';
$modulesDir = $baseDir . '/app/Modules';

$moves = [
    [
        'source' => "$appDir/Policies/AlumnoPolicy.php",
        'targetDir' => "$modulesDir/Usuarios/Presentation/Policies",
        'oldNamespace' => "App\\Policies\\AlumnoPolicy",
        'newNamespace' => "App\\Modules\\Usuarios\\Presentation\\Policies\\AlumnoPolicy",
        'oldNsDecl' => "namespace App\\Policies;",
        'newNsDecl' => "namespace App\\Modules\\Usuarios\\Presentation\\Policies;"
    ],
    [
        'source' => "$appDir/Policies/UserPolicy.php",
        'targetDir' => "$modulesDir/Usuarios/Presentation/Policies",
        'oldNamespace' => "App\\Policies\\UserPolicy",
        'newNamespace' => "App\\Modules\\Usuarios\\Presentation\\Policies\\UserPolicy",
        'oldNsDecl' => "namespace App\\Policies;",
        'newNsDecl' => "namespace App\\Modules\\Usuarios\\Presentation\\Policies;"
    ],
    [
        'source' => "$appDir/Policies/PeriodoAcademicoPolicy.php",
        'targetDir' => "$modulesDir/Academico/Presentation/Policies",
        'oldNamespace' => "App\\Policies\\PeriodoAcademicoPolicy",
        'newNamespace' => "App\\Modules\\Academico\\Presentation\\Policies\\PeriodoAcademicoPolicy",
        'oldNsDecl' => "namespace App\\Policies;",
        'newNsDecl' => "namespace App\\Modules\\Academico\\Presentation\\Policies;"
    ],
    [
        'source' => "$appDir/Notifications/StudentAttendanceMovementNotification.php",
        'targetDir' => "$modulesDir/Asistencia/Infrastructure/Notifications",
        'oldNamespace' => "App\\Notifications\\StudentAttendanceMovementNotification",
        'newNamespace' => "App\\Modules\\Asistencia\\Infrastructure\\Notifications\\StudentAttendanceMovementNotification",
        'oldNsDecl' => "namespace App\\Notifications;",
        'newNsDecl' => "namespace App\\Modules\\Asistencia\\Infrastructure\\Notifications;"
    ]
];

foreach ($moves as $move) {
    if (file_exists($move['source'])) {
        if (!is_dir($move['targetDir'])) {
            mkdir($move['targetDir'], 0777, true);
        }
        
        $targetPath = $move['targetDir'] . '/' . basename($move['source']);
        rename($move['source'], $targetPath);
        echo "Moved " . basename($move['source']) . "\n";
        
        $content = file_get_contents($targetPath);
        $content = str_replace($move['oldNsDecl'], $move['newNsDecl'], $content);
        file_put_contents($targetPath, $content);
        
        // Update references
        $directories = [
            $baseDir . '/app',
            $baseDir . '/tests',
            $baseDir . '/routes',
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) continue;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $c = file_get_contents($file->getPathname());
                    $mod = false;
                    
                    if (strpos($c, "use {$move['oldNamespace']};") !== false) {
                        $c = str_replace("use {$move['oldNamespace']};", "use {$move['newNamespace']};", $c);
                        $mod = true;
                    }
                    if (strpos($c, $move['oldNamespace']) !== false) {
                        $c = str_replace($move['oldNamespace'], $move['newNamespace'], $c);
                        $mod = true;
                    }
                    if ($mod) {
                        file_put_contents($file->getPathname(), $c);
                    }
                }
            }
        }
    }
}

function delTree($dir) { 
    if (!file_exists($dir)) return;
    $files = array_diff(scandir($dir), array('.','..')); 
    foreach ($files as $file) { 
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
    } 
    return rmdir($dir); 
}

delTree("$appDir/Policies");
delTree("$appDir/Notifications");
delTree("$appDir/Support/Attendance");
delTree("$appDir/Support/Biometrics");
delTree("$appDir/Support/Facial");

echo "Done final refactoring!\n";

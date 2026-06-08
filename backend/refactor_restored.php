<?php

$baseDir = __DIR__;
$appDir = $baseDir . '/app';
$modulesDir = $baseDir . '/app/Modules';

$moves = [
    [
        'source' => "$appDir/Http/Controllers/Api/V1/Auth/PasswordRecoveryController.php",
        'targetDir' => "$modulesDir/Auth/Presentation/Controllers",
        'oldNamespace' => "App\\Http\\Controllers\\Api\\V1\\Auth",
        'newNamespace' => "App\\Modules\\Auth\\Presentation\\Controllers"
    ],
    [
        'source' => "$appDir/Http/Controllers/Api/V1/Auth/SessionController.php",
        'targetDir' => "$modulesDir/Auth/Presentation/Controllers",
        'oldNamespace' => "App\\Http\\Controllers\\Api\\V1\\Auth",
        'newNamespace' => "App\\Modules\\Auth\\Presentation\\Controllers"
    ],
    [
        'source' => "$appDir/Http/Controllers/Api/V1/Academic/AcademicController.php",
        'targetDir' => "$modulesDir/Academico/Presentation/Controllers",
        'oldNamespace' => "App\\Http\\Controllers\\Api\\V1\\Academic",
        'newNamespace' => "App\\Modules\\Academico\\Presentation\\Controllers"
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
        $content = str_replace("namespace {$move['oldNamespace']};", "namespace {$move['newNamespace']};", $content);
        file_put_contents($targetPath, $content);
        
        $oldClass = $move['oldNamespace'] . '\\' . basename($move['source'], '.php');
        $newClass = $move['newNamespace'] . '\\' . basename($move['source'], '.php');
        
        // Update references (e.g. routes/api.php)
        $routePath = $baseDir . '/routes/api.php';
        if (file_exists($routePath)) {
            $c = file_get_contents($routePath);
            $c = str_replace("use {$oldClass};", "use {$newClass};", $c);
            $c = str_replace($oldClass, $newClass, $c);
            file_put_contents($routePath, $c);
        }
    }
}

// Cleanup the restored dir
function delTree($dir) { 
    if (!file_exists($dir)) return;
    $files = array_diff(scandir($dir), array('.','..')); 
    foreach ($files as $file) { 
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
    } 
    return rmdir($dir); 
}

delTree("$appDir/Http/Controllers/Api");

echo "Restored controllers successfully moved!\n";

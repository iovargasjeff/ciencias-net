<?php

$planPath = 'openspec/EXECUTION_PLAN.md';
$plan = file_get_contents($planPath);
$archives = array_map('basename', glob('openspec/changes/archive/*', GLOB_ONLYDIR));

foreach ($archives as $arch) {
    $changeName = preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', $arch);
    $plan = preg_replace('/\|\s*(\w+-\d+)\s*\|\s*`\[\s*\]`\s*\|\s*`'.preg_quote($changeName, '/').'`\s*\|/', '| $1 | `[x]` | `'.$changeName.'` |', $plan);
}

file_put_contents($planPath, $plan);
echo "EXECUTION_PLAN.md updated.\n";

$block = <<<'EOF'

## Source of Truth Check

- Product docs reviewed:
- Architecture docs reviewed:
- API contracts reviewed:
- Domain docs reviewed:
- Security docs reviewed:
- Conflicts found: yes/no

If any conflict exists, do not implement until docs are corrected or the task is rewritten.

## Backend Placement

All backend domain code must be placed under:

```text
backend/app/Modules/<ModuleName>/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

No domain models/controllers/use cases/policies may be created under root `app/`.

EOF;

$activeChanges = glob('openspec/changes/*', GLOB_ONLYDIR);
foreach ($activeChanges as $dir) {
    if (basename($dir) === 'archive') {
        continue;
    }

    foreach (['design.md', 'tasks.md'] as $file) {
        $path = $dir.'/'.$file;
        if (! file_exists($path)) {
            continue;
        }

        $content = file_get_contents($path);

        // Inject block if not present
        if (strpos($content, '## Source of Truth Check') === false) {
            // Find first line starting with #
            $content = preg_replace('/^(# .+)$/m', "$1\n".$block, $content, 1);
        }

        // Replace bad tasks
        $content = str_replace('app/Models/', 'app/Modules/<ModuleName>/Infrastructure/Models/', $content);
        $content = str_replace('app/Http/Controllers/Api/V1/', 'app/Modules/<ModuleName>/Presentation/Controllers/', $content);
        $content = str_replace('app/Policies/', 'app/Modules/<ModuleName>/Presentation/Policies/', $content);
        $content = str_replace('app/Notifications/', 'app/Modules/<ModuleName>/Infrastructure/Notifications/', $content);
        $content = preg_replace('/app\\\\Models\\\\/m', 'App\\\\Modules\\\\<ModuleName>\\\\Infrastructure\\\\Models\\\\', $content);

        file_put_contents($path, $content);
        echo "Updated $path\n";
    }
}

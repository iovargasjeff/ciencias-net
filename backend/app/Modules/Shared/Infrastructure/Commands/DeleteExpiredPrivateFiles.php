<?php

namespace App\Modules\Shared\Infrastructure\Commands;

use App\Modules\Shared\Application\PrivateFileService;
use Illuminate\Console\Command;

class DeleteExpiredPrivateFiles extends Command
{
    protected $signature = 'private-files:delete-expired';

    protected $description = 'Delete expired private files from storage and audit the retention cleanup.';

    public function handle(PrivateFileService $service): int
    {
        $deleted = $service->deleteExpired();
        $this->info("Deleted {$deleted} expired private file(s).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use App\Support\Attendance\StudentAttendanceClosureService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateStudentAttendanceAlertsJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $threshold = 3,
    ) {}

    public function handle(StudentAttendanceClosureService $closure): array
    {
        return $closure->unjustifiedAbsenceAlerts($this->threshold);
    }
}

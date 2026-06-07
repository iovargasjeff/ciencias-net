<?php

namespace App\Jobs;

use App\Models\User;
use App\Support\Attendance\StudentAttendanceClosureService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CloseStudentAttendanceDayJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $date,
        public readonly string $actorId,
    ) {}

    public function handle(StudentAttendanceClosureService $closure): array
    {
        return $closure->close(Carbon::parse($this->date), User::findOrFail($this->actorId));
    }
}

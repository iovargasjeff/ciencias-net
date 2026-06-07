<?php

namespace App\Http\Controllers\Api\V1\StudentAttendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentAttendance\CreateManualStudentAttendanceEventRequest;
use App\Http\Resources\StudentAttendanceMovementResource;
use App\Http\Resources\StudentAttendanceResource;
use App\Models\Alumno;
use App\Models\AsistenciaAlumno;
use App\Support\Attendance\StudentAttendanceProcessor;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StudentAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AsistenciaAlumno::query()->with(['movimientos', 'alumno.padres'])->latest('fecha');
        $user = $request->user();

        if ($user?->hasAnyRole(['superadmin', 'auxiliar', 'toe']) !== true) {
            if ($user?->padre !== null) {
                $studentIds = $user->padre->alumnos()->pluck('alumnos.id');
                $query->whereIn('alumno_id', $studentIds);
            } elseif ($user?->alumno !== null) {
                $query->where('alumno_id', $user->alumno->id);
            } else {
                abort(403);
            }
        }

        $query->when($request->filled('student_id'), fn ($q) => $q->where('alumno_id', $request->string('student_id')));

        return StudentAttendanceResource::collection($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function manual(
        CreateManualStudentAttendanceEventRequest $request,
        StudentAttendanceProcessor $processor,
        AuditLogger $audit,
    ): JsonResponse {
        $student = Alumno::findOrFail($request->string('student_id'));
        $movement = $processor->processManualEvent(
            $student,
            $request->string('event_type')->toString(),
            Carbon::parse($request->date('occurred_at')),
            $request->string('reason')->toString(),
            $request->user(),
        );

        $audit->record($request, 'student_attendance.manual_event_created', $request->user(), $movement, newValues: [
            'student_id' => $student->id,
            'event_type' => $request->string('event_type')->toString(),
        ]);

        return response()->json(['data' => new StudentAttendanceMovementResource($movement)], 201);
    }
}

<?php

namespace App\Modules\Horarios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Horarios\Application\UseCases\CreateCalendarEvent;
use App\Modules\Horarios\Application\UseCases\CreateSchedule;
use App\Modules\Horarios\Infrastructure\Models\EventoCalendario;
use App\Modules\Horarios\Infrastructure\Models\Horario;
use App\Modules\Horarios\Presentation\Requests\CreateCalendarEventRequest;
use App\Modules\Horarios\Presentation\Requests\CreateScheduleRequest;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScheduleController extends Controller
{
    public function listSchedules(Request $request)
    {
        Gate::authorize('viewAny', Horario::class);

        $query = Horario::query();

        if ($request->has('carga_academica_id')) {
            $query->where('carga_academica_id', $request->input('carga_academica_id'));
        }
        if ($request->has('teacher_id')) {
            $query->whereHas('cargaAcademica', fn ($q) => $q->where('docente_id', $request->input('teacher_id')));
        }
        if ($request->has('student_id')) {
            $student = Alumno::findOrFail($request->input('student_id'));
            $sectionIds = $student->matriculas()->whereIn('estado', ['activo', 'activa'])->pluck('seccion_id');
            $query->whereHas('cargaAcademica', fn ($q) => $q->whereIn('seccion_id', $sectionIds));
        }
        if ($request->user()?->hasRole('docente') && ! $request->has('teacher_id')) {
            $teacherId = Docente::where('user_id', $request->user()->id)->value('id');
            $query->whereHas('cargaAcademica', fn ($q) => $q->where('docente_id', $teacherId));
        }
        if ($request->user()?->hasRole('alumno') && ! $request->has('student_id')) {
            $student = Alumno::where('user_id', $request->user()->id)->first();
            $sectionIds = $student?->matriculas()->whereIn('estado', ['activo', 'activa'])->pluck('seccion_id') ?? collect();
            $query->whereHas('cargaAcademica', fn ($q) => $q->whereIn('seccion_id', $sectionIds));
        }

        $schedules = $query->paginate($request->input('per_page', 15));

        return response()->json($schedules);
    }

    public function createSchedule(CreateScheduleRequest $request, CreateSchedule $useCase)
    {
        $horario = $useCase->execute(
            $request->input('teaching_assignment_id'),
            $request->input('weekday'),
            $request->input('starts_at'),
            $request->input('ends_at'),
            $request->input('room')
        );

        return response()->json(['data' => $horario], 201);
    }

    public function listCalendarEvents(Request $request)
    {
        Gate::authorize('viewAny', EventoCalendario::class);

        $query = EventoCalendario::query();

        if ($request->has('periodo_academico_id')) {
            $query->where('periodo_academico_id', $request->input('periodo_academico_id'));
        }

        $events = $query->paginate($request->input('per_page', 15));

        return response()->json($events);
    }

    public function createCalendarEvent(CreateCalendarEventRequest $request, CreateCalendarEvent $useCase)
    {
        $event = $useCase->execute(
            $request->input('title'),
            $request->input('starts_at'),
            $request->input('ends_at'),
            $request->input('event_type'),
            $request->input('description')
        );

        return response()->json(['data' => $event], 201);
    }
}

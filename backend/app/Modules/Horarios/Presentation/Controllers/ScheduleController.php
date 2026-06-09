<?php

namespace App\Modules\Horarios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Horarios\Application\UseCases\CreateCalendarEvent;
use App\Modules\Horarios\Application\UseCases\CreateSchedule;
use App\Modules\Horarios\Infrastructure\Models\EventoCalendario;
use App\Modules\Horarios\Infrastructure\Models\Horario;
use App\Modules\Horarios\Presentation\Requests\CreateCalendarEventRequest;
use App\Modules\Horarios\Presentation\Requests\CreateScheduleRequest;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function listSchedules(Request $request)
    {
        $this->authorize('viewAny', Horario::class);

        $query = Horario::query();

        // Si se provee carga_academica_id
        if ($request->has('carga_academica_id')) {
            $query->where('carga_academica_id', $request->input('carga_academica_id'));
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
        $this->authorize('viewAny', EventoCalendario::class);

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

<?php

namespace App\Modules\Academico\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academico\Application\UseCases\CorrectPublishedAssessmentResult;
use App\Modules\Academico\Application\UseCases\PublishAssessment;
use App\Modules\Academico\Application\UseCases\SetAssessmentClosure;
use App\Modules\Academico\Infrastructure\Models\Examen;
use App\Modules\Academico\Infrastructure\Models\Nota;
use App\Modules\Academico\Infrastructure\Models\ReporteAcademico;
use App\Modules\Academico\Presentation\Requests\CorrectPublishedAssessmentResultRequest;
use App\Modules\Academico\Presentation\Requests\GenerateAcademicReportRequest;
use App\Modules\Academico\Presentation\Requests\SetAssessmentClosureRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AcademicReportController extends Controller
{
    public function publishAssessment(Examen $examen, PublishAssessment $useCase)
    {
        Gate::authorize('publish', $examen);

        $useCase->execute($examen);

        return response()->json(['data' => ['id' => $examen->id]]);
    }

    public function listRankings(Request $request)
    {
        Gate::authorize('viewAny', ReporteAcademico::class);

        $query = Nota::whereNotNull('puesto_ranking')
            ->whereHas('examen', function ($q) {
                $q->whereIn('estado', ['publicado', 'cerrado']);
            })
            ->with(['matricula.student', 'examen']);

        if ($request->has('assessment_id')) {
            $query->where('examen_id', $request->query('assessment_id'));
        }

        $perPage = $request->query('per_page', 15);
        $rankings = $query->orderBy('puesto_ranking', 'asc')->paginate($perPage);

        return response()->json($rankings);
    }

    public function setAssessmentClosure(Examen $examen, SetAssessmentClosureRequest $request, SetAssessmentClosure $useCase)
    {
        Gate::authorize('close', $examen);

        $useCase->execute($examen, $request->input('closed'));

        return response()->json(['data' => ['id' => $examen->id]]);
    }

    public function correctPublishedAssessmentResult(Nota $nota, CorrectPublishedAssessmentResultRequest $request, CorrectPublishedAssessmentResult $useCase)
    {
        Gate::authorize('correct', $nota);

        $useCase->execute($nota, $request->input('score'), $request->input('reason'));

        return response()->json(['data' => ['id' => $nota->id]]);
    }

    public function generateAcademicReport(GenerateAcademicReportRequest $request)
    {
        Gate::authorize('generate', ReporteAcademico::class);

        // Simular la generación de un PDF y retornarlo.
        // En una implementación real, aquí se usaría dompdf o excel para generar el reporte.
        $content = "Reporte Académico Simulado\n";
        $content .= 'Formato: '.$request->input('format')."\n";
        $content .= 'Tipo: '.$request->input('report_type')."\n";

        return response($content)
            ->header('Content-Type', $request->input('format') === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="report.'.$request->input('format').'"');
    }
}

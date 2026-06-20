<?php

namespace App\Http\Resources;

use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Academico\Infrastructure\Models\Curso;
use App\Modules\Academico\Infrastructure\Models\Grado;
use App\Modules\Academico\Infrastructure\Models\Matricula;
use App\Modules\Academico\Infrastructure\Models\PeriodoAcademico;
use App\Modules\Academico\Infrastructure\Models\Seccion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return match (true) {
            $this->resource instanceof PeriodoAcademico => [
                'id' => $this->id, 'name' => $this->nombre, 'start_date' => $this->fecha_inicio?->toDateString(),
                'end_date' => $this->fecha_fin?->toDateString(), 'status' => match ($this->estado) {
                    'activo' => 'active', 'cerrado' => 'closed', default => 'draft',
                },
                'terms' => $this->whenLoaded('bimestres', fn () => $this->bimestres->map(fn ($term) => [
                    'id' => $term->id,
                    'name' => $term->nombre,
                    'start_date' => $term->fecha_inicio?->toDateString(),
                    'end_date' => $term->fecha_fin?->toDateString(),
                ])->values()),
            ],
            $this->resource instanceof Grado => [
                'id' => $this->id, 'academic_period_id' => $this->periodo_academico_id,
                'catalog_code' => $this->catalog_code,
                'name' => $this->nombre, 'level' => mb_strtolower($this->nivel), 'order' => $this->orden,
            ],
            $this->resource instanceof Seccion => [
                'id' => $this->id, 'grade_id' => $this->grado_id, 'name' => $this->nombre,
                'capacity' => $this->capacidad,
            ],
            $this->resource instanceof Curso => [
                'id' => $this->id, 'grade_id' => $this->grado_id, 'code' => $this->codigo, 'name' => $this->nombre,
                'description' => $this->descripcion,
            ],
            $this->resource instanceof Matricula => [
                'id' => $this->id, 'student_id' => $this->alumno_id, 'section_id' => $this->seccion_id,
                'name' => ($this->alumno ? "{$this->alumno->nombres} {$this->alumno->apellidos}" : 'Alumno').' - '.($this->seccion && $this->seccion->grado ? "{$this->seccion->grado->nombre} {$this->seccion->nombre}" : 'Sección'),
                'academic_period_id' => $this->seccion?->grado?->periodo_academico_id,
                'enrolled_at' => $this->fecha?->toDateString(), 'status' => $this->estado,
            ],
            $this->resource instanceof CargaAcademica => [
                'id' => $this->id, 'teacher_id' => $this->docente_id, 'course_id' => $this->curso_id,
                'section_id' => $this->seccion_id,
                'name' => ($this->docente ? "{$this->docente->nombres} {$this->docente->apellidos}" : 'Docente').' - '.($this->curso ? $this->curso->nombre : 'Curso').' ('.($this->seccion && $this->seccion->grado ? "{$this->seccion->grado->nombre} {$this->seccion->nombre}" : 'Sección').')',
                'academic_period_id' => $this->seccion?->grado?->periodo_academico_id,
                'valid_from' => $this->vigente_desde?->toDateString(),
                'valid_until' => $this->vigente_hasta?->toDateString(), 'active' => $this->activo,
            ],
            default => parent::toArray($request),
        };
    }
}

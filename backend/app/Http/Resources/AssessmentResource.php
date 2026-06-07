<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'teaching_assignment_id' => $this->carga_academica_id,
            'title'                  => $this->titulo,
            'assessment_date'        => $this->fecha_aplicacion ? $this->fecha_aplicacion->format('Y-m-d') : null,
            'assessment_type'        => $this->periodo_nombre,
            'channel'                => $this->canal,
            'total_questions'        => $this->total_preguntas,
            'max_score'              => $this->puntaje_maximo,
            'status'                 => $this->estado,
            'published_by'           => $this->publicado_por,
            'published_at'           => $this->publicado_en ? $this->publicado_en->toIso8601String() : null,
            'created_at'             => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'             => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}

<?php

namespace App\Modules\Materiales\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academico\Infrastructure\Models\CargaAcademica;
use App\Modules\Materiales\Application\UseCases\CreateExternalMaterialLink;
use App\Modules\Materiales\Application\UseCases\UploadMaterialFile;
use App\Modules\Materiales\Infrastructure\Models\Material;
use App\Modules\Materiales\Presentation\Requests\CreateExternalMaterialRequest;
use App\Modules\Materiales\Presentation\Requests\CreateMaterialRequest;
use App\Modules\Materiales\Presentation\Requests\ReplaceMaterialFileRequest;
use App\Modules\Materiales\Presentation\Requests\UpdateMaterialRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    public function listMaterials(Request $request)
    {
        $cargaId = $request->query('carga_academica_id');
        if (! $cargaId) {
            return response()->json(['error' => 'carga_academica_id is required'], 400);
        }

        $carga = CargaAcademica::findOrFail($cargaId);
        Gate::authorize('viewAny', [Material::class, $carga]);

        $query = Material::where('carga_academica_id', $carga->id);

        if ($request->user()->hasRole('alumno') || $request->user()->hasRole('padre')) {
            $query->where('activo', true);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function createMaterial(CreateMaterialRequest $request, UploadMaterialFile $useCase)
    {
        $carga = CargaAcademica::findOrFail($request->input('carga_academica_id'));
        Gate::authorize('create', [Material::class, $carga]);

        $material = $useCase->execute(
            $carga,
            $request->file('file'),
            $request->validated(),
            $request->user()->id
        );

        return response()->json(['data' => $material], 201);
    }

    public function createExternalMaterial(CreateExternalMaterialRequest $request, CreateExternalMaterialLink $useCase)
    {
        $carga = CargaAcademica::findOrFail($request->input('carga_academica_id'));
        Gate::authorize('create', [Material::class, $carga]);

        $material = $useCase->execute(
            $carga,
            $request->validated(),
            $request->user()->id
        );

        return response()->json(['data' => $material], 201);
    }

    public function downloadMaterial(Material $material)
    {
        Gate::authorize('view', $material);

        if ($material->tipo !== 'archivo') {
            return response()->json(['error' => 'Material is not a file'], 400);
        }

        if (! Storage::disk('private')->exists($material->ruta_o_url)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::disk('private')->download($material->ruta_o_url, Str::slug($material->titulo).'.'.pathinfo($material->ruta_o_url, PATHINFO_EXTENSION));
    }

    public function updateMaterial(Material $material, UpdateMaterialRequest $request)
    {
        Gate::authorize('update', $material);

        $material->update($request->validated());

        return response()->json(['data' => $material]);
    }

    public function replaceMaterialFile(Material $material, ReplaceMaterialFileRequest $request)
    {
        Gate::authorize('update', $material);

        if ($material->tipo !== 'archivo') {
            return response()->json(['error' => 'Material is not a file'], 400);
        }

        $file = $request->file('file');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs("materials/{$material->carga_academica_id}", $filename, 'private');

        // Delete old file
        if (Storage::disk('private')->exists($material->ruta_o_url)) {
            Storage::disk('private')->delete($material->ruta_o_url);
        }

        $material->update(['ruta_o_url' => $path]);

        return response()->json(['data' => $material]);
    }

    public function archiveMaterial(Material $material)
    {
        Gate::authorize('delete', $material);

        $material->update(['activo' => false]);

        return response()->json(['message' => 'Material archived']);
    }
}

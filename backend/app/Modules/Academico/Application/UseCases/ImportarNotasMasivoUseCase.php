<?php

namespace App\Modules\Academico\Application\UseCases;

use App\Modules\Academico\Infrastructure\Models\Examen;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportarNotasMasivoUseCase
{
    private RegistrarNotaIndividualUseCase $registrarIndividualUseCase;

    public function __construct(RegistrarNotaIndividualUseCase $registrarIndividualUseCase)
    {
        $this->registrarIndividualUseCase = $registrarIndividualUseCase;
    }

    public function execute(Examen $examen, array $notasData, string $userId, bool $preview = false): array
    {
        $result = [];

        try {
            DB::transaction(function () use ($examen, $notasData, $userId, $preview, &$result) {
                $processed = 0;

                foreach ($notasData as $index => $notaData) {
                    try {
                        $nota = $this->registrarIndividualUseCase->execute($examen, $notaData, $userId);
                        $processed++;
                    } catch (ValidationException $e) {
                        // Include the index/row in the error to help the user identify the problem
                        $errors = [];
                        foreach ($e->errors() as $key => $messages) {
                            $errors["notas.{$index}.{$key}"] = $messages;
                        }
                        throw ValidationException::withMessages($errors);
                    }
                }

                $result = [
                    'message' => "Procesadas {$processed} notas correctamente.",
                    'processed_count' => $processed,
                ];

                if ($preview) {
                    // Si es preview, forzamos rollback
                    DB::rollBack();
                    $result['message'] = "Preview exitoso. Ninguna nota fue guardada. {$processed} notas válidas.";
                    $result['is_preview'] = true;
                }
            });
        } catch (\Exception $e) {
            // Si es un ValidationException lo lanzamos normal, si es otro lo relanzamos
            throw $e;
        }

        return $result;
    }
}

<?php

$root = dirname(__DIR__);

$guardedDirectories = [
    'app/Models',
    'app/Http/Controllers/Api/V1',
    'app/Http/Requests',
    'app/Http/Resources',
    'app/UseCases',
    'app/Policies',
];

$allowedLegacyFiles = [
    'app/Models/Administrativo.php',
    'app/Models/Alumno.php',
    'app/Models/CargaAcademica.php',
    'app/Models/Comunicado.php',
    'app/Models/ComunicadoLectura.php',
    'app/Models/Curso.php',
    'app/Models/Docente.php',
    'app/Models/EventoCalendario.php',
    'app/Models/Grado.php',
    'app/Models/Horario.php',
    'app/Models/Material.php',
    'app/Models/Matricula.php',
    'app/Models/Nota.php',
    'app/Models/Notificacion.php',
    'app/Models/Padre.php',
    'app/Models/PeriodoAcademico.php',
    'app/Models/ReporteAcademico.php',
    'app/Models/Seccion.php',
    'app/Models/User.php',
    'app/Http/Controllers/Api/V1/Academic/AcademicController.php',
    'app/Http/Controllers/Api/V1/Auth/PasswordRecoveryController.php',
    'app/Http/Controllers/Api/V1/Auth/SessionController.php',
    'app/Http/Controllers/Api/V1/Family/FamilyLinkController.php',
    'app/Http/Controllers/Api/V1/IdentityAccess/AccountController.php',
    'app/Http/Requests/Academic/AcademicEntityRequest.php',
    'app/Http/Requests/Auth/ForgotPasswordRequest.php',
    'app/Http/Requests/Auth/LoginRequest.php',
    'app/Http/Requests/Auth/ResetPasswordRequest.php',
    'app/Http/Requests/Family/CreateFamilyLinkRequest.php',
    'app/Http/Requests/IdentityAccess/ActivationRequest.php',
    'app/Http/Requests/IdentityAccess/CreateAccountRequest.php',
    'app/Http/Requests/IdentityAccess/RolesRequest.php',
    'app/Http/Requests/IdentityAccess/UpdateAccountRequest.php',
    'app/Http/Resources/AcademicResource.php',
    'app/Http/Resources/AccountResource.php',
    'app/Http/Resources/AuthUserResource.php',
    'app/Http/Resources/FamilyLinkResource.php',
    'app/Http/Resources/PaginatedCollection.php',
    'app/Policies/AlumnoPolicy.php',
    'app/Policies/PeriodoAcademicoPolicy.php',
    'app/Policies/UserPolicy.php',
];

$allowed = array_fill_keys($allowedLegacyFiles, true);
$violations = [];

foreach ($guardedDirectories as $directory) {
    $absoluteDirectory = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $directory);

    if (! is_dir($absoluteDirectory)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absoluteDirectory));

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

        if (! isset($allowed[$relativePath])) {
            $violations[] = $relativePath;
        }
    }
}

if ($violations !== []) {
    fwrite(STDERR, "Architecture guard failed. New backend domain code must live under app/Modules/<Modulo>/.\n\n");

    foreach ($violations as $violation) {
        fwrite(STDERR, "- {$violation}\n");
    }

    exit(1);
}

echo "Architecture guard passed.\n";

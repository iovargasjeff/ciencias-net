<?php

namespace App\Modules\Usuarios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\IdentityAccess\ActivationRequest;
use App\Http\Requests\IdentityAccess\CreateAccountRequest;
use App\Http\Requests\IdentityAccess\RolesRequest;
use App\Http\Requests\IdentityAccess\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Modules\Usuarios\Infrastructure\Models\Administrativo;
use App\Modules\Usuarios\Infrastructure\Models\Alumno;
use App\Modules\Usuarios\Infrastructure\Models\Docente;
use App\Modules\Usuarios\Infrastructure\Models\Padre;
use App\Modules\Usuarios\Infrastructure\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('manage', User::class);
        $query = User::query()->with('roles')->latest();
        $query->when($request->filled('search'), fn ($q) => $q->where(
            fn ($inner) => $inner->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('email', 'like', '%'.$request->string('search').'%')
        ));
        $query->when($request->has('active'), fn ($q) => $q->where('activo', $request->boolean('active')));

        $query->when($request->filled('exclude_roles'), function ($q) use ($request) {
            $roles = explode(',', $request->string('exclude_roles'));
            $q->whereDoesntHave('roles', fn ($r) => $r->whereIn('name', $roles));
        });

        return AccountResource::collection($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(CreateAccountRequest $request, AuditLogger $audit): JsonResponse
    {
        $roles = $request->array('roles');
        $this->authorizeRoles($request, new User, $roles, $audit);
        $account = DB::transaction(function () use ($request, $roles) {
            $account = User::create([
                'name' => $request->string('name'), 'email' => mb_strtolower($request->string('email')),
                'password' => Str::password(24), 'activo' => true,
            ]);
            $account->syncRoles($roles);
            $this->createRoleProfiles($account, $request, $roles);

            return $account;
        });
        $audit->record($request, 'account.created', $request->user(), $account, newValues: ['roles' => $roles]);

        return response()->json(['data' => new AccountResource($account->load('roles'))], 201);
    }

    public function show(string $accountId): JsonResponse
    {
        Gate::authorize('manage', User::class);
        $account = User::findOrFail($accountId);

        return response()->json(['data' => new AccountResource($account->load('roles'))]);
    }

    public function update(UpdateAccountRequest $request, string $accountId, AuditLogger $audit): JsonResponse
    {
        $account = User::findOrFail($accountId);
        $old = $account->only(['name', 'email']);
        $account->update($request->validated());
        $audit->record($request, 'account.updated', $request->user(), $account, $old, $account->only(['name', 'email']));

        return response()->json(['data' => new AccountResource($account->load('roles'))]);
    }

    public function activation(ActivationRequest $request, string $accountId, AuditLogger $audit): JsonResponse
    {
        $account = User::findOrFail($accountId);
        Gate::authorize('changeSensitiveState', $account);
        $old = ['active' => $account->activo];
        $account->update(['activo' => $request->boolean('active')]);
        $audit->record($request, 'account.activation_changed', $request->user(), $account, $old, ['active' => $account->activo]);

        return response()->json(['data' => new AccountResource($account->load('roles'))]);
    }

    public function roles(RolesRequest $request, string $accountId, AuditLogger $audit): JsonResponse
    {
        $account = User::findOrFail($accountId);
        $roles = $request->array('roles');
        $this->authorizeRoles($request, $account, $roles, $audit);
        $old = $account->getRoleNames()->all();
        $account->syncRoles($roles);
        $audit->record($request, 'account.roles_changed', $request->user(), $account, ['roles' => $old], ['roles' => $roles]);

        return response()->json(['data' => new AccountResource($account->load('roles'))]);
    }

    public function passwordReset(Request $request, string $accountId, AuditLogger $audit): JsonResponse
    {
        $account = User::findOrFail($accountId);
        Gate::authorize('changeSensitiveState', $account);
        $token = Password::broker()->createToken($account);
        $account->notify(new ResetPassword($token));
        $audit->record($request, 'account.password_reset_issued', $request->user(), $account);

        return response()->json(['data' => ['message' => 'Se enviaron instrucciones de restablecimiento.']]);
    }

    private function authorizeRoles(Request $request, User $account, array $roles, AuditLogger $audit): void
    {
        if (! Gate::forUser($request->user())->allows('assignRoles', [$account, $roles])) {
            $audit->record($request, 'account.roles_rejected', $request->user(), $account, newValues: ['roles' => $roles]);
            Gate::forUser($request->user())->authorize('assignRoles', [$account, $roles]);
        }
    }

    private function createRoleProfiles(User $account, CreateAccountRequest $request, array $roles): void
    {
        $firstNames = $this->firstNames($request);
        $lastNames = $request->string('last_names')->toString();

        if (in_array('docente', $roles, true)) {
            Docente::create([
                'user_id' => $account->id,
                'dni' => $request->string('dni')->toString(),
                'nombres' => $firstNames,
                'apellidos' => $lastNames,
                'telefono' => $request->string('phone')->toString(),
            ]);
        }

        if (in_array('padre', $roles, true)) {
            Padre::create([
                'user_id' => $account->id,
                'dni' => $request->string('dni')->toString(),
                'nombres' => $firstNames,
                'apellidos' => $lastNames,
                'celular' => $request->string('phone')->toString(),
                'correo_notificaciones' => mb_strtolower($request->string('notification_email')->toString()),
            ]);
        }

        if (in_array('alumno', $roles, true)) {
            Alumno::create([
                'user_id' => $account->id,
                'dni' => $request->string('dni')->toString(),
                'nombres' => $firstNames,
                'apellidos' => $lastNames,
            ]);
        }

        if (array_intersect($roles, ['administrativo', 'toe', 'auxiliar', 'psicologia']) !== []) {
            Administrativo::create([
                'user_id' => $account->id,
                'nombres' => $request->string('name')->toString(),
                'cargo' => $this->administrativeRole($roles),
            ]);
        }
    }

    private function firstNames(CreateAccountRequest $request): string
    {
        $lastNames = $request->string('last_names')->toString();
        if ($lastNames === '') {
            return $request->string('name')->toString();
        }

        return trim(Str::beforeLast($request->string('name')->toString(), $lastNames)) ?: $request->string('name')->toString();
    }

    private function administrativeRole(array $roles): string
    {
        foreach (['toe', 'auxiliar', 'psicologia', 'administrativo'] as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return 'administrativo';
    }
}

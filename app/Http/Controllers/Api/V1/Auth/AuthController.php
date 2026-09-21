<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Services\Auth\CredentialChecker;
use App\Services\Users\UserProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Authentification de l'application mobile (ARCHITECTURE §13). Mêmes règles que le Web : identifiants vérifiés par
 * CredentialChecker (téléphone, 5 tentatives, audit), un jeton Bearer par appareil, durée de vie 30 jours (paramétrable).
 */
class AuthController extends Controller
{
    /** Capacité du jeton émis tant que le mot de passe est temporaire : il ne permet que de le changer. */
    private const RESTRICTED = ['password:change'];

    public function login(LoginRequest $request, CredentialChecker $checker): JsonResponse
    {
        $user = $checker->attempt($request->validated('phone'), $request->validated('password'), 'api', (string) $request->ip());

        $checker->succeeded($user, 'api');

        return response()->json($this->issueToken($user, $request->validated('device_name')));
    }

    public function logout(Request $request, ActivityLogger $logger): JsonResponse
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();
        $logger->log('auth.logout', 'auth', "Déconnexion — {$user->name}", $user, actor: $user);

        return response()->json(['message' => 'Déconnecté.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => (new UserResource($request->user()))->resolve()]);
    }

    /**
     * Change le mot de passe (accessible même avec le jeton restreint), révoque tous les jetons et en émet un nouveau.
     */
    public function password(ChangePasswordRequest $request, UserProvisioningService $users): JsonResponse
    {
        $user = $request->user();
        $device = $user->currentAccessToken()->name;

        $users->changePassword($user, $request->validated('password'));

        return response()->json($this->issueToken($user->refresh(), $device));
    }

    /**
     * Un seul jeton par appareil : se reconnecter remplace le précédent.
     *
     * @return array<string, mixed>
     */
    private function issueToken(User $user, string $device): array
    {
        $user->tokens()->where('name', $device)->delete();

        $abilities = $user->must_change_password ? self::RESTRICTED : ['*'];
        $expiresAt = now()->addMinutes((int) config('sanctum.expiration'));
        $token = $user->createToken($device, $abilities, $expiresAt);

        return [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'abilities' => $abilities,
            'password_change_required' => $user->must_change_password,
            'user' => (new UserResource($user))->resolve(),
        ];
    }
}

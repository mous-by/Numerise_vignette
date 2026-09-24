<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreCommissariatRequest;
use App\Http\Requests\Web\UpdateCommissariatRequest;
use App\Models\Commissariat;
use App\Models\User;
use App\Services\Users\UserProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Écran Commissariats (W1) : liste, création et modification en modale, activation/désactivation, suppression en
 * soft delete. Désactiver un commissariat coupe l'accès de tous ses utilisateurs (sessions et jetons supprimés,
 * ARCHITECTURE §11) ; le fail-closed de `EnsureUserIsActive` couvre déjà la requête suivante, cette révocation
 * immédiate évite d'attendre une session déjà ouverte.
 */
class CommissariatController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Commissariat::class);

        return view('commissariats.index', [
            'commissariats' => Commissariat::withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCommissariatRequest $request): RedirectResponse
    {
        $commissariat = Commissariat::create($request->validated());

        return redirect()->route('commissariats.index')->with('status', "Commissariat « {$commissariat->name} » créé.");
    }

    public function update(UpdateCommissariatRequest $request, Commissariat $commissariat, UserProvisioningService $provisioning): RedirectResponse
    {
        $wasActive = $commissariat->is_active;

        $commissariat->update($request->validated());

        if ($wasActive && ! $commissariat->is_active) {
            $commissariat->users()->get()->each(fn (User $user) => $provisioning->revokeAccess($user));
        }

        return redirect()->route('commissariats.index')->with('status', "Commissariat « {$commissariat->name} » modifié.");
    }

    public function destroy(Commissariat $commissariat): RedirectResponse
    {
        Gate::authorize('delete', $commissariat);

        // Logique métier, jamais contournée (même par le superadmin, D16) : contrairement à la permission,
        // elle ne passe pas par Gate::before.
        abort_if($commissariat->users()->exists(), 403, 'Ce commissariat a encore des utilisateurs rattachés : réaffectez-les avant de le supprimer.');

        $commissariat->delete();

        return redirect()->route('commissariats.index')->with('status', "Commissariat « {$commissariat->name} » supprimé.");
    }
}

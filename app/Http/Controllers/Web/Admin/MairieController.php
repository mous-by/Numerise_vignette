<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreMairieRequest;
use App\Http\Requests\Web\UpdateMairieRequest;
use App\Models\Mairie;
use App\Models\User;
use App\Services\Users\UserProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Écran Mairies (W2) : même patron que CommissariatController (W1). Liste, création et modification en
 * modale, activation/désactivation, suppression en soft delete. Désactiver une mairie coupe l'accès de tous
 * ses utilisateurs (sessions et jetons supprimés, ARCHITECTURE §11).
 */
class MairieController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Mairie::class);

        return view('mairies.index', [
            'mairies' => Mairie::withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreMairieRequest $request): RedirectResponse
    {
        $mairie = Mairie::create($request->validated());

        return redirect()->route('mairies.index')->with('status', "Mairie « {$mairie->name} » créée.");
    }

    public function update(UpdateMairieRequest $request, Mairie $mairie, UserProvisioningService $provisioning): RedirectResponse
    {
        $wasActive = $mairie->is_active;

        $mairie->update($request->validated());

        if ($wasActive && ! $mairie->is_active) {
            $mairie->users()->get()->each(fn (User $user) => $provisioning->revokeAccess($user));
        }

        return redirect()->route('mairies.index')->with('status', "Mairie « {$mairie->name} » modifiée.");
    }

    public function destroy(Mairie $mairie): RedirectResponse
    {
        Gate::authorize('delete', $mairie);

        // Logique métier, jamais contournée (même par le superadmin, D16) : contrairement à la permission,
        // elle ne passe pas par Gate::before.
        abort_if($mairie->users()->exists(), 403, 'Cette mairie a encore des utilisateurs rattachés : réaffectez-les avant de la supprimer.');

        $mairie->delete();

        return redirect()->route('mairies.index')->with('status', "Mairie « {$mairie->name} » supprimée.");
    }
}

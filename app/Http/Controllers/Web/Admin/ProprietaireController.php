<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreProprietaireRequest;
use App\Http\Requests\Web\UpdateProprietaireRequest;
use App\Models\Proprietaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Écran Propriétaires (W7) : saisis par le commissaire lors de l'enregistrement initial (cahier §5 Cas 1).
 * Cloisonné par commissariat (BelongsToCommissariat sur Proprietaire, ARCHITECTURE §11) : liste, création et
 * modification n'exposent jamais les propriétaires d'un autre commissariat, fail-closed.
 */
class ProprietaireController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Proprietaire::class);

        return view('proprietaires.index', [
            'proprietaires' => Proprietaire::orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function store(StoreProprietaireRequest $request): RedirectResponse
    {
        $proprietaire = Proprietaire::create($request->validated() + [
            'commissariat_id' => $request->user()->commissariat_id,
        ]);

        return redirect()->route('proprietaires.index')->with('status', "Propriétaire « {$proprietaire->fullName()} » créé.");
    }

    public function update(UpdateProprietaireRequest $request, Proprietaire $proprietaire): RedirectResponse
    {
        $proprietaire->update($request->validated());

        return redirect()->route('proprietaires.index')->with('status', "Propriétaire « {$proprietaire->fullName()} » modifié.");
    }

    public function destroy(Proprietaire $proprietaire): RedirectResponse
    {
        Gate::authorize('delete', $proprietaire);

        $proprietaire->delete();

        return redirect()->route('proprietaires.index')->with('status', "Propriétaire « {$proprietaire->fullName()} » supprimé.");
    }
}

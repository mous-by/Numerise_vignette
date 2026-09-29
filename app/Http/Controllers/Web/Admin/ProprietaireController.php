<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreProprietaireRequest;
use App\Http\Requests\Web\UpdateProprietaireRequest;
use App\Models\Proprietaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'proprietaires' => Proprietaire::with('motos')->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    /**
     * Recherche pour les champs Select2 « + Nouveau » d'un autre écran (Motos, W8) : commissariat trop peuplé
     * pour tout charger dans le select. Format attendu par Select2 : `{"results": [{"id", "text"}, ...]}`.
     */
    public function search(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Proprietaire::class);

        $term = trim((string) $request->query('q', ''));

        $proprietaires = Proprietaire::query()
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
            ))
            ->orderBy('last_name')->orderBy('first_name')
            ->limit(20)
            ->get()
            ->map(fn (Proprietaire $proprietaire) => ['id' => $proprietaire->id, 'text' => $proprietaire->fullName()]);

        return response()->json(['results' => $proprietaires]);
    }

    public function store(StoreProprietaireRequest $request): RedirectResponse
    {
        $proprietaire = Proprietaire::create($request->validated() + [
            'commissariat_id' => $request->user()->commissariat_id,
        ]);

        // Créé depuis la modale « + Nouveau » de l'écran Motos (W8) : on y retourne avec le propriétaire
        // fraîchement créé, plutôt que d'atterrir sur l'écran Propriétaires.
        if ($request->input('return_to') === 'motos') {
            return redirect()->route('motos.index', ['new_proprietaire' => $proprietaire->id])
                ->with('status', "Propriétaire « {$proprietaire->fullName()} » créé.");
        }

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

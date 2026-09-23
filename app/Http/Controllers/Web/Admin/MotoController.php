<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreMotoRequest;
use App\Http\Requests\Web\UpdateMotoRequest;
use App\Models\Moto;
use App\Models\MotoRetrouvee;
use App\Models\Proprietaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Écran Motos (W8) : saisies par le commissaire lors de l'enregistrement initial (cahier §5 Cas 1, §9 Accueil).
 * Cloisonné par commissariat (BelongsToCommissariat sur Moto), comme les propriétaires (W7). Le matricule est
 * l'identifiant national unique de la moto (cahier : jamais de châssis).
 */
class MotoController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Moto::class);

        return view('motos.index', [
            'motos' => Moto::with('proprietaire')->orderBy('plate_number')->get(),
            // Le select Propriétaire (modale) charge par recherche (Select2 AJAX, search()) plutôt que tout
            // précharger : ce booléen suffit à savoir s'il faut proposer « Ajouter » ou « Créez d'abord ... ».
            'hasProprietaires' => Proprietaire::query()->exists(),
        ]);
    }

    /**
     * Recherche pour le select Propriétaire de la modale de création/modification : commissariat trop peuplé
     * pour tout charger. Format attendu par Select2 : `{"results": [{"id", "text"}, ...]}`.
     */
    public function search(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Moto::class);

        $term = trim((string) $request->query('q', ''));

        $motos = Moto::with('proprietaire')
            ->when($term !== '', fn ($query) => $query->where('plate_number', 'like', "%{$term}%"))
            ->orderBy('plate_number')
            ->limit(20)
            ->get()
            ->map(fn (Moto $moto) => ['id' => $moto->id, 'text' => "{$moto->plate_number} — {$moto->proprietaire->fullName()}"]);

        return response()->json(['results' => $motos]);
    }

    /**
     * Recherche nationale des motos actuellement volées, pour le select Moto de l'écran Motos retrouvées (W10,
     * §4.7 acrossCommissariats — une moto volée peut être retrouvée dans un autre commissariat que le sien).
     * Format attendu par Select2 : `{"results": [{"id", "text"}, ...]}`.
     */
    public function searchStolen(Request $request): JsonResponse
    {
        Gate::authorize('create', MotoRetrouvee::class);

        $term = trim((string) $request->query('q', ''));

        $motos = Moto::query()->acrossCommissariats()
            ->with(['proprietaire' => fn ($query) => $query->acrossCommissariats()])
            ->where('is_stolen', true)
            ->when($term !== '', fn ($query) => $query->where('plate_number', 'like', "%{$term}%"))
            ->orderBy('plate_number')
            ->limit(20)
            ->get()
            ->map(fn (Moto $moto) => ['id' => $moto->id, 'text' => "{$moto->plate_number} — {$moto->proprietaire->fullName()}"]);

        return response()->json(['results' => $motos]);
    }

    public function store(StoreMotoRequest $request): RedirectResponse
    {
        $moto = Moto::create($request->validated() + [
            'commissariat_id' => $request->user()->commissariat_id,
        ]);

        return redirect()->route('motos.index')->with('status', "Moto « {$moto->plate_number} » créée.");
    }

    public function update(UpdateMotoRequest $request, Moto $moto): RedirectResponse
    {
        $moto->update($request->validated());

        return redirect()->route('motos.index')->with('status', "Moto « {$moto->plate_number} » modifiée.");
    }

    public function destroy(Moto $moto): RedirectResponse
    {
        Gate::authorize('delete', $moto);

        $moto->delete();

        return redirect()->route('motos.index')->with('status', "Moto « {$moto->plate_number} » supprimée.");
    }
}

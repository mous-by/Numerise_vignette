<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreMotoRequest;
use App\Http\Requests\Web\UpdateMotoRequest;
use App\Models\Moto;
use App\Models\Proprietaire;
use Illuminate\Http\RedirectResponse;
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
            'proprietaires' => Proprietaire::orderBy('last_name')->orderBy('first_name')->get(),
        ]);
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

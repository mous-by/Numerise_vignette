<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreMotoRetrouveeRequest;
use App\Http\Requests\Web\UpdateMotoRetrouveeRequest;
use App\Models\MotoRetrouvee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Écran Motos retrouvées (W10) : cahier §5 Cas 2, §9 Moto retrouvée. Enregistrée par le commissariat qui l'a
 * retrouvée — pas forcément celui où elle a été déclarée volée (§4.7, lecture nationale). Marque la moto liée
 * comme localisée (`Moto::is_stolen = false`, PROPOSITION TECHNIQUE — voir MotoRetrouvee). Le SMS au propriétaire
 * (cahier) viendra avec W14 ; pas d'action `destroy` (absente du cahier pour ce module).
 */
class MotoRetrouveeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', MotoRetrouvee::class);

        $motosRetrouvees = MotoRetrouvee::orderByDesc('found_at')->get();
        // moto() bypasse déjà le cloisonnement (une trouvaille peut concerner un autre commissariat) ; son
        // propriétaire doit l'être aussi, explicitement, pour ne pas silencieusement disparaître de l'affichage.
        $motosRetrouvees->load(['moto.proprietaire' => fn ($query) => $query->acrossCommissariats()]);

        return view('motos-retrouvees.index', ['motosRetrouvees' => $motosRetrouvees]);
    }

    public function store(StoreMotoRetrouveeRequest $request): RedirectResponse
    {
        $motoRetrouvee = DB::transaction(function () use ($request) {
            $motoRetrouvee = MotoRetrouvee::create($request->validated() + [
                'commissariat_id' => $request->user()->commissariat_id,
            ]);
            // La moto est localisée : plus une alerte active (recalculateStolenStatus ne s'applique pas ici,
            // il ne porte que sur les déclarations de vol/braquage — voir MotoRetrouvee).
            $request->moto()?->forceFill(['is_stolen' => false])->save();

            return $motoRetrouvee;
        });

        return redirect()->route('motos-retrouvees.index')->with('status', "Moto « {$motoRetrouvee->activityLabel()} » enregistrée comme retrouvée.");
    }

    public function update(UpdateMotoRetrouveeRequest $request, MotoRetrouvee $motoRetrouvee): RedirectResponse
    {
        $motoRetrouvee->update($request->validated());

        return redirect()->route('motos-retrouvees.index')->with('status', "Moto « {$motoRetrouvee->activityLabel()} » modifiée.");
    }
}

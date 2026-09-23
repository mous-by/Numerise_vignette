<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreDeclarationRequest;
use App\Http\Requests\Web\UpdateDeclarationRequest;
use App\Models\Declaration;
use App\Models\Moto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Écran Déclarations (W9) : vol, braquage ou autre (cahier §5 Cas 2, §9 Déclaration), saisies par le commissaire.
 * Une fois enregistrée, une déclaration de vol ou de braquage marque la moto liée « Volée »
 * (Moto::recalculateStolenStatus()), recalculé après chaque écriture pour rester exact même après modification
 * ou suppression.
 */
class DeclarationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Declaration::class);

        return view('declarations.index', [
            'declarations' => Declaration::with('moto.proprietaire')->orderByDesc('occurred_at')->get(),
            // Le select Moto (modale) charge par recherche (Select2 AJAX, MotoController::search()) plutôt que
            // tout précharger : ce booléen suffit à savoir s'il faut proposer « Ajouter » ou « Créez d'abord ... ».
            'hasMotos' => Moto::query()->exists(),
        ]);
    }

    public function store(StoreDeclarationRequest $request): RedirectResponse
    {
        $declaration = DB::transaction(function () use ($request) {
            $declaration = Declaration::create($request->validated() + [
                'commissariat_id' => $request->user()->commissariat_id,
            ]);
            $declaration->moto->recalculateStolenStatus();

            return $declaration;
        });

        return redirect()->route('declarations.index')->with('status', "Déclaration « {$declaration->activityLabel()} » créée.");
    }

    public function update(UpdateDeclarationRequest $request, Declaration $declaration): RedirectResponse
    {
        DB::transaction(function () use ($request, $declaration) {
            $previousMotoId = $declaration->moto_id;
            $declaration->update($request->validated());

            Moto::find($previousMotoId)?->recalculateStolenStatus();
            if ($declaration->moto_id !== $previousMotoId) {
                Moto::find($declaration->moto_id)?->recalculateStolenStatus();
            }
        });

        return redirect()->route('declarations.index')->with('status', "Déclaration « {$declaration->activityLabel()} » modifiée.");
    }

    public function destroy(Declaration $declaration): RedirectResponse
    {
        Gate::authorize('delete', $declaration);

        DB::transaction(function () use ($declaration) {
            $moto = $declaration->moto;
            $declaration->delete();
            $moto->recalculateStolenStatus();
        });

        return redirect()->route('declarations.index')->with('status', "Déclaration « {$declaration->activityLabel()} » supprimée.");
    }
}

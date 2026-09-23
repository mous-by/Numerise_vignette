<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreTarifVgtRequest;
use App\Http\Requests\Web\UpdateTarifVgtRequest;
use App\Models\TarifVgt;
use Illuminate\Http\RedirectResponse;

/**
 * Réglage des tarifs VGT par genre ou marque (W11, cahier §9 : « le tarif dépend du genre de moto »), géré
 * depuis l'écran Demandes VGT (bouton « Tarifs »), réservé au superadmin et à l'admin national — voir
 * App\Models\TarifVgt.
 */
class TarifVgtController extends Controller
{
    public function store(StoreTarifVgtRequest $request): RedirectResponse
    {
        $tarif = TarifVgt::create($request->validated());

        return redirect()->route('demandes-vgt.index')->with('status', "Tarif « {$tarif->type_or_brand} » créé.");
    }

    public function update(UpdateTarifVgtRequest $request, TarifVgt $tarifVgt): RedirectResponse
    {
        $tarifVgt->update($request->validated());

        return redirect()->route('demandes-vgt.index')->with('status', "Tarif « {$tarifVgt->type_or_brand} » modifié.");
    }
}

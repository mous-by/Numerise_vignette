<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\VgtCardTemplate;
use App\Http\Controllers\Controller;
use App\Models\Mairie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Réglage du modèle de carte VGT par mairie (W13, App\Enums\VgtCardTemplate). PROPOSITION TECHNIQUE — le cahier
 * ne décrit aucun visuel : voir CLAUDE.md §5. Pas d'écran Mairies dédié (W2, pas encore construit) : ce réglage
 * reste, comme les tarifs (W12), une petite gestion intégrée à l'écran Demandes VGT, gardée par `mairies.update`
 * (permission déjà du manifeste `mairies`, pas une nouvelle permission).
 */
class MairieCardTemplateController extends Controller
{
    public function update(Request $request, Mairie $mairie): RedirectResponse
    {
        $data = $request->validate([
            'card_template' => ['required', 'string', Rule::enum(VgtCardTemplate::class)],
        ], [
            'card_template.required' => 'Le modèle de carte est obligatoire.',
        ]);

        $mairie->update($data);

        return redirect()->route('demandes-vgt.index')->with('status', "Modèle de carte VGT de « {$mairie->name} » mis à jour.");
    }
}

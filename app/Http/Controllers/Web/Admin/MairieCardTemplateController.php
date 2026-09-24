<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\VgtCardTemplate;
use App\Http\Controllers\Controller;
use App\Models\Mairie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Réglage du modèle de carte VGT par mairie (W13, App\Enums\VgtCardTemplate). PROPOSITION TECHNIQUE — le cahier
 * ne décrit aucun visuel : voir CLAUDE.md §5. L'écran Mairies (W2) gère le CRUD des mairies ; ce réglage
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

        return $this->backTo($request)->with('status', "Modèle de carte VGT de « {$mairie->name} » mis à jour.");
    }

    /** Remplace le logo imprimé sur la carte VGT de la mairie (image PNG, JPEG ou WebP, 1 Mo maximum). */
    public function updateLogo(Request $request, Mairie $mairie): RedirectResponse
    {
        return $this->storeImage($request, $mairie, 'logo', 'logo_path', 'Logo', 1024);
    }

    /** Revient à l'image par défaut. */
    public function resetLogo(Request $request, Mairie $mairie): RedirectResponse
    {
        return $this->clearImage($request, $mairie, 'logo_path', 'Logo');
    }

    /** Remplace l'image du monument (photo réelle) imprimée sur les cartes VGT 2025 et 2026 (2 Mo maximum). */
    public function updateMonument(Request $request, Mairie $mairie): RedirectResponse
    {
        return $this->storeImage($request, $mairie, 'monument', 'monument_path', 'Image du monument', 2048);
    }

    public function resetMonument(Request $request, Mairie $mairie): RedirectResponse
    {
        return $this->clearImage($request, $mairie, 'monument_path', 'Image du monument');
    }

    private function storeImage(Request $request, Mairie $mairie, string $field, string $column, string $label, int $maxKb): RedirectResponse
    {
        $request->validate([
            $field => ['required', 'file', 'mimes:png,jpg,jpeg,webp', "max:$maxKb", 'dimensions:min_width=100,min_height=100'],
        ], [
            "$field.required" => 'Choisissez une image.',
            "$field.mimes" => "$label : image PNG, JPEG ou WebP uniquement.",
            "$field.max" => "$label : ".($maxKb / 1024).' Mo maximum.',
            "$field.dimensions" => "$label : au moins 100 × 100 pixels.",
        ]);

        $previous = $mairie->{$column};
        $mairie->update([$column => $request->file($field)->store('mairies/'.$field, 'public')]);

        if ($previous) {
            Storage::disk('public')->delete($previous);
        }

        return $this->backTo($request)->with('status', "$label de « {$mairie->name} » mis à jour.");
    }

    private function clearImage(Request $request, Mairie $mairie, string $column, string $label): RedirectResponse
    {
        $previous = $mairie->{$column};
        $mairie->update([$column => null]);

        if ($previous) {
            Storage::disk('public')->delete($previous);
        }

        return $this->backTo($request)->with('status', "$label de « {$mairie->name} » remis par défaut.");
    }

    /** Ces réglages sont accessibles depuis Demandes VGT et depuis l'écran Mairies : on revient là où l'on était. */
    private function backTo(Request $request): RedirectResponse
    {
        return redirect()->route($request->input('back') === 'mairies' ? 'mairies.index' : 'demandes-vgt.index');
    }
}

<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\DemandeVgtStatus;
use App\Enums\VgtCardTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ConfirmPaiementRequest;
use App\Http\Requests\Web\ConfirmRetraitRequest;
use App\Http\Requests\Web\StoreDemandeVgtRequest;
use App\Http\Requests\Web\UpdateDemandeVgtRequest;
use App\Http\Requests\Web\ValidateDemandeVgtRequest;
use App\Models\DemandeVgt;
use App\Models\Mairie;
use App\Models\Moto;
use App\Models\TarifVgt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Écran Demandes VGT (W11, cahier §9 Demande VGT) : déposée par le commissaire, validée ou rejetée par la
 * mairie de retrait choisie. PROPOSITION TECHNIQUE — À VALIDER pour le workflow complet : voir
 * App\Models\DemandeVgt et App\Enums\DemandeVgtStatus.
 */
class DemandeVgtController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DemandeVgt::class);

        $user = auth()->user();

        // moto() bypasse déjà le cloisonnement (une demande est visible du commissariat qui l'a déposée ET de
        // la mairie choisie, qui n'a pas de commissariat_id du tout) ; son propriétaire doit l'être aussi,
        // explicitement, pour ne pas silencieusement disparaître de l'affichage (voir W10, même besoin).
        return view('demandes-vgt.index', [
            'demandes' => DemandeVgt::visibleTo($user)
                ->with(['moto' => fn ($query) => $query->with(['proprietaire' => fn ($q) => $q->acrossCommissariats()]), 'commissariat', 'mairie'])
                ->orderByDesc('created_at')->get(),
            'mairies' => Mairie::active()->orderBy('name')->get(),
            'hasMotos' => Moto::query()->exists(),
            'tarifs' => Gate::allows('manageTarifs', DemandeVgt::class) ? TarifVgt::orderBy('type_or_brand')->get() : collect(),
        ]);
    }

    /**
     * Carte VGT imprimable (W13) dans le modèle demandé : aperçu et impression au choix de la mairie. Réservée
     * aux demandes payées ou retirées, visibles de l'utilisateur (cloisonnement, comme index()).
     */
    public function card(Request $request, int $demandeId, string $template): View
    {
        Gate::authorize('viewAny', DemandeVgt::class);

        $model = VgtCardTemplate::tryFrom($template) ?? abort(404);
        $demande = DemandeVgt::visibleTo($request->user())
            ->with(['moto' => fn ($query) => $query->with(['proprietaire' => fn ($q) => $q->acrossCommissariats()]), 'commissariat', 'mairie'])
            ->findOrFail($demandeId);
        abort_unless(in_array($demande->status, [DemandeVgtStatus::Payee, DemandeVgtStatus::Retiree], true), 404);

        $face = in_array($request->query('face'), ['recto', 'verso'], true) ? $request->query('face') : 'both';

        return view('demandes-vgt.card', ['demande' => $demande, 'template' => $model, 'face' => $face, 'preview' => $request->boolean('preview')]);
    }

    public function store(StoreDemandeVgtRequest $request): RedirectResponse
    {
        // DemandeVgt n'utilise pas BelongsToCommissariat (visibilité double, voir le modèle) : pas de garde
        // automatique contre une institution manquante (superadmin, D16) — à répliquer ici à la main.
        if ($request->user()->commissariat_id === null) {
            return redirect()->route('demandes-vgt.index')->with('error', 'Seul un commissaire, rattaché à un commissariat, peut déposer une demande VGT.');
        }

        $moto = Moto::findOrFail($request->validated('moto_id'));
        $pricing = $this->pricing($moto, (int) $request->validated('vgt_year'));

        $demande = DemandeVgt::create($request->validated() + $pricing + [
            'commissariat_id' => $request->user()->commissariat_id,
        ]);

        return redirect()->route('demandes-vgt.index')->with('status', "Demande VGT « {$demande->activityLabel()} » créée.");
    }

    public function update(UpdateDemandeVgtRequest $request, DemandeVgt $demandeVgt): RedirectResponse
    {
        $moto = Moto::findOrFail($request->validated('moto_id'));
        $pricing = $this->pricing($moto, (int) $request->validated('vgt_year'));

        $demandeVgt->update($request->validated() + $pricing + [
            'status' => 'en_attente',
            'rejection_reason' => null,
        ]);

        return redirect()->route('demandes-vgt.index')->with('status', "Demande VGT « {$demandeVgt->activityLabel()} » resoumise.");
    }

    public function validateRequest(ValidateDemandeVgtRequest $request, DemandeVgt $demandeVgt): RedirectResponse
    {
        $demandeVgt->update([
            'status' => $request->validated('decision'),
            'rejection_reason' => $request->validated('rejection_reason'),
        ]);

        $message = $request->validated('decision') === 'validee'
            ? "Demande VGT « {$demandeVgt->activityLabel()} » validée."
            : "Demande VGT « {$demandeVgt->activityLabel()} » rejetée.";

        return redirect()->route('demandes-vgt.index')->with('status', $message);
    }

    public function confirmPaiement(ConfirmPaiementRequest $request, DemandeVgt $demandeVgt): RedirectResponse
    {
        $demandeVgt->update([
            'status' => 'payee',
            'payment_confirmed_at' => $request->validated('payment_confirmed_at'),
        ]);

        return redirect()->route('demandes-vgt.index')->with('status', "Paiement de « {$demandeVgt->activityLabel()} » confirmé.");
    }

    public function confirmRetrait(ConfirmRetraitRequest $request, DemandeVgt $demandeVgt): RedirectResponse
    {
        $demandeVgt->update([
            'status' => 'retiree',
            'retrait_date' => $request->validated('retrait_date'),
        ]);

        return redirect()->route('demandes-vgt.index')->with('status', "Retrait de la carte VGT « {$demandeVgt->activityLabel()} » confirmé.");
    }

    /**
     * @return array{base_amount: int, is_late: bool, surcharge_amount: int}
     */
    private function pricing(Moto $moto, int $vgtYear): array
    {
        $isLate = $vgtYear < (int) date('Y');

        return [
            'base_amount' => TarifVgt::forGenre($moto->type_or_brand)->amount,
            'is_late' => $isLate,
            'surcharge_amount' => $isLate ? (int) config('vgt.late_surcharge_amount') : 0,
        ];
    }
}

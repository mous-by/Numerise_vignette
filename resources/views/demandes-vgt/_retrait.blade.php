{{-- Retrait de la carte VGT (W13) : modale de synthèse et de confirmation, puis modale distincte de choix du modèle à imprimer. --}}
@php
    $status = $demande->status;
    $proprietaire = $demande->moto->proprietaire;
    $retraitFailed = $errors->any() && old('retrait_demande_id') == $demande->id;
    $canConfirmRetrait = $status === \App\Enums\DemandeVgtStatus::Payee && \Illuminate\Support\Facades\Gate::allows('confirmRetrait', $demande);
    $rank = ['en_attente' => 0, 'validee' => 1, 'payee' => 2, 'retiree' => 3][$status->value] ?? 0;
    $steps = [
        ['icon' => 'bx-file', 'label' => 'Demande déposée', 'date' => $demande->created_at],
        ['icon' => 'bx-check-shield', 'label' => 'Validée par la mairie', 'date' => null],
        ['icon' => 'bx-money', 'label' => 'Paiement confirmé', 'date' => $demande->payment_confirmed_at],
        ['icon' => 'bx-id-card', 'label' => 'Carte remise', 'date' => $demande->retrait_date],
    ];
@endphp
<div class="modal fade" id="retraitVgt-{{ $demande->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('demandes-vgt.confirm-retrait', $demande) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="retrait_demande_id" value="{{ $demande->id }}">
                <div class="modal-header">
                    <h5 class="modal-title"><i class='bx bx-id-card me-2'></i>Carte VGT {{ $demande->vgt_year }} — {{ $demande->moto->plate_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    <ol class="nv-steps mb-4">
                        @foreach ($steps as $index => $step)
                            <li class="{{ $index <= $rank ? 'done' : '' }} {{ $index === $rank ? 'current' : '' }}">
                                <span class="nv-step-dot"><i class='bx {{ $step['icon'] }}'></i></span>
                                <span class="nv-step-label">{{ $step['label'] }}</span>
                                <span class="nv-step-date">{{ $index <= $rank && $step['date'] ? $step['date']->format('d/m/Y') : '—' }}</span>
                            </li>
                        @endforeach
                    </ol>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <div class="nv-panel h-100">
                                <div class="nv-panel-title">Moto et propriétaire</div>
                                <div class="nv-plate">{{ $demande->moto->plate_number }}</div>
                                <dl class="nv-dl">
                                    <dt>Genre</dt><dd>{{ $demande->moto->type_or_brand }}</dd>
                                    <dt>Couleur</dt><dd>{{ $demande->moto->color ?? '—' }}</dd>
                                    <dt>Propriétaire</dt><dd>{{ $proprietaire->fullName() }}</dd>
                                    <dt>Téléphone</dt><dd>{{ $proprietaire->phone }}</dd>
                                    <dt>Adresse</dt><dd>{{ $proprietaire->address ?? '—' }}</dd>
                                </dl>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="nv-panel h-100">
                                <div class="nv-panel-title">Montant</div>
                                <dl class="nv-dl">
                                    <dt>Vignette {{ $demande->vgt_year }}</dt><dd>{{ number_format($demande->base_amount, 0, ',', ' ') }} FCFA</dd>
                                    @if ($demande->is_late)
                                        <dt>Majoration</dt><dd>{{ number_format($demande->surcharge_amount, 0, ',', ' ') }} FCFA</dd>
                                    @endif
                                    <dt class="fw-bold">Total</dt><dd class="fw-bold fs-6">{{ number_format($demande->totalAmount(), 0, ',', ' ') }} FCFA</dd>
                                    <dt>Code marchand</dt><dd>{{ $demande->merchant_code ?? '—' }}</dd>
                                    <dt>Mairie</dt><dd>{{ $demande->mairie->name }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        @if ($canConfirmRetrait)
                            <label for="retrait_date{{ $demande->id }}" class="form-label fw-semibold">Date de retrait de la carte</label>
                            <input type="date" class="form-control @if ($retraitFailed && $errors->has('retrait_date')) is-invalid @endif" id="retrait_date{{ $demande->id }}" name="retrait_date" value="{{ $retraitFailed ? old('retrait_date') : date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                            @if ($retraitFailed && $errors->has('retrait_date'))<div class="invalid-feedback">{{ $errors->first('retrait_date') }}</div>@endif
                        @elseif ($status === \App\Enums\DemandeVgtStatus::Retiree)
                            <div class="alert alert-success mb-0 py-2"><i class='bx bx-check-circle me-1'></i>Carte remise le <strong>{{ $demande->retrait_date?->format('d/m/Y') }}</strong>.</div>
                        @else
                            <div class="alert alert-secondary mb-0 py-2">En attente de retrait par la mairie.</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="vgt-open-models btn btn-outline-primary me-auto" data-models="printModels-{{ $demande->id }}"><i class='bx bx-printer me-1'></i>Imprimer la carte</button>
                    @if ($canConfirmRetrait)
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Confirmer le retrait</button>
                    @else
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade vgt-models-modal" id="printModels-{{ $demande->id }}" tabindex="-1" aria-hidden="true" data-back="retraitVgt-{{ $demande->id }}" data-default="{{ $demande->mairie->card_template->value }}">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-palette me-2'></i>Choisir le modèle de vignette — {{ $demande->moto->plate_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="nv-panel-title">Modèles disponibles</div>
                        <div class="d-grid gap-2">
                            @foreach (\App\Enums\VgtCardTemplate::cases() as $template)
                                <button type="button" class="vgt-model btn btn-outline-secondary p-2 text-start d-flex gap-3 align-items-center" data-template="{{ $template->value }}" data-src="{{ route('demandes-vgt.card', ['demandeId' => $demande->id, 'template' => $template->value, 'face' => 'recto']) }}">
                                    <span class="flex-shrink-0 overflow-hidden rounded" style="position:relative;width:154px;height:97px;pointer-events:none">
                                        <iframe title="{{ $template->label() }}" tabindex="-1" style="width:323px;height:204px;border:0;transform:scale(.476);transform-origin:0 0"></iframe>
                                    </span>
                                    <span>
                                        <span class="d-block fw-semibold">{{ $template->label() }}</span>
                                        <span class="d-block small opacity-75">{{ $template->description() }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="nv-panel-title">Aperçu du modèle choisi</div>
                        <div class="d-flex flex-column align-items-center gap-3 nv-preview">
                            @foreach (['recto' => 'Recto', 'verso' => 'Verso (nom et numéro du détenteur)'] as $face => $label)
                                <div class="text-center">
                                    <div class="small text-muted mb-1">{{ $label }}</div>
                                    <div class="nv-preview-card"><iframe class="vgt-preview-{{ $face }}" title="{{ $label }}" tabindex="-1"></iframe></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="vgt-models-back btn btn-secondary me-auto"><i class='bx bx-arrow-back me-1'></i>Retour</button>
                <button type="button" class="vgt-print-chosen btn btn-primary" disabled><i class='bx bx-printer me-1'></i>Imprimer ce modèle (recto + verso)</button>
            </div>
        </div>
    </div>
</div>

@if ($retraitFailed)
    <script>
        window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('retraitVgt-{{ $demande->id }}')).show());
    </script>
@endif

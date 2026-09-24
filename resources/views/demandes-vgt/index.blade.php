@extends('layouts.admin')

@section('title', 'Demandes VGT')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Demandes VGT</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Demandes VGT</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-file me-2'></i>DEMANDES VGT</h6>
            <div class="d-flex align-items-center gap-2">
                @can('manageTarifs', \App\Models\DemandeVgt::class)
                    <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#tarifsVgtModal">
                        <i class='bx bx-money'></i> Tarifs
                    </button>
                @endcan
                @if (auth()->user()->can('mairies.update'))
                    <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#cardTemplatesModal">
                        <i class='bx bx-id-card'></i> Modèles de carte
                    </button>
                @endif
                @can('create', \App\Models\DemandeVgt::class)
                    @if ($hasMotos)
                        <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createDemandeVgtModal">
                            <i class='bx bx-plus'></i> Ajouter
                        </button>
                    @else
                        <span class="badge bg-light text-dark">Créez d'abord une moto</span>
                    @endif
                @endcan
            </div>
        </div>
        <div class="card-body">
            @php($counts = $demandes->groupBy(fn ($d) => $d->status->value)->map->count())
            <div class="nv-chips mb-3" id="demandes-status-filter">
                <button type="button" class="nv-chip active" data-status="">Toutes <b>{{ $demandes->count() }}</b></button>
                @foreach (\App\Enums\DemandeVgtStatus::cases() as $case)
                    <button type="button" class="nv-chip" data-key="{{ $case->value }}" data-status="{{ $case->label() }}">{{ $case->label() }} <b>{{ $counts[$case->value] ?? 0 }}</b></button>
                @endforeach
            </div>
            <div class="table-responsive">
                <table class="table" id="demandes-vgt-table">
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>MOTO</th>
                            <th>PROPRIÉTAIRE</th>
                            <th>ANNÉE</th>
                            <th>MAIRIE</th>
                            <th>MONTANT</th>
                            <th>STATUT</th>
                            <th width="10%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($demandes as $demande)
                            <tr>
                                <td>{{ $demande->created_at->format('d/m/Y') }}</td>
                                <td><div class="fw-semibold">{{ $demande->moto->plate_number }}</div><div class="small text-muted">{{ $demande->moto->type_or_brand }}</div></td>
                                <td><div>{{ $demande->moto->proprietaire->fullName() }}</div><div class="small text-muted">{{ $demande->moto->proprietaire->phone }}</div></td>
                                <td>{{ $demande->vgt_year }}</td>
                                <td>{{ $demande->mairie->name }}</td>
                                <td>
                                    <div class="fw-semibold">{{ number_format($demande->totalAmount(), 0, ',', ' ') }} FCFA</div>
                                    @if ($demande->is_late)
                                        <span class="badge bg-warning-subtle" title="Majoration de {{ number_format($demande->surcharge_amount, 0, ',', ' ') }} FCFA incluse">Arriéré</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $demande->status->badgeClass() }}">{{ $demande->status->label() }}</span>
                                    @if ($demande->status === \App\Enums\DemandeVgtStatus::Rejetee && $demande->rejection_reason)
                                        <div class="small text-muted cell-wrap text-break">{{ $demande->rejection_reason }}</div>
                                    @endif
                                    @if ($demande->status === \App\Enums\DemandeVgtStatus::Payee && $demande->payment_confirmed_at)
                                        <div class="small text-muted">le {{ $demande->payment_confirmed_at->format('d/m/Y') }}</div>
                                    @endif
                                    @if ($demande->status === \App\Enums\DemandeVgtStatus::Retiree && $demande->retrait_date)
                                        <div class="small text-muted">le {{ $demande->retrait_date->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    @can('update', $demande)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editDemandeVgt-{{ $demande->id }}" title="Corriger et resoumettre">
                                            <i class='bx bx-edit'></i>
                                        </button>
                                    @endcan
                                    @can('validateRequest', $demande)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#decideDemandeVgt-{{ $demande->id }}" title="Valider ou rejeter">
                                            <i class='bx bx-check-shield'></i>
                                        </button>
                                    @endcan
                                    @can('confirmPayment', $demande)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#confirmPaiement-{{ $demande->id }}" title="Confirmer le paiement">
                                            <i class='bx bx-money'></i>
                                        </button>
                                    @endcan
                                    @if (in_array($demande->status, [\App\Enums\DemandeVgtStatus::Payee, \App\Enums\DemandeVgtStatus::Retiree], true) && \Illuminate\Support\Facades\Gate::allows('view', $demande))
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#retraitVgt-{{ $demande->id }}" title="Retrait de la carte VGT">
                                            <i class='bx bx-id-card'></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('create', \App\Models\DemandeVgt::class)
        @php($createFailed = $errors->any() && ! old('demande_vgt_id') && ! old('decision'))
        <div class="modal fade" id="createDemandeVgtModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('demandes-vgt.store') }}">
                        @csrf
                        @include('demandes-vgt._form', ['demande' => null, 'failed' => $createFailed, 'idSuffix' => '', 'mairies' => $mairies])
                    </form>
                </div>
            </div>
        </div>

        @if ($createFailed)
            <script>
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('createDemandeVgtModal')).show());
            </script>
        @endif
    @endcan

    @foreach ($demandes as $demande)
        @can('update', $demande)
            @php($editFailed = $errors->any() && old('demande_vgt_id') == $demande->id)
            <div class="modal fade" id="editDemandeVgt-{{ $demande->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('demandes-vgt.update', $demande) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="demande_vgt_id" value="{{ $demande->id }}">
                            @include('demandes-vgt._form', ['demande' => $demande, 'failed' => $editFailed, 'idSuffix' => '-'.$demande->id, 'mairies' => $mairies])
                        </form>
                    </div>
                </div>
            </div>

            @if ($editFailed)
                <script>
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('editDemandeVgt-{{ $demande->id }}')).show());
                </script>
            @endif
        @endcan

        @can('validateRequest', $demande)
            @php($decideFailed = $errors->any() && old('decision_demande_id') == $demande->id)
            <div class="modal fade" id="decideDemandeVgt-{{ $demande->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('demandes-vgt.validate', $demande) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="decision_demande_id" value="{{ $demande->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class='bx bx-check-shield me-2'></i>{{ $demande->moto->plate_number }} — {{ $demande->vgt_year }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="mb-3">Propriétaire : <strong>{{ $demande->moto->proprietaire->fullName() }}</strong><br>
                                   Montant : <strong>{{ number_format($demande->totalAmount(), 0, ',', ' ') }} FCFA</strong></p>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Décision</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="decision" id="decision_validee{{ $demande->id }}" value="validee" checked>
                                        <label class="form-check-label" for="decision_validee{{ $demande->id }}">Valider</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="decision" id="decision_rejetee{{ $demande->id }}" value="rejetee">
                                        <label class="form-check-label" for="decision_rejetee{{ $demande->id }}">Rejeter</label>
                                    </div>
                                </div>
                                <div id="rejection_reason_wrap{{ $demande->id }}" class="d-none">
                                    <label for="rejection_reason{{ $demande->id }}" class="form-label">Motif du rejet</label>
                                    <textarea class="form-control @if ($decideFailed && $errors->has('rejection_reason')) is-invalid @endif" id="rejection_reason{{ $demande->id }}" name="rejection_reason" rows="3">{{ $decideFailed ? old('rejection_reason') : '' }}</textarea>
                                    @if ($decideFailed && $errors->has('rejection_reason'))<div class="invalid-feedback">{{ $errors->first('rejection_reason') }}</div>@endif
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @if ($decideFailed)
                <script>
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('decideDemandeVgt-{{ $demande->id }}')).show());
                </script>
            @endif
        @endcan

        @can('confirmPayment', $demande)
            @php($paiementFailed = $errors->any() && old('paiement_demande_id') == $demande->id)
            <div class="modal fade" id="confirmPaiement-{{ $demande->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('demandes-vgt.confirm-payment', $demande) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="paiement_demande_id" value="{{ $demande->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class='bx bx-money me-2'></i>{{ $demande->moto->plate_number }} — {{ $demande->vgt_year }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="mb-3">Propriétaire : <strong>{{ $demande->moto->proprietaire->fullName() }}</strong><br>
                                   Montant : <strong>{{ number_format($demande->totalAmount(), 0, ',', ' ') }} FCFA</strong><br>
                                   Code marchand : <strong>{{ $demande->merchant_code ?? '—' }}</strong></p>
                                <label for="payment_confirmed_at{{ $demande->id }}" class="form-label fw-semibold">Date de paiement</label>
                                <input type="date" class="form-control @if ($paiementFailed && $errors->has('payment_confirmed_at')) is-invalid @endif" id="payment_confirmed_at{{ $demande->id }}" name="payment_confirmed_at" value="{{ $paiementFailed ? old('payment_confirmed_at') : date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                                @if ($paiementFailed && $errors->has('payment_confirmed_at'))<div class="invalid-feedback">{{ $errors->first('payment_confirmed_at') }}</div>@endif
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Confirmer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @if ($paiementFailed)
                <script>
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('confirmPaiement-{{ $demande->id }}')).show());
                </script>
            @endif
        @endcan

        @if (in_array($demande->status, [\App\Enums\DemandeVgtStatus::Payee, \App\Enums\DemandeVgtStatus::Retiree], true) && \Illuminate\Support\Facades\Gate::allows('view', $demande))
            @include('demandes-vgt._retrait')
        @endif
    @endforeach

    @can('manageTarifs', \App\Models\DemandeVgt::class)
        @include('demandes-vgt._tarifs')
    @endcan

    @if (auth()->user()->can('mairies.update'))
        @include('demandes-vgt._card_templates_settings')
    @endif
@endsection

@push('scripts')
    <script>
        const demandesTable = $('#demandes-vgt-table').DataTable({ scrollX: false, order: [[0, 'desc']] });

        // Filtre par statut (pastilles au-dessus du tableau).
        const wantedStatus = new URLSearchParams(window.location.search).get('statut');
        document.querySelectorAll('#demandes-status-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#demandes-status-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                demandesTable.column(6).search(chip.dataset.status || '', false, true).draw();
            });
        });
        document.querySelector('#demandes-status-filter .nv-chip[data-key="' + wantedStatus + '"]')?.click();

        document.querySelectorAll('[id^="decideDemandeVgt-"]').forEach((modal) => {
            const id = modal.id.replace('decideDemandeVgt-', '');
            const wrap = document.getElementById('rejection_reason_wrap' + id);
            modal.querySelectorAll('input[name="decision"]').forEach((radio) => {
                radio.addEventListener('change', () => wrap.classList.toggle('d-none', radio.value !== 'rejetee' || !radio.checked));
            });
        });

        // Impression : « Imprimer la carte » ferme la synthèse et ouvre une modale dédiée au choix du modèle
        // (liste + grand aperçu recto/verso, le modèle de la mairie est présélectionné). « Imprimer ce modèle »
        // charge la carte choisie (recto + verso, CR80) dans un iframe caché puis lance l'impression.
        // Bootstrap 5.0 (thème) : pas de getOrCreateInstance.
        const modalOf = (element) => bootstrap.Modal.getInstance(element) || new bootstrap.Modal(element);

        document.querySelectorAll('.vgt-models-modal').forEach((modelsEl) => {
            const retraitEl = document.getElementById(modelsEl.dataset.back);
            const models = modelsEl.querySelectorAll('.vgt-model');
            const printButton = modelsEl.querySelector('.vgt-print-chosen');
            const recto = modelsEl.querySelector('.vgt-preview-recto');
            const verso = modelsEl.querySelector('.vgt-preview-verso');
            let chosen = null;

            const faceUrl = (model, face) => model.dataset.src.replace('face=recto', 'face=' + face);

            const choose = (model) => {
                chosen = model;
                models.forEach((item) => {
                    const active = item === model;
                    item.classList.toggle('btn-primary', active);
                    item.classList.toggle('btn-outline-secondary', !active);
                });
                recto.setAttribute('src', faceUrl(model, 'recto'));
                verso.setAttribute('src', faceUrl(model, 'verso'));
                printButton.disabled = false;
            };

            retraitEl.querySelector('.vgt-open-models').addEventListener('click', () => {
                retraitEl.addEventListener('hidden.bs.modal', () => modalOf(modelsEl).show(), { once: true });
                modalOf(retraitEl).hide();
            });

            modelsEl.querySelector('.vgt-models-back').addEventListener('click', () => {
                modelsEl.addEventListener('hidden.bs.modal', () => modalOf(retraitEl).show(), { once: true });
                modalOf(modelsEl).hide();
            });

            // Adapte la taille de l'aperçu à la largeur disponible (carte de 323 px à l'échelle 1).
            const fitPreview = () => {
                modelsEl.querySelectorAll('.nv-preview-card').forEach((card) => {
                    const room = card.parentElement.parentElement.clientWidth || 484;
                    const scale = Math.min(1.5, room / 323);
                    card.style.width = (323 * scale) + 'px';
                    card.style.height = (204 * scale) + 'px';
                    card.querySelector('iframe').style.transform = 'scale(' + scale + ')';
                });
            };

            modelsEl.addEventListener('shown.bs.modal', () => {
                fitPreview();
                models.forEach((model) => {
                    const frame = model.querySelector('iframe');
                    if (!frame.getAttribute('src')) { frame.setAttribute('src', model.dataset.src); }
                });
                if (!chosen) {
                    choose([...models].find((model) => model.dataset.template === modelsEl.dataset.default) || models[0]);
                }
            });

            models.forEach((model) => model.addEventListener('click', () => choose(model)));

            printButton.addEventListener('click', () => {
                if (!chosen) { return; }
                const iframe = document.createElement('iframe');
                iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0';
                iframe.addEventListener('load', () => {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                    setTimeout(() => iframe.remove(), 60000);
                });
                iframe.src = faceUrl(chosen, 'both');
                document.body.appendChild(iframe);
            });
        });
    </script>
@endpush

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
            <div class="table-responsive">
                <table class="table" id="demandes-vgt-table">
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>MATRICULE</th>
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
                                <td class="fw-semibold">{{ $demande->moto->plate_number }}</td>
                                <td>{{ $demande->moto->proprietaire->fullName() }}</td>
                                <td>{{ $demande->vgt_year }}</td>
                                <td>{{ $demande->mairie->name }}</td>
                                <td>
                                    {{ number_format($demande->totalAmount(), 0, ',', ' ') }} FCFA
                                    @if ($demande->is_late)
                                        <span class="badge bg-warning-subtle text-warning" title="Majoration pour arriéré incluse">Arriéré</span>
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
            @php($retraitFailed = $errors->any() && old('retrait_demande_id') == $demande->id)
            @php($proprietaire = $demande->moto->proprietaire)
            @php($canConfirmRetrait = $demande->status === \App\Enums\DemandeVgtStatus::Payee && \Illuminate\Support\Facades\Gate::allows('confirmRetrait', $demande))
            <div class="modal fade" id="retraitVgt-{{ $demande->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('demandes-vgt.confirm-retrait', $demande) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="retrait_demande_id" value="{{ $demande->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class='bx bx-id-card me-2'></i>Carte VGT — {{ $demande->moto->plate_number }} ({{ $demande->vgt_year }})</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <table class="table table-sm mb-3">
                                    <tbody>
                                        <tr><th class="text-muted">Propriétaire</th><td>{{ $proprietaire->fullName() }}</td></tr>
                                        <tr><th class="text-muted">Numéro</th><td>{{ $proprietaire->phone }}</td></tr>
                                        <tr><th class="text-muted">Adresse</th><td>{{ $proprietaire->address ?? '—' }}</td></tr>
                                        <tr><th class="text-muted">Contact urgence</th><td>{{ $proprietaire->emergency_contact ?? '—' }}</td></tr>
                                        <tr><th class="text-muted">Mairie de retrait</th><td>{{ $demande->mairie->name }}</td></tr>
                                    </tbody>
                                </table>

                                @if ($canConfirmRetrait)
                                    <label for="retrait_date{{ $demande->id }}" class="form-label fw-semibold">Date de retrait</label>
                                    <input type="date" class="form-control @if ($retraitFailed && $errors->has('retrait_date')) is-invalid @endif" id="retrait_date{{ $demande->id }}" name="retrait_date" value="{{ $retraitFailed ? old('retrait_date') : date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                                    @if ($retraitFailed && $errors->has('retrait_date'))<div class="invalid-feedback">{{ $errors->first('retrait_date') }}</div>@endif
                                @elseif ($demande->status === \App\Enums\DemandeVgtStatus::Retiree)
                                    <p class="mb-0">Date de retrait : <strong>{{ $demande->retrait_date?->format('d/m/Y') }}</strong></p>
                                @else
                                    <p class="mb-0 text-muted">En attente de retrait par la mairie.</p>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-primary" onclick="printVgtCard({{ $demande->id }})"><i class='bx bx-printer me-1'></i>Imprimer</button>
                                @if ($canConfirmRetrait)
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary">Confirmer</button>
                                @else
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <template id="printCard-{{ $demande->id }}">
                @include($demande->mairie->card_template->view(), ['demande' => $demande, 'proprietaire' => $proprietaire])
            </template>

            @if ($retraitFailed)
                <script>
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('retraitVgt-{{ $demande->id }}')).show());
                </script>
            @endif
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
        $('#demandes-vgt-table').DataTable({ scrollX: false, order: [[0, 'desc']] });

        document.querySelectorAll('[id^="decideDemandeVgt-"]').forEach((modal) => {
            const id = modal.id.replace('decideDemandeVgt-', '');
            const wrap = document.getElementById('rejection_reason_wrap' + id);
            modal.querySelectorAll('input[name="decision"]').forEach((radio) => {
                radio.addEventListener('change', () => wrap.classList.toggle('d-none', radio.value !== 'rejetee' || !radio.checked));
            });
        });

        function printVgtCard(id) {
            const tpl = document.getElementById('printCard-' + id);
            if (!tpl) { return; }

            // Iframe caché plutôt que window.open() : imprime sans dépendre d'un bloqueur de popup.
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            document.body.appendChild(iframe);

            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write('<!doctype html><html><head><title>Carte VGT</title></head><body>' + tpl.innerHTML + '</body></html>');
            doc.close();

            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            setTimeout(() => document.body.removeChild(iframe), 1000);
        }
    </script>
@endpush

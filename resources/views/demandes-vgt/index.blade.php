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
    @endforeach

    @can('manageTarifs', \App\Models\DemandeVgt::class)
        @include('demandes-vgt._tarifs')
    @endcan
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
    </script>
@endpush

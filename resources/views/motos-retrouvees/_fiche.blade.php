{{-- Fiche d'une moto retrouvée (consultation en modale, §7) : circonstances de l'arrêt, moto et propriétaire. --}}
<div class="modal fade" id="ficheMotoRetrouvee-{{ $motoRetrouvee->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-search-alt me-2'></i>{{ $motoRetrouvee->moto->plate_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @if ($motoRetrouvee->recovered)
                        <span class="badge bg-success-subtle">Récupérée le {{ $motoRetrouvee->recovered_at->format('d/m/Y') }}</span>
                    @else
                        <span class="badge bg-warning-subtle">En attente de récupération</span>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Arrêt</div>
                            <dl class="nv-dl">
                                <dt>Date</dt><dd>{{ $motoRetrouvee->found_at->format('d/m/Y') }}</dd>
                                <dt>Lieu</dt><dd>{{ $motoRetrouvee->location }}</dd>
                                <dt>Récupération</dt><dd>{{ $motoRetrouvee->recovered ? $motoRetrouvee->recovered_at->format('d/m/Y') : 'Pas encore' }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Moto</div>
                            <dl class="nv-dl">
                                <dt>Matricule</dt><dd>{{ $motoRetrouvee->moto->plate_number }}</dd>
                                <dt>Genre / marque</dt><dd>{{ $motoRetrouvee->moto->type_or_brand }}</dd>
                                <dt>Couleur</dt><dd>{{ $motoRetrouvee->moto->color }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="nv-panel">
                            <div class="nv-panel-title">Propriétaire</div>
                            <dl class="nv-dl">
                                <dt>Nom</dt><dd>{{ $motoRetrouvee->moto->proprietaire->fullName() }}</dd>
                                <dt>Téléphone</dt><dd>{{ $motoRetrouvee->moto->proprietaire->phone }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

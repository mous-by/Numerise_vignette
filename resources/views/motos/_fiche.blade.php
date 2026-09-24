{{-- Fiche d'une moto (consultation en modale, §7) : moto, propriétaire, vente, déclarations et demandes de vignette. --}}
<div class="modal fade" id="ficheMoto-{{ $moto->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-cycling me-2'></i>{{ $moto->plate_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @if ($moto->is_stolen)
                        <span class="badge bg-danger-subtle">Déclarée volée</span>
                    @endif
                    @if ($moto->isVgtCurrent())
                        <span class="badge bg-success-subtle">Vignette {{ $moto->vgt_year }} à jour</span>
                    @else
                        <span class="badge bg-warning-subtle">Vignette {{ $moto->vgt_year }} à renouveler</span>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Moto</div>
                            <dl class="nv-dl">
                                <dt>Matricule</dt><dd>{{ $moto->plate_number }}</dd>
                                <dt>Genre / marque</dt><dd>{{ $moto->type_or_brand }}</dd>
                                <dt>Couleur</dt><dd>{{ $moto->color }}</dd>
                                <dt>Année de la vignette</dt><dd>{{ $moto->vgt_year }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Propriétaire</div>
                            <dl class="nv-dl">
                                <dt>Nom</dt><dd>{{ $moto->proprietaire->fullName() }}</dd>
                                <dt>Téléphone</dt><dd>{{ $moto->proprietaire->phone }}</dd>
                                <dt>Adresse</dt><dd>{{ $moto->proprietaire->address }}</dd>
                                <dt>Urgence</dt><dd>{{ $moto->proprietaire->emergency_contact ?? '—' }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Attestation de vente</div>
                            @if ($moto->has_sale_certificate)
                                <dl class="nv-dl">
                                    <dt>Vendeur</dt><dd>{{ $moto->sellerFullName() ?? '—' }}</dd>
                                    <dt>Téléphone</dt><dd>{{ $moto->seller_phone ?? '—' }}</dd>
                                    <dt>Adresse</dt><dd>{{ $moto->seller_address ?? '—' }}</dd>
                                </dl>
                            @else
                                <div class="text-muted small">Aucune attestation de vente.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Témoin</div>
                            @if ($moto->has_witness)
                                <dl class="nv-dl">
                                    <dt>Nom</dt><dd>{{ $moto->witnessFullName() ?? '—' }}</dd>
                                    <dt>Téléphone</dt><dd>{{ $moto->witness_phone ?? '—' }}</dd>
                                    <dt>Adresse</dt><dd>{{ $moto->witness_address ?? '—' }}</dd>
                                </dl>
                            @else
                                <div class="text-muted small">Pas de témoin.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="nv-panel-title mt-4">Déclarations ({{ $moto->declarations->count() }})</div>
                @forelse ($moto->declarations as $declaration)
                    <div class="nv-panel mb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">{{ $declaration->type->label() }} — {{ $declaration->location }}</div>
                            <div class="small text-muted">{{ $declaration->occurred_at->format('d/m/Y') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted small">Aucune déclaration.</div>
                @endforelse

                <div class="nv-panel-title mt-4">Demandes de vignette ({{ $moto->demandesVgt->count() }})</div>
                @forelse ($moto->demandesVgt as $demande)
                    <div class="nv-panel mb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">Vignette {{ $demande->vgt_year }} — {{ number_format($demande->totalAmount(), 0, ',', ' ') }} FCFA</div>
                            <div class="small text-muted">déposée le {{ $demande->created_at->format('d/m/Y') }}</div>
                        </div>
                        <span class="badge {{ $demande->status->badgeClass() }}">{{ $demande->status->label() }}</span>
                    </div>
                @empty
                    <div class="text-muted small">Aucune demande de vignette.</div>
                @endforelse
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

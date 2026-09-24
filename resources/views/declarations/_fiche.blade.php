{{-- Fiche d'une déclaration (consultation en modale, §7) : acte, moto et propriétaire. --}}
<div class="modal fade" id="ficheDeclaration-{{ $declaration->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-file me-2'></i>{{ $declaration->type->label() }} — {{ $declaration->moto->plate_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-primary-subtle">{{ $declaration->type->label() }}</span>
                    @if ($declaration->moto->is_stolen)
                        <span class="badge bg-danger-subtle">Moto déclarée volée</span>
                    @else
                        <span class="badge bg-success-subtle">Moto en règle</span>
                    @endif
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Acte</div>
                            <dl class="nv-dl">
                                <dt>Type</dt><dd>{{ $declaration->type->label() }}</dd>
                                <dt>Date</dt><dd>{{ $declaration->occurred_at->format('d/m/Y') }}</dd>
                                <dt>Lieu</dt><dd>{{ $declaration->location }}</dd>
                                <dt>Enregistrée le</dt><dd>{{ $declaration->created_at->format('d/m/Y') }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Moto et propriétaire</div>
                            <dl class="nv-dl">
                                <dt>Matricule</dt><dd>{{ $declaration->moto->plate_number }}</dd>
                                <dt>Genre / marque</dt><dd>{{ $declaration->moto->type_or_brand }}</dd>
                                <dt>Propriétaire</dt><dd>{{ $declaration->moto->proprietaire->fullName() }}</dd>
                                <dt>Téléphone</dt><dd>{{ $declaration->moto->proprietaire->phone }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="nv-panel-title mt-4">Circonstances</div>
                <div class="nv-panel">
                    @if ($declaration->description)
                        <div class="text-break">{{ $declaration->description }}</div>
                    @else
                        <div class="text-muted small">Aucune circonstance renseignée.</div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

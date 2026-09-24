{{-- Fiche d'un propriétaire (consultation en modale, §7) : identité, contacts et motos avec leur situation. --}}
<div class="modal fade" id="ficheProprietaire-{{ $proprietaire->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-user-pin me-2'></i>{{ $proprietaire->fullName() }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Identité</div>
                            <dl class="nv-dl">
                                <dt>Nom</dt><dd>{{ $proprietaire->last_name }}</dd>
                                <dt>Prénom</dt><dd>{{ $proprietaire->first_name }}</dd>
                                <dt>Genre</dt><dd>{{ $proprietaire->gender->label() }}</dd>
                                <dt>Adresse</dt><dd>{{ $proprietaire->address }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="nv-panel h-100">
                            <div class="nv-panel-title">Contacts</div>
                            <dl class="nv-dl">
                                <dt>Téléphone</dt><dd>{{ $proprietaire->phone }}</dd>
                                <dt>Urgence</dt><dd>{{ $proprietaire->emergency_contact ?? '—' }}</dd>
                                <dt>Enregistré le</dt><dd>{{ $proprietaire->created_at->format('d/m/Y') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="nv-panel-title mt-4">Motos ({{ $proprietaire->motos->count() }})</div>
                @forelse ($proprietaire->motos as $moto)
                    <div class="nv-panel mb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="fw-bold">{{ $moto->plate_number }}</div>
                            <div class="small text-muted">{{ $moto->type_or_brand }} · {{ $moto->color ?? 'couleur non renseignée' }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if ($moto->is_stolen)
                                <span class="badge bg-danger-subtle">Déclarée volée</span>
                            @endif
                            @if ($moto->isVgtCurrent())
                                <span class="badge bg-success-subtle">Vignette {{ $moto->vgt_year }} à jour</span>
                            @else
                                <span class="badge bg-warning-subtle">Vignette {{ $moto->vgt_year }} à renouveler</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="alert alert-secondary mb-0 py-2">Aucune moto enregistrée pour ce propriétaire.</div>
                @endforelse
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

{{-- Réglage des tarifs VGT par genre ou marque (superadmin, admin national) — voir App\Models\TarifVgt. --}}
<div class="modal fade" id="tarifsVgtModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-money me-2'></i>Tarifs VGT par genre ou marque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>GENRE OU MARQUE</th>
                            <th width="35%">MONTANT (FCFA)</th>
                            <th width="10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tarifs as $tarif)
                            <tr>
                                <form method="POST" action="{{ route('tarifs-vgt.update', $tarif) }}">
                                    @csrf
                                    @method('PUT')
                                    <td class="align-middle">{{ $tarif->type_or_brand }}</td>
                                    <td><input type="number" class="form-control form-control-sm" name="amount" value="{{ $tarif->amount }}" min="0" required></td>
                                    <td><button type="submit" class="btn btn-primary btn-sm"><i class='bx bx-save'></i></button></td>
                                </form>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">Aucun tarif enregistré pour l'instant — un tarif par défaut ({{ number_format(\App\Models\TarifVgt::DEFAULT_AMOUNT, 0, ',', ' ') }} FCFA) s'applique à tout nouveau genre.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <hr>
                <form method="POST" action="{{ route('tarifs-vgt.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-6">
                        <label class="form-label">Nouveau genre ou marque</label>
                        <input type="text" class="form-control @error('type_or_brand') is-invalid @enderror" name="type_or_brand" value="{{ old('type_or_brand') }}">
                        @error('type_or_brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-4">
                        <label class="form-label">Montant (FCFA)</label>
                        <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" min="0" value="{{ old('amount') }}">
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-primary w-100">Ajouter</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

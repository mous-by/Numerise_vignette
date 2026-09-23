@php
    $v = fn (string $field, $default = '') => $failed ? old($field) : ($declaration?->{$field} ?? $default);
    // Select2 en recherche (AJAX, MotoController::search() n'affiche pas tout le commissariat) : seule l'option
    // déjà choisie est pré-rendue, le reste se charge en tapant.
    $selectedMoto = $declaration?->moto
        ?? ($failed && old('moto_id') ? \App\Models\Moto::with('proprietaire')->find(old('moto_id')) : null);
@endphp

<div class="modal-header">
    <h5 class="modal-title"><i class='bx bx-error-alt me-2'></i>{{ $declaration ? 'Modifier une déclaration' : 'Nouvelle déclaration' }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>
<div class="modal-body p-4">
    <div class="row g-3">
        <div class="col-6">
            <label for="moto_id{{ $idSuffix }}" class="form-label fw-semibold">Moto</label>
            <select class="form-select ajax-select" id="moto_id{{ $idSuffix }}" name="moto_id" data-search-url="{{ route('motos.search') }}" data-placeholder="Rechercher une moto…" required>
                @if ($selectedMoto)
                    <option value="{{ $selectedMoto->id }}" selected>{{ $selectedMoto->plate_number }} — {{ $selectedMoto->proprietaire->fullName() }}</option>
                @endif
            </select>
            @if ($failed && $errors->has('moto_id'))<div class="text-danger small mt-1">{{ $errors->first('moto_id') }}</div>@endif
        </div>
        <div class="col-6">
            <label for="type{{ $idSuffix }}" class="form-label fw-semibold">Type d'acte</label>
            <select class="form-select single-select" id="type{{ $idSuffix }}" name="type" required>
                <option value="">— Choisir —</option>
                @foreach (\App\Enums\DeclarationType::cases() as $type)
                    <option value="{{ $type->value }}" @selected($v('type') === $type->value || (! $failed && $declaration?->type === $type))>{{ $type->label() }}</option>
                @endforeach
            </select>
            @if ($failed && $errors->has('type'))<div class="text-danger small mt-1">{{ $errors->first('type') }}</div>@endif
        </div>
    </div>
    <div class="row g-3 mt-0">
        <div class="col-6">
            <label for="location{{ $idSuffix }}" class="form-label fw-semibold">Lieu</label>
            <input type="text" class="form-control @if ($failed && $errors->has('location')) is-invalid @endif" id="location{{ $idSuffix }}" name="location" value="{{ $v('location') }}" required>
            @if ($failed && $errors->has('location'))<div class="invalid-feedback">{{ $errors->first('location') }}</div>@endif
        </div>
        <div class="col-6">
            <label for="occurred_at{{ $idSuffix }}" class="form-label fw-semibold">Date de l'acte</label>
            <input type="date" class="form-control @if ($failed && $errors->has('occurred_at')) is-invalid @endif" id="occurred_at{{ $idSuffix }}" name="occurred_at" value="{{ $failed ? old('occurred_at') : $declaration?->occurred_at?->format('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
            @if ($failed && $errors->has('occurred_at'))<div class="invalid-feedback">{{ $errors->first('occurred_at') }}</div>@endif
        </div>
    </div>
    <div class="mb-3 mt-3">
        <label for="description{{ $idSuffix }}" class="form-label fw-semibold">Description des circonstances</label>
        <textarea class="form-control @if ($failed && $errors->has('description')) is-invalid @endif" id="description{{ $idSuffix }}" name="description" rows="3" required>{{ $v('description') }}</textarea>
        @if ($failed && $errors->has('description'))<div class="invalid-feedback">{{ $errors->first('description') }}</div>@endif
    </div>
    @if (! $declaration)
        <small class="text-muted">Une déclaration de vol ou de braquage marque automatiquement la moto « Volée ».</small>
    @endif
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
</div>

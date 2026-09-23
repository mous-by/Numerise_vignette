@php
    $v = fn (string $field, $default = '') => $failed ? old($field) : ($motoRetrouvee?->{$field} ?? $default);
    // Select2 en recherche nationale (AJAX, MotoController::searchStolen()) : seule l'option déjà choisie (après
    // une erreur de validation) est pré-rendue, le reste se charge en tapant.
    $selectedMoto = $failed && old('moto_id')
        ? \App\Models\Moto::query()->acrossCommissariats()->with(['proprietaire' => fn ($q) => $q->acrossCommissariats()])->find(old('moto_id'))
        : null;
@endphp

<div class="modal-header">
    <h5 class="modal-title"><i class='bx bx-search-alt me-2'></i>{{ $motoRetrouvee ? 'Modifier — '.$motoRetrouvee->moto->plate_number : 'Moto retrouvée' }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>
<div class="modal-body p-4">
    @if ($motoRetrouvee)
        <div class="mb-3">
            <label class="form-label fw-semibold">Moto</label>
            <input type="text" class="form-control" value="{{ $motoRetrouvee->moto->plate_number }} — {{ $motoRetrouvee->moto->proprietaire->fullName() }}" disabled>
        </div>
    @else
        <div class="mb-3">
            <label for="moto_id{{ $idSuffix }}" class="form-label fw-semibold">Moto (recherche nationale)</label>
            <select class="form-select ajax-select" id="moto_id{{ $idSuffix }}" name="moto_id" data-search-url="{{ route('motos.search-stolen') }}" data-placeholder="Matricule d'une moto volée…" required>
                @if ($selectedMoto)
                    <option value="{{ $selectedMoto->id }}" selected>{{ $selectedMoto->plate_number }} — {{ $selectedMoto->proprietaire->fullName() }}</option>
                @endif
            </select>
            @if ($failed && $errors->has('moto_id'))<div class="text-danger small mt-1">{{ $errors->first('moto_id') }}</div>@endif
            <small class="text-muted">Seules les motos actuellement signalées volées apparaissent, quel que soit leur commissariat d'origine.</small>
        </div>
    @endif
    <div class="row g-3">
        <div class="col-6">
            <label for="location{{ $idSuffix }}" class="form-label fw-semibold">Lieu d'arrêt</label>
            <input type="text" class="form-control @if ($failed && $errors->has('location')) is-invalid @endif" id="location{{ $idSuffix }}" name="location" value="{{ $v('location') }}" required>
            @if ($failed && $errors->has('location'))<div class="invalid-feedback">{{ $errors->first('location') }}</div>@endif
        </div>
        <div class="col-6">
            <label for="found_at{{ $idSuffix }}" class="form-label fw-semibold">Date d'arrêt</label>
            <input type="date" class="form-control @if ($failed && $errors->has('found_at')) is-invalid @endif" id="found_at{{ $idSuffix }}" name="found_at" value="{{ $v('found_at') instanceof \Illuminate\Support\Carbon ? $v('found_at')->format('Y-m-d') : $v('found_at') }}" max="{{ date('Y-m-d') }}" required>
            @if ($failed && $errors->has('found_at'))<div class="invalid-feedback">{{ $errors->first('found_at') }}</div>@endif
        </div>
    </div>

    @if ($motoRetrouvee)
        <hr />
        <div class="form-check form-switch mb-2" data-recovered-suffix="{{ $idSuffix }}">
            <input class="form-check-input" type="checkbox" role="switch" id="recovered{{ $idSuffix }}" name="recovered" value="1" @checked($v('recovered'))>
            <label class="form-check-label fw-semibold" for="recovered{{ $idSuffix }}">Récupérée par le propriétaire</label>
        </div>
        <div id="recovered_at_wrap{{ $idSuffix }}">
            <label for="recovered_at{{ $idSuffix }}" class="form-label">Date de récupération</label>
            <input type="date" class="form-control @if ($failed && $errors->has('recovered_at')) is-invalid @endif" id="recovered_at{{ $idSuffix }}" name="recovered_at" value="{{ $v('recovered_at') instanceof \Illuminate\Support\Carbon ? $v('recovered_at')->format('Y-m-d') : $v('recovered_at') }}" max="{{ date('Y-m-d') }}">
            @if ($failed && $errors->has('recovered_at'))<div class="invalid-feedback">{{ $errors->first('recovered_at') }}</div>@endif
        </div>
    @else
        <small class="text-muted">La moto sera retirée de la liste des motos volées dès l'enregistrement.</small>
    @endif
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
</div>

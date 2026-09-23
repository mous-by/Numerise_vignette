@php
    $v = fn (string $field, $default = '') => $failed ? old($field) : ($demande?->{$field} ?? $default);
    // Select2 en recherche (AJAX, MotoController::search(), déjà utilisé par W9) : seule l'option déjà choisie
    // est pré-rendue, le reste se charge en tapant.
    $selectedMoto = $demande?->moto
        ?? ($failed && old('moto_id') ? \App\Models\Moto::with('proprietaire')->find(old('moto_id')) : null);
@endphp

<div class="modal-header">
    <h5 class="modal-title"><i class='bx bx-file me-2'></i>{{ $demande ? 'Corriger — '.$demande->moto->plate_number : 'Nouvelle demande VGT' }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>
<div class="modal-body p-4">
    @if ($demande && $demande->rejection_reason)
        <div class="alert alert-danger">Motif du rejet précédent : {{ $demande->rejection_reason }}</div>
    @endif
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
            <label for="mairie_id{{ $idSuffix }}" class="form-label fw-semibold">Mairie de retrait</label>
            <select class="form-select single-select" id="mairie_id{{ $idSuffix }}" name="mairie_id" required>
                <option value="">— Choisir —</option>
                @foreach ($mairies as $mairie)
                    <option value="{{ $mairie->id }}" @selected($v('mairie_id') == $mairie->id)>{{ $mairie->name }}</option>
                @endforeach
            </select>
            @if ($failed && $errors->has('mairie_id'))<div class="text-danger small mt-1">{{ $errors->first('mairie_id') }}</div>@endif
        </div>
    </div>
    <div class="row g-3 mt-0">
        <div class="col-4">
            <label for="vgt_year{{ $idSuffix }}" class="form-label fw-semibold">Année de la vignette</label>
            <input type="number" class="form-control @if ($failed && $errors->has('vgt_year')) is-invalid @endif" id="vgt_year{{ $idSuffix }}" name="vgt_year" value="{{ $v('vgt_year', date('Y')) }}" min="2000" max="{{ date('Y') + 1 }}" required>
            @if ($failed && $errors->has('vgt_year'))<div class="invalid-feedback">{{ $errors->first('vgt_year') }}</div>@endif
            <small class="text-muted">Année déjà passée = majoration pour arriéré.</small>
        </div>
        <div class="col-4">
            <label for="contact_phone{{ $idSuffix }}" class="form-label fw-semibold">Contact SMS</label>
            <input type="text" class="form-control @if ($failed && $errors->has('contact_phone')) is-invalid @endif" id="contact_phone{{ $idSuffix }}" name="contact_phone" value="{{ $v('contact_phone') }}" placeholder="70 00 00 01" required>
            @if ($failed && $errors->has('contact_phone'))<div class="invalid-feedback">{{ $errors->first('contact_phone') }}</div>@endif
        </div>
        <div class="col-4">
            <label for="merchant_code{{ $idSuffix }}" class="form-label fw-semibold">Code marchand</label>
            <input type="text" class="form-control @if ($failed && $errors->has('merchant_code')) is-invalid @endif" id="merchant_code{{ $idSuffix }}" name="merchant_code" value="{{ $v('merchant_code') }}">
            @if ($failed && $errors->has('merchant_code'))<div class="invalid-feedback">{{ $errors->first('merchant_code') }}</div>@endif
            <small class="text-muted">Facultatif.</small>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
</div>

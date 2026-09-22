@php
    $v = fn (string $field, $default = '') => $failed ? old($field) : ($moto?->{$field} ?? $default);
    $checked = fn (string $field) => $failed ? (bool) old($field) : (bool) ($moto?->{$field} ?? false);
@endphp

<div class="modal-header">
    <h5 class="modal-title"><i class='bx bx-cycling me-2'></i>{{ $moto ? 'Modifier — '.$moto->plate_number : 'Nouvelle moto' }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
</div>
<div class="modal-body p-4" data-moto-suffix="{{ $idSuffix }}">
    <div class="row g-3">
        <div class="col-6">
            <label for="proprietaire_id{{ $idSuffix }}" class="form-label fw-semibold">Propriétaire</label>
            <select class="form-select single-select" id="proprietaire_id{{ $idSuffix }}" name="proprietaire_id" required>
                <option value="">— Choisir —</option>
                @foreach ($proprietaires as $proprietaire)
                    <option value="{{ $proprietaire->id }}" @selected($v('proprietaire_id') == $proprietaire->id)>{{ $proprietaire->fullName() }}</option>
                @endforeach
            </select>
            @if ($failed && $errors->has('proprietaire_id'))<div class="text-danger small mt-1">{{ $errors->first('proprietaire_id') }}</div>@endif
        </div>
        <div class="col-6">
            <label for="plate_number{{ $idSuffix }}" class="form-label fw-semibold">Matricule</label>
            <input type="text" class="form-control @if ($failed && $errors->has('plate_number')) is-invalid @endif" id="plate_number{{ $idSuffix }}" name="plate_number" value="{{ $v('plate_number') }}" required>
            @if ($failed && $errors->has('plate_number'))<div class="invalid-feedback">{{ $errors->first('plate_number') }}</div>@endif
        </div>
    </div>
    <div class="row g-3 mt-0">
        <div class="col-4">
            <label for="color{{ $idSuffix }}" class="form-label fw-semibold">Couleur</label>
            <input type="text" class="form-control @if ($failed && $errors->has('color')) is-invalid @endif" id="color{{ $idSuffix }}" name="color" value="{{ $v('color') }}" required>
            @if ($failed && $errors->has('color'))<div class="invalid-feedback">{{ $errors->first('color') }}</div>@endif
        </div>
        <div class="col-4">
            <label for="type_or_brand{{ $idSuffix }}" class="form-label fw-semibold">Genre ou marque</label>
            <input type="text" class="form-control @if ($failed && $errors->has('type_or_brand')) is-invalid @endif" id="type_or_brand{{ $idSuffix }}" name="type_or_brand" value="{{ $v('type_or_brand') }}" required>
            @if ($failed && $errors->has('type_or_brand'))<div class="invalid-feedback">{{ $errors->first('type_or_brand') }}</div>@endif
        </div>
        <div class="col-4">
            <label for="vgt_year{{ $idSuffix }}" class="form-label fw-semibold">Année de la vignette</label>
            <input type="number" class="form-control @if ($failed && $errors->has('vgt_year')) is-invalid @endif" id="vgt_year{{ $idSuffix }}" name="vgt_year" value="{{ $v('vgt_year', date('Y')) }}" min="2000" max="{{ date('Y') + 1 }}" required>
            @if ($failed && $errors->has('vgt_year'))<div class="invalid-feedback">{{ $errors->first('vgt_year') }}</div>@endif
        </div>
    </div>

    <hr />
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" role="switch" id="has_sale_certificate{{ $idSuffix }}" name="has_sale_certificate" value="1" @checked($checked('has_sale_certificate'))>
        <label class="form-check-label fw-semibold" for="has_sale_certificate{{ $idSuffix }}">Attestation de vente</label>
    </div>
    <div id="seller-fields{{ $idSuffix }}" class="border rounded p-3 mb-3">
        <p class="text-muted small mb-2">Informations sur le vendeur</p>
        <div class="row g-3">
            <div class="col-6">
                <label for="seller_first_name{{ $idSuffix }}" class="form-label">Prénom du vendeur</label>
                <input type="text" class="form-control @if ($failed && $errors->has('seller_first_name')) is-invalid @endif" id="seller_first_name{{ $idSuffix }}" name="seller_first_name" value="{{ $v('seller_first_name') }}">
                @if ($failed && $errors->has('seller_first_name'))<div class="invalid-feedback">{{ $errors->first('seller_first_name') }}</div>@endif
            </div>
            <div class="col-6">
                <label for="seller_last_name{{ $idSuffix }}" class="form-label">Nom du vendeur</label>
                <input type="text" class="form-control @if ($failed && $errors->has('seller_last_name')) is-invalid @endif" id="seller_last_name{{ $idSuffix }}" name="seller_last_name" value="{{ $v('seller_last_name') }}">
                @if ($failed && $errors->has('seller_last_name'))<div class="invalid-feedback">{{ $errors->first('seller_last_name') }}</div>@endif
            </div>
            <div class="col-6">
                <label for="seller_phone{{ $idSuffix }}" class="form-label">Téléphone du vendeur</label>
                <input type="text" class="form-control @if ($failed && $errors->has('seller_phone')) is-invalid @endif" id="seller_phone{{ $idSuffix }}" name="seller_phone" value="{{ $v('seller_phone') }}" placeholder="70 00 00 01">
                @if ($failed && $errors->has('seller_phone'))<div class="invalid-feedback">{{ $errors->first('seller_phone') }}</div>@endif
            </div>
            <div class="col-6">
                <label for="seller_address{{ $idSuffix }}" class="form-label">Adresse du vendeur</label>
                <input type="text" class="form-control @if ($failed && $errors->has('seller_address')) is-invalid @endif" id="seller_address{{ $idSuffix }}" name="seller_address" value="{{ $v('seller_address') }}">
                @if ($failed && $errors->has('seller_address'))<div class="invalid-feedback">{{ $errors->first('seller_address') }}</div>@endif
            </div>
        </div>

        <div id="witness-toggle{{ $idSuffix }}" class="form-check form-switch mt-3 mb-2">
            <input class="form-check-input" type="checkbox" role="switch" id="has_witness{{ $idSuffix }}" name="has_witness" value="1" @checked($checked('has_witness'))>
            <label class="form-check-label fw-semibold" for="has_witness{{ $idSuffix }}">Témoin</label>
        </div>
        <div id="witness-fields{{ $idSuffix }}">
            <p class="text-muted small mb-2">Informations sur le témoin</p>
            <div class="row g-3">
                <div class="col-6">
                    <label for="witness_first_name{{ $idSuffix }}" class="form-label">Prénom du témoin</label>
                    <input type="text" class="form-control @if ($failed && $errors->has('witness_first_name')) is-invalid @endif" id="witness_first_name{{ $idSuffix }}" name="witness_first_name" value="{{ $v('witness_first_name') }}">
                    @if ($failed && $errors->has('witness_first_name'))<div class="invalid-feedback">{{ $errors->first('witness_first_name') }}</div>@endif
                </div>
                <div class="col-6">
                    <label for="witness_last_name{{ $idSuffix }}" class="form-label">Nom du témoin</label>
                    <input type="text" class="form-control @if ($failed && $errors->has('witness_last_name')) is-invalid @endif" id="witness_last_name{{ $idSuffix }}" name="witness_last_name" value="{{ $v('witness_last_name') }}">
                    @if ($failed && $errors->has('witness_last_name'))<div class="invalid-feedback">{{ $errors->first('witness_last_name') }}</div>@endif
                </div>
                <div class="col-6">
                    <label for="witness_phone{{ $idSuffix }}" class="form-label">Téléphone du témoin</label>
                    <input type="text" class="form-control @if ($failed && $errors->has('witness_phone')) is-invalid @endif" id="witness_phone{{ $idSuffix }}" name="witness_phone" value="{{ $v('witness_phone') }}" placeholder="70 00 00 01">
                    @if ($failed && $errors->has('witness_phone'))<div class="invalid-feedback">{{ $errors->first('witness_phone') }}</div>@endif
                </div>
                <div class="col-6">
                    <label for="witness_address{{ $idSuffix }}" class="form-label">Adresse du témoin</label>
                    <input type="text" class="form-control @if ($failed && $errors->has('witness_address')) is-invalid @endif" id="witness_address{{ $idSuffix }}" name="witness_address" value="{{ $v('witness_address') }}">
                    @if ($failed && $errors->has('witness_address'))<div class="invalid-feedback">{{ $errors->first('witness_address') }}</div>@endif
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
</div>

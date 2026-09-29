{{-- Champ image d'une mairie pour la carte VGT (logo, monument) : aperçu, envoi, retour à l'image par défaut. `back` : écran où revenir. --}}
<label class="form-label small text-muted">{{ $title }}</label>
<div class="d-flex align-items-center gap-3">
    <span class="nv-logo-preview">
        @if ($url)
            <img src="{{ $url }}" alt="{{ $title }}">
        @else
            @include('demandes-vgt._card_parts.'.$default, ['class' => '', 'uid' => 'p'.$mairieOption->id, 'demande' => (object) ['mairie' => $mairieOption]])
        @endif
    </span>
    <div class="flex-grow-1">
        <form method="POST" action="{{ $updateUrl }}" enctype="multipart/form-data" class="d-flex gap-2">
            @csrf
            <input type="hidden" name="back" value="{{ $back ?? 'demandes' }}">
            <input type="hidden" name="card_mairie" value="{{ $mairieOption->id }}">
            <input type="file" name="{{ $field }}" class="form-control form-control-sm @if (old('card_mairie') == $mairieOption->id) @error($field) is-invalid @enderror @endif" accept="image/png,image/jpeg,image/webp" required>
            <button type="submit" class="btn btn-primary btn-sm" title="Téléverser"><i class='bx bx-upload'></i></button>
        </form>
        @if (old('card_mairie') == $mairieOption->id)
            @error($field)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        @endif
        <div class="small text-muted mt-1">
            @if ($url)
                Image personnalisée.
                <form method="POST" action="{{ $resetUrl }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="back" value="{{ $back ?? 'demandes' }}">
                    <button type="submit" class="btn btn-link btn-sm p-0 text-danger">Revenir à l'image par défaut</button>
                </form>
            @else
                Image par défaut. {{ $hint }}
            @endif
        </div>
    </div>
</div>

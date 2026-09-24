{{-- Réglages de la carte VGT d'une mairie : modèle présélectionné, logo, monument (W13). Variables : $mairieOption, $back ('demandes' par défaut, ou 'mairies'). --}}
@php($back = $back ?? 'demandes')
<div class="nv-panel mb-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="fw-bold">{{ $mairieOption->name }}</div>
        <span class="badge bg-primary-subtle">{{ $mairieOption->card_template->label() }}</span>
    </div>

    <div class="row g-3 align-items-end">
        <div class="col-md-6">
            <form method="POST" action="{{ route('mairies.card-template.update', $mairieOption) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="back" value="{{ $back }}">
                <label class="form-label small text-muted">Modèle présélectionné à l'impression</label>
                <div class="d-flex gap-2">
                    <select class="form-select" name="card_template">
                        @foreach (\App\Enums\VgtCardTemplate::cases() as $template)
                            <option value="{{ $template->value }}" @selected($mairieOption->card_template === $template)>{{ $template->label() }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" title="Enregistrer le modèle"><i class='bx bx-save'></i></button>
                </div>
            </form>
        </div>

        <div class="col-md-6">
            @include('demandes-vgt._image_field', [
                'title' => 'Logo imprimé sur la carte', 'field' => 'logo', 'url' => $mairieOption->logoUrl(), 'default' => 'arms',
                'updateUrl' => route('mairies.logo.update', $mairieOption), 'resetUrl' => route('mairies.logo.reset', $mairieOption),
                'hint' => 'PNG, JPEG ou WebP, 100 × 100 px minimum, 1 Mo maximum.', 'back' => $back,
            ])
        </div>
        <div class="col-12"><hr class="my-1"></div>
        <div class="col-md-6">
            @include('demandes-vgt._image_field', [
                'title' => 'Image du monument (cartes 2025 et 2026)', 'field' => 'monument', 'url' => $mairieOption->monumentUrl(), 'default' => 'tower',
                'updateUrl' => route('mairies.monument.update', $mairieOption), 'resetUrl' => route('mairies.monument.reset', $mairieOption),
                'hint' => 'Photo réelle en portrait (idéalement PNG détouré), 2 Mo maximum.', 'back' => $back,
            ])
        </div>
    </div>
</div>

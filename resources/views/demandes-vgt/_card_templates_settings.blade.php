{{-- Réglages de la carte VGT par mairie (W13) : modèle présélectionné et logo imprimé — gardé par `mairies.update`. --}}
<div class="modal fade" id="cardTemplatesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-id-card me-2'></i>Carte VGT : modèle et logo par mairie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                @forelse ($mairies as $mairieOption)
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
                                    'hint' => 'PNG, JPEG ou WebP, 100 × 100 px minimum, 1 Mo maximum.',
                                ])
                            </div>
                            <div class="col-12"><hr class="my-1"></div>
                            <div class="col-md-6">
                                @include('demandes-vgt._image_field', [
                                    'title' => 'Image du monument (cartes 2025 et 2026)', 'field' => 'monument', 'url' => $mairieOption->monumentUrl(), 'default' => 'tower',
                                    'updateUrl' => route('mairies.monument.update', $mairieOption), 'resetUrl' => route('mairies.monument.reset', $mairieOption),
                                    'hint' => 'Photo réelle en portrait (idéalement PNG détouré), 2 Mo maximum.',
                                ])
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Aucune mairie active pour l'instant.</p>
                @endforelse
                <p class="small text-muted mb-0">Le modèle « Officiel (2026) » et les armoiries par défaut sont des dessins originaux (PROPOSITION TECHNIQUE — à valider avec le client avant mise en production, CLAUDE.md §5). Le choix du modèle se refait à chaque impression.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@if ($errors->has('logo') || $errors->has('monument'))
    <script>
        window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('cardTemplatesModal')).show());
    </script>
@endif

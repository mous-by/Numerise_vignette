{{-- Réglages de la carte VGT par mairie (W13) : modèle présélectionné, logo et monument — gardé par `mairies.update`. --}}
<div class="modal fade" id="cardTemplatesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-id-card me-2'></i>Carte VGT : modèle, logo et monument par mairie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                @forelse ($mairies as $mairieOption)
                    @include('demandes-vgt._card_settings_block', ['mairieOption' => $mairieOption, 'back' => 'demandes'])
                @empty
                    <p class="text-muted mb-0">Aucune mairie active pour l'instant.</p>
                @endforelse
                <p class="small text-muted mb-0">Le modèle « Officiel (2026) » et les armoiries par défaut sont des dessins et images d'après de vraies vignettes (PROPOSITION TECHNIQUE — à valider avec le client avant mise en production, CLAUDE.md §5). Le choix du modèle se refait à chaque impression. Ces réglages sont aussi accessibles depuis l'écran Mairies.</p>
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

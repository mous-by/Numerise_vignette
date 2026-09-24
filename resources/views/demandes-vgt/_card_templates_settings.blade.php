{{-- Réglage du modèle de carte VGT par mairie (W13, App\Enums\VgtCardTemplate) — gardé par `mairies.update`. --}}
<div class="modal fade" id="cardTemplatesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-id-card me-2'></i>Modèles de carte VGT par mairie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>MAIRIE</th>
                            <th width="45%">MODÈLE</th>
                            <th width="10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mairies as $mairieOption)
                            <tr>
                                <form method="POST" action="{{ route('mairies.card-template.update', $mairieOption) }}">
                                    @csrf
                                    @method('PUT')
                                    <td class="align-middle">{{ $mairieOption->name }}</td>
                                    <td>
                                        <select class="form-select form-select-sm" name="card_template">
                                            @foreach (\App\Enums\VgtCardTemplate::cases() as $template)
                                                <option value="{{ $template->value }}" @selected($mairieOption->card_template === $template)>{{ $template->label() }} — {{ $template->description() }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><button type="submit" class="btn btn-primary btn-sm"><i class='bx bx-save'></i></button></td>
                                </form>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">Aucune mairie active pour l'instant.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <p class="small text-muted mb-0">Le modèle « Officiel » évoque les couleurs et le sceau du Mali (PROPOSITION TECHNIQUE — à valider avec le client avant mise en production, CLAUDE.md §5).</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

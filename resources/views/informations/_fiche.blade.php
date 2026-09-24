{{-- Fiche d'une information (consultation en modale, §7) : texte complet, images et PDF. --}}
<div class="modal fade" id="ficheInformation-{{ $information->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class='bx bx-info-circle me-2'></i>Information du {{ $information->published_at->format('d/m/Y') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="nv-panel mb-3">
                    <dl class="nv-dl">
                        <dt>Commissariat</dt><dd>{{ $information->commissariat->name }}</dd>
                        <dt>Commissaire</dt><dd>{{ $information->commissaire->name }}</dd>
                        <dt>Publiée le</dt><dd>{{ $information->published_at->format('d/m/Y') }}</dd>
                    </dl>
                </div>

                <div class="nv-panel-title">Description</div>
                <div class="nv-panel mb-3">
                    @if ($information->description)
                        <div class="text-break" style="white-space: pre-line;">{{ $information->description }}</div>
                    @else
                        <div class="text-muted small">Aucune description.</div>
                    @endif
                </div>

                <div class="nv-panel-title">Images ({{ $information->images->count() }})</div>
                @if ($information->images->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach ($information->images as $image)
                            <a href="{{ $image->url() }}" target="_blank" rel="noopener"><img src="{{ $image->url() }}" class="gallery-thumb" alt="Image" style="width: 96px; height: 96px; object-fit: cover;"></a>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted small mb-3">Aucune image.</div>
                @endif

                <div class="nv-panel-title">Fichiers PDF ({{ $information->documents->count() }})</div>
                @forelse ($information->documents as $document)
                    <a href="{{ $document->url() }}" target="_blank" rel="noopener" class="d-flex align-items-center gap-2 mb-1">
                        <i class='bx bxs-file-pdf text-danger fs-4'></i>Document {{ $loop->iteration }}
                    </a>
                @empty
                    <div class="text-muted small">Aucun fichier PDF.</div>
                @endforelse
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@extends('layouts.admin')

@section('title', 'Informations')

@push('styles')
    <style>
        /* Zone de glisser-déposer (aucun équivalent dans le thème) : composant propre au module, pas de CSS ad hoc
           sur les éléments du thème. */
        .dz { border: none; }
        .dz-surface {
            border: 2px dashed #c7d2e0;
            border-radius: .5rem;
            padding: 1.25rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: border-color .15s ease, background-color .15s ease;
            color: #6b7d99;
        }
        .dz-surface:hover, .dz.dz-active .dz-surface {
            border-color: #1d4e89;
            background-color: rgba(29, 78, 137, .05);
            color: #1d4e89;
        }
        .dz-surface i { font-size: 1.75rem; display: block; margin-bottom: .25rem; }
        .dz-surface p { margin: 0; font-size: .875rem; }
        .dz-preview { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .75rem; }
        .dz-chip { position: relative; width: 72px; }
        .dz-chip img { width: 72px; height: 72px; object-fit: cover; border-radius: .375rem; border: 1px solid #e2e8f0; }
        .dz-chip .dz-doc { width: 72px; height: 72px; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 1px solid #e2e8f0; border-radius: .375rem; background: #f8fafc; padding: .25rem; }
        .dz-chip .dz-doc i { font-size: 1.5rem; color: #dc2626; }
        .dz-chip .dz-doc span { font-size: .625rem; text-align: center; word-break: break-all; line-height: 1.1; margin-top: .125rem; }
        .dz-chip .dz-remove {
            position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; border-radius: 50%;
            border: none; background: #dc2626; color: #fff; line-height: 1; font-size: .875rem; cursor: pointer;
            display: flex; align-items: center; justify-content: center; padding: 0;
        }
        .dz-chip.dz-marked-removed { opacity: .35; }
        .dz-chip.dz-marked-removed .dz-remove { background: #64748b; }
        .gallery-thumb { width: 28px; height: 28px; object-fit: cover; border-radius: .25rem; border: 1px solid #e2e8f0; }
    </style>
@endpush

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Informations</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Informations</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-info-circle me-2'></i>INFORMATIONS</h6>
            @can('create', \App\Models\Information::class)
                <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createInformationModal">
                    <i class='bx bx-plus'></i> Publier
                </button>
            @endcan
        </div>
        <div class="card-body">
            @php
                $withImages = $informations->filter(fn ($i) => $i->images->isNotEmpty())->count();
                $withDocuments = $informations->filter(fn ($i) => $i->documents->isNotEmpty())->count();
                $textOnly = $informations->filter(fn ($i) => $i->images->isEmpty() && $i->documents->isEmpty())->count();
            @endphp
            <div class="nv-chips mb-3" id="informations-filter">
                <button type="button" class="nv-chip active" data-token="">Toutes <b>{{ $informations->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="avec-image">Avec images <b>{{ $withImages }}</b></button>
                <button type="button" class="nv-chip" data-token="avec-pdf">Avec PDF <b>{{ $withDocuments }}</b></button>
                <button type="button" class="nv-chip" data-token="texte-seul">Texte seul <b>{{ $textOnly }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="informations-table">
                    <thead>
                        <tr>
                            <th>PUBLICATION</th>
                            <th>DESCRIPTION</th>
                            <th>PIÈCES</th>
                            <th width="12%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($informations as $information)
                            <tr>
                                <td data-order="{{ $information->published_at->format('Y-m-d') }}">
                                    <div class="fw-semibold">{{ $information->commissariat->name }}</div>
                                    <div class="small text-muted">{{ $information->commissaire->name }} · {{ $information->published_at->format('d/m/Y') }}</div>
                                </td>
                                <td class="cell-wrap text-break">{{ \Illuminate\Support\Str::limit($information->description ?? '—', 120) }}</td>
                                <td class="text-nowrap">
                                    <span class="d-none">{{ $information->images->isNotEmpty() ? 'avec-image ' : '' }}{{ $information->documents->isNotEmpty() ? 'avec-pdf ' : '' }}{{ $information->images->isEmpty() && $information->documents->isEmpty() ? 'texte-seul' : '' }}</span>
                                    <div class="d-flex align-items-center gap-1">
                                        @foreach ($information->images->take(3) as $image)
                                            <a href="{{ $image->url() }}" target="_blank" rel="noopener"><img src="{{ $image->url() }}" class="gallery-thumb" alt="Image"></a>
                                        @endforeach
                                        @if ($information->images->count() > 3)
                                            <span class="badge bg-light text-dark border">+{{ $information->images->count() - 3 }}</span>
                                        @endif
                                        @if ($information->documents->isNotEmpty())
                                            <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                                                <i class='bx bxs-file-pdf text-danger'></i>{{ $information->documents->count() }}
                                            </span>
                                        @endif
                                        @if ($information->images->isEmpty() && $information->documents->isEmpty())
                                            —
                                        @endif
                                    </div>
                                </td>
                                <td class="d-flex gap-1">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ficheInformation-{{ $information->id }}" title="Consulter">
                                        <i class='bx bx-show'></i>
                                    </button>
                                    @can('update', $information)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editInformation-{{ $information->id }}" title="Modifier">
                                            <i class='bx bx-edit'></i>
                                        </button>
                                    @endcan
                                    @can('delete', $information)
                                        <form method="POST" action="{{ route('informations.destroy', $information) }}" class="js-delete-information" data-name="cette information">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-primary btn-sm" title="Supprimer">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach ($informations as $information)
        @include('informations._fiche', ['information' => $information])
    @endforeach
    @can('create', \App\Models\Information::class)
        <div class="modal fade" id="createInformationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('informations.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class='bx bx-info-circle me-2'></i>Nouvelle information</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        @php($createFailed = $errors->any() && ! old('information_id'))
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label for="description" class="form-label fw-semibold">Description</label>
                                <textarea class="form-control @if ($createFailed && $errors->has('description')) is-invalid @endif" id="description" name="description" rows="3">{{ $createFailed ? old('description') : '' }}</textarea>
                                @if ($createFailed && $errors->has('description'))<div class="invalid-feedback">{{ $errors->first('description') }}</div>@endif
                                <small class="text-muted">Description, images ou PDF : au moins un des trois.</small>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Images <span class="text-muted fw-normal">(5 maximum)</span></label>
                                    <div class="dz js-dropzone" data-input="images-input-create" data-preview="images-preview-create" data-max="{{ \App\Models\Information::MAX_IMAGES }}" data-kind="image">
                                        <div class="dz-surface">
                                            <i class='bx bx-cloud-upload'></i>
                                            <p>Glissez des images ici, ou cliquez pour parcourir</p>
                                            <span class="dz-count text-muted small">0 / {{ \App\Models\Information::MAX_IMAGES }}</span>
                                        </div>
                                        <div class="dz-preview" id="images-preview-create"></div>
                                        <input type="file" id="images-input-create" name="images[]" accept="image/png,image/jpeg,image/webp" multiple hidden>
                                    </div>
                                    @if ($createFailed && ($errors->has('images') || $errors->has('images.*')))<div class="text-danger small mt-1">{{ $errors->first('images') ?: $errors->first('images.*') }}</div>@endif
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Fichiers PDF <span class="text-muted fw-normal">(3 maximum)</span></label>
                                    <div class="dz js-dropzone" data-input="documents-input-create" data-preview="documents-preview-create" data-max="{{ \App\Models\Information::MAX_DOCUMENTS }}" data-kind="document">
                                        <div class="dz-surface">
                                            <i class='bx bx-cloud-upload'></i>
                                            <p>Glissez des PDF ici, ou cliquez pour parcourir</p>
                                            <span class="dz-count text-muted small">0 / {{ \App\Models\Information::MAX_DOCUMENTS }}</span>
                                        </div>
                                        <div class="dz-preview" id="documents-preview-create"></div>
                                        <input type="file" id="documents-input-create" name="documents[]" accept="application/pdf" multiple hidden>
                                    </div>
                                    @if ($createFailed && ($errors->has('documents') || $errors->has('documents.*')))<div class="text-danger small mt-1">{{ $errors->first('documents') ?: $errors->first('documents.*') }}</div>@endif
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Publier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($createFailed ?? false)
            <script>
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('createInformationModal')).show());
            </script>
        @endif
    @endcan

    @foreach ($informations as $information)
        @can('update', $information)
            @php($editFailed = $errors->any() && old('information_id') == $information->id)
            <div class="modal fade" id="editInformation-{{ $information->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('informations.update', $information) }}" enctype="multipart/form-data" class="js-edit-information-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="information_id" value="{{ $information->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class='bx bx-info-circle me-2'></i>Modifier l'information</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label for="description-{{ $information->id }}" class="form-label fw-semibold">Description</label>
                                    <textarea class="form-control @if ($editFailed && $errors->has('description')) is-invalid @endif" id="description-{{ $information->id }}" name="description" rows="3">{{ $editFailed ? old('description') : $information->description }}</textarea>
                                    @if ($editFailed && $errors->has('description'))<div class="invalid-feedback">{{ $errors->first('description') }}</div>@endif
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">Images <span class="text-muted fw-normal">(5 maximum au total)</span></label>
                                        @if ($information->images->isNotEmpty())
                                            <div class="dz-preview mb-2">
                                                @foreach ($information->images as $image)
                                                    <div class="dz-chip js-existing-file" data-id="{{ $image->id }}">
                                                        <img src="{{ $image->url() }}" alt="Image">
                                                        <button type="button" class="dz-remove js-toggle-remove" title="Retirer">&times;</button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="dz js-dropzone" data-input="images-input-{{ $information->id }}" data-preview="images-preview-{{ $information->id }}" data-max="{{ \App\Models\Information::MAX_IMAGES }}" data-kind="image">
                                            <div class="dz-surface">
                                                <i class='bx bx-cloud-upload'></i>
                                                <p>Ajouter des images</p>
                                                <span class="dz-count text-muted small">0 / {{ \App\Models\Information::MAX_IMAGES }}</span>
                                            </div>
                                            <div class="dz-preview" id="images-preview-{{ $information->id }}"></div>
                                            <input type="file" id="images-input-{{ $information->id }}" name="images[]" accept="image/png,image/jpeg,image/webp" multiple hidden>
                                        </div>
                                        @if ($editFailed && ($errors->has('images') || $errors->has('images.*')))<div class="text-danger small mt-1">{{ $errors->first('images') ?: $errors->first('images.*') }}</div>@endif
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">Fichiers PDF <span class="text-muted fw-normal">(3 maximum au total)</span></label>
                                        @if ($information->documents->isNotEmpty())
                                            <div class="dz-preview mb-2">
                                                @foreach ($information->documents as $document)
                                                    <div class="dz-chip js-existing-file" data-id="{{ $document->id }}">
                                                        <div class="dz-doc">
                                                            <i class='bx bxs-file-pdf'></i>
                                                            <span>{{ \Illuminate\Support\Str::limit(basename($document->path), 14) }}</span>
                                                        </div>
                                                        <button type="button" class="dz-remove js-toggle-remove" title="Retirer">&times;</button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="dz js-dropzone" data-input="documents-input-{{ $information->id }}" data-preview="documents-preview-{{ $information->id }}" data-max="{{ \App\Models\Information::MAX_DOCUMENTS }}" data-kind="document">
                                            <div class="dz-surface">
                                                <i class='bx bx-cloud-upload'></i>
                                                <p>Ajouter des PDF</p>
                                                <span class="dz-count text-muted small">0 / {{ \App\Models\Information::MAX_DOCUMENTS }}</span>
                                            </div>
                                            <div class="dz-preview" id="documents-preview-{{ $information->id }}"></div>
                                            <input type="file" id="documents-input-{{ $information->id }}" name="documents[]" accept="application/pdf" multiple hidden>
                                        </div>
                                        @if ($editFailed && ($errors->has('documents') || $errors->has('documents.*')))<div class="text-danger small mt-1">{{ $errors->first('documents') ?: $errors->first('documents.*') }}</div>@endif
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @if ($editFailed ?? false)
                <script>
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('editInformation-{{ $information->id }}')).show());
                </script>
            @endif
        @endcan
    @endforeach
@endsection

@push('scripts')
    <script>
        const informationsTable = $('#informations-table').DataTable({ scrollX: false, order: [[0, 'desc']] });

        document.querySelectorAll('#informations-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#informations-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                informationsTable.column(2).search(chip.dataset.token || '', false, true).draw();
            });
        });

        $(document).on('submit', '.js-delete-information', function (event) {
            event.preventDefault();
            const form = this;
            Swal.fire({
                icon: 'warning',
                title: 'Supprimer cette information ?',
                text: 'Elle sera supprimée (récupérable) ainsi que ses fichiers.',
                showCancelButton: true,
                confirmButtonText: 'Supprimer',
                cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });

        // Zones de glisser-déposer : gèrent un input file[multiple] caché, avec aperçus et retrait individuel
        // avant envoi. Aucune dépendance externe (D15) : API HTML5 native (DataTransfer, drag events).
        function initDropzone(root) {
            const input = document.getElementById(root.dataset.input);
            const preview = document.getElementById(root.dataset.preview);
            const surface = root.querySelector('.dz-surface');
            const countEl = root.querySelector('.dz-count');
            const max = parseInt(root.dataset.max, 10) || 99;
            const kind = root.dataset.kind;
            let files = [];

            function render() {
                preview.innerHTML = '';
                files.forEach((file, index) => {
                    const chip = document.createElement('div');
                    chip.className = 'dz-chip';
                    if (kind === 'image') {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(file);
                        chip.appendChild(img);
                    } else {
                        const box = document.createElement('div');
                        box.className = 'dz-doc';
                        box.innerHTML = "<i class='bx bxs-file-pdf'></i>";
                        const name = document.createElement('span');
                        name.textContent = file.name;
                        box.appendChild(name);
                        chip.appendChild(box);
                    }
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'dz-remove';
                    remove.title = 'Retirer';
                    remove.innerHTML = '&times;';
                    remove.addEventListener('click', () => { files.splice(index, 1); sync(); });
                    chip.appendChild(remove);
                    preview.appendChild(chip);
                });
                if (countEl) countEl.textContent = files.length + ' / ' + max;
            }

            function sync() {
                const dt = new DataTransfer();
                files.forEach((f) => dt.items.add(f));
                input.files = dt.files;
                render();
            }

            function addFiles(list) {
                for (const file of list) {
                    if (files.length >= max) break;
                    files.push(file);
                }
                sync();
            }

            surface.addEventListener('click', () => input.click());
            input.addEventListener('change', (event) => addFiles(event.target.files));

            ['dragenter', 'dragover'].forEach((evt) => root.addEventListener(evt, (event) => {
                event.preventDefault(); event.stopPropagation(); root.classList.add('dz-active');
            }));
            ['dragleave', 'drop'].forEach((evt) => root.addEventListener(evt, (event) => {
                event.preventDefault(); event.stopPropagation(); root.classList.remove('dz-active');
            }));
            root.addEventListener('drop', (event) => addFiles(event.dataTransfer.files));

            render();
        }

        document.querySelectorAll('.js-dropzone').forEach(initDropzone);

        // Pièces déjà enregistrées (modales d'édition) : la croix bascule une coche cachée remove_files[]
        // plutôt que de supprimer tout de suite — la suppression réelle attend l'enregistrement du formulaire.
        document.querySelectorAll('.js-edit-information-form').forEach((form) => {
            form.querySelectorAll('.js-existing-file').forEach((chip) => {
                const button = chip.querySelector('.js-toggle-remove');
                const id = chip.dataset.id;
                let marked = false;
                button.addEventListener('click', () => {
                    marked = !marked;
                    chip.classList.toggle('dz-marked-removed', marked);
                    if (marked) {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'remove_files[]';
                        hidden.value = id;
                        hidden.dataset.removeFor = id;
                        form.appendChild(hidden);
                    } else {
                        form.querySelector(`input[data-remove-for="${id}"]`)?.remove();
                    }
                });
            });
        });
    </script>
@endpush

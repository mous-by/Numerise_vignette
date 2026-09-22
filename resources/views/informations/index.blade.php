@extends('layouts.admin')

@section('title', 'Informations')

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
            <div class="table-responsive">
                <table class="table" id="informations-table">
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>COMMISSAIRE</th>
                            <th>COMMISSARIAT</th>
                            <th>DESCRIPTION</th>
                            <th>PIÈCES</th>
                            <th width="10%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($informations as $information)
                            <tr>
                                <td class="text-nowrap">{{ $information->published_at->format('d/m/Y') }}</td>
                                <td>{{ $information->commissaire->name }}</td>
                                <td>{{ $information->commissariat->name }}</td>
                                <td class="cell-wrap text-break">{{ $information->description ?? '—' }}</td>
                                <td class="text-nowrap">
                                    @if ($information->image_path)
                                        <a href="{{ $information->imageUrl() }}" target="_blank" rel="noopener" title="Image"><i class='bx bx-image'></i></a>
                                    @endif
                                    @if ($information->document_path)
                                        <a href="{{ $information->documentUrl() }}" target="_blank" rel="noopener" title="PDF"><i class='bx bxs-file-pdf'></i></a>
                                    @endif
                                    @if (! $information->image_path && ! $information->document_path)
                                        —
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
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

    @can('create', \App\Models\Information::class)
        <div class="modal fade" id="createInformationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
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
                                <small class="text-muted">Description, image ou PDF : au moins un des trois.</small>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label fw-semibold">Image</label>
                                <input type="file" class="form-control @if ($createFailed && $errors->has('image')) is-invalid @endif" id="image" name="image" accept="image/png,image/jpeg,image/webp">
                                @if ($createFailed && $errors->has('image'))<div class="invalid-feedback">{{ $errors->first('image') }}</div>@endif
                            </div>
                            <div class="mb-3">
                                <label for="document" class="form-label fw-semibold">Fichier PDF</label>
                                <input type="file" class="form-control @if ($createFailed && $errors->has('document')) is-invalid @endif" id="document" name="document" accept="application/pdf">
                                @if ($createFailed && $errors->has('document'))<div class="invalid-feedback">{{ $errors->first('document') }}</div>@endif
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
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('informations.update', $information) }}" enctype="multipart/form-data">
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
                                <div class="mb-3">
                                    <label for="image-{{ $information->id }}" class="form-label fw-semibold">Image</label>
                                    @if ($information->image_path)
                                        <div class="mb-2 d-flex align-items-center gap-2">
                                            <a href="{{ $information->imageUrl() }}" target="_blank" rel="noopener">Image actuelle</a>
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" value="1" name="remove_image" id="remove-image-{{ $information->id }}">
                                                <label class="form-check-label" for="remove-image-{{ $information->id }}">Supprimer</label>
                                            </div>
                                        </div>
                                    @endif
                                    <input type="file" class="form-control @if ($editFailed && $errors->has('image')) is-invalid @endif" id="image-{{ $information->id }}" name="image" accept="image/png,image/jpeg,image/webp">
                                    @if ($editFailed && $errors->has('image'))<div class="invalid-feedback">{{ $errors->first('image') }}</div>@endif
                                </div>
                                <div class="mb-3">
                                    <label for="document-{{ $information->id }}" class="form-label fw-semibold">Fichier PDF</label>
                                    @if ($information->document_path)
                                        <div class="mb-2 d-flex align-items-center gap-2">
                                            <a href="{{ $information->documentUrl() }}" target="_blank" rel="noopener">PDF actuel</a>
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" value="1" name="remove_document" id="remove-document-{{ $information->id }}">
                                                <label class="form-check-label" for="remove-document-{{ $information->id }}">Supprimer</label>
                                            </div>
                                        </div>
                                    @endif
                                    <input type="file" class="form-control @if ($editFailed && $errors->has('document')) is-invalid @endif" id="document-{{ $information->id }}" name="document" accept="application/pdf">
                                    @if ($editFailed && $errors->has('document'))<div class="invalid-feedback">{{ $errors->first('document') }}</div>@endif
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
        $('#informations-table').DataTable({ scrollX: false });

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
    </script>
@endpush

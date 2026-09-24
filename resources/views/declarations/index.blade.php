@extends('layouts.admin')

@section('title', 'Déclarations')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Déclarations</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Déclarations</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />
    @include('partials.registre-tabs')

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-error-alt me-2'></i>DÉCLARATIONS</h6>
            @can('create', \App\Models\Declaration::class)
                @if (! $hasMotos)
                    <span class="badge bg-light text-dark">Créez d'abord une moto</span>
                @else
                    <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createDeclarationModal">
                        <i class='bx bx-plus'></i> Ajouter
                    </button>
                @endif
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="declarations-table">
                    <thead>
                        <tr>
                            <th>DATE DE L'ACTE</th>
                            <th>MATRICULE</th>
                            <th>PROPRIÉTAIRE</th>
                            <th>TYPE</th>
                            <th>LIEU</th>
                            <th>MOTO</th>
                            <th width="10%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($declarations as $declaration)
                            <tr>
                                <td>{{ $declaration->occurred_at->format('d/m/Y') }}</td>
                                <td class="fw-semibold">{{ $declaration->moto->plate_number }}</td>
                                <td>{{ $declaration->moto->proprietaire->fullName() }}</td>
                                <td>{{ $declaration->type->label() }}</td>
                                <td class="cell-wrap text-break">{{ $declaration->location }}</td>
                                <td>
                                    @if ($declaration->moto->is_stolen)
                                        <span class="badge bg-danger">Volée</span>
                                    @else
                                        <span class="badge bg-light text-dark">En règle</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    @can('update', $declaration)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editDeclaration-{{ $declaration->id }}" title="Modifier">
                                            <i class='bx bx-edit'></i>
                                        </button>
                                    @endcan
                                    @can('delete', $declaration)
                                        <form method="POST" action="{{ route('declarations.destroy', $declaration) }}" class="js-delete-declaration" data-name="{{ $declaration->type->label() }} — {{ $declaration->moto->plate_number }}">
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

    @can('create', \App\Models\Declaration::class)
        @php($createFailed = $errors->any() && ! old('declaration_id'))
        <div class="modal fade" id="createDeclarationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('declarations.store') }}">
                        @csrf
                        @include('declarations._form', ['declaration' => null, 'failed' => $createFailed, 'idSuffix' => ''])
                    </form>
                </div>
            </div>
        </div>

        @if ($createFailed)
            <script>
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('createDeclarationModal')).show());
            </script>
        @endif
    @endcan

    @foreach ($declarations as $declaration)
        @can('update', $declaration)
            @php($editFailed = $errors->any() && old('declaration_id') == $declaration->id)
            <div class="modal fade" id="editDeclaration-{{ $declaration->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('declarations.update', $declaration) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="declaration_id" value="{{ $declaration->id }}">
                            @include('declarations._form', ['declaration' => $declaration, 'failed' => $editFailed, 'idSuffix' => '-'.$declaration->id])
                        </form>
                    </div>
                </div>
            </div>

            @if ($editFailed)
                <script>
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('editDeclaration-{{ $declaration->id }}')).show());
                </script>
            @endif
        @endcan
    @endforeach
@endsection

@push('scripts')
    <script>
        $('#declarations-table').DataTable({ scrollX: false, order: [[0, 'desc']] });

        $(document).on('submit', '.js-delete-declaration', function (event) {
            event.preventDefault();
            const form = this;
            Swal.fire({
                icon: 'warning',
                title: 'Supprimer cette déclaration ?',
                text: $(form).data('name') + ' sera supprimée (récupérable). Le statut « Volée » de la moto sera recalculé.',
                showCancelButton: true,
                confirmButtonText: 'Supprimer',
                cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });
    </script>
@endpush

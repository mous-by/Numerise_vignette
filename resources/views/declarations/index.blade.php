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
            @php
                $countOf = fn ($type) => $declarations->filter(fn ($d) => $d->type === $type)->count();
                $stolenMotos = $declarations->filter(fn ($d) => $d->moto->is_stolen)->count();
            @endphp
            <div class="nv-chips mb-3" id="declarations-filter">
                <button type="button" class="nv-chip active" data-token="">Toutes <b>{{ $declarations->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="type-vol">Vols <b>{{ $countOf(\App\Enums\DeclarationType::Vol) }}</b></button>
                <button type="button" class="nv-chip" data-token="type-braquage">Braquages <b>{{ $countOf(\App\Enums\DeclarationType::Braquage) }}</b></button>
                <button type="button" class="nv-chip" data-token="type-autre">Autres <b>{{ $countOf(\App\Enums\DeclarationType::Autre) }}</b></button>
                <button type="button" class="nv-chip" data-token="moto-volee">Moto toujours volée <b>{{ $stolenMotos }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="declarations-table">
                    <thead>
                        <tr>
                            <th>ACTE</th>
                            <th>MOTO</th>
                            <th>LIEU</th>
                            <th>SITUATION</th>
                            <th width="12%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($declarations as $declaration)
                            <tr>
                                <td data-order="{{ $declaration->occurred_at->format('Y-m-d') }}">
                                    <div class="fw-semibold">{{ $declaration->type->label() }}</div>
                                    <div class="small text-muted">{{ $declaration->occurred_at->format('d/m/Y') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $declaration->moto->plate_number }}</div>
                                    <div class="small text-muted">{{ $declaration->moto->proprietaire->fullName() }}</div>
                                </td>
                                <td class="cell-wrap text-break">{{ $declaration->location }}</td>
                                <td>
                                    <span class="d-none">type-{{ $declaration->type->value }}{{ $declaration->moto->is_stolen ? ' moto-volee' : '' }}</span>
                                    @if ($declaration->moto->is_stolen)
                                        <span class="badge bg-danger-subtle">Moto volée</span>
                                    @else
                                        <span class="badge bg-success-subtle">Moto en règle</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ficheDeclaration-{{ $declaration->id }}" title="Fiche de la déclaration">
                                        <i class='bx bx-show'></i>
                                    </button>
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

    @foreach ($declarations as $declaration)
        @include('declarations._fiche', ['declaration' => $declaration])
    @endforeach

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
        const declarationsTable = $('#declarations-table').DataTable({ scrollX: false, order: [[0, 'desc']] });

        document.querySelectorAll('#declarations-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#declarations-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                declarationsTable.column(3).search(chip.dataset.token || '', false, true).draw();
            });
        });

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

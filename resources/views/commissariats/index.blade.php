@extends('layouts.admin')

@section('title', 'Commissariats')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Paramètres</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Commissariats</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="row">
        <div class="col-12 col-lg-3">
            @include('configuration._menu')
        </div>

        <div class="col-12 col-lg-9">
    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-buildings me-2'></i>COMMISSARIATS</h6>
            @can('create', \App\Models\Commissariat::class)
                <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createCommissariatModal">
                    <i class='bx bx-plus'></i> Ajouter
                </button>
            @endcan
        </div>
        <div class="card-body">
            @php($inactiveCount = $commissariats->where('is_active', false)->count())
            <div class="nv-chips mb-3" id="commissariats-filter">
                <button type="button" class="nv-chip active" data-token="">Tous <b>{{ $commissariats->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="etat-actif">Actifs <b>{{ $commissariats->count() - $inactiveCount }}</b></button>
                <button type="button" class="nv-chip" data-token="etat-inactif">Inactifs <b>{{ $inactiveCount }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="commissariats-table">
                    <thead>
                        <tr>
                            <th>NOM</th>
                            <th>CODE</th>
                            <th>UTILISATEURS</th>
                            <th>STATUT</th>
                            <th width="10%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($commissariats as $commissariat)
                            <tr>
                                <td class="fw-semibold">{{ $commissariat->name }}</td>
                                <td>{{ $commissariat->code ?? '—' }}</td>
                                <td>{{ $commissariat->users_count }}</td>
                                <td>
                                    <span class="d-none">{{ $commissariat->is_active ? 'etat-actif' : 'etat-inactif' }}</span>
                                    @if ($commissariat->is_active)
                                        <span class="badge bg-success-subtle">Actif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle">Inactif</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    @can('update', $commissariat)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCommissariat-{{ $commissariat->id }}" title="Modifier">
                                            <i class='bx bx-edit'></i>
                                        </button>
                                    @endcan
                                    @can('delete', $commissariat)
                                        <form method="POST" action="{{ route('commissariats.destroy', $commissariat) }}" class="js-delete-commissariat" data-name="{{ $commissariat->name }}">
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
        </div>
    </div>

    @can('create', \App\Models\Commissariat::class)
        <div class="modal fade" id="createCommissariatModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('commissariats.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class='bx bx-buildings me-2'></i>Nouveau commissariat</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        @php($createFailed = $errors->any() && ! old('commissariat_id'))
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Nom</label>
                                <input type="text" class="form-control @if ($createFailed && $errors->has('name')) is-invalid @endif" id="name" name="name" value="{{ $createFailed ? old('name') : '' }}" required>
                                @if ($createFailed && $errors->has('name'))<div class="invalid-feedback">{{ $errors->first('name') }}</div>@endif
                            </div>
                            <div class="mb-3">
                                <label for="code" class="form-label fw-semibold">Code</label>
                                <input type="text" class="form-control @if ($createFailed && $errors->has('code')) is-invalid @endif" id="code" name="code" value="{{ $createFailed ? old('code') : '' }}">
                                @if ($createFailed && $errors->has('code'))<div class="invalid-feedback">{{ $errors->first('code') }}</div>@endif
                                <small class="text-muted">Facultatif.</small>
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

        @if ($createFailed ?? false)
            <script>
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('createCommissariatModal')).show());
            </script>
        @endif
    @endcan

    @foreach ($commissariats as $commissariat)
        @can('update', $commissariat)
            <div class="modal fade" id="editCommissariat-{{ $commissariat->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('commissariats.update', $commissariat) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="commissariat_id" value="{{ $commissariat->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class='bx bx-buildings me-2'></i>Modifier — {{ $commissariat->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            @php($editFailed = $errors->any() && old('commissariat_id') == $commissariat->id)
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label for="name-{{ $commissariat->id }}" class="form-label fw-semibold">Nom</label>
                                    <input type="text" class="form-control @if ($editFailed && $errors->has('name')) is-invalid @endif" id="name-{{ $commissariat->id }}" name="name" value="{{ $editFailed ? old('name') : $commissariat->name }}" required>
                                    @if ($editFailed && $errors->has('name'))<div class="invalid-feedback">{{ $errors->first('name') }}</div>@endif
                                </div>
                                <div class="mb-3">
                                    <label for="code-{{ $commissariat->id }}" class="form-label fw-semibold">Code</label>
                                    <input type="text" class="form-control @if ($editFailed && $errors->has('code')) is-invalid @endif" id="code-{{ $commissariat->id }}" name="code" value="{{ $editFailed ? old('code') : $commissariat->code }}">
                                    @if ($editFailed && $errors->has('code'))<div class="invalid-feedback">{{ $errors->first('code') }}</div>@endif
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="active-{{ $commissariat->id }}" name="is_active" value="1" @checked($editFailed ? old('is_active') : $commissariat->is_active)>
                                    <label class="form-check-label" for="active-{{ $commissariat->id }}">Commissariat actif</label>
                                </div>
                                <small class="text-muted">Désactiver coupe immédiatement l'accès de tous les utilisateurs de ce commissariat.</small>
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
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('editCommissariat-{{ $commissariat->id }}')).show());
                </script>
            @endif
        @endcan
    @endforeach
@endsection

@push('scripts')
    <script>
        const commissariatsTable = $('#commissariats-table').DataTable({ scrollX: false });

        document.querySelectorAll('#commissariats-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#commissariats-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                commissariatsTable.column(3).search(chip.dataset.token || '', false, true).draw();
            });
        });

        $(document).on('submit', '.js-delete-commissariat', function (event) {
            event.preventDefault();
            const form = this;
            Swal.fire({
                icon: 'warning',
                title: 'Supprimer ce commissariat ?',
                text: $(form).data('name') + ' sera supprimé (récupérable). Impossible s\'il a encore des utilisateurs rattachés.',
                showCancelButton: true,
                confirmButtonText: 'Supprimer',
                cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });
    </script>
@endpush

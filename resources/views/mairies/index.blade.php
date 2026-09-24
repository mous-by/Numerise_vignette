@extends('layouts.admin')

@section('title', 'Mairies')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Paramètres</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mairies</li>
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
            <h6 class="mb-0 text-white"><i class='bx bx-building-house me-2'></i>MAIRIES</h6>
            @can('create', \App\Models\Mairie::class)
                <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createMairieModal">
                    <i class='bx bx-plus'></i> Ajouter
                </button>
            @endcan
        </div>
        <div class="card-body">
            @php($inactiveCount = $mairies->where('is_active', false)->count())
            <div class="nv-chips mb-3" id="mairies-filter">
                <button type="button" class="nv-chip active" data-token="">Toutes <b>{{ $mairies->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="etat-actif">Actives <b>{{ $mairies->count() - $inactiveCount }}</b></button>
                <button type="button" class="nv-chip" data-token="etat-inactif">Inactives <b>{{ $inactiveCount }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="mairies-table">
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
                        @foreach ($mairies as $mairie)
                            <tr>
                                <td class="fw-semibold">{{ $mairie->name }}</td>
                                <td>{{ $mairie->code ?? '—' }}</td>
                                <td>{{ $mairie->users_count }}</td>
                                <td>
                                    <span class="d-none">{{ $mairie->is_active ? 'etat-actif' : 'etat-inactif' }}</span>
                                    @if ($mairie->is_active)
                                        <span class="badge bg-success-subtle">Actif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle">Inactif</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    @can('update', $mairie)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editMairie-{{ $mairie->id }}" title="Modifier">
                                            <i class='bx bx-edit'></i>
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cardSettings-{{ $mairie->id }}" title="Carte VGT : modèle, logo et monument">
                                            <i class='bx bx-id-card'></i>
                                        </button>
                                    @endcan
                                    @can('delete', $mairie)
                                        <form method="POST" action="{{ route('mairies.destroy', $mairie) }}" class="js-delete-mairie" data-name="{{ $mairie->name }}">
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

    @can('create', \App\Models\Mairie::class)
        <div class="modal fade" id="createMairieModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('mairies.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class='bx bx-building-house me-2'></i>Nouvelle mairie</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        @php($createFailed = $errors->any() && ! old('mairie_id'))
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
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('createMairieModal')).show());
            </script>
        @endif
    @endcan

    @foreach ($mairies as $mairie)
        @can('update', $mairie)
            <div class="modal fade" id="editMairie-{{ $mairie->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('mairies.update', $mairie) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="mairie_id" value="{{ $mairie->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class='bx bx-building-house me-2'></i>Modifier — {{ $mairie->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            @php($editFailed = $errors->any() && old('mairie_id') == $mairie->id)
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label for="name-{{ $mairie->id }}" class="form-label fw-semibold">Nom</label>
                                    <input type="text" class="form-control @if ($editFailed && $errors->has('name')) is-invalid @endif" id="name-{{ $mairie->id }}" name="name" value="{{ $editFailed ? old('name') : $mairie->name }}" required>
                                    @if ($editFailed && $errors->has('name'))<div class="invalid-feedback">{{ $errors->first('name') }}</div>@endif
                                </div>
                                <div class="mb-3">
                                    <label for="code-{{ $mairie->id }}" class="form-label fw-semibold">Code</label>
                                    <input type="text" class="form-control @if ($editFailed && $errors->has('code')) is-invalid @endif" id="code-{{ $mairie->id }}" name="code" value="{{ $editFailed ? old('code') : $mairie->code }}">
                                    @if ($editFailed && $errors->has('code'))<div class="invalid-feedback">{{ $errors->first('code') }}</div>@endif
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="active-{{ $mairie->id }}" name="is_active" value="1" @checked($editFailed ? old('is_active') : $mairie->is_active)>
                                    <label class="form-check-label" for="active-{{ $mairie->id }}">Mairie active</label>
                                </div>
                                <small class="text-muted">Désactiver coupe immédiatement l'accès de tous les utilisateurs de cette mairie.</small>
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
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('editMairie-{{ $mairie->id }}')).show());
                </script>
            @endif

            <div class="modal fade" id="cardSettings-{{ $mairie->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class='bx bx-id-card me-2'></i>Carte VGT — {{ $mairie->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body p-4">
                            @include('demandes-vgt._card_settings_block', ['mairieOption' => $mairie, 'back' => 'mairies'])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>

            @if (($errors->has('logo') || $errors->has('monument')) && old('card_mairie') == $mairie->id)
                <script>
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('cardSettings-{{ $mairie->id }}')).show());
                </script>
            @endif
        @endcan
    @endforeach
@endsection

@push('scripts')
    <script>
        const mairiesTable = $('#mairies-table').DataTable({ scrollX: false });

        document.querySelectorAll('#mairies-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#mairies-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                mairiesTable.column(3).search(chip.dataset.token || '', false, true).draw();
            });
        });

        $(document).on('submit', '.js-delete-mairie', function (event) {
            event.preventDefault();
            const form = this;
            Swal.fire({
                icon: 'warning',
                title: 'Supprimer cette mairie ?',
                text: $(form).data('name') + ' sera supprimée (récupérable). Impossible si elle a encore des utilisateurs rattachés.',
                showCancelButton: true,
                confirmButtonText: 'Supprimer',
                cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });
    </script>
@endpush

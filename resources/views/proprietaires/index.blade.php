@extends('layouts.admin')

@section('title', 'Propriétaires')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Propriétaires</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Propriétaires</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />
    @include('partials.registre-tabs')

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-user-pin me-2'></i>PROPRIÉTAIRES</h6>
            @can('create', \App\Models\Proprietaire::class)
                <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createProprietaireModal">
                    <i class='bx bx-plus'></i> Ajouter
                </button>
            @endcan
        </div>
        <div class="card-body">
            @php
                $withMoto = $proprietaires->filter(fn ($p) => $p->motos->isNotEmpty())->count();
                $withStolen = $proprietaires->filter(fn ($p) => $p->motos->contains('is_stolen', true))->count();
            @endphp
            <div class="nv-chips mb-3" id="proprietaires-filter">
                <button type="button" class="nv-chip active" data-token="">Tous <b>{{ $proprietaires->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="avec-moto">Avec moto <b>{{ $withMoto }}</b></button>
                <button type="button" class="nv-chip" data-token="sans-moto">Sans moto <b>{{ $proprietaires->count() - $withMoto }}</b></button>
                <button type="button" class="nv-chip" data-token="moto-volee">Moto volée <b>{{ $withStolen }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="proprietaires-table">
                    <thead>
                        <tr>
                            <th>PROPRIÉTAIRE</th>
                            <th>CONTACT</th>
                            <th>ADRESSE</th>
                            <th>MOTOS</th>
                            <th width="12%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proprietaires as $proprietaire)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $proprietaire->fullName() }}</div>
                                    <div class="small text-muted">{{ $proprietaire->gender->label() }}</div>
                                </td>
                                <td>
                                    <div>{{ $proprietaire->phone }}</div>
                                    <div class="small text-muted">Urgence : {{ $proprietaire->emergency_contact ?? '—' }}</div>
                                </td>
                                <td class="cell-wrap text-break">{{ $proprietaire->address }}</td>
                                <td>
                                    <span class="d-none">{{ $proprietaire->motos->isNotEmpty() ? 'avec-moto' : 'sans-moto' }}{{ $proprietaire->motos->contains('is_stolen', true) ? ' moto-volee' : '' }}</span>
                                    @forelse ($proprietaire->motos as $moto)
                                        <span class="badge {{ $moto->is_stolen ? 'bg-danger-subtle' : 'bg-primary-subtle' }}" title="{{ $moto->is_stolen ? 'Déclarée volée' : ($moto->isVgtCurrent() ? 'Vignette à jour' : 'Vignette à renouveler') }}">{{ $moto->plate_number }}</span>
                                    @empty
                                        <span class="text-muted small">Aucune</span>
                                    @endforelse
                                </td>
                                <td class="d-flex gap-1">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ficheProprietaire-{{ $proprietaire->id }}" title="Fiche du propriétaire">
                                        <i class='bx bx-show'></i>
                                    </button>
                                    @can('update', $proprietaire)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editProprietaire-{{ $proprietaire->id }}" title="Modifier">
                                            <i class='bx bx-edit'></i>
                                        </button>
                                    @endcan
                                    @can('delete', $proprietaire)
                                        <form method="POST" action="{{ route('proprietaires.destroy', $proprietaire) }}" class="js-delete-proprietaire" data-name="{{ $proprietaire->fullName() }}">
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

    @foreach ($proprietaires as $proprietaire)
        @include('proprietaires._fiche', ['proprietaire' => $proprietaire])
    @endforeach
    @can('create', \App\Models\Proprietaire::class)
        <div class="modal fade" id="createProprietaireModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('proprietaires.store') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class='bx bx-user-pin me-2'></i>Nouveau propriétaire</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        @php($createFailed = $errors->any() && ! old('proprietaire_id'))
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="first_name" class="form-label fw-semibold">Prénom</label>
                                    <input type="text" class="form-control @if ($createFailed && $errors->has('first_name')) is-invalid @endif" id="first_name" name="first_name" value="{{ $createFailed ? old('first_name') : '' }}" required>
                                    @if ($createFailed && $errors->has('first_name'))<div class="invalid-feedback">{{ $errors->first('first_name') }}</div>@endif
                                </div>
                                <div class="col-6">
                                    <label for="last_name" class="form-label fw-semibold">Nom</label>
                                    <input type="text" class="form-control @if ($createFailed && $errors->has('last_name')) is-invalid @endif" id="last_name" name="last_name" value="{{ $createFailed ? old('last_name') : '' }}" required>
                                    @if ($createFailed && $errors->has('last_name'))<div class="invalid-feedback">{{ $errors->first('last_name') }}</div>@endif
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label for="gender" class="form-label fw-semibold">Genre</label>
                                <select class="form-select single-select" id="gender" name="gender" required>
                                    <option value="">— Choisir —</option>
                                    @foreach (\App\Enums\Genre::cases() as $genre)
                                        <option value="{{ $genre->value }}" @selected($createFailed && old('gender') === $genre->value)>{{ $genre->label() }}</option>
                                    @endforeach
                                </select>
                                @if ($createFailed && $errors->has('gender'))<div class="text-danger small mt-1">{{ $errors->first('gender') }}</div>@endif
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label fw-semibold">Adresse</label>
                                <input type="text" class="form-control @if ($createFailed && $errors->has('address')) is-invalid @endif" id="address" name="address" value="{{ $createFailed ? old('address') : '' }}" required>
                                @if ($createFailed && $errors->has('address'))<div class="invalid-feedback">{{ $errors->first('address') }}</div>@endif
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label fw-semibold">Numéro de téléphone</label>
                                <input type="text" class="form-control @if ($createFailed && $errors->has('phone')) is-invalid @endif" id="phone" name="phone" value="{{ $createFailed ? old('phone') : '' }}" placeholder="70 00 00 01" required>
                                @if ($createFailed && $errors->has('phone'))<div class="invalid-feedback">{{ $errors->first('phone') }}</div>@endif
                                <small class="text-muted">Identifié au nom du propriétaire (cahier).</small>
                            </div>
                            <div class="mb-3">
                                <label for="emergency_contact" class="form-label fw-semibold">Contact en cas d'urgence</label>
                                <input type="text" class="form-control @if ($createFailed && $errors->has('emergency_contact')) is-invalid @endif" id="emergency_contact" name="emergency_contact" value="{{ $createFailed ? old('emergency_contact') : '' }}" placeholder="70 00 00 01">
                                @if ($createFailed && $errors->has('emergency_contact'))<div class="invalid-feedback">{{ $errors->first('emergency_contact') }}</div>@endif
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
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('createProprietaireModal')).show());
            </script>
        @endif
    @endcan

    @foreach ($proprietaires as $proprietaire)
        @can('update', $proprietaire)
            @php($editFailed = $errors->any() && old('proprietaire_id') == $proprietaire->id)
            <div class="modal fade" id="editProprietaire-{{ $proprietaire->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('proprietaires.update', $proprietaire) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="proprietaire_id" value="{{ $proprietaire->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class='bx bx-user-pin me-2'></i>Modifier — {{ $proprietaire->fullName() }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label for="first_name-{{ $proprietaire->id }}" class="form-label fw-semibold">Prénom</label>
                                        <input type="text" class="form-control @if ($editFailed && $errors->has('first_name')) is-invalid @endif" id="first_name-{{ $proprietaire->id }}" name="first_name" value="{{ $editFailed ? old('first_name') : $proprietaire->first_name }}" required>
                                        @if ($editFailed && $errors->has('first_name'))<div class="invalid-feedback">{{ $errors->first('first_name') }}</div>@endif
                                    </div>
                                    <div class="col-6">
                                        <label for="last_name-{{ $proprietaire->id }}" class="form-label fw-semibold">Nom</label>
                                        <input type="text" class="form-control @if ($editFailed && $errors->has('last_name')) is-invalid @endif" id="last_name-{{ $proprietaire->id }}" name="last_name" value="{{ $editFailed ? old('last_name') : $proprietaire->last_name }}" required>
                                        @if ($editFailed && $errors->has('last_name'))<div class="invalid-feedback">{{ $errors->first('last_name') }}</div>@endif
                                    </div>
                                </div>
                                <div class="mb-3 mt-3">
                                    <label for="gender-{{ $proprietaire->id }}" class="form-label fw-semibold">Genre</label>
                                    <select class="form-select single-select" id="gender-{{ $proprietaire->id }}" name="gender" required>
                                        @foreach (\App\Enums\Genre::cases() as $genre)
                                            <option value="{{ $genre->value }}" @selected($editFailed ? old('gender') === $genre->value : $proprietaire->gender === $genre)>{{ $genre->label() }}</option>
                                        @endforeach
                                    </select>
                                    @if ($editFailed && $errors->has('gender'))<div class="text-danger small mt-1">{{ $errors->first('gender') }}</div>@endif
                                </div>
                                <div class="mb-3">
                                    <label for="address-{{ $proprietaire->id }}" class="form-label fw-semibold">Adresse</label>
                                    <input type="text" class="form-control @if ($editFailed && $errors->has('address')) is-invalid @endif" id="address-{{ $proprietaire->id }}" name="address" value="{{ $editFailed ? old('address') : $proprietaire->address }}" required>
                                    @if ($editFailed && $errors->has('address'))<div class="invalid-feedback">{{ $errors->first('address') }}</div>@endif
                                </div>
                                <div class="mb-3">
                                    <label for="phone-{{ $proprietaire->id }}" class="form-label fw-semibold">Numéro de téléphone</label>
                                    <input type="text" class="form-control @if ($editFailed && $errors->has('phone')) is-invalid @endif" id="phone-{{ $proprietaire->id }}" name="phone" value="{{ $editFailed ? old('phone') : $proprietaire->phone }}" required>
                                    @if ($editFailed && $errors->has('phone'))<div class="invalid-feedback">{{ $errors->first('phone') }}</div>@endif
                                </div>
                                <div class="mb-3">
                                    <label for="emergency_contact-{{ $proprietaire->id }}" class="form-label fw-semibold">Contact en cas d'urgence</label>
                                    <input type="text" class="form-control @if ($editFailed && $errors->has('emergency_contact')) is-invalid @endif" id="emergency_contact-{{ $proprietaire->id }}" name="emergency_contact" value="{{ $editFailed ? old('emergency_contact') : $proprietaire->emergency_contact }}">
                                    @if ($editFailed && $errors->has('emergency_contact'))<div class="invalid-feedback">{{ $errors->first('emergency_contact') }}</div>@endif
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
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('editProprietaire-{{ $proprietaire->id }}')).show());
                </script>
            @endif
        @endcan
    @endforeach
@endsection

@push('scripts')
    <script>
        const proprietairesTable = $('#proprietaires-table').DataTable({ scrollX: false });

        // Filtre par pastille : le jeton (avec-moto, sans-moto, moto-volee) est caché dans la colonne « Motos ».
        document.querySelectorAll('#proprietaires-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#proprietaires-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                proprietairesTable.column(3).search(chip.dataset.token || '', false, true).draw();
            });
        });

        $(document).on('submit', '.js-delete-proprietaire', function (event) {
            event.preventDefault();
            const form = this;
            Swal.fire({
                icon: 'warning',
                title: 'Supprimer ce propriétaire ?',
                text: $(form).data('name') + ' sera supprimé (récupérable).',
                showCancelButton: true,
                confirmButtonText: 'Supprimer',
                cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });
    </script>
@endpush

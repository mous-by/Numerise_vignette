@extends('layouts.admin')

@section('title', 'Motos')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Motos</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Motos</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />
    @include('partials.registre-tabs')

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-cycling me-2'></i>MOTOS</h6>
            @can('create', \App\Models\Moto::class)
                @if (! $hasProprietaires)
                    <span class="badge bg-light text-dark">Créez d'abord un propriétaire</span>
                @else
                    <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createMotoModal">
                        <i class='bx bx-plus'></i> Ajouter
                    </button>
                @endif
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="motos-table">
                    <thead>
                        <tr>
                            <th>MATRICULE</th>
                            <th>PROPRIÉTAIRE</th>
                            <th>COULEUR</th>
                            <th>GENRE / MARQUE</th>
                            <th>ANNÉE VGT</th>
                            <th>ATTESTATION</th>
                            <th width="10%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($motos as $moto)
                            <tr>
                                <td class="fw-semibold">{{ $moto->plate_number }}</td>
                                <td>{{ $moto->proprietaire->fullName() }}</td>
                                <td>{{ $moto->color }}</td>
                                <td>{{ $moto->type_or_brand }}</td>
                                <td>{{ $moto->vgt_year }}</td>
                                <td>
                                    @if ($moto->has_sale_certificate)
                                        <span class="badge bg-success-subtle text-success">Oui</span>
                                    @else
                                        <span class="badge bg-light text-dark">Non</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    @can('update', $moto)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editMoto-{{ $moto->id }}" title="Modifier">
                                            <i class='bx bx-edit'></i>
                                        </button>
                                    @endcan
                                    @can('delete', $moto)
                                        <form method="POST" action="{{ route('motos.destroy', $moto) }}" class="js-delete-moto" data-name="{{ $moto->plate_number }}">
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

    @can('create', \App\Models\Moto::class)
        @php($proprietaireCreateFailed = $errors->any() && $errors->has('first_name'))
        @php($createFailed = $errors->any() && ! old('moto_id') && ! $proprietaireCreateFailed)
        {{-- Revenue de la modale « + Nouveau propriétaire » (ProprietaireController::store, return_to=motos) : la
             recherche AJAX du select ne connaît pas encore ce propriétaire tout juste créé, on le pré-rend. --}}
        @php($preselectedProprietaire = request()->filled('new_proprietaire') ? \App\Models\Proprietaire::find(request()->query('new_proprietaire')) : null)
        <div class="modal fade" id="createMotoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('motos.store') }}">
                        @csrf
                        @include('motos._form', ['moto' => null, 'failed' => $createFailed, 'idSuffix' => '', 'preselectedProprietaire' => $preselectedProprietaire])
                    </form>
                </div>
            </div>
        </div>

        @if ($createFailed)
            <script>
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('createMotoModal')).show());
            </script>
        @endif

        {{-- Créer un propriétaire sans quitter l'écran Motos : ouverte depuis le bouton « + Nouveau » de la
             modale de création, elle renvoie ensuite ici avec le propriétaire présélectionné (contrôleur W7). --}}
        @can('create', \App\Models\Proprietaire::class)
        <div class="modal fade" id="createProprietaireFromMotoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('proprietaires.store') }}">
                        @csrf
                        <input type="hidden" name="return_to" value="motos">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class='bx bx-user-pin me-2'></i>Nouveau propriétaire</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="moto_prop_first_name" class="form-label fw-semibold">Prénom</label>
                                    <input type="text" class="form-control @if ($proprietaireCreateFailed && $errors->has('first_name')) is-invalid @endif" id="moto_prop_first_name" name="first_name" value="{{ $proprietaireCreateFailed ? old('first_name') : '' }}" required>
                                    @if ($proprietaireCreateFailed && $errors->has('first_name'))<div class="invalid-feedback">{{ $errors->first('first_name') }}</div>@endif
                                </div>
                                <div class="col-6">
                                    <label for="moto_prop_last_name" class="form-label fw-semibold">Nom</label>
                                    <input type="text" class="form-control @if ($proprietaireCreateFailed && $errors->has('last_name')) is-invalid @endif" id="moto_prop_last_name" name="last_name" value="{{ $proprietaireCreateFailed ? old('last_name') : '' }}" required>
                                    @if ($proprietaireCreateFailed && $errors->has('last_name'))<div class="invalid-feedback">{{ $errors->first('last_name') }}</div>@endif
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label for="moto_prop_gender" class="form-label fw-semibold">Genre</label>
                                <select class="form-select single-select" id="moto_prop_gender" name="gender" required>
                                    <option value="">— Choisir —</option>
                                    @foreach (\App\Enums\Genre::cases() as $genre)
                                        <option value="{{ $genre->value }}" @selected($proprietaireCreateFailed && old('gender') === $genre->value)>{{ $genre->label() }}</option>
                                    @endforeach
                                </select>
                                @if ($proprietaireCreateFailed && $errors->has('gender'))<div class="text-danger small mt-1">{{ $errors->first('gender') }}</div>@endif
                            </div>
                            <div class="mb-3">
                                <label for="moto_prop_address" class="form-label fw-semibold">Adresse</label>
                                <input type="text" class="form-control @if ($proprietaireCreateFailed && $errors->has('address')) is-invalid @endif" id="moto_prop_address" name="address" value="{{ $proprietaireCreateFailed ? old('address') : '' }}" required>
                                @if ($proprietaireCreateFailed && $errors->has('address'))<div class="invalid-feedback">{{ $errors->first('address') }}</div>@endif
                            </div>
                            <div class="mb-3">
                                <label for="moto_prop_phone" class="form-label fw-semibold">Numéro de téléphone</label>
                                <input type="text" class="form-control @if ($proprietaireCreateFailed && $errors->has('phone')) is-invalid @endif" id="moto_prop_phone" name="phone" value="{{ $proprietaireCreateFailed ? old('phone') : '' }}" placeholder="70 00 00 01" required>
                                @if ($proprietaireCreateFailed && $errors->has('phone'))<div class="invalid-feedback">{{ $errors->first('phone') }}</div>@endif
                            </div>
                            <div class="mb-3">
                                <label for="moto_prop_emergency_contact" class="form-label fw-semibold">Contact en cas d'urgence</label>
                                <input type="text" class="form-control @if ($proprietaireCreateFailed && $errors->has('emergency_contact')) is-invalid @endif" id="moto_prop_emergency_contact" name="emergency_contact" value="{{ $proprietaireCreateFailed ? old('emergency_contact') : '' }}" placeholder="70 00 00 01">
                                @if ($proprietaireCreateFailed && $errors->has('emergency_contact'))<div class="invalid-feedback">{{ $errors->first('emergency_contact') }}</div>@endif
                                <small class="text-muted">Facultatif.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Retour à la moto</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($proprietaireCreateFailed)
            <script>
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('createProprietaireFromMotoModal')).show());
            </script>
        @endif
        @endcan
    @endcan

    @foreach ($motos as $moto)
        @can('update', $moto)
            @php($editFailed = $errors->any() && old('moto_id') == $moto->id)
            <div class="modal fade" id="editMoto-{{ $moto->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('motos.update', $moto) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="moto_id" value="{{ $moto->id }}">
                            @include('motos._form', ['moto' => $moto, 'failed' => $editFailed, 'idSuffix' => '-'.$moto->id])
                        </form>
                    </div>
                </div>
            </div>

            @if ($editFailed)
                <script>
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('editMoto-{{ $moto->id }}')).show());
                </script>
            @endif
        @endcan
    @endforeach
@endsection

@push('scripts')
    <script>
        $('#motos-table').DataTable({ scrollX: false });

        $(document).on('submit', '.js-delete-moto', function (event) {
            event.preventDefault();
            const form = this;
            Swal.fire({
                icon: 'warning',
                title: 'Supprimer cette moto ?',
                text: $(form).data('name') + ' sera supprimée (récupérable).',
                showCancelButton: true,
                confirmButtonText: 'Supprimer',
                cancelButtonText: 'Annuler',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });

        function toggleMotoConditionalFields(suffix) {
            const saleBox = document.getElementById('has_sale_certificate' + suffix);
            const witnessBox = document.getElementById('has_witness' + suffix);
            const saleFields = document.getElementById('seller-fields' + suffix);
            const witnessFields = document.getElementById('witness-fields' + suffix);
            const witnessToggleWrap = document.getElementById('witness-toggle' + suffix);

            const saleChecked = saleBox.checked;
            saleFields.classList.toggle('d-none', !saleChecked);
            witnessToggleWrap.classList.toggle('d-none', !saleChecked);
            if (!saleChecked) { witnessBox.checked = false; }
            witnessFields.classList.toggle('d-none', !witnessBox.checked);
        }

        document.querySelectorAll('[data-moto-suffix]').forEach((wrapper) => {
            const suffix = wrapper.dataset.motoSuffix;
            toggleMotoConditionalFields(suffix);
            document.getElementById('has_sale_certificate' + suffix).addEventListener('change', () => toggleMotoConditionalFields(suffix));
            document.getElementById('has_witness' + suffix).addEventListener('change', () => toggleMotoConditionalFields(suffix));
        });

        // Créer un propriétaire sans quitter la moto : bascule entre les deux modales, présélection au retour.
        const motoModalEl = document.getElementById('createMotoModal');
        const propModalEl = document.getElementById('createProprietaireFromMotoModal');

        if (motoModalEl && propModalEl) {
            document.getElementById('add-proprietaire-btn')?.addEventListener('click', () => {
                bootstrap.Modal.getInstance(motoModalEl)?.hide();
            });
            motoModalEl.addEventListener('hidden.bs.modal', () => {
                if (motoModalEl.dataset.openProprietaireNext === '1') {
                    motoModalEl.dataset.openProprietaireNext = '';
                    new bootstrap.Modal(propModalEl).show();
                }
            });
            document.getElementById('add-proprietaire-btn')?.addEventListener('click', () => {
                motoModalEl.dataset.openProprietaireNext = '1';
            });
            propModalEl.addEventListener('hidden.bs.modal', () => {
                new bootstrap.Modal(motoModalEl).show();
            });
        }

        // Retour depuis la création du propriétaire (?new_proprietaire=ID) : la modale moto le pré-affiche déjà
        // (côté serveur, la recherche AJAX ne le connaît pas encore) — on rouvre juste la modale et nettoie l'URL.
        if (new URLSearchParams(window.location.search).get('new_proprietaire') && motoModalEl) {
            new bootstrap.Modal(motoModalEl).show();
            window.history.replaceState({}, '', window.location.pathname);
        }
    </script>
@endpush

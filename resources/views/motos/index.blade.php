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

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-cycling me-2'></i>MOTOS</h6>
            @can('create', \App\Models\Moto::class)
                @if ($proprietaires->isEmpty())
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
        @php($createFailed = $errors->any() && ! old('moto_id'))
        <div class="modal fade" id="createMotoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ route('motos.store') }}">
                        @csrf
                        @include('motos._form', ['moto' => null, 'failed' => $createFailed, 'idSuffix' => ''])
                    </form>
                </div>
            </div>
        </div>

        @if ($createFailed)
            <script>
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('createMotoModal')).show());
            </script>
        @endif
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
    </script>
@endpush

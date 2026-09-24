@extends('layouts.admin')

@section('title', 'Motos retrouvées')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Motos retrouvées</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Motos retrouvées</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />
    @include('partials.registre-tabs')

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-search-alt me-2'></i>MOTOS RETROUVÉES</h6>
            @can('create', \App\Models\MotoRetrouvee::class)
                <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createMotoRetrouveeModal">
                    <i class='bx bx-plus'></i> Ajouter
                </button>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" id="motos-retrouvees-table">
                    <thead>
                        <tr>
                            <th>DATE D'ARRÊT</th>
                            <th>MATRICULE</th>
                            <th>PROPRIÉTAIRE</th>
                            <th>LIEU</th>
                            <th>STATUT</th>
                            <th width="10%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($motosRetrouvees as $motoRetrouvee)
                            <tr>
                                <td>{{ $motoRetrouvee->found_at->format('d/m/Y') }}</td>
                                <td class="fw-semibold">{{ $motoRetrouvee->moto->plate_number }}</td>
                                <td>{{ $motoRetrouvee->moto->proprietaire->fullName() }}</td>
                                <td class="cell-wrap text-break">{{ $motoRetrouvee->location }}</td>
                                <td>
                                    @if ($motoRetrouvee->recovered)
                                        <span class="badge bg-success-subtle text-success">Récupérée le {{ $motoRetrouvee->recovered_at->format('d/m/Y') }}</span>
                                    @else
                                        <span class="badge bg-light text-dark">En attente</span>
                                    @endif
                                </td>
                                <td>
                                    @can('update', $motoRetrouvee)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editMotoRetrouvee-{{ $motoRetrouvee->id }}" title="Modifier">
                                            <i class='bx bx-edit'></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('create', \App\Models\MotoRetrouvee::class)
        @php($createFailed = $errors->any() && ! old('moto_retrouvee_id'))
        <div class="modal fade" id="createMotoRetrouveeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('motos-retrouvees.store') }}">
                        @csrf
                        @include('motos-retrouvees._form', ['motoRetrouvee' => null, 'failed' => $createFailed, 'idSuffix' => ''])
                    </form>
                </div>
            </div>
        </div>

        @if ($createFailed)
            <script>
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('createMotoRetrouveeModal')).show());
            </script>
        @endif
    @endcan

    @foreach ($motosRetrouvees as $motoRetrouvee)
        @can('update', $motoRetrouvee)
            @php($editFailed = $errors->any() && old('moto_retrouvee_id') == $motoRetrouvee->id)
            <div class="modal fade" id="editMotoRetrouvee-{{ $motoRetrouvee->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('motos-retrouvees.update', $motoRetrouvee) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="moto_retrouvee_id" value="{{ $motoRetrouvee->id }}">
                            @include('motos-retrouvees._form', ['motoRetrouvee' => $motoRetrouvee, 'failed' => $editFailed, 'idSuffix' => '-'.$motoRetrouvee->id])
                        </form>
                    </div>
                </div>
            </div>

            @if ($editFailed)
                <script>
                    window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('editMotoRetrouvee-{{ $motoRetrouvee->id }}')).show());
                </script>
            @endif
        @endcan
    @endforeach
@endsection

@push('scripts')
    <script>
        $('#motos-retrouvees-table').DataTable({ scrollX: false, order: [[0, 'desc']] });

        function toggleRecoveredField(suffix) {
            const box = document.getElementById('recovered' + suffix);
            const field = document.getElementById('recovered_at_wrap' + suffix);
            if (box && field) { field.classList.toggle('d-none', !box.checked); }
        }

        document.querySelectorAll('[data-recovered-suffix]').forEach((wrapper) => {
            const suffix = wrapper.dataset.recoveredSuffix;
            toggleRecoveredField(suffix);
            document.getElementById('recovered' + suffix).addEventListener('change', () => toggleRecoveredField(suffix));
        });
    </script>
@endpush

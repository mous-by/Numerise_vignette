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
            @php
                $recoveredCount = $motosRetrouvees->where('recovered', true)->count();
            @endphp
            <div class="nv-chips mb-3" id="motos-retrouvees-filter">
                <button type="button" class="nv-chip active" data-token="">Toutes <b>{{ $motosRetrouvees->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="en-attente">En attente de récupération <b>{{ $motosRetrouvees->count() - $recoveredCount }}</b></button>
                <button type="button" class="nv-chip" data-token="recuperee">Récupérées <b>{{ $recoveredCount }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="motos-retrouvees-table">
                    <thead>
                        <tr>
                            <th>MOTO</th>
                            <th>PROPRIÉTAIRE</th>
                            <th>ARRÊT</th>
                            <th>STATUT</th>
                            <th width="12%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($motosRetrouvees as $motoRetrouvee)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $motoRetrouvee->moto->plate_number }}</div>
                                    <div class="small text-muted">{{ $motoRetrouvee->moto->type_or_brand }} · {{ $motoRetrouvee->moto->color }}</div>
                                </td>
                                <td>
                                    <div>{{ $motoRetrouvee->moto->proprietaire->fullName() }}</div>
                                    <div class="small text-muted">{{ $motoRetrouvee->moto->proprietaire->phone }}</div>
                                </td>
                                <td data-order="{{ $motoRetrouvee->found_at->format('Y-m-d') }}" class="cell-wrap text-break">
                                    <div>{{ $motoRetrouvee->location }}</div>
                                    <div class="small text-muted">{{ $motoRetrouvee->found_at->format('d/m/Y') }}</div>
                                </td>
                                <td>
                                    <span class="d-none">{{ $motoRetrouvee->recovered ? 'recuperee' : 'en-attente' }}</span>
                                    @if ($motoRetrouvee->recovered)
                                        <span class="badge bg-success-subtle">Récupérée le {{ $motoRetrouvee->recovered_at->format('d/m/Y') }}</span>
                                    @else
                                        <span class="badge bg-warning-subtle">En attente</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ficheMotoRetrouvee-{{ $motoRetrouvee->id }}" title="Fiche">
                                        <i class='bx bx-show'></i>
                                    </button>
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

    @foreach ($motosRetrouvees as $motoRetrouvee)
        @include('motos-retrouvees._fiche', ['motoRetrouvee' => $motoRetrouvee])
    @endforeach
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
        const motosRetrouveesTable = $('#motos-retrouvees-table').DataTable({ scrollX: false, order: [[2, 'desc']] });

        document.querySelectorAll('#motos-retrouvees-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#motos-retrouvees-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                motosRetrouveesTable.column(3).search(chip.dataset.token || '', false, true).draw();
            });
        });

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

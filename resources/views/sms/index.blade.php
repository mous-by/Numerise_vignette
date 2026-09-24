@extends('layouts.admin')

@section('title', 'Notifications SMS')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Notifications SMS</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Notifications SMS</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @if (config('sms.driver') === 'log')
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class='bx bx-test-tube fs-4'></i>
            <div><strong>Envoi simulé.</strong> Aucun opérateur SMS n'est configuré (point ouvert client) : les messages sont enregistrés ici et écrits dans le journal, mais personne ne les reçoit.</div>
        </div>
    @endif

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-message-rounded-dots me-2'></i>JOURNAL DES SMS</h6>
        </div>
        <div class="card-body">
            <div class="nv-chips mb-3" id="sms-status-filter">
                <button type="button" class="nv-chip active" data-status="">Tous <b>{{ $messages->count() }}</b></button>
                @foreach ($statuses as $status)
                    <button type="button" class="nv-chip" data-status="{{ $status->label() }}">{{ $status->label() }} <b>{{ $counts[$status->value] ?? 0 }}</b></button>
                @endforeach
            </div>
            <div class="table-responsive">
                <table class="table" id="sms-table">
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>ÉVÉNEMENT</th>
                            <th>DESTINATAIRE</th>
                            <th>MESSAGE</th>
                            <th>STATUT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($messages as $sms)
                            <tr>
                                <td>{{ $sms->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $sms->event->label() }}</td>
                                <td>{{ $sms->phone }}</td>
                                <td class="cell-wrap text-break">{{ $sms->message }}</td>
                                <td>
                                    <span class="badge {{ $sms->status->badgeClass() }}">{{ $sms->status->label() }}</span>
                                    @if ($sms->error)<div class="small text-danger cell-wrap text-break">{{ $sms->error }}</div>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const smsTable = $('#sms-table').DataTable({ scrollX: false, order: [[0, 'desc']] });
        document.querySelectorAll('#sms-status-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#sms-status-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                smsTable.column(4).search(chip.dataset.status || '', false, true).draw();
            });
        });
    </script>
@endpush

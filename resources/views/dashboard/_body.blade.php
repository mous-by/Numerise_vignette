{{-- Corps commun des deux tableaux de bord (D22). $dashboard : voir App\Services\Dashboard\DashboardService. --}}
@if ($dashboard['mock'])
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class='bx bx-test-tube fs-4'></i>
        <div>
            <strong>Données fictives.</strong> Maquette du tableau de bord : les modules métier ne sont pas encore implémentés,
            aucun de ces chiffres ni de ces noms n'est réel.
        </div>
    </div>
@endif

@foreach ($dashboard['alerts'] as $alert)
    <div class="alert {{ $alert['class'] }} d-flex align-items-center gap-2">
        <i class='{{ $alert['icon'] }} fs-4'></i>
        <div><strong>{{ $alert['title'] }}</strong> {{ $alert['text'] }}</div>
    </div>
@endforeach

@if (! empty($dashboard['cards']))
    <h6 class="mb-0 text-uppercase">{{ $heading }}</h6>
    <hr />
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3">
        @foreach ($dashboard['cards'] as $card)
            @php($text = $card['darkText'] ? 'text-dark' : 'text-white')
            <div class="col mb-3">
                <div class="card radius-10 bg-{{ $card['color'] }} bg-gradient">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 {{ $text }}">{{ $card['label'] }}</p>
                                <h4 class="my-1 {{ $text }}">{{ $card['value'] }}</h4>
                                @if ($card['sub'])
                                    <p class="mb-0 {{ $text }} small">{{ $card['sub'] }}</p>
                                @endif
                            </div>
                            <div class="{{ $text }} ms-auto font-35"><i class='{{ $card['icon'] }}'></i></div>
                        </div>
                        @if ($card['module'])
                            <a href="{{ route('modules.show', $card['module']) }}" class="badge bg-light text-dark mt-2 text-decoration-none">Module à venir</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@if (! empty($dashboard['quick']))
    <div class="card">
        <div class="card-header card-header-brand">
            <h6 class="text-white mb-0"><i class='bx bx-bolt-circle me-2'></i>ACCÈS RAPIDES</h6>
        </div>
        <div class="card-body d-flex flex-wrap gap-2">
            @foreach ($dashboard['quick'] as $link)
                @if (Route::has($link['route']))
                    <a href="{{ route($link['route'], $link['params'] ?? []) }}" class="btn btn-outline-primary">
                        <i class='{{ $link['icon'] }} me-1'></i>{{ $link['label'] }}
                        @if (! empty($link['planned']))<span class="badge bg-warning text-dark ms-1">À venir</span>@endif
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endif

@if (! empty($dashboard['tables']))
    <h6 class="mb-0 text-uppercase mt-2">Aperçu rapide</h6>
    <hr />
    <div class="row">
        @foreach ($dashboard['tables'] as $table)
            <div class="col-xl-{{ count($dashboard['tables']) === 1 ? 12 : 6 }}">
                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="text-white mb-0"><i class='{{ $table['icon'] }} me-2'></i>{{ $table['title'] }}</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm dash-table">
                            <thead>
                                <tr>
                                    @foreach ($table['columns'] as $column)
                                        <th>{{ $column }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($table['rows'] as $row)
                                    <tr>
                                        @foreach ($row as $cell)
                                            <td>
                                                @if (is_array($cell))
                                                    <span class="badge {{ $cell['class'] }}">{{ $cell['badge'] }}</span>
                                                @else
                                                    {{ $cell }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@if (empty($dashboard['cards']) && empty($dashboard['tables']) && empty($dashboard['quick']))
    <div class="card">
        <div class="card-body text-center py-5">
            <i class='bx bx-hourglass fs-1 text-primary'></i>
            <h5 class="mt-2">Bienvenue, {{ auth()->user()->name }}</h5>
            <p class="text-muted mb-0">Votre espace s'enrichira avec les modules à venir (voir le menu « Modules à venir »).</p>
        </div>
    </div>
@endif

@push('scripts')
    <script>
        $('.dash-table').DataTable({
            pageLength: 5,
            lengthChange: false,
            info: false,
            language: { search: 'Filtrer :', paginate: { previous: '‹', next: '›' }, zeroRecords: 'Rien à afficher', emptyTable: 'Aucune donnée' }
        });
    </script>
@endpush

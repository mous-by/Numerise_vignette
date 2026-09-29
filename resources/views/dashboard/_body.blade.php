@if (! empty($dashboard['hero']))
    <div class="nv-hero mb-4">
        <div>
            <div class="nv-hero-date">{{ $dashboard['hero']['date'] }}</div>
            <h3 class="nv-hero-title">{{ $dashboard['hero']['greeting'] }}</h3>
            @if ($dashboard['hero']['subtitle'])
                <div class="nv-hero-sub"><i class='bx bx-briefcase-alt-2 me-1'></i>{{ $dashboard['hero']['subtitle'] }}</div>
            @endif
        </div>
        @if (! empty($dashboard['quick']))
            <div class="nv-hero-actions">
                @foreach (array_slice($dashboard['quick'], 0, 3) as $link)
                    @if (Route::has($link['route']))
                        <a href="{{ route($link['route'], $link['params'] ?? []) }}" class="btn btn-light btn-sm"><i class='{{ $link['icon'] }} me-1'></i>{{ $link['label'] }}</a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endif
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

@if (! empty($dashboard['tasks']))
    @php($openTasks = collect($dashboard['tasks'])->sum('count'))
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="mb-0 text-uppercase nv-section-title"><i class='bx bx-task me-1'></i>À traiter maintenant</h6>
        <span class="badge {{ $openTasks > 0 ? 'bg-warning-subtle' : 'bg-success-subtle' }}">{{ $openTasks > 0 ? $openTasks.' en attente' : 'Tout est à jour' }}</span>
    </div>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        @foreach ($dashboard['tasks'] as $task)
            <div class="col">
                <a href="{{ route($task['route'], $task['params'] ?? []) }}" class="nv-task nv-task-{{ $task['color'] }} {{ $task['count'] === 0 ? 'is-empty' : '' }}">
                    <span class="nv-task-icon"><i class='{{ $task['icon'] }}'></i></span>
                    <span class="nv-task-body">
                        <span class="nv-task-count">{{ $task['count'] }}</span>
                        <span class="nv-task-label">{{ $task['label'] }}</span>
                        <span class="nv-task-hint">{{ $task['hint'] }}</span>
                    </span>
                    <i class='bx bx-right-arrow-alt nv-task-go'></i>
                </a>
            </div>
        @endforeach
    </div>
@endif
@if (! empty($dashboard['cards']))
    <h6 class="mb-2 text-uppercase nv-section-title"><i class='bx bx-bar-chart-alt-2 me-1'></i>{{ $heading }}</h6>
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

@if (! empty($dashboard['pipeline']) && $dashboard['pipeline']['total'] > 0)
    <div class="card mb-4">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between">
            <h6 class="text-white mb-0"><i class='bx bx-git-merge me-2'></i>PARCOURS DES DEMANDES VGT</h6>
            <span class="small text-white-50">{{ $dashboard['pipeline']['total'] }} demande(s)</span>
        </div>
        <div class="card-body">
            <div class="nv-pipeline mb-3">
                @foreach ($dashboard['pipeline']['segments'] as $segment)
                    @if ($segment['count'] > 0)
                        <a href="{{ route($segment['route'], $segment['params']) }}" class="nv-pipe nv-pipe-{{ $segment['class'] }}" style="flex: {{ $segment['count'] }}" title="{{ $segment['label'] }} : {{ $segment['count'] }}">{{ $segment['count'] }}</a>
                    @endif
                @endforeach
            </div>
            <div class="nv-legend">
                @foreach ($dashboard['pipeline']['segments'] as $segment)
                    <a href="{{ route($segment['route'], $segment['params']) }}" class="nv-legend-item"><span class="nv-dot nv-pipe-{{ $segment['class'] }}"></span>{{ $segment['label'] }} <b>{{ $segment['count'] }}</b><small>{{ $segment['percent'] }} %</small></a>
                @endforeach
            </div>
        </div>
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
    <h6 class="mb-2 text-uppercase mt-2 nv-section-title"><i class='bx bx-time-five me-1'></i>Activité récente</h6>
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

{{-- Onglets du registre (config/sidebar.php) : un seul lien dans le menu, ces onglets relient les écrans voisins. --}}
@php
    $registreTabs = collect(config('sidebar.registre.tabs'))
        ->filter(fn ($tab) => \Illuminate\Support\Facades\Route::has($tab['route']) && auth()->user()->can($tab['permission']));
@endphp
@if ($registreTabs->count() > 1)
    <nav class="nv-tabs mb-3" aria-label="Registre">
        @foreach ($registreTabs as $tab)
            <a href="{{ route($tab['route']) }}" class="nv-tab {{ request()->routeIs($tab['active']) ? 'active' : '' }}">
                <i class='{{ $tab['icon'] }}'></i>{{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
@endif

<div class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
        <div>
            <span class="logo-icon logo-3d-mini">{{ config('brand.mini') }}</span>
        </div>
        <div>
            <h4 class="logo-text logo-3d mb-0"><span class="w3d-w">{{ config('brand.logo.first') }}</span><span class="w3d-n">{{ config('brand.logo.second') }}</span></h4>
        </div>
        <div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left'></i></div>
    </div>

    <ul class="metismenu" id="menu">
        <li>
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'mm-active' : '' }}">
                <div class="parent-icon"><i class='bx bx-home-circle'></i></div>
                <div class="menu-title">Tableau de bord</div>
            </a>
        </li>

        {{-- Entrées déclarées par les manifestes config/modules/*.php, filtrées par permission (ModuleRegistry). --}}
        @php
            $navigation = collect($navigation ?? []);
            $bottomNavigation = $navigation->filter(fn ($item) => ($item['position'] ?? 'main') === 'bottom');
        @endphp
        @foreach ($navigation->reject(fn ($item) => ($item['position'] ?? 'main') === 'bottom') as $item)
            <li>
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs(...(array) $item['active']) ? 'mm-active' : '' }}">
                    <div class="parent-icon"><i class='{{ $item['icon'] }}'></i></div>
                    <div class="menu-title">{{ $item['label'] }}</div>
                </a>
            </li>
        @endforeach

        {{-- Modules métier prévus, non implémentés (D25) : simple liste pour repérer ce qui reste à construire. --}}
        @if (! empty($plannedModules))
            <li class="menu-label">Modules à venir</li>
            @foreach ($plannedModules as $key => $planned)
                <li class="planned">
                    <a href="{{ route('modules.show', $key) }}" class="{{ request()->is('modules/'.$key) ? 'mm-active' : '' }}">
                        <div class="parent-icon"><i class='{{ $planned['icon'] }}'></i></div>
                        <div class="menu-title">{{ $planned['label'] }}</div>
                        <span class="planned-badge">À venir</span>
                    </a>
                </li>
            @endforeach
        @endif

        {{-- Paramètres : toujours sous tous les autres menus. --}}
        @foreach ($bottomNavigation as $item)
            <li class="sidebar-bottom">
                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs(...(array) $item['active']) ? 'mm-active' : '' }}">
                    <div class="parent-icon"><i class='{{ $item['icon'] }}'></i></div>
                    <div class="menu-title">{{ $item['label'] }}</div>
                </a>
            </li>
        @endforeach
    </ul>
</div>

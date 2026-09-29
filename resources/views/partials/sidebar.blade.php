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

    {{-- Entrées déclarées par les manifestes config/modules/*.php, filtrées par permission (ModuleRegistry),
         rangées ici par domaine : registre, vignettes, administration. Aucun manifeste n'est modifié. --}}
    @php
        $navigation = collect($navigation ?? []);
        $bottomNavigation = $navigation->filter(fn ($item) => ($item['position'] ?? 'main') === 'bottom');
        $groupOf = [
            'proprietaires' => 'Registre', 'motos' => 'Registre', 'declarations' => 'Registre', 'motos-retrouvees' => 'Registre',
            'demandes-vgt' => 'Vignettes VGT',
            'users' => 'Administration', 'commissariats' => 'Administration', 'mairies' => 'Administration', 'sms' => 'Administration',
        ];
        $groupIcons = ['Registre' => 'bx bx-folder-open', 'Vignettes VGT' => 'bx bx-id-card', 'Administration' => 'bx bx-buildings'];
        // Le registre (propriétaires, motos, déclarations, motos retrouvées) tient en une seule entrée : les écrans
        // voisins se rejoignent par des onglets (partials/registre-tabs, config/sidebar.php).
        $registreKeys = collect(config('sidebar.registre.tabs'))->map(fn ($tab) => explode('.', $tab['route'])[0])->all();
        $registreItems = $navigation->filter(fn ($item) => in_array(explode('.', $item['route'])[0], $registreKeys, true));
        $mergedNavigation = $navigation->reject(fn ($item) => in_array(explode('.', $item['route'])[0], $registreKeys, true));
        if ($registreItems->isNotEmpty()) {
            $landing = config('sidebar.registre.landing');
            $mergedNavigation->push([
                'label' => config('sidebar.registre.label'), 'icon' => config('sidebar.registre.icon'),
                'route' => $registreItems->contains(fn ($item) => $item['route'] === $landing) ? $landing : $registreItems->first()['route'],
                'active' => $registreItems->flatMap(fn ($item) => (array) $item['active'])->all(), 'order' => $registreItems->min('order'),
            ]);
            $mergedNavigation = $mergedNavigation->sortBy('order')->values();
        }
                $groups = $mergedNavigation
            ->reject(fn ($item) => ($item['position'] ?? 'main') === 'bottom')
            ->groupBy(fn ($item) => $groupOf[explode('.', $item['route'])[0]] ?? 'Général')
            ->sortBy(fn ($items, $name) => array_search($name, ['Général', 'Registre', 'Vignettes VGT', 'Administration']) ?: 0);
        $badges = $sidebarBadges ?? [];
        $plannedModules = collect($plannedModules ?? [])->except(config('sidebar.embedded_planned'))->all();
        $plannedActive = collect($plannedModules)->keys()->contains(fn ($key) => request()->is('modules/'.$key));
    @endphp

    <ul class="metismenu" id="menu">
        <li class="nv-side-search">
            <i class='bx bx-search'></i>
            <input type="search" id="nv-side-filter" placeholder="Rechercher dans le menu…" autocomplete="off" aria-label="Rechercher dans le menu">
        </li>
        <li>
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'mm-active' : '' }}">
                <div class="parent-icon"><i class='bx bx-home-circle'></i></div>
                <div class="menu-title">Tableau de bord</div>
            </a>
        </li>

        @foreach ($groups as $groupName => $items)
            @if ($groupName !== 'Général' && $items->count() > 1)
                <li class="menu-label nv-group"><i class='{{ $groupIcons[$groupName] ?? 'bx bx-folder' }}'></i>{{ $groupName }}</li>
            @endif
            @foreach ($items as $item)
                @php($badge = $badges[explode('.', $item['route'])[0]] ?? 0)
                <li>
                    <a href="{{ route($item['route']) }}" class="{{ request()->routeIs(...(array) $item['active']) ? 'mm-active' : '' }}">
                        <div class="parent-icon"><i class='{{ $item['icon'] }}'></i></div>
                        <div class="menu-title">{{ $item['label'] }}</div>
                        @if ($badge > 0)
                            <span class="nv-badge" title="{{ $badge }} à traiter">{{ $badge > 99 ? '99+' : $badge }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        @endforeach

        {{-- Modules métier prévus, non implémentés (D25) : regroupés et repliés pour ne pas encombrer le menu. --}}
        @if (! empty($plannedModules))
            <li class="menu-label nv-group"><i class='bx bx-rocket'></i>Bientôt disponible</li>
            <li class="planned-group {{ $plannedActive ? 'mm-active' : '' }}">
                <a href="javascript:;" class="has-arrow" aria-expanded="{{ $plannedActive ? 'true' : 'false' }}">
                    <div class="parent-icon"><i class='bx bx-time-five'></i></div>
                    <div class="menu-title">Modules à venir</div>
                    <span class="planned-badge">{{ count($plannedModules) }}</span>
                </a>
                <ul class="{{ $plannedActive ? 'mm-show' : '' }}">
                    @foreach ($plannedModules as $key => $planned)
                        <li class="planned {{ request()->is('modules/'.$key) ? 'mm-active' : '' }}">
                            <a href="{{ route('modules.show', $key) }}">
                                <i class='{{ $planned['icon'] }}'></i>{{ $planned['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>
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

    <div class="nv-side-user">
        <span class="nv-side-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
        <span class="nv-side-who">
            <b>{{ auth()->user()->name }}</b>
            <small>{{ auth()->user()->roleName()?->label() }}</small>
        </span>
    </div>
</div>

<script>
    // Recherche dans le menu : masque les entrées qui ne correspondent pas (texte du menu, sans requête serveur).
    (function () {
        var input = document.getElementById('nv-side-filter');
        if (!input) { return; }
        input.addEventListener('input', function () {
            var term = input.value.trim().toLowerCase();
            document.querySelectorAll('#menu > li:not(.sidebar-bottom):not(.nv-side-search), #menu .planned-group li').forEach(function (item) {
                if (item.classList.contains('menu-label')) { item.style.display = term ? 'none' : ''; return; }
                if (item.classList.contains('planned-group')) { return; }
                item.style.display = !term || item.textContent.toLowerCase().indexOf(term) !== -1 ? '' : 'none';
            });
            var group = document.querySelector('#menu .planned-group');
            if (group && term) {
                var visible = group.querySelectorAll('li:not([style*="none"])').length > 0;
                group.style.display = visible ? '' : 'none';
                group.classList.toggle('mm-active', visible);
                group.querySelector('ul').classList.toggle('mm-show', visible);
            } else if (group) {
                group.style.display = '';
            }
        });
    })();
</script>

{{-- Sous-menu « Paramètres » (list-group) : entrées visibles selon les permissions et les routes existantes. --}}
@php
    $configItems = [
        ['users.index', 'users.view', 'Utilisateurs', 'bx bx-user-circle', 'users.index'],
        ['commissariats.index', 'commissariats.view', 'Commissariats', 'bx bx-buildings', 'commissariats.*'],
        ['mairies.index', 'mairies.view', 'Mairies', 'bx bx-building-house', 'mairies.*'],
        ['roles.index', 'roles.view', 'Rôles', 'bx bx-id-card', 'roles.*'],
        ['permissions.index', 'permissions.view', 'Permissions', 'bx bx-shield-alt-2', 'permissions.*'],
        ['user-permissions.index', 'permissions.assign', 'Attribution des permissions', 'bx bx-user-check', 'user-permissions.*'],
        ['audit.index', 'audit.view', 'Audit', 'bx bx-list-check', 'audit.*'],
        ['system.index', 'system.view', 'Système', 'bx bx-server', 'system.*'],
    ];
@endphp
<div class="card">
    <div class="card-header card-header-brand">
        <h6 class="mb-0 text-white"><i class='bx bx-cog me-2'></i>PARAMÈTRES</h6>
    </div>
    <div class="card-body">
        <div class="list-group">
            @foreach ($configItems as [$route, $permission, $label, $icon, $active])
                @if (Route::has($route) && auth()->user()->can($permission))
                    <a href="{{ route($route) }}" class="list-group-item list-group-item-action {{ request()->routeIs($active) ? 'active' : '' }}">
                        <i class='{{ $icon }} me-2'></i>{{ $label }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</div>

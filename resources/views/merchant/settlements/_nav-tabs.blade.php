<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('merchant.settlements.summary*') ? 'active' : '' }}"
           href="{{ route('merchant.settlements.summary') }}">
            <i class="bi bi-collection me-1"></i> Settlement Summary
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('merchant.settlements.details*') ? 'active' : '' }}"
           href="{{ route('merchant.settlements.details') }}">
            <i class="bi bi-list-check me-1"></i> Settlement Details
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('merchant.settlements.cron*') ? 'active' : '' }}"
           href="{{ route('merchant.settlements.cron') }}">
            <i class="bi bi-clock-history me-1"></i> Cron &amp; schedule
        </a>
    </li>
</ul>

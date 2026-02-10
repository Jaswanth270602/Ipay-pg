@props(['meta'])
@if(($meta['last_page'] ?? 1) > 1)
<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted" style="font-size:14px;">
        Showing {{ $meta['from'] ?? 0 }} to {{ $meta['to'] ?? 0 }} of {{ $meta['total'] ?? 0 }} results
    </div>
    <nav>
        <ul class="pagination mb-0">
            <li class="page-item {{ ($meta['current_page'] ?? 1) === 1 ? 'disabled':'' }}">
                <a class="page-link" href="#" data-page="{{ max(1, ($meta['current_page'] ?? 1)-1) }}">Previous</a>
            </li>
            @for($p=max(1, ($meta['current_page'] ?? 1)-2); $p<=min(($meta['last_page'] ?? 1), ($meta['current_page'] ?? 1)+2); $p++)
                <li class="page-item {{ $p === ($meta['current_page'] ?? 1) ? 'active':'' }}">
                    <a class="page-link" href="#" data-page="{{ $p }}">{{ $p }}</a>
                </li>
            @endfor
            <li class="page-item {{ ($meta['current_page'] ?? 1) === ($meta['last_page'] ?? 1) ? 'disabled':'' }}">
                <a class="page-link" href="#" data-page="{{ min(($meta['last_page'] ?? 1), ($meta['current_page'] ?? 1)+1) }}">Next</a>
            </li>
        </ul>
    </nav>
</div>
@endif



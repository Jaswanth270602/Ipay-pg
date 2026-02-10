@props(['items' => []])
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        @foreach($items as $idx => $item)
            @if(isset($item['url']) && $idx < count($items)-1)
                <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
            @else
                <li class="breadcrumb-item active">{{ $item['label'] }}</li>
            @endif
        @endforeach
    </ol>
</nav>



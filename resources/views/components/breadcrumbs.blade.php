@props(['items'])
{{-- items: a list of ['label' => ..., 'url' => ...]; the last item is the current page and has no url. --}}
<nav aria-label="Breadcrumb" class="breadcrumbs">
    <ol>
        @foreach($items as $item)
            <li>
                @if(! $loop->last && isset($item['url']))
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span @if($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

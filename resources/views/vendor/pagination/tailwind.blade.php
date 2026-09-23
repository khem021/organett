@if ($paginator->hasPages())
<nav style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;" aria-label="Pagination">

    {{-- Result count --}}
    <span style="font-size:.75rem;color:var(--text-dim);">
        @if ($paginator->firstItem())
            Showing <strong style="color:var(--text-muted);">{{ $paginator->firstItem() }}</strong>
            to <strong style="color:var(--text-muted);">{{ $paginator->lastItem() }}</strong>
            of <strong style="color:var(--text-muted);">{{ $paginator->total() }}</strong> results
        @else
            {{ $paginator->count() }} results
        @endif
    </span>

    {{-- Page buttons --}}
    <div style="display:inline-flex;align-items:center;gap:.25rem;">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:.375rem;border:1px solid var(--card-border);background:transparent;color:var(--text-dim);cursor:not-allowed;font-size:.75rem;" aria-disabled="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:.375rem;border:1px solid var(--card-border);background:var(--card-bg);color:var(--text-muted);text-decoration:none;font-size:.75rem;transition:border-color .15s,color .15s;"
               onmouseover="this.style.borderColor='var(--green-accent)';this.style.color='var(--green-light)'"
               onmouseout="this.style.borderColor='var(--card-border)';this.style.color='var(--text-muted)'"
               aria-label="Previous">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
        @endif

        {{-- Pages --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;font-size:.75rem;color:var(--text-dim);">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"
                              style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:.375rem;border:1px solid var(--green-accent);background:var(--green-accent);color:#fff;font-size:.75rem;font-weight:700;cursor:default;">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                           style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:.375rem;border:1px solid var(--card-border);background:var(--card-bg);color:var(--text-muted);text-decoration:none;font-size:.75rem;font-weight:500;transition:border-color .15s,color .15s;"
                           onmouseover="this.style.borderColor='var(--green-accent)';this.style.color='var(--green-light)'"
                           onmouseout="this.style.borderColor='var(--card-border)';this.style.color='var(--text-muted)'"
                           aria-label="Go to page {{ $page }}">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:.375rem;border:1px solid var(--card-border);background:var(--card-bg);color:var(--text-muted);text-decoration:none;font-size:.75rem;transition:border-color .15s,color .15s;"
               onmouseover="this.style.borderColor='var(--green-accent)';this.style.color='var(--green-light)'"
               onmouseout="this.style.borderColor='var(--card-border)';this.style.color='var(--text-muted)'"
               aria-label="Next">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        @else
            <span style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:.375rem;border:1px solid var(--card-border);background:transparent;color:var(--text-dim);cursor:not-allowed;font-size:.75rem;" aria-disabled="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </span>
        @endif

    </div>
</nav>
@endif

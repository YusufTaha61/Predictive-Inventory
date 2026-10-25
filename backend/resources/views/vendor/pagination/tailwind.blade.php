{{-- Sayfa numaraları (Laravel'in varsayılan sayfalama görünümünün yerine) --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfalar" class="flex items-center justify-between gap-4">
        <p class="hidden text-sm text-ink-muted sm:block">
            {{ $paginator->total() }} kayıttan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} arası
        </p>

        <div class="flex flex-1 items-center justify-between gap-1 sm:flex-none sm:justify-end">
            @if ($paginator->onFirstPage())
                <span class="btn-secondary pointer-events-none px-3 py-1.5 opacity-40" aria-disabled="true">Önceki</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-secondary px-3 py-1.5">Önceki</a>
            @endif

            <div class="hidden items-center gap-1 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 text-sm text-ink-muted">{{ $element }}</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="grid h-8 min-w-8 place-items-center rounded-lg bg-ink px-2 text-sm font-medium text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="grid h-8 min-w-8 place-items-center rounded-lg px-2 text-sm text-ink-soft hover:bg-leaf-50 hover:text-ink" aria-label="{{ $page }}. sayfa">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-secondary px-3 py-1.5">Sonraki</a>
            @else
                <span class="btn-secondary pointer-events-none px-3 py-1.5 opacity-40" aria-disabled="true">Sonraki</span>
            @endif
        </div>
    </nav>
@endif

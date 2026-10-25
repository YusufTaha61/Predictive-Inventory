@props(['title', 'text' => null])

{{-- Liste boşken gösterilen kutu --}}
<div class="flex flex-col items-center px-6 py-14 text-center">
    <svg class="h-10 w-10 text-leaf-300" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <rect x="4" y="22" width="24" height="5" rx="1.5" fill="currentColor"/>
        <rect x="4" y="15" width="16" height="5" rx="1.5" fill="currentColor" opacity=".6"/>
    </svg>
    <p class="mt-4 font-display text-lg font-semibold text-ink">{{ $title }}</p>
    @if ($text)
        <p class="mt-1 max-w-md text-sm text-ink-muted">{{ $text }}</p>
    @endif
    @if (trim($slot) !== '')
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>

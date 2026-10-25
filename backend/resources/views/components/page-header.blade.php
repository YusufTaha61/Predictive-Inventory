@props(['title', 'subtitle' => null])

{{-- Sayfanın üstündeki başlık ve sağdaki butonlar --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        <h1 class="page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim($slot) !== '')
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>

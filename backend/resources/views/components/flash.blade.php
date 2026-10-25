{{-- Kayıt/silme sonrası bilgi mesajları --}}
@php
    // Breeze profil sayfası kendi "Kaydedildi." yazısını gösteriyor; o kodları burada gösterme
    $status = session('status');
    if (in_array($status, ['profile-updated', 'password-updated', 'verification-link-sent'], true)) {
        $status = null;
    }
@endphp
@if ($status)
    <div class="alert-success" role="status">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
        <span>{{ $status }}</span>
    </div>
@endif
@if (session('error'))
    <div class="alert-error" role="alert">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm-.9 4.5a.9.9 0 1 1 1.8 0v4a.9.9 0 1 1-1.8 0v-4Zm.9 7.9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

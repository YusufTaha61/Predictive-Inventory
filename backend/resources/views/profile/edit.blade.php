<x-app-layout>
    <x-slot name="title">Profil</x-slot>
    <x-slot name="header">
        <x-page-header title="Profil ve şifre" subtitle="Hesap bilgilerini ve şifreni buradan değiştirebilirsin." />
    </x-slot>

    @if ($user->isDemo())
        {{-- Demo hesap korumalı: bilgiler, şifre ve hesap silme kapalı --}}
        <div class="card card-body">
            <div class="max-w-xl">
                <h2 class="card-title text-lg">Demo hesap</h2>
                <p class="mt-1 text-sm text-ink-soft">
                    Bu hesap herkesin deneyebilmesi için ayrılmış bir demo hesaptır.
                    Adı, e-postası ve şifresi değiştirilemez, hesap silinemez.
                    Kendi verilerinle çalışmak için yeni bir hesap oluşturabilirsin.
                </p>
            </div>
        </div>
    @else
        <div class="card card-body">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card card-body">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card card-body border-tomato-200">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    @endif
</x-app-layout>

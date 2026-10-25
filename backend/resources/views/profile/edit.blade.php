<x-app-layout>
    <x-slot name="title">Profil</x-slot>
    <x-slot name="header">
        <x-page-header title="Profil ve şifre" subtitle="Hesap bilgilerini ve şifreni buradan değiştirebilirsin." />
    </x-slot>

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
</x-app-layout>

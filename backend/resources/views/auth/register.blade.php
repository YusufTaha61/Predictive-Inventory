<x-guest-layout>
    <h1 class="font-display text-3xl font-semibold tracking-tight text-ink">Hesap oluştur</h1>
    <p class="mt-2 text-ink-muted">
        Zaten hesabın var mı?
        <a href="{{ route('login') }}" class="text-link">Giriş yap</a>
    </p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="company_name" value="Şirket adı" />
            <x-text-input id="company_name" class="mt-1.5 block w-full" type="text"
                          name="company_name" :value="old('company_name')" placeholder="Örn: Yıldız Market" required autofocus />
            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="name" value="Adın soyadın" />
            <x-text-input id="name" class="mt-1.5 block w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="E-posta" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="password" value="Şifre" />
                <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Şifre (tekrar)" />
                <x-text-input id="password_confirmation" class="mt-1.5 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <x-primary-button class="w-full py-2.5">Hesap oluştur</x-primary-button>
    </form>
</x-guest-layout>

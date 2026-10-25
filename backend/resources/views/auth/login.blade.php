<x-guest-layout>
    <h1 class="font-display text-3xl font-semibold tracking-tight text-ink">Giriş yap</h1>
    <p class="mt-2 text-ink-muted">
        Hesabın yok mu?
        <a href="{{ route('register') }}" class="text-link">Hesap oluştur</a>
    </p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="E-posta" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Şifre" />
                @if (Route::has('password.request'))
                    <a class="text-sm text-leaf-600 hover:underline" href="{{ route('password.request') }}">Şifremi unuttum</a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2">
            <input id="remember_me" type="checkbox" name="remember"
                   class="rounded border-line text-leaf-500 shadow-sm focus:ring-leaf-400">
            <span class="text-sm text-ink-soft">Beni hatırla</span>
        </label>

        <x-primary-button class="w-full py-2.5">Giriş yap</x-primary-button>
    </form>

    {{-- Demo hesap: butona basınca e-posta ve şifre alanları otomatik dolar --}}
    <div class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-dashed border-line bg-white px-4 py-3 text-sm text-ink-soft">
        <span>
            Demo hesap: <strong class="font-medium text-ink">demo@market.test</strong>, şifre <strong class="font-medium text-ink">password</strong>
        </span>
        <button type="button" class="btn-secondary px-3 py-1.5 text-xs"
                onclick="document.getElementById('email').value = 'demo@market.test';
                         document.getElementById('password').value = 'password';
                         document.getElementById('password').focus();">
            Demo bilgilerini doldur
        </button>
    </div>
</x-guest-layout>

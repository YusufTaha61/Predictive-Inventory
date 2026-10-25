<section>
    <header>
        <h2 class="card-title text-lg">
            {{ 'Profil bilgileri' }}
        </h2>

        <p class="mt-1 text-sm text-ink-soft">
            Adını ve e-posta adresini güncelle.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="'Ad soyad'" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="'E-posta'" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-ink">
                        {{ 'E-posta adresin doğrulanmadı.' }}

                        <button form="send-verification" class="underline text-sm text-ink-soft hover:text-ink rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-leaf-400">
                            {{ 'Doğrulama e-postasını tekrar göndermek için tıkla.' }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-leaf-600">
                            {{ 'E-posta adresine yeni bir doğrulama bağlantısı gönderildi.' }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ 'Kaydet' }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-ink-soft"
                >{{ 'Kaydedildi.' }}</p>
            @endif
        </div>
    </form>
</section>

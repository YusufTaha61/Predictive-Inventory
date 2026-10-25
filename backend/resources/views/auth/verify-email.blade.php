<x-guest-layout>
    <div class="mb-4 text-sm text-ink-soft">
        {{ 'Kaydolduğun için teşekkürler. Başlamadan önce e-postana gönderdiğimiz bağlantıya tıklayarak adresini doğrula. E-posta gelmediyse yenisini gönderebiliriz.' }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-leaf-600">
            {{ 'Kayıtta verdiğin e-posta adresine yeni bir doğrulama bağlantısı gönderildi.' }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ 'Doğrulama e-postasını tekrar gönder' }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-ink-soft hover:text-ink rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-leaf-400">
                {{ 'Çıkış yap' }}
            </button>
        </form>
    </div>
</x-guest-layout>

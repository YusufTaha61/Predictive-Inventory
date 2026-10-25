<section class="space-y-6">
    <header>
        <h2 class="card-title text-lg">
            {{ 'Hesabı sil' }}
        </h2>

        <p class="mt-1 text-sm text-ink-soft">
            {{ 'Hesabın silinince tüm verilerin kalıcı olarak silinir. Saklamak istediğin bilgiler varsa önce onları kaydet.' }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ 'Hesabı sil' }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="card-title text-lg">
                {{ 'Hesabını silmek istediğine emin misin?' }}
            </h2>

            <p class="mt-1 text-sm text-ink-soft">
                {{ 'Hesabın silinince tüm verilerin kalıcı olarak silinir. Onaylamak için şifreni gir.' }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ 'Şifre' }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ 'Şifre' }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ 'Vazgeç' }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ 'Hesabı sil' }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

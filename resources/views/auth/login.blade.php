<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-gray-800">Batam Seken POS</h2>
        <p class="text-sm text-gray-500 mt-2">Silakan pilih peran untuk masuk</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="flex mb-4 border-b">
        <button onclick="switchTab('admin')" id="tab-admin" class="w-1/2 py-2 font-semibold text-blue-600 border-b-2 border-blue-600">Kasir (Admin)</button>
        <button onclick="switchTab('owner')" id="tab-owner" class="w-1/2 py-2 font-semibold text-gray-500 hover:text-gray-800">Owner</button>
    </div>

    <!-- Form Admin (PIN) -->
    <form method="POST" action="{{ route('login') }}" id="form-admin">
        @csrf
        <input type="hidden" name="login_type" value="admin">
        
        <div class="mt-4">
            <x-input-label for="pin" value="PIN Akses (6 Digit)" />
            <x-text-input id="pin" class="block mt-1 w-full tracking-[0.5em] text-center text-xl font-bold" type="password" name="pin" maxlength="6" required autofocus />
            <x-input-error :messages="$errors->get('pin')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button class="w-full justify-center py-3">
                {{ __('Masuk sebagai Kasir') }}
            </x-primary-button>
        </div>
    </form>

    <!-- Form Owner (Email & Password) -->
    <form method="POST" action="{{ route('login') }}" id="form-owner" class="hidden">
        @csrf
        <input type="hidden" name="login_type" value="owner">

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <x-primary-button class="w-full justify-center py-3">
                {{ __('Masuk sebagai Owner') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        function switchTab(type) {
            const formAdmin = document.getElementById('form-admin');
            const formOwner = document.getElementById('form-owner');
            const tabAdmin = document.getElementById('tab-admin');
            const tabOwner = document.getElementById('tab-owner');

            if (type === 'admin') {
                formAdmin.classList.remove('hidden');
                formOwner.classList.add('hidden');
                tabAdmin.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                tabAdmin.classList.remove('text-gray-500');
                tabOwner.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                tabOwner.classList.add('text-gray-500');
            } else {
                formOwner.classList.remove('hidden');
                formAdmin.classList.add('hidden');
                tabOwner.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                tabOwner.classList.remove('text-gray-500');
                tabAdmin.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                tabAdmin.classList.add('text-gray-500');
            }
        }
    </script>
</x-guest-layout>
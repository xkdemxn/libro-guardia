<section>
    <header>
        <h2 class="font-display text-lg font-semibold text-white">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-slate-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="indicativo" value="Indicativo" />
            <x-text-input id="indicativo" type="text" class="font-mono" :value="$user->indicativo" disabled />
            <p class="mt-1 text-xs text-slate-500">El indicativo no se puede cambiar.</p>
        </div>

        <div>
            <x-input-label for="nombres" value="Nombres" />
            <x-text-input id="nombres" name="nombres" type="text" class="mt-1 block w-full" :value="old('nombres', $user->nombres)" required autofocus autocomplete="given-name" />
            <x-input-error class="mt-2" :messages="$errors->get('nombres')" />
        </div>

        <div>
            <x-input-label for="apellidos" value="Apellidos" />
            <x-text-input id="apellidos" name="apellidos" type="text" class="mt-1 block w-full" :value="old('apellidos', $user->apellidos)" required autocomplete="family-name" />
            <x-input-error class="mt-2" :messages="$errors->get('apellidos')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-slate-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-acento-300 hover:text-acento-200 no-underline">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-emerald-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

<div>
    <h2 class="text-lg font-bold text-frost-900">{{ __('auth.login.heading') }}</h2>
    <p class="mt-1 text-sm text-frost-500">{{ __('auth.login.subtitle') }}</p>

    <form wire:submit="login" class="mt-5 space-y-4">
        <div>
            <label class="label" for="email">{{ __('common.email') }}</label>
            <input id="email" type="email" wire:model="email" autocomplete="email" inputmode="email" required autofocus
                   class="input @error('email') input-error @enderror">
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Das Auge rechts im Feld zeigt das eingegebene Passwort im Klartext und
             versteckt es wieder (resources/js/app.js, window.togglePasswordVisibility).
             Gewechselt wird allein das type-Attribut des Feldes, der Wert bleibt
             damit stehen, wo er ist - das Feld verliert weder den Text noch den
             Cursor, weil der Klick ihm nicht die Eingabemarke entzieht
             (onmousedown). Der Zustand liegt als data-revealed am Feld; die beiden
             Symbole folgen ihm über CSS (group-data-[revealed=true]), so dass der
             Knopf selbst keinen Zustand halten muss. --}}
        <div>
            <label class="label" for="password">{{ __('common.password') }}</label>
            <div class="group relative" data-password-field data-revealed="false">
                <input id="password" type="password" wire:model="password" autocomplete="current-password" required
                       class="input pr-11 @error('password') input-error @enderror" data-password-input>

                <button type="button" data-password-toggle
                        class="btn btn-ghost btn-icon absolute right-1 top-1/2 -translate-y-1/2"
                        aria-label="{{ __('common.show_password') }}" aria-pressed="false"
                        title="{{ __('common.show_password') }}"
                        data-label-show="{{ __('common.show_password') }}"
                        data-label-hide="{{ __('common.hide_password') }}"
                        onmousedown="event.preventDefault()"
                        onclick="window.togglePasswordVisibility(this)">
                    <x-icon name="eye" class="h-5 w-5 group-data-[revealed=true]:hidden" />
                    <x-icon name="eye-off" class="hidden h-5 w-5 group-data-[revealed=true]:block" />
                </button>
            </div>
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex cursor-pointer items-center gap-2 text-sm text-frost-700">
            <input type="checkbox" wire:model="remember" class="checkbox">
            {{ __('auth.login.remember') }}
        </label>

        <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login">{{ __('auth.login.submit') }}</span>
            <span wire:loading wire:target="login">{{ __('common.busy') }}</span>
        </button>
    </form>

    <p class="mt-4 text-center text-sm">
        <a href="{{ route('password.request') }}" wire:navigate class="font-medium text-frost-600 dark:text-frost-400">
            {{ __('auth.forgot.heading') }}
        </a>
    </p>

    <p class="mt-5 text-center text-sm text-frost-600">
        {{ __('auth.login.no_account') }}
        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-brand-700 dark:text-brand-400">{{ __('auth.login.register') }}</a>
    </p>
</div>

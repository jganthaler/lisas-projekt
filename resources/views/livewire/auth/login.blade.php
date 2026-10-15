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

        <div>
            <label class="label" for="password">{{ __('common.password') }}</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password" required
                   class="input @error('password') input-error @enderror">
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

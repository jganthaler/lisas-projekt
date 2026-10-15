<div>
    <h2 class="text-lg font-bold text-frost-900">{{ __('auth.reset.title') }}</h2>
    <p class="mt-1 text-sm text-frost-500">
        {{ __('auth.reset.subtitle') }}
    </p>

    <form wire:submit="resetPassword" class="mt-5 space-y-4">
        <div>
            <label class="label" for="email">{{ __('common.email') }}</label>
            <input id="email" type="email" wire:model="email" autocomplete="email" inputmode="email" required
                   class="input @error('email') input-error @enderror">
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="password">{{ __('common.password') }}</label>
            <input id="password" type="password" wire:model="password" autocomplete="new-password" required autofocus
                   class="input @error('password') input-error @enderror">
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="label" for="password_confirmation">{{ __('common.password_confirmation') }}</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation"
                   autocomplete="new-password" required class="input">
        </div>

        <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="resetPassword">
            <span wire:loading.remove wire:target="resetPassword">{{ __('auth.reset.submit') }}</span>
            <span wire:loading wire:target="resetPassword">{{ __('common.saving') }}</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-frost-600">
        {{ __('auth.reset.expired_question') }}
        <a href="{{ route('password.request') }}" wire:navigate class="font-semibold text-brand-700 dark:text-brand-400">
            {{ __('auth.reset.request_new') }}
        </a>
    </p>
</div>

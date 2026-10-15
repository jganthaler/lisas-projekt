<div>
    <h2 class="text-lg font-bold text-frost-900">{{ __('auth.forgot.heading') }}</h2>
    <p class="mt-1 text-sm text-frost-500">
        {{ __('auth.forgot.subtitle') }}
    </p>

    @if ($sent)
        <x-notice class="mt-4"
                  :message="__('auth.forgot.sent', ['email' => $email])" />

        <p class="mt-3 text-xs text-frost-500">
            {{ __('auth.forgot.hint') }}
        </p>
    @endif

    <form wire:submit="send" class="mt-5 space-y-4">
        <div>
            <label class="label" for="email">{{ __('common.email') }}</label>
            <input id="email" type="email" wire:model="email" autocomplete="email" inputmode="email" required autofocus
                   class="input @error('email') input-error @enderror">
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="send">
            <span wire:loading.remove wire:target="send">{{ __('auth.forgot.submit') }}</span>
            <span wire:loading wire:target="send">{{ __('common.sending') }}</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-frost-600">
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-700 dark:text-brand-400">
            {{ __('auth.forgot.back_to_login') }}
        </a>
    </p>
</div>

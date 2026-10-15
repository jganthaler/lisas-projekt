<div>
    <h2 class="text-lg font-bold text-frost-900">{{ __('auth.verify.heading') }}</h2>
    <p class="mt-1 text-sm text-frost-500">
        {{ __('auth.verify.sent_before') }}
        <span class="font-medium text-frost-700">{{ $user->email }}</span>
        {{ __('auth.verify.sent_after') }}
    </p>

    <x-notice :message="$feedback" class="mt-4" />

    <button type="button" wire:click="send" class="btn btn-primary mt-4 w-full"
            wire:loading.attr="disabled" wire:target="send">
        <span wire:loading.remove wire:target="send">{{ __('auth.verify.resend') }}</span>
        <span wire:loading wire:target="send">{{ __('common.sending') }}</span>
    </button>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf

        <button type="submit" class="btn btn-ghost w-full">
            <x-icon name="logout" class="h-5 w-5" />
            {{ __('common.logout') }}
        </button>
    </form>
</div>

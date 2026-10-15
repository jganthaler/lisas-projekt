<div>
    <h2 class="text-lg font-bold text-frost-900">{{ __('auth.register.heading') }}</h2>
    <p class="mt-1 text-sm text-frost-500">
        {{ __('auth.register.subtitle') }}
    </p>

    <form wire:submit="register" class="mt-5 space-y-4">
        <div>
            <label class="label" for="name">{{ __('common.name') }}</label>
            <input id="name" type="text" wire:model="name" autocomplete="name" required autofocus
                   class="input @error('name') input-error @enderror">
            @error('name')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

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
            <input id="password" type="password" wire:model="password" autocomplete="new-password" required
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

        <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="register">
            <span wire:loading.remove wire:target="register">{{ __('auth.register.submit') }}</span>
            <span wire:loading wire:target="register">{{ __('auth.register.busy') }}</span>
        </button>
    </form>

    {{-- Der Hinweis auf Nutzungsbedingungen und Datenschutzerklärung gehört vor
         die Registrierung: Nur so werden die Bedingungen Vertragsbestandteil
         (§ 305 Abs. 2 BGB), und Art. 13 DSGVO verlangt die Information vor der
         Erhebung. Die beiden Links baut der Satz aus auth.register.consent
         selbst ein, damit jede Sprache ihre eigene Wortstellung behält; als
         Linktext dient die Überschrift der Seite, weil sie im Satz steht
         ("der Datenschutzerklärung zu"). --}}
    <p class="mt-4 text-center text-xs leading-relaxed text-frost-500">
        {!! __('auth.register.consent', [
            'terms' => '<a href="'.route('legal.terms').'" class="font-medium text-brand-700 dark:text-brand-400">'.e(__('legal.terms.heading')).'</a>',
            'privacy' => '<a href="'.route('legal.privacy').'" class="font-medium text-brand-700 dark:text-brand-400">'.e(__('legal.privacy.heading')).'</a>',
        ]) !!}
    </p>

    <p class="mt-5 text-center text-sm text-frost-600">
        {{ __('auth.register.have_account') }}
        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-700 dark:text-brand-400">{{ __('auth.register.login') }}</a>
    </p>
</div>

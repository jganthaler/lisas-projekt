@props(['message' => null, 'variant' => 'ok'])

@if ($message)
    <div role="status"
         {{ $attributes->class(['flex items-start gap-2 rounded-2xl px-4 py-3 text-sm font-medium', $variant === 'error' ? 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300' : 'bg-brand-50 text-brand-800 dark:bg-brand-950 dark:text-brand-200']) }}>
        <x-icon :name="$variant === 'error' ? 'alert' : 'check'" class="mt-0.5 h-4 w-4 shrink-0" />
        <span>{{ $message }}</span>
    </div>
@endif

@if (session('status'))
    <div class="mb-4 flex items-start gap-2 rounded-2xl bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800 dark:bg-brand-950 dark:text-brand-200">
        <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
        <span>{{ session('status') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="mb-4 flex items-start gap-2 rounded-2xl bg-danger-50 px-4 py-3 text-sm font-medium text-danger-700 dark:bg-danger-950 dark:text-danger-300">
        <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
        <span>{{ session('error') }}</span>
    </div>
@endif

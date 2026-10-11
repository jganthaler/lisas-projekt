@if (session('status'))
    <div class="mb-4 flex items-start gap-2 rounded-2xl bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800">
        <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
        <span>{{ session('status') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="mb-4 flex items-start gap-2 rounded-2xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
        <span>{{ session('error') }}</span>
    </div>
@endif

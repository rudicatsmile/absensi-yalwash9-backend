<div class="flex items-center justify-between gap-4">
    <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
        {{ $heading }}
    </h2>

    @if ($filterText)
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $filterText }}
        </p>
    @endif
</div>

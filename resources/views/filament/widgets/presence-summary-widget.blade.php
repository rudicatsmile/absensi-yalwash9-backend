<x-filament-widgets::widget>
    <div
        @if ($pollingInterval = $this->getPollingInterval())
            wire:poll.{{ $pollingInterval }}
        @endif
        class="presence-summary-widget"
    >
        <style>
            .presence-card-container {
                display: flex;
                flex-wrap: wrap;
                gap: 1.5%;
                justify-content: flex-start;
            }
            .presence-card {
                width: 22%;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                overflow: hidden;
                transition: all 0.3s ease;
                background-color: white;
                display: flex;
                flex-direction: column;
                margin-bottom: 1.5%;
            }
            .dark .presence-card {
                background-color: rgb(24 24 27); /* gray-900 */
                border-color: rgba(255, 255, 255, 0.1);
            }
            .presence-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            .presence-card-header {
                padding: 12px;
                display: flex;
                align-items: center;
                gap: 8px;
                font-weight: 700;
                font-size: 1.125rem;
            }
            .presence-card-body {
                padding: 12px;
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .presence-card-value {
                font-size: 1.875rem; /* 3xl */
                font-weight: 700;
                line-height: 1.2;
                margin-bottom: 4px;
            }
            .presence-card-description {
                font-size: 0.875rem; /* sm */
                color: #6b7280; /* gray-500 */
                display: flex;
                align-items: center;
                gap: 4px;
            }
            .dark .presence-card-description {
                color: #9ca3af; /* gray-400 */
            }

            @media (max-width: 768px) {
                .presence-card {
                    width: 48.5%; /* 2 per row approx */
                }
            }
            @media (max-width: 480px) {
                .presence-card {
                    width: 100%;
                }
            }
        </style>

        <div class="presence-card-container">
            @foreach ($this->getCachedStats() as $stat)
                @php
                    $color = $stat->getColor() ?? 'primary';
                    $icon = $stat->getIcon();
                    $label = $stat->getLabel();
                    $value = $stat->getValue();
                    $description = $stat->getDescription();
                    $descriptionIcon = $stat->getDescriptionIcon();
                    $url = $stat->getUrl();
                    $tag = $url ? 'a' : 'div';

                    // Map Filament colors to Tailwind bg classes
                    $bgClass = match ($color) {
                        'success' => 'bg-green-600 dark:bg-green-500',
                        'danger' => 'bg-red-600 dark:bg-red-500',
                        'warning' => 'bg-amber-500 dark:bg-amber-500',
                        'info' => 'bg-blue-600 dark:bg-blue-500',
                        'gray' => 'bg-gray-600 dark:bg-gray-500',
                        default => 'bg-primary-600 dark:bg-primary-500',
                    };
                @endphp

                <{!! $tag !!}
                    class="presence-card group"
                    @if ($url)
                        href="{{ $url }}"
                        target="{{ $stat->shouldOpenUrlInNewTab() ? '_blank' : '_self' }}"
                    @endif
                    {{ $stat->getExtraAttributeBag() }}
                >
                    <div class="presence-card-header {{ $bgClass }} text-white group-[.text-black-header]:text-black">
                        @if ($icon)
                            <x-filament::icon
                                :icon="$icon"
                                class="h-5 w-5 text-white group-[.text-black-header]:text-black"
                            />
                        @endif
                        <span class="truncate">{{ $label }}</span>
                    </div>

                    <div class="presence-card-body">
                        <div class="presence-card-value text-gray-900 dark:text-white">
                            {{ $value }}
                        </div>

                        @if ($description)
                            <div class="presence-card-description">
                                @if ($descriptionIcon)
                                    <x-filament::icon
                                        :icon="$descriptionIcon"
                                        class="h-4 w-4"
                                    />
                                @endif
                                <span>{{ $description }}</span>
                            </div>
                        @endif
                    </div>
                </{!! $tag !!}>
            @endforeach
        </div>

        <x-filament-actions::modals />
    </div>
</x-filament-widgets::widget>

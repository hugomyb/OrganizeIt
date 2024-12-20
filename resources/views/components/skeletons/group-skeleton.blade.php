<x-filament::section
    collapsible
    class="group-loading"
    style="margin-bottom: 30px; width: 100%">

    <x-slot name="heading">
        <h3 class="group-heading-skeleton" style="width: {{ rand(15,50) }}%"></h3>
    </x-slot>

    <div class="group-content" style="height: {{ rand(80,220) }}px"></div>

</x-filament::section>


<style>
    :root {
        --loading-grey: #ededed;
        --loading-gradient-start: rgba(255, 255, 255, 0);
        --loading-gradient-middle: rgba(255, 255, 255, 0.5);
        --loading-gradient-end: rgba(255, 255, 255, 0);
    }

    .dark {
        --loading-grey: #2d2d2d;
        --loading-gradient-start: rgba(0, 0, 0, 0);
        --loading-gradient-middle: rgba(0, 0, 0, 0.3);
        --loading-gradient-end: rgba(0, 0, 0, 0);
    }

    .group-loading .group-heading-skeleton,
    .group-loading .group-content {
        background-color: var(--loading-grey);
        background: linear-gradient(
            100deg,
            var(--loading-gradient-start) 40%,
            var(--loading-gradient-middle) 50%,
            var(--loading-gradient-end) 60%
        ) var(--loading-grey);
        background-size: 200% 100%;
        background-position-x: 180%;
        animation: 1s loading ease-in-out infinite;
    }

    @keyframes loading {
        to {
            background-position-x: -20%;
        }
    }

    .group-loading .group-heading-skeleton {
        min-height: 1.3rem;
        border-radius: 4px;
        animation-delay: 0.05s;
    }
</style>


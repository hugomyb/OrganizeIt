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
    }

    .group-loading .group-heading-skeleton,
    .group-loading .group-content {
        background-color: var(--loading-grey);
        background: linear-gradient(
            100deg,
            rgba(255, 255, 255, 0) 40%,
            rgba(255, 255, 255, .5) 50%,
            rgba(255, 255, 255, 0) 60%
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
        animation-delay: .05s;
    }
</style>

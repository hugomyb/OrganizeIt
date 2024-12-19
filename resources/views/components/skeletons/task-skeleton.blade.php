<li class="flex flex-col justify-between dark:hover:bg-white/5 text-sm loading"
    style="padding-left: 8px; width: 100%"
>
    <div class="flex content-item" style="width: 100%; margin-left: 30px">
        <div class="flex space-x-2" style="width: 100%">
            <div class="status-skeleton h-6 w-6 rounded-full"></div>

            <div class="flex gap-2 items-center flex-wrap content-skeleton" style="width: 100%">
                <h3 class="task-title-skeleton" style="width: {{ rand(20,70) }}%"></h3>
                <h3 class="task-title-skeleton" style="width: {{ rand(3,7) }}%"></h3>
                <h3 class="task-title-skeleton" style="width: {{ rand(2,5) }}%"></h3>
            </div>
        </div>
    </div>
</li>

<style>
    :root {
        --loading-grey: #ededed;
    }

    .loading .status-skeleton,
    .loading .content-skeleton .task-title-skeleton {
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

    .loading .task-title-skeleton {
        min-height: 1.3rem;
        border-radius: 4px;
        animation-delay: .05s;
    }
</style>

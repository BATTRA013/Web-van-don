@props([
    'title' => 'Xác nhận',
    'description' => null,
])

<div x-data="{ open: false }" class="inline-block">
    <div @click="open = true" class="inline-block">
        {{ $trigger ?? '' }}
    </div>

    <template x-teleport="body">
        <div
            x-cloak
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto bg-black/50 px-4 py-6 sm:items-center"
            @keydown.escape.window="open = false"
        >
            <div
                class="w-full max-w-xl rounded-xl bg-white shadow-xl"
                x-show="open"
                x-transition
                @click.outside="open = false"
            >
                <div class="flex items-start justify-between border-b border-gray-200 px-5 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
                        @if ($description)
                            <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
                        @endif
                    </div>
                    <button type="button" class="rounded-lg p-1 text-gray-500 hover:bg-gray-100" @click="open = false">
                        <span class="sr-only">Đóng</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <div class="px-5 py-4">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </template>
</div>
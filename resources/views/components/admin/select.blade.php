@props([
    'placeholder' => 'Pilih salah satu...',
    'searchPlaceholder' => 'Cari pilihan...',
    'compact' => false,
])
<div
    x-data="satkerSearchSelect({
        placeholder: @js($placeholder),
        searchPlaceholder: @js($searchPlaceholder),
    })"
    x-on:keydown.escape.stop.prevent="close(true)"
    class="satker-select relative w-full min-w-0"
>
    <select
        x-ref="native"
        tabindex="-1"
        aria-hidden="true"
        x-on:focus="open()"
        x-on:change="syncFromNative()"
        {{ $attributes->merge(['class' => 'sr-only']) }}
    >
        {{ $slot }}
    </select>

    <div wire:ignore class="w-full">
        <button
            type="button"
            x-ref="trigger"
            role="combobox"
            aria-haspopup="listbox"
            x-bind:aria-expanded="isOpen.toString()"
            x-bind:aria-label="accessibleLabel"
            x-on:click="toggle()"
            x-on:keydown.arrow-down.prevent="open(); move(1)"
            x-on:keydown.arrow-up.prevent="open(); move(-1)"
            x-on:keydown.enter.prevent="toggle()"
            x-on:keydown.space.prevent="toggle()"
            x-bind:disabled="isDisabled"
            x-bind:class="isOpen
                ? 'border-primary ring-3 ring-primary/20'
                : 'border-[#768780] hover:border-[#475569]'"
            @class([
                'flex w-full cursor-pointer items-center justify-between gap-2 rounded-lg border bg-white text-left text-ink transition-colors focus-visible:border-primary focus-visible:ring-3 focus-visible:ring-primary/20 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60',
                'min-h-11 px-3 py-2 text-xs font-semibold sm:min-h-9' => $compact,
                'min-h-11 px-3.5 py-2 text-sm font-normal' => ! $compact,
            ])
        >
            <span
                class="min-w-0 flex-1 truncate"
                x-bind:class="selectedLabel ? 'text-ink' : 'text-[#6b7280]'"
                x-text="selectedLabel || placeholder"
            ></span>
            <svg
                class="size-4 shrink-0 text-ink-muted transition-transform duration-150"
                x-bind:class="isOpen ? 'rotate-180 text-primary' : ''"
                viewBox="0 0 20 20"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="m5.5 7.75 4.5 4.5 4.5-4.5" />
            </svg>
        </button>

        <template x-teleport="body">
            <div
                x-show="isOpen"
                x-cloak
                x-ref="panel"
                x-on:mousedown.outside="handleOutside($event)"
                x-on:keydown.escape.stop.prevent="close(true)"
                x-bind:style="panelStyle"
                class="fixed z-[80] flex flex-col overflow-hidden rounded-xl border border-hairline bg-white shadow-xl"
            >
                <div class="flex items-center gap-2 border-b border-hairline bg-mist/70 px-3 py-2">
                    <svg class="size-4 shrink-0 text-ink-muted" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="9" cy="9" r="5.5" />
                        <path stroke-linecap="round" d="m16.5 16.5-3.6-3.6" />
                    </svg>
                    <input
                        type="text"
                        role="combobox"
                        x-ref="searchInput"
                        x-model="query"
                        x-on:input="activeIndex = 0"
                        x-on:keydown.arrow-down.prevent="move(1)"
                        x-on:keydown.arrow-up.prevent="move(-1)"
                        x-on:keydown.enter.prevent="selectHighlighted()"
                        x-on:keydown.tab="close(false)"
                        x-bind:placeholder="searchPlaceholder"
                        aria-label="Cari pilihan"
                        autocomplete="off"
                        class="min-h-8 w-full border-0! bg-transparent! px-0! py-1! text-sm text-ink shadow-none! ring-0! focus:ring-0! focus:outline-none!"
                    />
                    <button
                        type="button"
                        x-show="query.length > 0"
                        x-on:click="query = ''; activeIndex = 0; $refs.searchInput?.focus()"
                        aria-label="Hapus pencarian"
                        class="inline-flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-md text-ink-muted hover:bg-hairline/60 hover:text-ink"
                    >
                        &times;
                    </button>
                </div>

                <ul
                    role="listbox"
                    x-ref="listbox"
                    class="max-h-64 overflow-y-auto p-1.5"
                >
                    <template x-for="(opt, idx) in filteredOptions" :key="opt.key">
                        <li
                            role="option"
                            x-bind:aria-selected="(opt.value === selectedValue).toString()"
                            x-bind:data-opt-idx="idx"
                            x-on:mouseenter="activeIndex = idx"
                            x-on:mousedown.prevent
                            x-on:click.stop="selectOption(opt)"
                            x-bind:class="[
                                'flex min-h-10 cursor-pointer items-center justify-between gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors',
                                opt.disabled ? 'pointer-events-none opacity-45' : '',
                                opt.value === selectedValue
                                    ? 'bg-primary-soft font-semibold text-primary'
                                    : (idx === activeIndex ? 'bg-mist text-ink' : 'text-ink-body hover:bg-mist hover:text-ink')
                            ]"
                        >
                            <span class="min-w-0 flex-1 break-words leading-snug">
                                <span class="block" x-text="opt.label"></span>
                                <span x-show="opt.group" class="mt-0.5 block text-[11px] font-normal text-ink-muted" x-text="opt.group"></span>
                            </span>
                            <svg
                                x-show="opt.value === selectedValue"
                                class="size-4 shrink-0 text-primary"
                                viewBox="0 0 20 20"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.2"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 10.5 3.5 3.5 7.5-8" />
                            </svg>
                        </li>
                    </template>
                    <li
                        x-show="filteredOptions.length === 0"
                        class="px-3 py-6 text-center text-xs text-ink-muted"
                    >
                        Tidak ada pilihan yang cocok.
                    </li>
                </ul>
            </div>
        </template>
    </div>
</div>

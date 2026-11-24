@props([
    'model',
    'id' => null,
    'label' => 'Editor Konten',
    'placeholder' => 'Tulis isi konten lengkap di sini...',
    'minHeight' => 280,
    'compact' => false,
])
@php
    $editorId = $id ?: 'rich-editor-' . str_replace(['.', '_'], '-', $model) . '-' . uniqid();
@endphp

<div
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-[#d8e2dd] bg-white shadow-2xs']) }}
    wire:ignore
    x-data="satkerRichEditor({
        model: @js($model),
        uploadUrl: @js(route('admin.media.upload', [], false)),
        csrfToken: @js(csrf_token()),
        tinymceBaseUrl: @js(parse_url(asset('vendor/tinymce'), PHP_URL_PATH) ?: '/vendor/tinymce'),
        placeholder: @js($placeholder),
        minHeight: {{ (int) $minHeight }},
        compact: @js((bool) $compact),
    })"
>
    <!-- Studio Toolbar Header: Quick Upload (Left) + Editor | Code | Preview Mode Tabs (Right) -->
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#e2e8e5] bg-[#f8faf9] px-3 py-2">
        <div class="flex flex-wrap items-center gap-1.5">
            <input
                type="file"
                x-ref="imageInput"
                accept="image/jpeg,image/png,image/webp,image/gif"
                class="sr-only"
                aria-label="Unggah gambar ke dalam editor"
                x-on:change="handleQuickUpload($event, 'image')"
            />
            <button
                type="button"
                x-on:click="$refs.imageInput.click()"
                x-bind:disabled="uploading"
                class="inline-flex h-8.5 cursor-pointer items-center gap-1.5 rounded-lg border border-[#d8e2dd] bg-white px-2.5 text-xs font-semibold text-primary transition-colors hover:border-primary/40 hover:bg-primary-soft disabled:opacity-50"
            >
                <svg class="size-3.5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <rect x="2.5" y="3.5" width="15" height="13" rx="2" />
                    <circle cx="7" cy="8" r="1.25" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4 14.5 3.8-3.8a1.2 1.2 0 0 1 1.7 0l2.2 2.2 1.8-1.8a1.2 1.2 0 0 1 1.7 0L17 13" />
                </svg>
                <span>Unggah Gambar</span>
            </button>

            <input
                type="file"
                x-ref="pdfInput"
                accept="application/pdf,.pdf"
                class="sr-only"
                aria-label="Unggah dokumen PDF ke dalam editor"
                x-on:change="handleQuickUpload($event, 'pdf')"
            />
            <button
                type="button"
                x-on:click="$refs.pdfInput.click()"
                x-bind:disabled="uploading"
                class="inline-flex h-8.5 cursor-pointer items-center gap-1.5 rounded-lg border border-[#d8e2dd] bg-white px-2.5 text-xs font-semibold text-primary transition-colors hover:border-primary/40 hover:bg-primary-soft disabled:opacity-50"
            >
                <svg class="size-3.5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 2.75h5.5L15.75 7v10.25a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-13.5a1 1 0 0 1 1-1Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 2.75V7.25h4.5M7.75 11.25h4.5M7.75 14h3" />
                </svg>
                <span>Unggah PDF</span>
            </button>
        </div>

        <!-- 3-Mode Tab Switcher (Editor | Code | Preview) -->
        <div role="tablist" aria-label="Mode tampilan editor {{ $label }}" class="inline-flex items-center rounded-lg border border-[#d8e2dd] bg-[#eef3f1] p-0.5">
            <button
                type="button"
                role="tab"
                x-on:click="switchMode('editor')"
                x-bind:aria-selected="(mode === 'editor').toString()"
                x-bind:class="mode === 'editor' ? 'bg-white text-primary font-bold shadow-2xs' : 'text-[#475569] font-medium hover:text-[#0f172a]'"
                class="inline-flex h-7.5 cursor-pointer items-center gap-1.5 rounded-md px-3 text-xs transition-colors"
            >
                <svg class="size-3.5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m13.5 3.5 3 3-9 9H4.5v-3l9-9Z" />
                </svg>
                <span>Editor</span>
            </button>
            <button
                type="button"
                role="tab"
                x-on:click="switchMode('code')"
                x-bind:aria-selected="(mode === 'code').toString()"
                x-bind:class="mode === 'code' ? 'bg-white text-primary font-bold shadow-2xs' : 'text-[#475569] font-medium hover:text-[#0f172a]'"
                class="inline-flex h-7.5 cursor-pointer items-center gap-1.5 rounded-md px-3 text-xs transition-colors"
            >
                <svg class="size-3.5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m7 6-4 4 4 4M13 6l4 4-4 4M11.25 4.5l-2.5 11" />
                </svg>
                <span>Code</span>
            </button>
            <button
                type="button"
                role="tab"
                x-on:click="switchMode('preview')"
                x-bind:aria-selected="(mode === 'preview').toString()"
                x-bind:class="mode === 'preview' ? 'bg-white text-primary font-bold shadow-2xs' : 'text-[#475569] font-medium hover:text-[#0f172a]'"
                class="inline-flex h-7.5 cursor-pointer items-center gap-1.5 rounded-md px-3 text-xs transition-colors"
            >
                <svg class="size-3.5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 10s3-5.25 7.75-5.25S17.75 10 17.75 10s-3 5.25-7.75 5.25S2.25 10 2.25 10Z" />
                    <circle cx="10" cy="10" r="2.25" />
                </svg>
                <span>Preview</span>
            </button>
        </div>
    </div>

    <!-- Upload Feedback Strip (only shown when active) -->
    <div
        x-cloak
        x-show="uploading || uploadMessage || uploadError"
        class="border-b border-[#e2e8e5] bg-[#f9fbfa] px-3 py-1.5 text-xs"
    >
        <span x-show="uploading" class="font-semibold text-primary">Mengunggah berkas...</span>
        <span x-show="!uploading && uploadMessage" x-text="uploadMessage" class="font-semibold text-emerald-700"></span>
        <span x-show="!uploading && uploadError" x-text="uploadError" class="font-semibold text-red-700"></span>
    </div>

    <!-- Mode 1: Visual WYSIWYG Editor (TinyMCE 8) -->
    <div x-show="mode === 'editor'" class="satker-tinymce-shell p-2">
        <textarea
            id="{{ $editorId }}"
            x-ref="textarea"
            aria-label="{{ $label }}"
            placeholder="{{ $placeholder }}"
            rows="{{ $compact ? 4 : 10 }}"
            class="w-full rounded-md border border-[#e2e8e5] bg-white p-3 text-sm text-ink focus:border-primary focus:outline-none"
            x-on:input="syncFromTextarea()"
        ></textarea>
    </div>

    <!-- Mode 2: Clean HTML Source Code Mode -->
    <div x-cloak x-show="mode === 'code'" class="space-y-2 bg-[#f8faf9] p-3">
        <div class="flex flex-wrap items-center justify-between gap-2 text-2xs text-[#64748b]">
            <span class="font-mono font-semibold text-[#334155]">Kode Sumber HTML (tersinkronisasi otomatis dengan mode Editor)</span>
            <button
                type="button"
                x-on:click="prettifyCode()"
                class="inline-flex h-7 cursor-pointer items-center rounded-md border border-[#d8e2dd] bg-white px-2.5 font-sans text-[11px] font-semibold text-primary hover:border-primary/40 hover:bg-primary-soft"
            >
                Rapikan Indentasi HTML
            </button>
        </div>
        <textarea
            x-ref="codeTextarea"
            x-model="codeValue"
            x-on:input="syncFromCode()"
            spellcheck="false"
            aria-label="Kode HTML {{ $label }}"
            rows="{{ $compact ? 6 : 16 }}"
            style="min-height: {{ max(160, (int) $minHeight) }}px;"
            class="w-full rounded-lg border border-[#d8e2dd] bg-white p-3.5 font-mono text-[12.5px] leading-relaxed text-[#0f172a] focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
        ></textarea>
    </div>

    <!-- Mode 3: Live Public Portal Preview -->
    <div x-cloak x-show="mode === 'preview'" class="space-y-2.5 bg-[#f8faf9] p-3 sm:p-4">
        <div class="flex flex-wrap items-center justify-between gap-2 text-2xs text-[#64748b]">
            <span class="font-mono font-semibold uppercase tracking-wide text-primary">Pratinjau Tampilan Portal Publik</span>
            <span>Mendukung format teks, tabel responsif, gambar &amp; lampiran PDF</span>
        </div>
        <div
            style="min-height: {{ max(160, (int) $minHeight) }}px;"
            class="overflow-x-auto rounded-lg border border-[#e2e8e5] bg-white p-5 sm:p-6"
        >
            <div
                x-show="previewHtml().trim() !== ''"
                x-html="previewHtml()"
                class="prose max-w-none space-y-4 text-sm leading-relaxed text-ink-body"
            ></div>
            <p x-show="previewHtml().trim() === ''" class="py-10 text-center text-xs text-[#64748b]">
                Belum ada konten untuk dipratinjau. Tulis konten pada tab Editor atau Code terlebih dahulu.
            </p>
        </div>
    </div>
</div>

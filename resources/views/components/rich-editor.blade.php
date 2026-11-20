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
    wire:ignore
    x-data="satkerRichEditor({
        model: @js($model),
        uploadUrl: @js(route('admin.media.upload')),
        csrfToken: @js(csrf_token()),
        placeholder: @js($placeholder),
        minHeight: {{ (int) $minHeight }},
        compact: @js((bool) $compact),
    })"
    class="space-y-2 rounded-lg border border-hairline bg-white p-2.5"
>
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline pb-2">
        <div class="flex flex-wrap items-center gap-2">
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
                class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-md border border-hairline bg-mist px-3 text-xs font-semibold text-primary hover:bg-primary-soft"
            >
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
                class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-md border border-hairline bg-mist px-3 text-xs font-semibold text-primary hover:bg-primary-soft"
            >
                <span>Unggah PDF</span>
            </button>
        </div>

        <div class="text-2xs text-ink-muted">
            <span x-show="uploading" class="font-semibold text-primary">Mengunggah berkas...</span>
            <span x-show="!uploading && uploadMessage" x-text="uploadMessage" class="font-semibold text-emerald-700"></span>
            <span x-show="!uploading && uploadError" x-text="uploadError" class="font-semibold text-red-700"></span>
            <span x-show="!uploading && !uploadMessage && !uploadError">Mendukung format teks, tabel, gambar (JPG/PNG/WEBP), &amp; lampiran PDF (maks. 10 MB)</span>
        </div>
    </div>

    <textarea
        id="{{ $editorId }}"
        x-ref="textarea"
        aria-label="{{ $label }}"
        placeholder="{{ $placeholder }}"
        rows="{{ $compact ? 4 : 8 }}"
        class="w-full rounded-md border border-hairline bg-white p-3 text-sm text-ink"
        x-on:input="syncFromTextarea()"
    ></textarea>
</div>


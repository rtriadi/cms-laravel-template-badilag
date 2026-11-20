@php
    $satker = \App\Models\SatkerSetting::current();
    $user = auth()->user();
    $tingkatLabel = $satker?->tingkat === 'banding' ? 'Tingkat Banding' : 'Tingkat Pertama';

    $commands = collect([
        ['label' => 'Dashboard Kepatuhan', 'url' => route('admin.dashboard'), 'show' => true],
        ['label' => 'Link Kriteria SINOVA', 'url' => route('admin.sinova-links'), 'show' => $user?->can('manage-sinova-links') || $user?->hasRole('super_admin')],
        ['label' => 'Menu Navigasi', 'url' => route('admin.menus'), 'show' => $user?->can('manage-menus') || $user?->hasRole('super_admin')],
        ['label' => 'Halaman & Blok', 'url' => route('admin.pages'), 'show' => $user?->can('manage-pages') || $user?->hasRole('super_admin')],
        ['label' => 'Berita & Publikasi', 'url' => route('admin.posts'), 'show' => $user?->can('manage-ppid-humas') || $user?->hasRole('super_admin') || $user?->hasRole('admin_konten')],
        ['label' => 'Dokumen SAKIP & Keuangan', 'url' => route('admin.documents'), 'show' => $user?->can('manage-kesekretariatan') || $user?->can('manage-kepaniteraan') || $user?->can('manage-ppid-humas') || $user?->hasRole('super_admin')],
        ['label' => 'Kepaniteraan & Pegawai', 'url' => route('admin.kepaniteraan'), 'show' => $user?->can('manage-kepaniteraan') || $user?->can('manage-kesekretariatan') || $user?->hasRole('super_admin')],
        ['label' => 'Slider Beranda', 'url' => route('admin.sliders'), 'show' => $user?->can('manage-pages') || $user?->hasRole('super_admin')],
        ['label' => 'Pengaturan Satker', 'url' => route('admin.settings'), 'show' => $user?->can('manage-satker-settings') || $user?->hasRole('super_admin')],
        ['label' => 'Manajemen Kriteria', 'url' => route('admin.criteria'), 'show' => $user?->can('manage-criteria') || $user?->hasRole('super_admin')],
        ['label' => 'Pengguna & Peran (RBAC)', 'url' => route('admin.users'), 'show' => $user?->can('manage-users') || $user?->hasRole('super_admin')],
        ['label' => 'Audit Tautan & Log', 'url' => route('admin.audit'), 'show' => $user?->hasAnyRole(['super_admin', 'admin_konten', 'penilai_internal'])],
        ['label' => 'Keamanan 2FA', 'url' => route('admin.two-factor.setup'), 'show' => true],
    ])->filter(fn (array $item): bool => (bool) $item['show'])->values();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Panel Admin' }} - {{ $satker?->nama_satker ?? 'SatkerCMS' }}</title>
    @if (is_file(public_path('build/fonts-manifest.json')) || \Illuminate\Support\Facades\Vite::isRunningHot())
        {{ \Illuminate\Support\Facades\Vite::fonts() }}
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>try{localStorage.setItem('flux.appearance','light')}catch(e){}document.documentElement.classList.remove('dark');</script>
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
    <script>
        window.satkerRichEditor = function (config) {
            return {
                model: config.model,
                uploadUrl: config.uploadUrl,
                csrfToken: config.csrfToken,
                placeholder: config.placeholder || '',
                minHeight: config.minHeight || 280,
                compact: Boolean(config.compact),
                uploading: false,
                uploadMessage: '',
                uploadError: '',
                editorInstance: null,

                init() {
                    const initialValue = this.$wire.get(this.model) || '';
                    if (this.$refs.textarea) {
                        this.$refs.textarea.value = initialValue;
                    }

                    this.$watch(() => this.$wire.get(this.model), (newVal) => {
                        const normalized = newVal ?? '';
                        if (this.editorInstance) {
                            if (this.editorInstance.getContent() !== normalized) {
                                this.editorInstance.setContent(normalized);
                            }
                        } else if (this.$refs.textarea && this.$refs.textarea.value !== normalized) {
                            this.$refs.textarea.value = normalized;
                        }
                    });

                    if (typeof window.tinymce === 'undefined' || !this.$refs.textarea) {
                        return;
                    }

                    window.tinymce.init({
                        target: this.$refs.textarea,
                        license_key: 'gpl',
                        base_url: '/vendor/tinymce',
                        suffix: '.min',
                        menubar: !this.compact,
                        branding: false,
                        promotion: false,
                        statusbar: true,
                        min_height: this.minHeight,
                        placeholder: this.placeholder,
                        relative_urls: false,
                        remove_script_host: true,
                        convert_urls: true,
                        plugins: this.compact
                            ? 'lists link image table code autoresize'
                            : 'lists link image table code fullscreen searchreplace wordcount preview autoresize',
                        toolbar: this.compact
                            ? 'undo redo | bold italic underline | bullist numlist | link image table | code'
                            : 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | link image table blockquote | code fullscreen',
                        content_style: "body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; font-size: 14px; color: #111827; line-height: 1.65; margin: 12px; } table { border-collapse: collapse; width: 100%; margin: 8px 0; } th, td { border: 1px solid #d8e2dd; padding: 6px 10px; } th { background: #f0f7f4; font-weight: 700; } img { max-width: 100%; height: auto; border-radius: 6px; } a { color: #056e4f; font-weight: 600; text-decoration: underline; } blockquote { border-left: 4px solid #056e4f; padding-left: 12px; color: #374151; margin-left: 0; }",
                        images_upload_handler: (blobInfo) => this.uploadBlob(blobInfo.blob(), blobInfo.filename()),
                        file_picker_types: 'file image',
                        file_picker_callback: (cb, value, meta) => {
                            const input = document.createElement('input');
                            input.type = 'file';
                            input.accept = meta.filetype === 'image'
                                ? 'image/jpeg,image/png,image/webp,image/gif'
                                : 'image/jpeg,image/png,image/webp,image/gif,application/pdf,.pdf';
                            input.onchange = async () => {
                                const file = input.files && input.files[0];
                                if (!file) {
                                    return;
                                }
                                try {
                                    const res = await this.uploadFileToServer(file);
                                    if (res && res.location) {
                                        cb(res.location, {
                                            title: res.name || file.name,
                                            text: res.name || file.name,
                                            alt: res.name || file.name,
                                        });
                                    }
                                } catch (e) {}
                            };
                            input.click();
                        },
                        setup: (editor) => {
                            this.editorInstance = editor;
                            editor.on('init', () => {
                                editor.setContent(this.$wire.get(this.model) || '');
                            });
                            const syncToWire = () => {
                                const content = editor.getContent();
                                if (this.$refs.textarea) {
                                    this.$refs.textarea.value = content;
                                }
                                this.$wire.set(this.model, content, false);
                            };
                            editor.on('change input Undo Redo SetContent blur', syncToWire);
                        },
                    });
                },

                syncFromTextarea() {
                    if (this.$refs.textarea) {
                        this.$wire.set(this.model, this.$refs.textarea.value, false);
                    }
                },

                escapeHtml(str) {
                    return String(str || '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                },

                async uploadFileToServer(file) {
                    this.uploading = true;
                    this.uploadMessage = '';
                    this.uploadError = '';

                    const formData = new FormData();
                    formData.append('file', file);

                    try {
                        const response = await fetch(this.uploadUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            const msg = (data && data.errors && data.errors.file && data.errors.file[0])
                                || (data && data.message)
                                || 'Gagal mengunggah berkas.';
                            this.uploadError = msg;
                            throw new Error(msg);
                        }

                        this.uploadMessage = 'Berkas berhasil disisipkan: ' + (data.name || file.name);
                        return data;
                    } catch (err) {
                        if (!this.uploadError) {
                            this.uploadError = err.message || 'Gagal mengunggah berkas.';
                        }
                        throw err;
                    } finally {
                        this.uploading = false;
                    }
                },

                async uploadBlob(blob, filename) {
                    const file = new File([blob], filename || 'image.png', { type: blob.type || 'image/png' });
                    const data = await this.uploadFileToServer(file);
                    return data.location;
                },

                async handleQuickUpload(event, type) {
                    const input = event.target;
                    const file = input.files && input.files[0];
                    if (!file) {
                        return;
                    }

                    try {
                        const data = await this.uploadFileToServer(file);
                        const safeName = this.escapeHtml(data.name || file.name);
                        const safeUrl = this.escapeHtml(data.location);
                        const safeDate = this.escapeHtml(data.uploaded_at || '');

                        const html = (type === 'pdf' || data.is_pdf)
                            ? `<p><a href="${safeUrl}" target="_blank" rel="noopener noreferrer"><strong>Unduh Dokumen PDF:</strong> ${safeName}</a>${safeDate ? ` <span class="text-xs text-ink-muted">(Diunggah: ${safeDate})</span>` : ''}</p>`
                            : `<p><img src="${safeUrl}" alt="${safeName}" /></p>`;

                        if (this.editorInstance) {
                            this.editorInstance.insertContent(html);
                            const updated = this.editorInstance.getContent();
                            if (this.$refs.textarea) {
                                this.$refs.textarea.value = updated;
                            }
                            this.$wire.set(this.model, updated, false);
                        } else if (this.$refs.textarea) {
                            this.$refs.textarea.value = (this.$refs.textarea.value || '') + '\n' + html;
                            this.$wire.set(this.model, this.$refs.textarea.value, false);
                        }
                    } catch (e) {
                    } finally {
                        input.value = '';
                    }
                },

                destroy() {
                    if (this.editorInstance) {
                        try {
                            this.editorInstance.remove();
                        } catch (e) {}
                        this.editorInstance = null;
                    }
                },
            };
        };
    </script>
</head>
<body
    class="min-h-screen bg-mist font-sans text-ink antialiased"
    x-data="{
        cmdOpen: false,
        cmdQuery: '',
        cmdActive: 0,
        cmdTrap: null,
        items: @js($commands),
        get filteredItems() {
            const q = this.cmdQuery.trim().toLowerCase();
            if (! q) {
                return this.items;
            }
            return this.items.filter(i => i.label.toLowerCase().includes(q));
        },
        openCmd() {
            this.cmdOpen = true;
            this.cmdQuery = '';
            this.cmdActive = 0;
            this.$nextTick(() => {
                this.cmdTrap = window.createFocusTrap(this.$refs.cmdDialog, this.$refs.cmdInput);
            });
        },
        closeCmd() {
            this.cmdOpen = false;
            this.cmdTrap?.();
            this.cmdTrap = null;
        },
        moveCmd(step) {
            const total = this.filteredItems.length;
            if (total === 0) {
                return;
            }
            this.cmdActive = (this.cmdActive + step + total) % total;
        },
        runCmd() {
            const item = this.filteredItems[this.cmdActive];
            if (item) {
                window.location.assign(item.url);
            }
        }
    }"
    x-on:keydown.window="if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openCmd(); } else if (event.key === 'Escape' && cmdOpen) { closeCmd(); }"
>
    <div class="min-h-screen lg:flex">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-hairline bg-white lg:w-64">
            <flux:sidebar.header class="flex flex-col items-stretch gap-2 border-b border-hairline pb-3">
                <div class="flex items-center justify-between gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="font-serif text-lg font-bold text-primary-deep">
                        SatkerCMS
                    </a>
                    <flux:sidebar.collapse class="hidden lg:inline-flex" />
                    <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
                </div>
                <div class="space-y-1 in-data-flux-sidebar-collapsed-desktop:hidden">
                    <p class="truncate text-xs font-semibold text-ink">
                        {{ $satker?->nama_satker ?? 'Satuan Kerja' }}
                    </p>
                    <span class="inline-flex items-center rounded border border-emerald-200 bg-primary-soft px-2 py-0.5 font-mono text-2xs font-medium text-primary">
                        {{ $tingkatLabel }}
                    </span>
                </div>
            </flux:sidebar.header>

            <flux:sidebar.nav class="flex-1 space-y-4 overflow-y-auto py-2">
                <flux:sidebar.group heading="Utama">
                    <flux:sidebar.item
                        icon="squares-2x2"
                        href="{{ route('admin.dashboard') }}"
                        :current="request()->routeIs('admin.dashboard')"
                        class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                    >
                        Dashboard Kepatuhan
                    </flux:sidebar.item>

                    @if ($user?->can('manage-sinova-links') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="link"
                            href="{{ route('admin.sinova-links') }}"
                            :current="request()->routeIs('admin.sinova-links')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Link Kriteria SINOVA
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group heading="Konten Website">
                    @if ($user?->can('manage-menus') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="bars-3-bottom-left"
                            href="{{ route('admin.menus') }}"
                            :current="request()->routeIs('admin.menus')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Menu Navigasi
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-pages') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="document-duplicate"
                            href="{{ route('admin.pages') }}"
                            :current="request()->routeIs('admin.pages')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Halaman Website
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="photo"
                            href="{{ route('admin.sliders') }}"
                            :current="request()->routeIs('admin.sliders')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Slider Beranda
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                        <flux:sidebar.item
                            icon="newspaper"
                            href="{{ route('admin.posts') }}"
                            :current="request()->routeIs('admin.posts')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Berita, Artikel &amp; Pengumuman
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-kesekretariatan') || $user?->can('manage-kepaniteraan') || $user?->can('manage-ppid-humas') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="folder-open"
                            href="{{ route('admin.documents') }}"
                            :current="request()->routeIs('admin.documents')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Arsip Dokumen PDF
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-kepaniteraan') || $user?->can('manage-kesekretariatan') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="scale"
                            href="{{ route('admin.kepaniteraan') }}"
                            :current="request()->routeIs('admin.kepaniteraan')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Pegawai, Sidang &amp; Statistik
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group heading="Sistem &amp; Keamanan">
                    @if ($user?->can('manage-satker-settings') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="building-library"
                            href="{{ route('admin.settings') }}"
                            :current="request()->routeIs('admin.settings')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Pengaturan Satker
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-criteria') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="clipboard-document-check"
                            href="{{ route('admin.criteria') }}"
                            :current="request()->routeIs('admin.criteria')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Manajemen Kriteria
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-users') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="users"
                            href="{{ route('admin.users') }}"
                            :current="request()->routeIs('admin.users')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Pengguna &amp; Peran
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->hasAnyRole(['super_admin', 'admin_konten', 'penilai_internal']))
                        <flux:sidebar.item
                            icon="shield-check"
                            href="{{ route('admin.audit') }}"
                            :current="request()->routeIs('admin.audit')"
                            class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                        >
                            Audit &amp; Redirect 301
                        </flux:sidebar.item>
                    @endif

                    <flux:sidebar.item
                        icon="key"
                        href="{{ route('admin.two-factor.setup') }}"
                        :current="request()->routeIs('admin.two-factor.setup')"
                        class="min-h-11 data-current:border-s-4 data-current:border-primary data-current:bg-primary-soft data-current:font-semibold data-current:text-primary"
                    >
                        Keamanan 2FA
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <div class="mt-auto border-t border-hairline pt-3 in-data-flux-sidebar-collapsed-desktop:hidden">
                <div class="rounded-md border border-hairline bg-mist p-3">
                    <p class="truncate text-xs font-semibold text-ink">{{ $user?->name }}</p>
                    <p @class([
                        'mt-0.5 font-mono text-2xs',
                        'text-emerald-700' => $user?->two_factor_confirmed_at !== null,
                        'text-amber-700' => $user?->two_factor_confirmed_at === null,
                    ])>
                        {{ $user?->two_factor_confirmed_at !== null ? '2FA TOTP Aktif' : '2FA Belum Aktif' }}
                    </p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 w-full cursor-pointer items-center justify-center rounded border border-hairline bg-white px-2 text-xs font-semibold text-ink hover:bg-mist">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </flux:sidebar>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-10 flex min-h-16 items-center justify-between gap-3 border-b border-hairline bg-white px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        data-flux-sidebar-toggle
                        aria-label="Buka atau tutup menu navigasi admin"
                        class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-md border border-hairline bg-white px-3 text-sm font-semibold text-ink lg:hidden"
                        x-on:click="$dispatch('flux-sidebar-toggle')"
                    >
                        <span>Menu</span>
                    </button>
                    <h1 class="font-serif text-lg font-bold text-ink sm:text-xl">
                        {{ $title ?? 'Dashboard Kepatuhan' }}
                    </h1>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        x-on:click="openCmd()"
                        aria-label="Buka pencarian cepat perintah admin"
                        class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-md border border-hairline bg-mist px-3 text-xs font-medium text-ink-muted hover:text-ink"
                    >
                        <span>Cari cepat...</span>
                        <kbd class="hidden rounded border border-hairline bg-white px-1.5 py-0.5 font-mono text-2xs text-ink sm:inline">Ctrl+K</kbd>
                    </button>

                    <a
                        href="{{ route('home') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-11 items-center rounded-md border border-primary bg-white px-3 text-xs font-semibold text-primary hover:bg-primary-soft"
                    >
                        Lihat Website
                    </a>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1600px] flex-1 p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <div role="status" class="mb-6 rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Command Palette (Ctrl+K) -->
    <div
        x-cloak
        x-show="cmdOpen"
        x-ref="cmdDialog"
        role="dialog"
        aria-modal="true"
        aria-label="Navigasi Cepat Admin"
        class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 p-4 pt-20"
        x-on:click.self="closeCmd()"
    >
        <div class="w-full max-w-lg rounded-xl border border-hairline bg-white p-4 shadow-lg">
            <div class="flex items-center gap-2 border-b border-hairline pb-3">
                <label for="admin-cmd-input" class="sr-only">Cari menu admin</label>
                <input
                    id="admin-cmd-input"
                    x-ref="cmdInput"
                    x-model="cmdQuery"
                    x-on:input="cmdActive = 0"
                    x-on:keydown.arrow-down.prevent="moveCmd(1)"
                    x-on:keydown.arrow-up.prevent="moveCmd(-1)"
                    x-on:keydown.enter.prevent="runCmd()"
                    type="text"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-controls="admin-cmd-list"
                    x-bind:aria-activedescendant="'admin-cmd-item-' + cmdActive"
                    autocomplete="off"
                    placeholder="Ketik nama menu admin..."
                    class="min-h-11 w-full rounded-md border border-hairline px-3 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary"
                >
                <button
                    type="button"
                    x-on:click="closeCmd()"
                    class="inline-flex min-h-11 cursor-pointer items-center rounded-md border border-hairline px-3 text-xs font-semibold text-ink-muted hover:text-ink"
                >
                    Esc
                </button>
            </div>
            <ul id="admin-cmd-list" x-ref="cmdList" role="listbox" aria-label="Menu admin" class="mt-3 max-h-72 space-y-1 overflow-y-auto">
                <template x-for="(item, i) in filteredItems" :key="item.url">
                    <li
                        role="option"
                        x-bind:id="'admin-cmd-item-' + i"
                        x-bind:aria-selected="i === cmdActive ? 'true' : 'false'"
                        x-on:mouseenter="cmdActive = i"
                    >
                        <a
                            :href="item.url"
                            x-bind:class="i === cmdActive ? 'bg-primary-soft text-primary' : 'text-ink'"
                            class="flex min-h-11 items-center justify-between rounded-md px-3 text-sm font-medium hover:bg-primary-soft hover:text-primary"
                            x-text="item.label"
                        ></a>
                    </li>
                </template>
                <li x-show="filteredItems.length === 0" class="px-3 py-4 text-center text-sm text-ink-muted">
                    Tidak ada menu yang cocok dengan kata kunci tersebut.
                </li>
            </ul>
        </div>
    </div>

    @fluxScripts
</body>
</html>

@php
    $satker = \App\Models\SatkerSetting::current();
    $user = auth()->user();
    $tingkatLabel = $satker?->tingkat === 'banding' ? 'Tk. Banding' : 'Tk. Pertama';
    $canModerateComments = $user !== null && $user->can('viewAny', \App\Models\PostComment::class);
    $pendingCommentsBadge = $canModerateComments ? \App\Models\PostComment::cachedPendingCount() : 0;
    $userInitials = collect(preg_split('/\s+/', trim((string) ($user?->name ?? 'AD'))) ?: [])
        ->filter()
        ->take(2)
        ->map(static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: 'AD';
    $breadcrumbGroup = match (true) {
        request()->routeIs('admin.dashboard', 'admin.sinova-links') => 'Utama',
        request()->routeIs('admin.pages', 'admin.posts', 'admin.comments', 'admin.documents') => 'Editorial',
        request()->routeIs('admin.menus', 'admin.sliders', 'admin.kepaniteraan') => 'Struktur',
        default => 'Sistem',
    };

    $commands = collect([
        ['label' => 'Dashboard Studio & Kepatuhan', 'url' => route('admin.dashboard'), 'show' => true],
        ['label' => 'Link Kriteria SINOVA', 'url' => route('admin.sinova-links'), 'show' => $user?->can('manage-sinova-links') || $user?->hasRole('super_admin')],
        ['label' => 'Menu Navigasi', 'url' => route('admin.menus'), 'show' => $user?->can('manage-menus') || $user?->hasRole('super_admin')],
        ['label' => 'Halaman & Page Builder', 'url' => route('admin.pages'), 'show' => $user?->can('manage-pages') || $user?->hasRole('super_admin')],
        ['label' => 'Berita, Artikel & Pengumuman', 'url' => route('admin.posts'), 'show' => $user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten'])],
        ['label' => 'Moderasi Komentar Masyarakat', 'url' => route('admin.comments'), 'show' => $canModerateComments],
        ['label' => 'Arsip Dokumen PDF & SAKIP', 'url' => route('admin.documents'), 'show' => $user?->can('manage-kesekretariatan') || $user?->can('manage-kepaniteraan') || $user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten'])],
        ['label' => 'Pegawai, Sidang & Statistik', 'url' => route('admin.kepaniteraan'), 'show' => $user?->can('manage-kepaniteraan') || $user?->can('manage-kesekretariatan') || $user?->hasAnyRole(['super_admin', 'admin_konten'])],
        ['label' => 'Slider Beranda', 'url' => route('admin.sliders'), 'show' => $user?->can('manage-pages') || $user?->hasAnyRole(['super_admin', 'admin_konten'])],
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
    @if ($satker?->logo_path)
        <link rel="icon" href="{{ asset('storage/' . $satker->logo_path) }}">
    @endif
    <title>{{ $title ?? 'Panel Studio Admin' }} - {{ $satker?->nama_satker ?? 'SatkerCMS' }}</title>
    @if (is_file(public_path('build/fonts-manifest.json')) || \Illuminate\Support\Facades\Vite::isRunningHot())
        {{ \Illuminate\Support\Facades\Vite::fonts() }}
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>try{localStorage.setItem('flux.appearance','light')}catch(e){}document.documentElement.classList.remove('dark');</script>
    @php
        $tinymceScriptPath = parse_url(asset('vendor/tinymce/tinymce.min.js'), PHP_URL_PATH) ?: '/vendor/tinymce/tinymce.min.js';
    @endphp
    <script src="{{ $tinymceScriptPath }}"></script>
    <script>
        window.satkerRichEditor = function (config) {
            return {
                model: config.model,
                uploadUrl: config.uploadUrl,
                csrfToken: config.csrfToken,
                tinymceBaseUrl: config.tinymceBaseUrl || '/vendor/tinymce',
                placeholder: config.placeholder || '',
                minHeight: config.minHeight || 280,
                compact: Boolean(config.compact),
                mode: 'editor',
                codeValue: '',
                uploading: false,
                uploadMessage: '',
                uploadError: '',
                editorInstance: null,
                unwatchWire: null,
                isDestroyed: false,
                syncingFromEditor: false,

                init() {
                    const initialValue = this.$wire.get(this.model) || '';
                    this.codeValue = this.formatHtml(initialValue);

                    if (this.$refs.textarea) {
                        this.$refs.textarea.value = initialValue;
                    }

                    if (this.$wire && typeof this.$wire.$watch === 'function') {
                        this.unwatchWire = this.$wire.$watch(this.model, (newVal) => {
                            if (this.syncingFromEditor) {
                                return;
                            }
                            const normalized = newVal ?? '';
                            if (this.mode === 'code') {
                                if (this.codeValue !== normalized) {
                                    this.codeValue = this.formatHtml(normalized);
                                }
                            } else {
                                this.codeValue = this.formatHtml(normalized);
                            }

                            if (this.editorInstance) {
                                if (this.editorInstance.getContent() !== normalized) {
                                    this.editorInstance.setContent(normalized);
                                }
                            } else if (this.$refs.textarea && this.$refs.textarea.value !== normalized) {
                                this.$refs.textarea.value = normalized;
                            }
                        });
                    }

                    this.$nextTick(() => {
                        requestAnimationFrame(() => {
                            this.mountTinyMce();
                        });
                    });
                },

                mountTinyMce() {
                    if (this.isDestroyed || typeof window.tinymce === 'undefined' || !this.$refs.textarea) {
                        return;
                    }

                    const targetEl = this.$refs.textarea;
                    if (!targetEl.isConnected) {
                        setTimeout(() => this.mountTinyMce(), 60);
                        return;
                    }

                    if (targetEl.id) {
                        const stale = window.tinymce.get(targetEl.id);
                        if (stale) {
                            try {
                                stale.remove();
                            } catch (e) {}
                        }
                    }

                    const fullToolbar = [
                        'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | removeformat',
                        'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table blockquote hr | charmap searchreplace visualblocks fullscreen',
                    ];
                    const compactToolbar = 'undo redo | bold italic underline | bullist numlist | link image | removeformat';

                    window.tinymce.init({
                        target: targetEl,
                        license_key: 'gpl',
                        base_url: this.tinymceBaseUrl,
                        suffix: '.min',
                        menubar: this.compact ? false : 'file edit view insert format table',
                        branding: false,
                        promotion: false,
                        statusbar: true,
                        elementpath: !this.compact,
                        min_height: this.minHeight,
                        max_height: this.compact ? 320 : 680,
                        autoresize_bottom_margin: 24,
                        placeholder: this.placeholder,
                        relative_urls: false,
                        remove_script_host: true,
                        convert_urls: true,
                        image_caption: true,
                        image_advtab: true,
                        object_resizing: true,
                        toolbar_mode: 'wrap',
                        plugins: this.compact
                            ? 'lists link image table code autoresize wordcount'
                            : 'lists advlist link image table code fullscreen searchreplace wordcount preview autoresize charmap visualblocks',
                        toolbar: this.compact ? compactToolbar : fullToolbar,
                        content_style: "body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; font-size: 14.5px; color: #1e293b; line-height: 1.75; margin: 16px 18px; background: #ffffff; } p { margin: 0 0 0.9em; } h1, h2, h3, h4 { font-family: 'Source Serif 4', Georgia, serif; color: #0f172a; font-weight: 700; margin: 1.1em 0 0.5em; } table { border-collapse: collapse; width: 100%; margin: 12px 0; } th, td { border: 1px solid #d8e2dd; padding: 8px 10px; vertical-align: top; } th { background: #f0f7f4; font-weight: 700; text-align: left; } img { max-width: 100%; height: auto; border-radius: 8px; display: inline-block; } a { color: #056e4f; font-weight: 600; text-decoration: underline; } blockquote { border-left: 4px solid #056e4f; padding: 6px 14px; color: #334155; background: #f8faf9; margin: 12px 0; border-radius: 0 6px 6px 0; }",
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
                                if (this.isDestroyed) {
                                    try {
                                        editor.remove();
                                    } catch (e) {}
                                    return;
                                }
                                const currentVal = this.$wire.get(this.model) || '';
                                editor.setContent(currentVal);
                                this.codeValue = this.formatHtml(currentVal);
                            });

                            const syncToWire = () => {
                                const content = editor.getContent();
                                this.codeValue = content;
                                if (this.$refs.textarea) {
                                    this.$refs.textarea.value = content;
                                }
                                this.pushToWire(content);
                            };

                            editor.on('change input Undo Redo SetContent blur', syncToWire);
                        },
                    });
                },

                pushToWire(content) {
                    this.syncingFromEditor = true;
                    try {
                        this.$wire.set(this.model, content, false);
                    } finally {
                        setTimeout(() => {
                            this.syncingFromEditor = false;
                        }, 0);
                    }
                },

                getCurrentHtml() {
                    if (this.mode === 'code') {
                        return this.codeValue || '';
                    }
                    if (this.editorInstance) {
                        return this.editorInstance.getContent() || '';
                    }
                    return (this.$refs.textarea && this.$refs.textarea.value) || this.codeValue || '';
                },

                switchMode(nextMode) {
                    if (this.mode === nextMode) {
                        return;
                    }

                    if (this.mode === 'editor') {
                        const current = this.editorInstance
                            ? this.editorInstance.getContent()
                            : ((this.$refs.textarea && this.$refs.textarea.value) || '');
                        this.codeValue = this.formatHtml(current);
                        this.pushToWire(current);
                    } else if (this.mode === 'code') {
                        const raw = this.codeValue || '';
                        if (this.editorInstance) {
                            this.editorInstance.setContent(raw);
                        }
                        if (this.$refs.textarea) {
                            this.$refs.textarea.value = raw;
                        }
                        this.pushToWire(raw);
                    }

                    this.mode = nextMode;

                    if (nextMode === 'editor') {
                        this.$nextTick(() => {
                            if (this.editorInstance) {
                                try {
                                    this.editorInstance.execCommand('mceAutoResize');
                                } catch (e) {}
                            }
                        });
                    }
                },

                prettifyCode() {
                    this.codeValue = this.formatHtml(this.codeValue || '');
                    this.syncFromCode();
                },

                formatHtml(html) {
                    const raw = String(html || '').trim();
                    if (!raw) {
                        return '';
                    }
                    return raw
                        .replace(/>\s+</g, '><')
                        .replace(/(<\/(?:p|div|h[1-6]|ul|ol|li|table|thead|tbody|tr|blockquote|figure|section|article|hr)>)/gi, '$1\n')
                        .replace(/(<(?:p|div|h[1-6]|ul|ol|li|table|thead|tbody|tr|blockquote|figure|hr)\b[^>]*>)/gi, '\n$1')
                        .replace(/\n{3,}/g, '\n\n')
                        .trim();
                },

                previewHtml() {
                    const raw = this.getCurrentHtml();
                    if (!raw || !raw.trim()) {
                        return '';
                    }
                    const container = document.createElement('div');
                    container.innerHTML = raw;
                    container.querySelectorAll('script, style, iframe, object, embed, form').forEach((el) => el.remove());
                    container.querySelectorAll('*').forEach((el) => {
                        Array.from(el.attributes).forEach((attr) => {
                            const name = attr.name.toLowerCase();
                            const val = attr.value.trim().toLowerCase();
                            if (name.startsWith('on') || ((name === 'href' || name === 'src') && val.startsWith('javascript:'))) {
                                el.removeAttribute(attr.name);
                            }
                        });
                    });
                    container.querySelectorAll('table').forEach((tbl) => {
                        if (!tbl.parentElement || !tbl.parentElement.classList.contains('prose-table-wrap')) {
                            const wrap = document.createElement('div');
                            wrap.className = 'prose-table-wrap';
                            tbl.parentNode.insertBefore(wrap, tbl);
                            wrap.appendChild(tbl);
                        }
                    });
                    return container.innerHTML;
                },

                syncFromTextarea() {
                    if (this.$refs.textarea) {
                        this.codeValue = this.$refs.textarea.value;
                        this.pushToWire(this.$refs.textarea.value);
                    }
                },

                syncFromCode() {
                    const val = this.codeValue || '';
                    if (this.$refs.textarea) {
                        this.$refs.textarea.value = val;
                    }
                    this.pushToWire(val);
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

                        if (this.mode === 'code' && this.$refs.codeTextarea) {
                            const el = this.$refs.codeTextarea;
                            const start = el.selectionStart ?? this.codeValue.length;
                            const end = el.selectionEnd ?? this.codeValue.length;
                            this.codeValue = this.codeValue.slice(0, start) + '\n' + html + '\n' + this.codeValue.slice(end);
                            if (this.editorInstance) {
                                this.editorInstance.setContent(this.codeValue);
                            }
                            this.syncFromCode();
                        } else if (this.editorInstance) {
                            this.editorInstance.insertContent(html);
                            const updated = this.editorInstance.getContent();
                            this.codeValue = this.formatHtml(updated);
                            if (this.$refs.textarea) {
                                this.$refs.textarea.value = updated;
                            }
                            this.pushToWire(updated);
                        } else if (this.$refs.textarea) {
                            this.$refs.textarea.value = (this.$refs.textarea.value || '') + '\n' + html;
                            this.codeValue = this.$refs.textarea.value;
                            this.pushToWire(this.$refs.textarea.value);
                        }
                    } catch (e) {
                    } finally {
                        input.value = '';
                    }
                },

                destroy() {
                    this.isDestroyed = true;
                    if (typeof this.unwatchWire === 'function') {
                        try {
                            this.unwatchWire();
                        } catch (e) {}
                        this.unwatchWire = null;
                    }
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
        sidebarCollapsed: false,
        mobileSidebarOpen: false,
        cmdOpen: false,
        createOpen: false,
        cmdQuery: '',
        cmdActive: 0,
        cmdTrap: null,
        items: @js($commands),
        init() {
            try {
                this.sidebarCollapsed = localStorage.getItem('satker.sidebar.collapsed') === '1';
            } catch (e) {}
        },
        toggleSidebar() {
            if (window.innerWidth >= 1024) {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                try {
                    localStorage.setItem('satker.sidebar.collapsed', this.sidebarCollapsed ? '1' : '0');
                } catch (e) {}
            } else {
                this.mobileSidebarOpen = !this.mobileSidebarOpen;
            }
        },
        toggleDesktopSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            try {
                localStorage.setItem('satker.sidebar.collapsed', this.sidebarCollapsed ? '1' : '0');
            } catch (e) {}
        },
        closeMobileSidebar() {
            this.mobileSidebarOpen = false;
        },
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
    x-on:keydown.window="if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openCmd(); } else if (event.key === 'Escape') { if (cmdOpen) closeCmd(); createOpen = false; mobileSidebarOpen = false; }"
>
    <div class="min-h-screen lg:flex">
        <!-- Mobile Sidebar Backdrop -->
        <div
            x-cloak
            x-show="mobileSidebarOpen"
            x-on:click="closeMobileSidebar()"
            aria-hidden="true"
            class="fixed inset-0 z-30 bg-black/50 backdrop-blur-2xs lg:hidden"
        ></div>

        <flux:sidebar
            sticky
            x-bind:data-studio-collapsed="sidebarCollapsed ? 'true' : 'false'"
            x-bind:data-mobile-open="mobileSidebarOpen ? 'true' : 'false'"
            class="studio-sidebar border-e border-[#104436] bg-[#06261d] text-[#cfe9df]"
        >
            <flux:sidebar.header class="flex flex-col items-stretch gap-2 border-b border-[#104436] pb-3">
                <div class="studio-sidebar-toprow flex items-center justify-between gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="studio-sidebar-brand flex min-w-0 items-center gap-2.5 text-white">
                        @if ($satker?->logo_path)
                            <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-[9px] border-[1.5px] border-gold bg-white p-1 shadow-2xs">
                                <img
                                    src="{{ asset('storage/' . $satker->logo_path) }}"
                                    alt="Logo {{ $satker->nama_satker }}"
                                    width="36"
                                    height="36"
                                    class="size-full object-contain"
                                />
                            </span>
                        @else
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-[9px] border-[1.5px] border-gold bg-[#0c3b2e] text-gold shadow-2xs">
                                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z" />
                                </svg>
                            </span>
                        @endif
                        <div class="studio-sidebar-label min-w-0">
                            <p class="truncate font-serif text-[15px] leading-tight font-bold text-white">
                                {{ $satker?->nama_satker ? \Illuminate\Support\Str::limit($satker->nama_satker, 20) : 'SatkerCMS' }}
                            </p>
                            <p class="mt-0.5 truncate font-mono text-[10px] text-[#7eb7a3]">
                                SatkerCMS &middot; {{ $tingkatLabel }}
                            </p>
                        </div>
                    </a>

                    <button
                        type="button"
                        x-on:click="toggleDesktopSidebar()"
                        x-bind:aria-expanded="(!sidebarCollapsed).toString()"
                        x-bind:title="sidebarCollapsed ? 'Perluas sidebar navigasi' : 'Ciutkan sidebar navigasi'"
                        aria-label="Ciutkan atau perluas sidebar navigasi"
                        class="studio-sidebar-toggle hidden size-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-[#104436] bg-[#0c3b2e] text-[#cfe9df] transition-colors hover:border-gold/60 hover:bg-primary hover:text-white lg:inline-flex"
                    >
                        <svg class="size-4 transition-transform duration-200" x-bind:class="sidebarCollapsed ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75v12.5M3.5 16.25h13c.55 0 1-.45 1-1V4.75c0-.55-.45-1-1-1h-13c-.55 0-1 .45-1 1v10.5c0 .55.45 1 1 1Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="m12.75 7.75-2.25 2.25 2.25 2.25" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        x-on:click="closeMobileSidebar()"
                        aria-label="Tutup menu navigasi"
                        class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-[#104436] bg-[#0c3b2e] text-[#cfe9df] hover:bg-primary hover:text-white lg:hidden"
                    >
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5l10 10M15 5 5 15" />
                        </svg>
                    </button>
                </div>
            </flux:sidebar.header>

            <flux:sidebar.nav class="flex-1 space-y-3 overflow-y-auto py-1">
                <flux:sidebar.group heading="Utama">
                    <flux:sidebar.item
                        icon="squares-2x2"
                        href="{{ route('admin.dashboard') }}"
                        :current="request()->routeIs('admin.dashboard')"
                        class="min-h-10"
                    >
                        Dashboard
                    </flux:sidebar.item>

                    @if ($user?->can('manage-sinova-links') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="link"
                            href="{{ route('admin.sinova-links') }}"
                            :current="request()->routeIs('admin.sinova-links')"
                            class="min-h-10"
                        >
                            Link SINOVA
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group heading="Editorial">
                    @if ($user?->can('manage-pages') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="document-duplicate"
                            href="{{ route('admin.pages') }}"
                            :current="request()->routeIs('admin.pages')"
                            class="min-h-10"
                        >
                            Halaman
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                        <flux:sidebar.item
                            icon="newspaper"
                            href="{{ route('admin.posts') }}"
                            :current="request()->routeIs('admin.posts')"
                            class="min-h-10"
                        >
                            Publikasi
                        </flux:sidebar.item>
                    @endif

                    @if ($canModerateComments)
                        <flux:sidebar.item
                            icon="chat-bubble-left-right"
                            href="{{ route('admin.comments') }}"
                            :current="request()->routeIs('admin.comments')"
                            :badge="$pendingCommentsBadge > 0 ? $pendingCommentsBadge : null"
                            class="min-h-10"
                        >
                            Komentar
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-kesekretariatan') || $user?->can('manage-kepaniteraan') || $user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                        <flux:sidebar.item
                            icon="folder-open"
                            href="{{ route('admin.documents') }}"
                            :current="request()->routeIs('admin.documents')"
                            class="min-h-10"
                        >
                            Dokumen PDF
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group heading="Struktur">
                    @if ($user?->can('manage-menus') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="bars-3-bottom-left"
                            href="{{ route('admin.menus') }}"
                            :current="request()->routeIs('admin.menus')"
                            class="min-h-10"
                        >
                            Menu Navigasi
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-pages') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                        <flux:sidebar.item
                            icon="photo"
                            href="{{ route('admin.sliders') }}"
                            :current="request()->routeIs('admin.sliders')"
                            class="min-h-10"
                        >
                            Slider Beranda
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-kepaniteraan') || $user?->can('manage-kesekretariatan') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                        <flux:sidebar.item
                            icon="scale"
                            href="{{ route('admin.kepaniteraan') }}"
                            :current="request()->routeIs('admin.kepaniteraan')"
                            class="min-h-10"
                        >
                            Kepaniteraan
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group heading="Sistem">
                    @if ($user?->can('manage-satker-settings') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="building-library"
                            href="{{ route('admin.settings') }}"
                            :current="request()->routeIs('admin.settings')"
                            class="min-h-10"
                        >
                            Pengaturan Satker
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-criteria') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="clipboard-document-check"
                            href="{{ route('admin.criteria') }}"
                            :current="request()->routeIs('admin.criteria')"
                            class="min-h-10"
                        >
                            Kriteria SINOVA
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-users') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="users"
                            href="{{ route('admin.users') }}"
                            :current="request()->routeIs('admin.users')"
                            class="min-h-10"
                        >
                            Pengguna &amp; Peran
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->hasAnyRole(['super_admin', 'admin_konten', 'penilai_internal']))
                        <flux:sidebar.item
                            icon="shield-check"
                            href="{{ route('admin.audit') }}"
                            :current="request()->routeIs('admin.audit')"
                            class="min-h-10"
                        >
                            Audit &amp; Keamanan
                        </flux:sidebar.item>
                    @endif

                    <flux:sidebar.item
                        icon="key"
                        href="{{ route('admin.two-factor.setup') }}"
                        :current="request()->routeIs('admin.two-factor.setup')"
                        class="min-h-10"
                    >
                        Keamanan 2FA
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <div class="mt-auto border-t border-[#104436] pt-3">
                <div class="studio-sidebar-meta flex items-center justify-between gap-2 px-1">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex size-8.5 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">
                            {{ $userInitials }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-white">{{ $user?->name }}</p>
                            <p class="mt-0.5 flex items-center gap-1.5 font-mono text-[10px] text-[#7eb7a3]">
                                <span @class([
                                    'inline-block size-1.5 rounded-full',
                                    'bg-emerald-400' => $user?->two_factor_confirmed_at !== null,
                                    'bg-amber-400' => $user?->two_factor_confirmed_at === null,
                                ])></span>
                                <span>{{ $user?->two_factor_confirmed_at !== null ? '2FA TOTP Aktif' : '2FA Belum Aktif' }}</span>
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button
                            type="submit"
                            title="Keluar dari panel admin"
                            aria-label="Keluar dari panel admin"
                            class="inline-flex size-8 cursor-pointer items-center justify-center rounded-lg border border-[#104436] bg-[#0c3b2e] text-[#cfe9df] transition-colors hover:bg-primary hover:text-white"
                        >
                            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12.5 6.25V4.75a1.5 1.5 0 0 0-1.5-1.5h-6a1.5 1.5 0 0 0-1.5 1.5v10.5a1.5 1.5 0 0 0 1.5 1.5h6a1.5 1.5 0 0 0 1.5-1.5v-1.5M8.5 10h8m0 0-2.5-2.5M16.5 10 14 12.5" />
                            </svg>
                        </button>
                    </form>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="studio-sidebar-collapsed-only hidden">
                    @csrf
                    <button
                        type="submit"
                        title="Keluar ({{ $user?->name }})"
                        aria-label="Keluar dari panel admin"
                        class="mx-auto flex size-9 cursor-pointer items-center justify-center rounded-full bg-primary text-xs font-bold text-white transition-colors hover:bg-primary-hover"
                    >
                        {{ $userInitials }}
                    </button>
                </form>
            </div>
        </flux:sidebar>

        <div class="flex min-w-0 flex-1 flex-col bg-[#f5f7f6]">
            <header class="sticky top-0 z-20 flex h-14 items-center justify-between gap-3 border-b border-[#e2e8e5] bg-white/95 px-4 backdrop-blur-xs sm:px-6 lg:px-7">
                <div class="flex min-w-0 items-center gap-2.5">
                    <button
                        type="button"
                        data-flux-sidebar-toggle
                        x-on:click="toggleSidebar()"
                        x-bind:aria-expanded="mobileSidebarOpen.toString()"
                        aria-label="Buka atau tutup menu navigasi admin"
                        class="inline-flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-[#e2e8e5] bg-[#f5f7f6] text-ink transition-colors hover:border-primary hover:bg-primary-soft hover:text-primary lg:hidden"
                    >
                        <svg class="size-4.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 5.25h13M3.5 10h13M3.5 14.75h8.5" />
                        </svg>
                    </button>
                    <nav aria-label="Breadcrumb panel admin" class="flex min-w-0 items-center gap-2 text-[13px]">
                        <span class="hidden font-medium text-[#64748b] sm:inline">{{ $breadcrumbGroup }}</span>
                        <span class="hidden text-[#cbd5e1] sm:inline" aria-hidden="true">/</span>
                        <h1 class="truncate font-semibold text-[#0f172a]">
                            {{ $title ?? 'Dashboard Studio & Kepatuhan' }}
                        </h1>
                    </nav>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        x-on:click="openCmd()"
                        aria-label="Buka pencarian cepat perintah admin"
                        class="inline-flex h-9 cursor-pointer items-center gap-3 rounded-lg border border-[#e2e8e5] bg-[#f5f7f6] px-3 text-xs font-medium text-[#64748b] transition-colors hover:border-[#cbd5e1] hover:text-[#0f172a]"
                    >
                        <span class="hidden sm:inline">Cari menu &amp; konten...</span>
                        <span class="sm:hidden">Cari...</span>
                        <kbd class="hidden rounded border border-[#e2e8e5] bg-white px-1.5 py-0.5 font-mono text-[10px] font-medium text-[#64748b] sm:inline">Ctrl K</kbd>
                    </button>

                    <!-- Quick + Buat Baru Studio Dropdown -->
                    <div class="relative hidden sm:block" x-on:click.outside="createOpen = false">
                        <button
                            type="button"
                            x-on:click="createOpen = !createOpen"
                            aria-expanded="false"
                            x-bind:aria-expanded="createOpen ? 'true' : 'false'"
                            class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-3.5 text-xs font-semibold text-white shadow-2xs transition-colors hover:bg-primary-hover"
                        >
                            <span>+ Buat Baru</span>
                        </button>
                        <div
                            x-cloak
                            x-show="createOpen"
                            class="absolute right-0 mt-1.5 w-60 rounded-xl border border-[#e2e8e5] bg-white p-1.5 shadow-lg"
                        >
                            @if ($user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                                <a href="{{ route('admin.posts', ['kategori' => 'berita']) }}" class="flex min-h-9 items-center rounded-lg px-3 text-xs font-semibold text-ink hover:bg-primary-soft hover:text-primary">Tulis Berita / Pengumuman</a>
                            @endif
                            @if ($user?->can('manage-pages') || $user?->hasRole('super_admin'))
                                <a href="{{ route('admin.pages') }}" class="flex min-h-9 items-center rounded-lg px-3 text-xs font-semibold text-ink hover:bg-primary-soft hover:text-primary">Buka Page Builder</a>
                            @endif
                            @if ($user?->can('manage-kesekretariatan') || $user?->can('manage-kepaniteraan') || $user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                                <a href="{{ route('admin.documents') }}" class="flex min-h-9 items-center rounded-lg px-3 text-xs font-semibold text-ink hover:bg-primary-soft hover:text-primary">Unggah Dokumen PDF</a>
                            @endif
                        </div>
                    </div>

                    <a
                        href="{{ route('home') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex h-9 items-center rounded-lg border border-[#e2e8e5] bg-white px-3 text-xs font-semibold text-primary transition-colors hover:border-primary/40 hover:bg-primary-soft"
                    >
                        Lihat Situs &nearr;
                    </a>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1480px] flex-1 p-4 sm:p-6 lg:p-7">
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

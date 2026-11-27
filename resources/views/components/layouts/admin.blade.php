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
        request()->routeIs('admin.pages', 'admin.posts', 'admin.comments', 'admin.documents') => 'Konten website',
        request()->routeIs('admin.menus', 'admin.sliders') => 'Tampilan beranda',
        request()->routeIs('admin.kepaniteraan') => 'Data pengadilan',
        default => 'Sistem',
    };
    $primaryRoleLabel = match ($user?->roles->first()?->name) {
        'super_admin' => 'Super Admin',
        'admin_konten' => 'Admin Konten',
        'operator_kepaniteraan' => 'Op. Kepaniteraan',
        'operator_kesekretariatan' => 'Op. Kesekretariatan',
        'operator_ppid_humas' => 'Op. PPID & Humas',
        'penilai_internal' => 'Penilai Internal',
        default => 'Pengelola',
    };

    $commands = collect([
        ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'show' => true],
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
    <meta name="robots" content="noindex, nofollow">
    @if ($satker?->logo_path)
        <link rel="icon" href="{{ asset('storage/' . $satker->logo_path) }}">
    @endif
    <title>{{ $title ?? 'Panel Admin' }} - {{ $satker?->nama_satker ?? 'SatkerCMS' }}</title>
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
                        'alignleft aligncenter alignright alignjustify | firstlineindent outdent indent | bullist numlist | link image table blockquote hr | charmap searchreplace visualblocks fullscreen',
                    ];
                    const compactToolbar = 'undo redo | bold italic underline | firstlineindent | bullist numlist | link image | removeformat';

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
                        formats: {
                            first_line_indent: {
                                selector: 'p',
                                styles: { 'text-indent': '2.5em' },
                            },
                        },
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

                            editor.ui.registry.addIcon(
                                'first-line-indent',
                                '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 6h11M4 11h16M4 16h16M4 20h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M4 4.5l3 2-3 2V4.5z" fill="currentColor"/></svg>'
                            );

                            const applyFirstLineToAll = (enable) => {
                                editor.undoManager.transact(() => {
                                    const paragraphs = editor.dom.select('p');
                                    paragraphs.forEach((p) => {
                                        if (!enable) {
                                            editor.dom.setStyle(p, 'text-indent', '');
                                            return;
                                        }
                                        const text = (p.textContent || '').replace(/\u00a0/g, ' ').trim();
                                        const hasOnlyMedia = text === '' && p.querySelector('img, figure, br');
                                        const align = (p.style.textAlign || '').toLowerCase();
                                        if (text === '' || hasOnlyMedia || align === 'center' || align === 'right') {
                                            return;
                                        }
                                        editor.dom.setStyle(p, 'text-indent', '2.5em');
                                    });
                                });
                                editor.nodeChanged();
                                editor.fire('change');
                            };

                            editor.ui.registry.addSplitButton('firstlineindent', {
                                icon: 'first-line-indent',
                                tooltip: 'Alinea: Baris Pertama Menjorok (Alt+Shift+I)',
                                onAction: () => {
                                    editor.formatter.toggle('first_line_indent');
                                    editor.nodeChanged();
                                    editor.fire('change');
                                },
                                onItemAction: (_api, value) => {
                                    if (value === 'apply_all') {
                                        applyFirstLineToAll(true);
                                    } else if (value === 'remove_all') {
                                        applyFirstLineToAll(false);
                                    }
                                },
                                fetch: (callback) => {
                                    callback([
                                        {
                                            type: 'choiceitem',
                                            text: 'Terapkan Alinea ke Semua Paragraf Teks',
                                            value: 'apply_all',
                                        },
                                        {
                                            type: 'choiceitem',
                                            text: 'Hapus Alinea Semua Paragraf',
                                            value: 'remove_all',
                                        },
                                    ]);
                                },
                                onSetup: (api) => {
                                    const unbind = editor.formatter.formatChanged('first_line_indent', (state) => {
                                        api.setActive(state);
                                    });
                                    return () => {
                                        if (unbind && typeof unbind.unbind === 'function') {
                                            unbind.unbind();
                                        }
                                    };
                                },
                            });

                            editor.addShortcut('alt+shift+i', 'Toggle alinea baris pertama paragraf', () => {
                                editor.formatter.toggle('first_line_indent');
                                editor.nodeChanged();
                                editor.fire('change');
                            });

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
                    const parsed = new DOMParser().parseFromString(raw, 'text/html');
                    const container = parsed.body;
                    if (!container) {
                        return '';
                    }
                    container.querySelectorAll('script, style, iframe, object, embed, form, svg, math, template, base, link, meta').forEach((el) => el.remove());
                    container.querySelectorAll('*').forEach((el) => {
                        Array.from(el.attributes).forEach((attr) => {
                            const name = attr.name.toLowerCase();
                            const val = attr.value.replace(/[\u0000-\u0020]+/g, '').toLowerCase();
                            if (
                                name.startsWith('on')
                                || name === 'srcdoc'
                                || name === 'formaction'
                                || ((name === 'href' || name === 'src' || name === 'action' || name === 'xlink:href')
                                    && (val.startsWith('javascript:') || val.startsWith('vbscript:') || val.startsWith('data:')))
                            ) {
                                el.removeAttribute(attr.name);
                            }
                        });
                    });
                    container.querySelectorAll('table').forEach((tbl) => {
                        if (!tbl.parentElement || !tbl.parentElement.classList.contains('prose-table-wrap')) {
                            const wrap = parsed.createElement('div');
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
                if (!this.mobileSidebarOpen) {
                    this.$nextTick(() => this.$refs.mobileMenuTrigger?.focus());
                }
            }
        },
        toggleDesktopSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            try {
                localStorage.setItem('satker.sidebar.collapsed', this.sidebarCollapsed ? '1' : '0');
            } catch (e) {}
        },
        closeMobileSidebar() {
            const wasOpen = this.mobileSidebarOpen;
            this.mobileSidebarOpen = false;
            if (wasOpen) {
                this.$nextTick(() => this.$refs.mobileMenuTrigger?.focus());
            }
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
    x-on:keydown.window="if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openCmd(); } else if (event.key === 'Escape') { if (cmdOpen) closeCmd(); createOpen = false; closeMobileSidebar(); }"
    x-bind:class="mobileSidebarOpen ? 'overflow-hidden lg:overflow-auto' : ''"
>
    <!-- Pita Identitas Emas 3px (SK Dirjen Badilag) -->
    <div class="sticky top-0 z-30 h-[3px] w-full bg-gold" aria-hidden="true"></div>

    <a href="#admin-main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-obsidian focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-white">
        Lompat ke konten utama
    </a>

    <div class="min-h-[calc(100dvh-3px)] md:flex">
        <!-- Mobile / Tablet Sidebar Backdrop -->
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
            class="studio-sidebar border-e border-[#1a4d3e] bg-obsidian text-[#d3e8df]"
        >
            <flux:sidebar.header class="flex flex-col items-stretch gap-2 border-b border-[#1a4d3e] pb-3.5">
                <div class="studio-sidebar-toprow flex items-center justify-between gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="studio-sidebar-brand flex min-w-0 flex-1 items-center gap-2.5 text-white">
                        @if ($satker?->logo_path)
                            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-[9px] border-[1.5px] border-gold bg-white p-1">
                                <img
                                    src="{{ asset('storage/' . $satker->logo_path) }}"
                                    alt="Logo {{ $satker->nama_satker }}"
                                    width="40"
                                    height="40"
                                    class="size-full object-contain"
                                />
                            </span>
                        @else
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-[9px] border-[1.5px] border-gold bg-white font-mono text-[10px] font-bold text-primary">
                                LOGO
                            </span>
                        @endif
                        <div class="studio-sidebar-label min-w-0 flex-1">
                            <p
                                class="line-clamp-2 font-serif text-[14.5px] leading-[1.25] font-bold text-white"
                                title="{{ $satker?->nama_satker ?? 'SatkerCMS' }}"
                            >
                                {{ $satker?->nama_satker ?: 'SatkerCMS' }}
                            </p>
                            <p class="mt-0.5 truncate text-[11px] font-medium text-[#8dbfac]">
                                Panel Admin &middot; {{ $tingkatLabel }}
                            </p>
                        </div>
                    </a>

                    <button
                        type="button"
                        x-on:click="closeMobileSidebar()"
                        aria-label="Tutup menu navigasi"
                        class="studio-sidebar-close-btn inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-1.5 rounded-lg border border-[#1a4d3e] bg-[#0d4434] px-2.5 text-xs font-semibold text-white hover:bg-primary lg:hidden"
                    >
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5l10 10M15 5 5 15" />
                        </svg>
                        <span>Tutup</span>
                    </button>
                </div>
            </flux:sidebar.header>

            <flux:sidebar.nav class="flex-1 space-y-3 overflow-y-auto py-1.5">
                <flux:sidebar.group heading="Utama">
                    <flux:sidebar.item
                        icon="squares-2x2"
                        href="{{ route('admin.dashboard') }}"
                        :current="request()->routeIs('admin.dashboard')"
                        title="Dashboard"
                        class="min-h-11 lg:min-h-10"
                    >
                        Dashboard
                    </flux:sidebar.item>

                    @if ($user?->can('manage-sinova-links') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="link"
                            href="{{ route('admin.sinova-links') }}"
                            :current="request()->routeIs('admin.sinova-links')"
                            title="Link SINOVA"
                            class="min-h-11 lg:min-h-10"
                        >
                            Link SINOVA
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group heading="Konten website">
                    @if ($user?->can('manage-pages') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="document-duplicate"
                            href="{{ route('admin.pages') }}"
                            :current="request()->routeIs('admin.pages')"
                            title="Halaman"
                            class="min-h-11 lg:min-h-10"
                        >
                            Halaman
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                        <flux:sidebar.item
                            icon="newspaper"
                            href="{{ route('admin.posts') }}"
                            :current="request()->routeIs('admin.posts')"
                            title="Publikasi"
                            class="min-h-11 lg:min-h-10"
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
                            title="Komentar"
                            class="min-h-11 lg:min-h-10"
                        >
                            Komentar
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-kesekretariatan') || $user?->can('manage-kepaniteraan') || $user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                        <flux:sidebar.item
                            icon="folder-open"
                            href="{{ route('admin.documents') }}"
                            :current="request()->routeIs('admin.documents')"
                            title="Dokumen PDF"
                            class="min-h-11 lg:min-h-10"
                        >
                            Dokumen PDF
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                <flux:sidebar.group heading="Tampilan beranda">
                    @if ($user?->can('manage-pages') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                        <flux:sidebar.item
                            icon="photo"
                            href="{{ route('admin.sliders') }}"
                            :current="request()->routeIs('admin.sliders')"
                            title="Slider Beranda"
                            class="min-h-11 lg:min-h-10"
                        >
                            Slider Beranda
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-menus') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="bars-3-bottom-left"
                            href="{{ route('admin.menus') }}"
                            :current="request()->routeIs('admin.menus')"
                            title="Menu Navigasi"
                            class="min-h-11 lg:min-h-10"
                        >
                            Menu Navigasi
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                @if ($user?->can('manage-kepaniteraan') || $user?->can('manage-kesekretariatan') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                    <flux:sidebar.group heading="Data pengadilan">
                        <flux:sidebar.item
                            icon="scale"
                            href="{{ route('admin.kepaniteraan') }}"
                            :current="request()->routeIs('admin.kepaniteraan')"
                            title="Kepaniteraan"
                            class="min-h-11 lg:min-h-10"
                        >
                            Kepaniteraan
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                <flux:sidebar.group heading="Sistem">
                    @if ($user?->can('manage-satker-settings') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="building-library"
                            href="{{ route('admin.settings') }}"
                            :current="request()->routeIs('admin.settings')"
                            title="Pengaturan Satker"
                            class="min-h-11 lg:min-h-10"
                        >
                            Pengaturan Satker
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-criteria') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="clipboard-document-check"
                            href="{{ route('admin.criteria') }}"
                            :current="request()->routeIs('admin.criteria')"
                            title="Kriteria SINOVA"
                            class="min-h-11 lg:min-h-10"
                        >
                            Kriteria SINOVA
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->can('manage-users') || $user?->hasRole('super_admin'))
                        <flux:sidebar.item
                            icon="users"
                            href="{{ route('admin.users') }}"
                            :current="request()->routeIs('admin.users')"
                            title="Pengguna & Peran"
                            class="min-h-11 lg:min-h-10"
                        >
                            Pengguna &amp; Peran
                        </flux:sidebar.item>
                    @endif

                    @if ($user?->hasAnyRole(['super_admin', 'admin_konten', 'penilai_internal']))
                        <flux:sidebar.item
                            icon="shield-check"
                            href="{{ route('admin.audit') }}"
                            :current="request()->routeIs('admin.audit')"
                            title="Audit & Backup"
                            class="min-h-11 lg:min-h-10"
                        >
                            Audit &amp; Backup
                        </flux:sidebar.item>
                    @endif

                    <flux:sidebar.item
                        icon="key"
                        href="{{ route('admin.two-factor.setup') }}"
                        :current="request()->routeIs('admin.two-factor.setup')"
                        title="Keamanan 2FA"
                        class="min-h-11 lg:min-h-10"
                    >
                        Keamanan 2FA
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <div class="mt-auto space-y-2.5 border-t border-[#1a4d3e] pt-3">
                <div class="studio-sidebar-meta flex items-center justify-between gap-2 px-1">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-[#0d4434] text-xs font-bold text-gold">
                            {{ $userInitials }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-white">{{ $user?->name }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-[#8dbfac]">
                                {{ $primaryRoleLabel }} &middot; {{ $user?->two_factor_confirmed_at !== null ? '2FA aktif' : '2FA belum aktif' }}
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button
                            type="submit"
                            title="Keluar dari panel admin"
                            aria-label="Keluar dari panel admin"
                            class="inline-flex size-10 cursor-pointer items-center justify-center rounded-lg border border-[#1a4d3e] bg-[#0d4434] text-[#d3e8df] transition-colors hover:bg-primary hover:text-white"
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
                        class="mx-auto flex size-10 cursor-pointer items-center justify-center rounded-full bg-[#0d4434] text-xs font-bold text-gold transition-colors hover:bg-primary hover:text-white"
                    >
                        {{ $userInitials }}
                    </button>
                </form>
            </div>
        </flux:sidebar>

        <div class="flex min-w-0 flex-1 flex-col overflow-x-clip bg-mist">
            <header class="sticky top-[3px] z-20 flex min-h-16 items-center justify-between gap-2 border-b border-hairline bg-white px-3.5 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-2.5">
                    <button
                        type="button"
                        x-ref="mobileMenuTrigger"
                        data-flux-sidebar-toggle
                        x-on:click="toggleSidebar()"
                        x-bind:aria-expanded="mobileSidebarOpen.toString()"
                        aria-label="Buka menu navigasi admin"
                        class="inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-1.5 rounded-lg border border-hairline bg-mist px-3 text-xs font-semibold text-ink transition-colors hover:border-primary hover:bg-primary-soft hover:text-primary lg:hidden"
                    >
                        <svg class="size-4.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 5.25h13M3.5 10h13M3.5 14.75h9" />
                        </svg>
                        <span>Menu</span>
                    </button>

                    <button
                        type="button"
                        x-on:click="toggleDesktopSidebar()"
                        x-bind:aria-expanded="(!sidebarCollapsed).toString()"
                        aria-label="Ciutkan atau perluas navigasi samping"
                        class="hidden size-9 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-hairline bg-white text-ink-body transition-colors hover:border-primary hover:bg-primary-soft hover:text-primary lg:inline-flex"
                    >
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <rect x="3" y="4" width="14" height="12" rx="1.5" />
                            <path d="M7.5 4v12" />
                        </svg>
                    </button>

                    <nav aria-label="Breadcrumb panel admin" class="flex min-w-0 items-center gap-2 text-sm">
                        <span class="hidden font-medium text-ink-muted md:inline">{{ $breadcrumbGroup }}</span>
                        <svg class="hidden size-3.5 shrink-0 text-ink-muted/60 md:inline" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5 10.5 8 6 12.5" />
                        </svg>
                        <span class="truncate font-semibold text-ink">
                            {{ $title ?? 'Dashboard' }}
                        </span>
                    </nav>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        x-on:click="openCmd()"
                        aria-label="Buka pencarian cepat menu admin"
                        class="inline-flex min-h-11 cursor-pointer items-center gap-2.5 rounded-lg border border-hairline bg-mist px-3 text-xs font-medium text-ink-muted transition-colors hover:border-primary/50 hover:text-ink sm:min-h-10 md:w-48 lg:w-72"
                    >
                        <svg class="size-4 shrink-0 text-ink-muted" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="9" cy="9" r="5.5" />
                            <path stroke-linecap="round" d="m16.5 16.5-3.6-3.6" />
                        </svg>
                        <span class="hidden truncate text-left md:inline md:flex-1">Cari menu, berita, dokumen</span>
                        <span class="sr-only md:hidden">Cari</span>
                        <kbd class="hidden rounded border border-hairline bg-white px-1.5 py-0.5 font-mono text-[11px] font-medium text-ink-muted md:inline">Ctrl K</kbd>
                    </button>

                    <a
                        href="{{ route('home') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden min-h-10 items-center gap-1.5 rounded-lg border border-hairline bg-white px-3.5 text-xs font-semibold text-ink transition-colors hover:border-primary/50 hover:bg-primary-soft hover:text-primary sm:inline-flex"
                    >
                        <span>Lihat Website</span>
                        <svg class="size-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 3h3.5v3.5M13 3 7.5 8.5M11 9.5V12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h2.5" />
                        </svg>
                    </a>

                    <!-- Quick Buat Baru Dropdown -->
                    <div class="relative" x-on:click.outside="createOpen = false">
                        <button
                            type="button"
                            x-on:click="createOpen = !createOpen"
                            aria-expanded="false"
                            x-bind:aria-expanded="createOpen ? 'true' : 'false'"
                            aria-label="Buat konten baru"
                            class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-3.5 text-xs font-semibold text-white transition-colors hover:bg-primary-hover sm:min-h-10"
                        >
                            <svg class="size-4 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M10 4.5v11M4.5 10h11" />
                            </svg>
                            <span class="hidden sm:inline">Buat Baru</span>
                        </button>
                        <div
                            x-cloak
                            x-show="createOpen"
                            class="absolute right-0 mt-1.5 w-64 rounded-xl border border-hairline bg-white p-1.5 shadow-lg"
                        >
                            @if ($user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                                <a href="{{ route('admin.posts', ['kategori' => 'berita']) }}" class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-ink hover:bg-primary-soft hover:text-primary">Tulis Berita / Pengumuman</a>
                            @endif
                            @if ($user?->can('manage-pages') || $user?->hasRole('super_admin'))
                                <a href="{{ route('admin.pages') }}" class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-ink hover:bg-primary-soft hover:text-primary">Kelola Halaman Website</a>
                            @endif
                            @if ($user?->can('manage-kesekretariatan') || $user?->can('manage-kepaniteraan') || $user?->can('manage-ppid-humas') || $user?->hasAnyRole(['super_admin', 'admin_konten']))
                                <a href="{{ route('admin.documents') }}" class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-ink hover:bg-primary-soft hover:text-primary">Unggah Dokumen PDF</a>
                            @endif
                            <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="flex min-h-11 items-center rounded-lg border-t border-hairline/70 px-3 text-sm font-medium text-primary hover:bg-primary-soft sm:hidden">Lihat Website Publik</a>
                        </div>
                    </div>
                </div>
            </header>

            <main id="admin-main-content" class="mx-auto w-full max-w-[1440px] flex-1 p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <div role="status" class="mb-6 rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
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
        class="fixed inset-0 z-50 flex items-start justify-center bg-black/45 p-4 pt-16 sm:pt-24"
        x-on:click.self="closeCmd()"
    >
        <div class="w-full max-w-lg overflow-hidden rounded-xl border border-primary bg-white shadow-xl">
            <div class="flex items-center gap-2.5 border-b border-hairline px-4 py-3">
                <svg class="size-4.5 shrink-0 text-primary" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="9" cy="9" r="5.5" />
                    <path stroke-linecap="round" d="m16.5 16.5-3.6-3.6" />
                </svg>
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
                    placeholder="Ketik nama menu admin (mis. SINOVA, Publikasi, 2FA)..."
                    class="min-h-11 w-full border-0 bg-transparent px-1 text-sm text-ink shadow-none focus:ring-0 focus:outline-none"
                >
                <button
                    type="button"
                    x-on:click="closeCmd()"
                    class="inline-flex min-h-10 shrink-0 cursor-pointer items-center rounded-md border border-hairline bg-mist px-2.5 font-mono text-xs font-semibold text-ink-muted hover:text-ink"
                >
                    Esc
                </button>
            </div>
            <ul id="admin-cmd-list" x-ref="cmdList" role="listbox" aria-label="Menu admin" class="max-h-72 space-y-1 overflow-y-auto p-2">
                <template x-for="(item, i) in filteredItems" :key="item.url">
                    <li
                        role="option"
                        x-bind:id="'admin-cmd-item-' + i"
                        x-bind:aria-selected="i === cmdActive ? 'true' : 'false'"
                        x-on:mouseenter="cmdActive = i"
                    >
                        <a
                            :href="item.url"
                            x-bind:class="i === cmdActive ? 'bg-primary-soft text-primary font-semibold' : 'text-ink font-medium'"
                            class="flex min-h-11 items-center justify-between rounded-lg px-3 text-sm hover:bg-primary-soft hover:text-primary"
                        >
                            <span x-text="item.label"></span>
                            <kbd x-show="i === cmdActive" class="rounded border border-primary/30 bg-white px-1.5 py-0.5 font-mono text-[11px] text-primary">Enter</kbd>
                        </a>
                    </li>
                </template>
                <li x-show="filteredItems.length === 0" class="px-3 py-6 text-center text-sm text-ink-muted">
                    Tidak ada menu yang cocok dengan kata kunci tersebut.
                </li>
            </ul>
            <div class="border-t border-hairline bg-mist px-4 py-2 text-xs text-ink-muted">
                Navigasi: Panah Atas/Bawah &middot; Buka: Enter &middot; Tutup: Esc
            </div>
        </div>
    </div>

    @fluxScripts
</body>
</html>

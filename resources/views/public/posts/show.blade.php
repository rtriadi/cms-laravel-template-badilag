@php
    $postCanonical = route('public.posts.show', ['slug' => $post->slug]);
    $rawPostDesc = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($post->excerpt ?: $post->body))));
    $postDescription = $rawPostDesc !== ''
        ? \App\Support\SeoText::excerpt($rawPostDesc, 155)
        : $post->title;
    $postOgImage = $post->featured_image_path ? asset('storage/' . $post->featured_image_path) : null;
    $postSchemaImage = $postOgImage
        ?: ($satker?->foto_gedung_path ? asset('storage/' . $satker->foto_gedung_path) : ($satker?->logo_path ? asset('storage/' . $satker->logo_path) : null));
    $publishedIso = ($post->published_at ?? $post->created_at)?->toAtomString();
    $modifiedIso = ($post->updated_at ?? $post->published_at ?? $post->created_at)?->toAtomString();
    $categoryLabel = $categories[$post->category] ?? $post->category;

    $postSchemas = [
        array_filter([
            '@type' => 'NewsArticle',
            'headline' => $post->title,
            'description' => $postDescription,
            'articleSection' => $categoryLabel,
            'inLanguage' => 'id-ID',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $postCanonical,
            ],
            'image' => $postSchemaImage ? [$postSchemaImage] : null,
            'datePublished' => $publishedIso,
            'dateModified' => $modifiedIso,
            'author' => [
                '@type' => 'Organization',
                '@id' => route('home') . '#organization',
                'name' => $satker?->nama_satker ?? 'Portal Resmi Peradilan Agama',
                'url' => route('home'),
            ],
            'publisher' => [
                '@id' => route('home') . '#organization',
            ],
        ], static fn (mixed $v): bool => $v !== null),
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Beranda',
                    'item' => route('home'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Publikasi',
                    'item' => route('public.posts'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $post->title,
                    'item' => $postCanonical,
                ],
            ],
        ],
    ];
@endphp
<x-layouts.public
    :title="$post->title"
    :description="$postDescription"
    :canonical="$postCanonical"
    og-type="article"
    :og-image="$postOgImage"
    :og-image-path="$post->featured_image_path"
    :og-image-alt="$post->featured_image_alt ?: $post->title"
    :published-at="$publishedIso"
    :modified-at="$modifiedIso"
    :json-ld="$postSchemas"
>
    <!-- GPU-Composited Reading Progress Bar -->
    <div aria-hidden="true" class="pointer-events-none fixed inset-x-0 top-0 z-50 h-0.5 bg-transparent">
        <div data-read-progress class="pub-progress-bar h-full w-full bg-gold"></div>
    </div>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <!-- Institutional Judicial Hero Banner -->
        <header class="relative overflow-hidden rounded-xl border border-emerald-900 border-b-4 border-b-gold bg-obsidian p-6 text-white shadow-xs sm:p-8">
            <svg aria-hidden="true" class="pub-crest-float pointer-events-none absolute -top-8 -right-8 size-56 text-white/6 sm:size-68" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M12 3v18M5 7h14M8 21h8M5 7l-3 6h6l-3-6zM19 7l-3 6h6l-3-6z" />
            </svg>
            <div class="relative z-10 flex flex-col gap-4">
                <nav aria-label="Navigasi Remah Roti" class="flex flex-wrap items-center gap-2 text-xs text-emerald-100">
                    <a href="{{ route('home') }}" class="font-semibold text-white hover:underline">Beranda</a>
                    <span aria-hidden="true" class="text-emerald-300">/</span>
                    <a href="{{ route('public.posts') }}" class="font-semibold text-white hover:underline">Publikasi</a>
                    <span aria-hidden="true" class="text-emerald-300">/</span>
                    <span class="font-medium text-amber-300">{{ $post->title }}</span>
                </nav>

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-4xl space-y-2">
                        <span class="inline-block rounded bg-gold px-2.5 py-0.5 font-mono text-2xs font-bold text-ink uppercase">
                            {{ $categoryLabel }}
                        </span>
                        <h1 class="font-serif text-2xl leading-tight font-bold text-white sm:text-3xl lg:text-4xl">
                            {{ $post->title }}
                        </h1>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                data-copy-share-url="{{ $postCanonical }}"
                                class="pub-btn inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-md border border-white/25 bg-white/10 px-3.5 text-xs font-semibold text-white hover:bg-white/20"
                            >
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                </svg>
                                <span>Salin Tautan</span>
                            </button>
                            <span data-share-status aria-live="polite" class="font-mono text-2xs text-amber-300"></span>
                        </div>
                        @auth
                            <a
                                href="{{ route('admin.posts', ['edit' => $post->id]) }}"
                                class="pub-btn inline-flex min-h-11 shrink-0 items-center rounded-md bg-gold px-4 py-2 text-xs font-bold text-ink shadow-2xs hover:opacity-95"
                            >
                                Edit Artikel Ini
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 border-t border-white/15 pt-3 font-mono text-2xs text-emerald-100">
                    <span>Diunggah: {{ \App\Support\Tanggal::format($post->published_at ?? $post->created_at, 'd M Y, H:i') }}</span>
                    @if ($post->updated_at && $post->created_at && $post->updated_at->gt($post->created_at))
                        <span>Diperbarui: {{ \App\Support\Tanggal::format($post->updated_at, 'd M Y, H:i') }}</span>
                    @endif
                    <span class="inline-flex items-center gap-1.5 text-amber-300">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <span>{{ number_format((int) $post->views_count) }} kali dilihat</span>
                    </span>
                    <a href="#bagian-komentar" class="inline-flex items-center gap-1.5 text-emerald-100 hover:text-amber-300 hover:underline">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"></path>
                        </svg>
                        <span>{{ number_format((int) $approvedCommentsCount) }} komentar</span>
                    </a>
                </div>
            </div>
        </header>

        <article class="min-w-0 overflow-x-clip rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-5 shadow-2xs sm:p-8">
            <div class="prose max-w-none space-y-4 text-sm leading-relaxed text-ink-body">
                {!! \App\Support\SafeHtml::cleanBody($post->body) !!}
            </div>
        </article>

        <!-- Public Comments & Official Replies Section -->
        <section id="bagian-komentar" aria-label="Komentar dan Tanggapan Masyarakat" class="scroll-mt-24 space-y-6 rounded-xl border border-hairline border-t-4 border-t-primary bg-white p-6 shadow-2xs sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline pb-4">
                <div>
                    <span class="font-mono text-2xs font-bold text-primary uppercase">Interaksi Layanan Publik</span>
                    <h2 class="mt-0.5 font-serif text-xl font-bold text-ink">
                        Komentar &amp; Tanggapan Masyarakat ({{ number_format((int) $approvedCommentsCount) }})
                    </h2>
                </div>
                <span class="rounded border border-emerald-200 bg-primary-soft px-2.5 py-1 font-mono text-2xs font-semibold text-primary-deep">
                    Termoderasi Sebelum Tayang
                </span>
            </div>

            @if (session('comment_status'))
                <div role="status" class="rounded-lg border border-emerald-300 bg-primary-soft px-4 py-3 text-sm font-medium text-primary-deep">
                    {{ session('comment_status') }}
                </div>
            @endif

            @if (session('comment_error'))
                <div role="alert" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {{ session('comment_error') }}
                </div>
            @endif

            @if ($comments->isEmpty())
                <div class="rounded-lg border border-hairline bg-ivory p-5 text-sm text-ink-muted">
                    Belum ada komentar yang ditayangkan untuk publikasi ini. Jadilah yang pertama menyampaikan pertanyaan atau tanggapan Anda di bawah ini.
                </div>
            @else
                <ol class="space-y-4">
                    @foreach ($comments as $comment)
                        <li class="rounded-lg border border-hairline bg-ivory p-4 sm:p-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-bold text-ink">{{ $comment->author_name }}</span>
                                    @if ($comment->is_official_reply)
                                        <span class="rounded bg-gold px-2 py-0.5 font-mono text-2xs font-bold text-ink uppercase">
                                            Tanggapan Resmi
                                        </span>
                                    @else
                                        <span class="rounded bg-white px-2 py-0.5 font-mono text-2xs font-medium text-ink-muted border border-hairline">
                                            Masyarakat
                                        </span>
                                    @endif
                                </div>
                                <time class="font-mono text-2xs text-ink-muted" datetime="{{ $comment->created_at?->toAtomString() }}">
                                    {{ \App\Support\Tanggal::format($comment->created_at, 'd M Y, H:i') }}
                                </time>
                            </div>
                            <p class="mt-2.5 text-sm leading-relaxed text-ink-body">{!! nl2br(e($comment->body)) !!}</p>

                            @if ($comment->approvedReplies->isNotEmpty())
                                <ul class="mt-4 space-y-3 border-s-4 border-primary ps-4">
                                    @foreach ($comment->approvedReplies as $reply)
                                        <li class="rounded-lg border border-emerald-200 bg-primary-soft/60 p-3.5">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xs font-bold text-primary-deep">{{ $reply->author_name }}</span>
                                                    <span class="rounded bg-gold px-2 py-0.5 font-mono text-2xs font-bold text-ink uppercase">
                                                        Tanggapan Resmi Satker
                                                    </span>
                                                </div>
                                                <time class="font-mono text-2xs text-ink-muted" datetime="{{ $reply->created_at?->toAtomString() }}">
                                                    {{ \App\Support\Tanggal::format($reply->created_at, 'd M Y, H:i') }}
                                                </time>
                                            </div>
                                            <p class="mt-2 text-xs leading-relaxed text-ink-body sm:text-sm">{!! nl2br(e($reply->body)) !!}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif

            @if ($post->comments_enabled)
                <form action="{{ route('public.posts.comments.store', ['slug' => $post->slug]) }}" method="POST" class="space-y-4 border-t border-hairline pt-6">
                    @csrf
                    <h3 class="font-serif text-lg font-bold text-ink">Tulis Komentar atau Pertanyaan</h3>
                    <p class="text-xs text-ink-muted">
                        Komentar akan diperiksa oleh pengelola sebelum ditayangkan. Alamat surel bersifat privat dan tidak pernah ditampilkan ke publik.
                    </p>

                    <!-- Honeypot anti-bot field (hidden from assistive tech & visual users) -->
                    <div aria-hidden="true" class="hidden">
                        <label for="comment-website-hp">Jangan isi kolom ini</label>
                        <input id="comment-website-hp" type="text" name="website_hp" tabindex="-1" autocomplete="off" value="">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="comment-author-name" class="block text-xs font-bold text-ink">
                                Nama Lengkap <span class="text-red-700" aria-hidden="true">*</span>
                            </label>
                            <input
                                id="comment-author-name"
                                type="text"
                                name="author_name"
                                value="{{ old('author_name') }}"
                                required
                                minlength="2"
                                maxlength="120"
                                autocomplete="name"
                                placeholder="Nama lengkap Anda"
                                class="mt-1.5 min-h-11 w-full rounded-lg border border-hairline bg-ivory px-3.5 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none"
                            />
                            @error('author_name')
                                <p class="mt-1 text-xs font-semibold text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="comment-author-email" class="block text-xs font-bold text-ink">
                                Alamat Surel (Opsional, Privat)
                            </label>
                            <input
                                id="comment-author-email"
                                type="email"
                                name="author_email"
                                value="{{ old('author_email') }}"
                                maxlength="160"
                                autocomplete="email"
                                placeholder="nama@contoh.id (tidak dipublikasikan)"
                                class="mt-1.5 min-h-11 w-full rounded-lg border border-hairline bg-ivory px-3.5 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none"
                            />
                            @error('author_email')
                                <p class="mt-1 text-xs font-semibold text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="comment-body" class="block text-xs font-bold text-ink">
                            Isi Komentar / Pertanyaan <span class="text-red-700" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            id="comment-body"
                            name="body"
                            rows="4"
                            required
                            minlength="5"
                            maxlength="1500"
                            placeholder="Sampaikan pertanyaan atau masukan Anda secara sopan dan jelas..."
                            class="mt-1.5 w-full rounded-lg border border-hairline bg-ivory p-3.5 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none"
                        >{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-1 text-xs font-semibold text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="comment-captcha" class="block text-xs font-bold text-ink">
                            Kode Keamanan (Captcha Lokal) <span class="text-red-700" aria-hidden="true">*</span>
                        </label>
                        <div class="mt-1.5 flex flex-col gap-2.5 sm:flex-row sm:items-center">
                            <div class="flex flex-wrap items-center gap-2">
                                <img
                                    id="comment-captcha-img"
                                    src="{{ route('captcha.image') }}"
                                    data-captcha-img-url="{{ route('captcha.image') }}"
                                    data-captcha-text-url="{{ route('captcha.text') }}"
                                    width="180"
                                    height="48"
                                    alt="Gambar kode keamanan captcha"
                                    class="h-12 w-44 rounded-md border border-primary/30 bg-primary-soft"
                                >
                                <p id="comment-captcha-text" hidden class="flex h-12 min-w-44 items-center justify-center rounded-md border border-primary/30 bg-primary-soft px-3 font-mono text-2xl font-bold tracking-widest text-primary-deep"></p>
                                <button
                                    type="button"
                                    id="comment-captcha-refresh"
                                    aria-label="Muat ulang kode keamanan"
                                    class="pub-btn inline-flex min-h-11 items-center rounded-md border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist"
                                >
                                    Ulang
                                </button>
                                <button
                                    type="button"
                                    id="comment-captcha-mode"
                                    aria-controls="comment-captcha-text"
                                    aria-expanded="false"
                                    class="pub-btn inline-flex min-h-11 items-center rounded-md border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist"
                                >
                                    Mode teks
                                </button>
                            </div>
                            <input
                                id="comment-captcha"
                                type="text"
                                name="captcha"
                                required
                                maxlength="10"
                                autocomplete="off"
                                placeholder="Ketik 5 karakter"
                                class="min-h-11 flex-1 rounded-lg border border-hairline bg-ivory px-3.5 text-sm text-ink focus:border-primary focus:bg-white focus:outline-none"
                            />
                        </div>
                        @error('captcha')
                            <p class="mt-1 text-xs font-semibold text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-1">
                        <button type="submit" class="pub-btn min-h-11 cursor-pointer rounded-lg bg-primary px-6 text-xs font-bold text-white hover:bg-primary-hover">
                            Kirim Komentar
                        </button>
                    </div>
                </form>
            @else
                <div class="rounded-lg border border-hairline bg-ivory p-4 text-xs text-ink-muted">
                    Kolom komentar dinonaktifkan untuk publikasi ini.
                </div>
            @endif
        </section>

        @if ($related->isNotEmpty())
            <aside aria-label="Publikasi Terkait" class="pub-reveal rounded-xl border border-hairline border-t-4 border-t-gold bg-white p-6 shadow-2xs" style="--stagger-delay: 90ms">
                <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Publikasi Terkait</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($related as $rel)
                        <li>
                            <a href="{{ route('public.posts.show', ['slug' => $rel->slug]) }}" class="pub-link-card flex min-h-11 items-center justify-between gap-2 rounded-lg border border-hairline bg-ivory px-3.5 py-2 text-sm font-semibold text-primary hover:border-primary hover:bg-primary-soft">
                                <span>{{ $rel->title }}</span>
                                <span class="flex shrink-0 items-center gap-3 font-mono text-2xs text-ink-muted">
                                    <span>{{ number_format((int) ($rel->views_count ?? 0)) }} dilihat</span>
                                    <span>{{ number_format((int) ($rel->approved_comments_count ?? 0)) }} komentar</span>
                                    <span aria-hidden="true" class="pub-chevron text-primary">&rarr;</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>
</x-layouts.public>

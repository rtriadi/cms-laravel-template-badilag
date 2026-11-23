<?php

use App\Http\Controllers\PublicHomeController;
use App\Livewire\Admin\CommentManager;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\SatkerSetting;
use App\Models\User;
use App\Services\LocalCaptchaService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function makeCommentAdmin(string $role): User
{
    app(RoleAndPermissionSeeder::class)->run();

    $user = User::factory()->create([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $user->assignRole($role);

    return $user;
}

it('increments post views safely once per visitor across berita, pengumuman, and artikel without flushing homepage cache or counting bots', function (): void {
    SatkerSetting::factory()->setupCompleted()->create(['tingkat' => 'pertama']);

    $berita = Post::factory()->create([
        'title' => 'Berita Layanan Informasi Sidang',
        'slug' => 'berita-layanan-sidang',
        'category' => 'berita',
        'views_count' => 0,
    ]);
    $pengumuman = Post::factory()->create([
        'title' => 'Pengumuman Libur Layanan PTSP',
        'slug' => 'pengumuman-libur-ptsp',
        'category' => 'pengumuman',
        'views_count' => 0,
    ]);
    $artikel = Post::factory()->create([
        'title' => 'Artikel Panduan Prodeo Warga',
        'slug' => 'artikel-panduan-prodeo-warga',
        'category' => 'layanan_hukum',
        'views_count' => 0,
    ]);

    // Populate homepage cache after fixture creation to verify RecordPostViewAction does not flush it.
    Cache::put('public_home.pertama', ['sentinel' => true, 'sliders' => [], 'sertaMerta' => [], 'latestNews' => [], 'announcements' => [], 'transparencyPages' => [], 'servicePages' => [], 'todaySchedules' => [], 'hasUpcomingSchedules' => false], 300);

    // Bot and prefetch requests must not increment views_count
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])
        ->get(route('public.posts.show', ['slug' => $berita->slug]))
        ->assertOk();
    $this->withHeaders(['Sec-Purpose' => 'prefetch'])
        ->get(route('public.posts.show', ['slug' => $berita->slug]))
        ->assertOk();
    $this->flushHeaders();

    expect((int) $berita->fresh()->views_count)->toBe(0);

    // Normal visitor increments once, repeated refresh within session deduplicates
    $this->get(route('public.posts.show', ['slug' => $berita->slug]))
        ->assertOk()
        ->assertSee('1 kali dilihat');
    $this->get(route('public.posts.show', ['slug' => $berita->slug]))
        ->assertOk()
        ->assertSee('1 kali dilihat');

    $this->get(route('public.posts.show', ['slug' => $pengumuman->slug]))
        ->assertOk()
        ->assertSee('1 kali dilihat');
    $this->get(route('public.posts.show', ['slug' => $artikel->slug]))
        ->assertOk()
        ->assertSee('1 kali dilihat');

    expect((int) $berita->fresh()->views_count)->toBe(1)
        ->and((int) $pengumuman->fresh()->views_count)->toBe(1)
        ->and((int) $artikel->fresh()->views_count)->toBe(1)
        ->and(Cache::get('public_home.pertama')['sentinel'] ?? false)->toBeTrue();
});

it('validates captcha, honeypot, comments_enabled, sanitizes XSS, hides email/ip PII, and moderates comments cleanly', function (): void {
    SatkerSetting::factory()->setupCompleted()->create(['nama_satker' => 'Pengadilan Agama Soreang']);

    $post = Post::factory()->create([
        'title' => 'Pengumuman Resmi Seleksi Posbakum',
        'slug' => 'pengumuman-seleksi-posbakum',
        'category' => 'pengumuman',
        'comments_enabled' => true,
        'views_count' => 42,
    ]);

    $closedPost = Post::factory()->create([
        'title' => 'Publikasi Tutup Komentar',
        'slug' => 'publikasi-tutup-komentar',
        'category' => 'berita',
        'comments_enabled' => false,
    ]);

    // Wrong captcha is rejected
    session([LocalCaptchaService::SESSION_KEY => null]);
    app(LocalCaptchaService::class)->generateCode();
    $this->post(route('public.posts.comments.store', ['slug' => $post->slug]), [
        'author_name' => 'Ahmad Warga',
        'author_email' => 'ahmad.rahasia@example.test',
        'body' => 'Kapan pendaftaran Posbakum dibuka?',
        'captcha' => 'SALAH',
    ])->assertRedirect(route('public.posts.show', ['slug' => $post->slug]).'#bagian-komentar')
        ->assertSessionHasErrors(['captcha']);

    expect(PostComment::query()->count())->toBe(0);

    // Filled honeypot is rejected
    $codeHp = app(LocalCaptchaService::class)->generateCode();
    $this->post(route('public.posts.comments.store', ['slug' => $post->slug]), [
        'author_name' => 'Bot Iklan',
        'body' => 'Pesan promosi tidak diinginkan.',
        'website_hp' => 'https://spam.invalid',
        'captcha' => $codeHp,
    ])->assertSessionHasErrors(['website_hp']);

    // Closed comments post rejects submissions
    $codeClosed = app(LocalCaptchaService::class)->generateCode();
    $this->post(route('public.posts.comments.store', ['slug' => $closedPost->slug]), [
        'author_name' => 'Ahmad Warga',
        'body' => 'Coba kirim komentar ke artikel terkunci.',
        'captcha' => $codeClosed,
    ])->assertRedirect(route('public.posts.show', ['slug' => $closedPost->slug]).'#bagian-komentar')
        ->assertSessionHas('comment_error');

    // Valid submission stores stripped plain text as pending + salted ip_hash (not raw IP)
    $validCode = app(LocalCaptchaService::class)->generateCode();
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.55'])
        ->post(route('public.posts.comments.store', ['slug' => $post->slug]), [
            'author_name' => '<b>Siti Nurhaliza</b>',
            'author_email' => 'siti.pribadi@example.test',
            'body' => 'Mohon info syarat <script>alert("xss")</script> pendaftaran Posbakum.',
            'captcha' => $validCode,
        ])->assertRedirect(route('public.posts.show', ['slug' => $post->slug]).'#bagian-komentar')
        ->assertSessionHas('comment_status');

    $comment = PostComment::query()->firstOrFail();
    expect($comment->status)->toBe(PostComment::STATUS_PENDING)
        ->and($comment->author_name)->toBe('Siti Nurhaliza')
        ->and($comment->body)->not->toContain('<script>')
        ->and($comment->body)->toContain('Mohon info syarat')
        ->and($comment->ip_hash)->not->toBe('203.0.113.55')
        ->and(strlen((string) $comment->ip_hash))->toBe(64);

    // Pending comment must NOT be visible on public post detail
    $this->get(route('public.posts.show', ['slug' => $post->slug]))
        ->assertOk()
        ->assertSee('43 kali dilihat')
        ->assertDontSee('Mohon info syarat');
});

it('displays approved comments, official replies, and view/comment counters across public pages while enforcing moderation RBAC', function (): void {
    SatkerSetting::factory()->setupCompleted()->create(['nama_satker' => 'Pengadilan Agama Soreang']);
    $ppidOperator = makeCommentAdmin('operator_ppid_humas');
    $penilai = makeCommentAdmin('penilai_internal');
    $kepaniteraan = makeCommentAdmin('operator_kepaniteraan');

    $post = Post::factory()->create([
        'title' => 'Berita Layanan Informasi Prodeo',
        'slug' => 'berita-layanan-prodeo',
        'category' => 'layanan_hukum',
        'views_count' => 15,
    ]);

    $pendingComment = PostComment::factory()->pending()->create([
        'post_id' => $post->id,
        'author_name' => 'Rudi Hartono',
        'author_email' => 'rudi.rahasia@example.test',
        'body' => 'Apakah sidang keliling gratis untuk warga?',
    ]);

    $extraSpam = PostComment::factory()->pending()->create([
        'post_id' => $post->id,
        'author_name' => 'Akun Promo',
        'body' => 'Link promosi tidak relevan.',
    ]);

    // Unauthorized roles receive 403 on CommentManager
    $this->actingAs($penilai)->get(route('admin.comments'))->assertForbidden();
    $this->actingAs($kepaniteraan)->get(route('admin.comments'))->assertForbidden();

    // Operator PPID / Humas approves & sends official satker reply
    $this->actingAs($ppidOperator);
    Livewire::test(CommentManager::class)
        ->call('openReply', $pendingComment->id)
        ->set('replyBody', 'Benar Bapak/Ibu, layanan sidang keliling dan Prodeo bebas biaya bagi yang memenuhi syarat.')
        ->call('saveReply')
        ->assertHasNoErrors();

    Livewire::test(CommentManager::class)
        ->set('selectedIds', [(string) $extraSpam->id])
        ->set('bulkAction', 'spam')
        ->call('applyBulkAction')
        ->assertHasNoErrors();

    expect($pendingComment->fresh()->status)->toBe(PostComment::STATUS_APPROVED)
        ->and($extraSpam->fresh()->status)->toBe(PostComment::STATUS_SPAM);

    // Public post detail shows approved comment + official reply (2 approved comments), never leaks author_email or ip_hash
    $showResp = $this->get(route('public.posts.show', ['slug' => $post->slug]));
    $showResp->assertOk()
        ->assertSee('Rudi Hartono')
        ->assertSee('Apakah sidang keliling gratis untuk warga?')
        ->assertSee('Tanggapan Resmi Pengadilan Agama Soreang')
        ->assertSee('Benar Bapak/Ibu, layanan sidang keliling dan Prodeo bebas biaya bagi yang memenuhi syarat.')
        ->assertSee('2 komentar')
        ->assertDontSee('rudi.rahasia@example.test')
        ->assertDontSee('Link promosi tidak relevan.');

    // Public index and homepage show view and comment counters
    $this->get(route('public.posts'))
        ->assertOk()
        ->assertSee('16 dilihat')
        ->assertSee('2 komentar');

    PublicHomeController::flushCache();
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('16 dilihat')
        ->assertSee('2 komentar');

    // Article list block page renders counters too without N+1 errors
    Page::factory()->create([
        'title' => 'Artikel Bantuan Hukum Uji',
        'slug' => 'uji/bantuan-hukum-counter',
        'is_published' => true,
        'blocks' => [
            [
                'type' => 'article_list',
                'heading' => 'Daftar Artikel Layanan Hukum',
                'post_category' => 'layanan_hukum',
            ],
        ],
    ]);

    DB::enableQueryLog();
    DB::flushQueryLog();
    $this->get('/halaman/uji/bantuan-hukum-counter')
        ->assertOk()
        ->assertSee('16 dilihat')
        ->assertSee('2 komentar');
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThan(15);
});

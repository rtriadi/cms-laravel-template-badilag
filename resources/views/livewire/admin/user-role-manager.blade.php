@php
    $roleDescriptions = [
        'super_admin' => 'Akses penuh semua menu, pengaturan satker, pengguna, kriteria SINOVA, dan backup.',
        'admin_konten' => 'Kelola halaman, berita, pengumuman, moderasi komentar, slider, dan audit tautan.',
        'operator_kepaniteraan' => 'Kelola jadwal sidang cadangan, keadaan perkara bulanan, dan dokumen perkara.',
        'operator_kesekretariatan' => 'Kelola dokumen SAKIP (LKjIP, DIPA, Renstra), keuangan, dan daftar aparatur.',
        'operator_ppid_humas' => 'Kelola berita, pengumuman, DIP PPID, serta moderasi & balas komentar warga.',
        'penilai_internal' => 'Mode baca & cek kesehatan 40 link SINOVA serta log aktivitas (tidak mengubah konten).',
    ];
@endphp
<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-xl border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    @error('delete_self')
        <div role="alert" class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-xs font-semibold text-red-800">
            {{ $message }}
        </div>
    @enderror

    <div>
        <h1 class="font-serif text-2xl font-bold tracking-tight text-ink sm:text-[30px]">Pengguna &amp; Peran</h1>
        <p class="mt-1 text-sm text-ink-body">
            Kelola akun pengelola website dengan 6 peran standar SatkerCMS. Setiap akun wajib mengaktifkan autentikasi 2 langkah (2FA TOTP).
        </p>
    </div>

    <!-- 6-Role RBAC Clarity Grid (3 cols x 2 rows on desktop, 2 on tablet, 1 on mobile) -->
    <section aria-label="Ringkasan Matriks 6 Peran RBAC" class="studio-card p-5">
        <div class="mb-4">
            <h2 class="text-base font-bold text-ink">Ringkasan 6 peran standar</h2>
            <p class="mt-0.5 text-xs text-ink-muted">Pilih peran sesuai bidang tugas agar setiap operator hanya melihat menu yang dibutuhkannya.</p>
        </div>
        <div class="grid items-stretch gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($roleLabels as $rKey => $rLabel)
                @php
                    $roleCount = $users->filter(fn ($u) => $u->roles->contains('name', $rKey))->count();
                @endphp
                <div class="flex h-full flex-col justify-between rounded-xl border border-hairline/80 bg-mist p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-ink">{{ $rLabel }}</p>
                            <span class="mt-1 inline-block rounded bg-primary-soft px-2 py-0.5 font-mono text-[11px] font-semibold text-primary">{{ $rKey }}</span>
                        </div>
                        <span class="shrink-0 whitespace-nowrap font-mono text-xs font-semibold text-ink-muted">{{ $roleCount }} akun</span>
                    </div>
                    @if (isset($roleDescriptions[$rKey]))
                        <p class="mt-2.5 text-xs leading-relaxed text-ink-body">{{ $roleDescriptions[$rKey] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <!-- Daftar Pengguna (7 cols) -->
        <section class="studio-card overflow-hidden lg:col-span-7">
            <div class="border-b border-hairline px-5 py-4">
                <h2 class="text-base font-bold text-ink">Daftar Pengelola &amp; Status 2FA ({{ $users->count() }})</h2>
            </div>

            <!-- Mobile Cards (< md) -->
            <ul class="divide-y divide-hairline md:hidden">
                @foreach ($users as $usr)
                    @php
                        $roleKey = $usr->roles->first()?->name ?? '-';
                        $has2fa = $usr->two_factor_confirmed_at !== null;
                        $uInit = collect(preg_split('/\s+/', trim((string) $usr->name)) ?: [])->filter()->take(2)->map(fn (string $p): string => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: 'AD';
                    @endphp
                    <li wire:key="usr-mob-{{ $usr->id }}" class="space-y-3 p-4">
                        <div class="flex items-center gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">{{ $uInit }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-ink">{{ $usr->name }}</p>
                                <p class="truncate font-mono text-xs text-ink-muted">{{ $usr->email }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="rounded-md bg-mist px-2.5 py-1 font-semibold text-ink-body">{{ $roleLabels[$roleKey] ?? $roleKey }}</span>
                            <span @class([
                                'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 font-semibold',
                                'bg-emerald-50 text-emerald-800' => $has2fa,
                                'bg-amber-50 text-amber-900' => ! $has2fa,
                            ])>
                                <span class="size-1.5 rounded-full {{ $has2fa ? 'bg-emerald-700' : 'bg-amber-700' }}" aria-hidden="true"></span>
                                {{ $has2fa ? '2FA Aktif' : 'Belum Aktivasi' }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <button type="button" wire:click="edit({{ $usr->id }})" class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-lg border border-hairline bg-white px-3 font-semibold text-ink hover:border-primary hover:text-primary">Ubah</button>
                            @if ($has2fa && auth()->user()?->hasRole('super_admin'))
                                <button type="button" wire:click="resetTwoFactor({{ $usr->id }})" wire:confirm="Reset 2FA akun {{ $usr->name }}? Pengguna harus aktivasi ulang saat masuk." class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-lg border border-amber-300 bg-amber-50 px-3 font-semibold text-amber-900">Reset 2FA</button>
                            @endif
                            @if ($usr->id !== auth()->id())
                                <button type="button" wire:click="delete({{ $usr->id }})" wire:confirm="Hapus akun admin ini?" class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-lg border border-red-200 bg-white px-3 font-semibold text-red-700">Hapus</button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            <!-- Desktop Table (>= md) -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-b border-hairline bg-mist text-ink-muted">
                            <th class="py-3 pr-3 pl-5 font-semibold">Pengelola</th>
                            <th class="px-3 py-3 font-semibold">Peran</th>
                            <th class="px-3 py-3 font-semibold">Status 2FA</th>
                            <th class="py-3 pr-5 pl-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline/70">
                        @foreach ($users as $usr)
                            @php
                                $roleKey = $usr->roles->first()?->name ?? '-';
                                $has2fa = $usr->two_factor_confirmed_at !== null;
                                $uInit = collect(preg_split('/\s+/', trim((string) $usr->name)) ?: [])->filter()->take(2)->map(fn (string $p): string => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: 'AD';
                            @endphp
                            <tr wire:key="usr-{{ $usr->id }}" class="hover:bg-mist/60">
                                <td class="py-3.5 pr-3 pl-5">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex size-8.5 shrink-0 items-center justify-center rounded-full bg-primary-soft text-xs font-bold text-primary">{{ $uInit }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-ink">{{ $usr->name }}</p>
                                            <p class="truncate font-mono text-[11px] text-ink-muted">{{ $usr->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5">
                                    <span class="rounded-md bg-mist px-2.5 py-1 font-semibold text-ink-body">
                                        {{ $roleLabels[$roleKey] ?? $roleKey }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5">
                                    <span @class([
                                        'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 font-semibold',
                                        'bg-emerald-50 text-emerald-800' => $has2fa,
                                        'bg-amber-50 text-amber-900' => ! $has2fa,
                                    ])>
                                        <span class="size-1.5 rounded-full {{ $has2fa ? 'bg-emerald-700' : 'bg-amber-700' }}" aria-hidden="true"></span>
                                        {{ $has2fa ? '2FA Aktif' : 'Belum Aktif' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap py-3.5 pr-5 pl-3 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" wire:click="edit({{ $usr->id }})" class="inline-flex h-8 cursor-pointer items-center rounded-lg border border-hairline bg-white px-2.5 font-semibold text-ink hover:border-primary hover:text-primary">Ubah</button>
                                        @if ($has2fa && auth()->user()?->hasRole('super_admin'))
                                            <button type="button" wire:click="resetTwoFactor({{ $usr->id }})" wire:confirm="Reset 2FA akun {{ $usr->name }}? Pengguna harus aktivasi ulang saat masuk." class="inline-flex h-8 cursor-pointer items-center rounded-lg border border-amber-300 bg-white px-2.5 font-semibold text-amber-900 hover:bg-amber-50">Reset 2FA</button>
                                        @endif
                                        @if ($usr->id !== auth()->id())
                                            <button type="button" wire:click="delete({{ $usr->id }})" wire:confirm="Hapus akun admin ini?" class="inline-flex h-8 cursor-pointer items-center rounded-lg border border-red-200 bg-white px-2.5 font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Form Pengguna (5 cols) -->
        <aside class="studio-card p-5 sm:p-6 lg:sticky lg:top-20 lg:col-span-5">
            <div class="flex items-center justify-between border-b border-hairline pb-3.5">
                <h2 class="text-base font-bold text-ink">
                    {{ $editingId ? 'Ubah Pengguna & Peran' : 'Tambah Pengelola Baru' }}
                </h2>
                @if ($editingId)
                    <button type="button" wire:click="createNew" class="min-h-10 cursor-pointer text-xs font-semibold text-primary hover:underline">+ Akun Baru</button>
                @endif
            </div>

            <form wire:submit="save" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label for="usr_name">Nama lengkap <span class="text-red-700">*</span></flux:label>
                    <flux:input id="usr_name" wire:model="name" required placeholder="Nama operator atau admin" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label for="usr_email">Email dinas <span class="text-red-700">*</span></flux:label>
                    <flux:input id="usr_email" type="email" wire:model="email" required placeholder="nama@pa-satker.go.id" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label for="usr_role">Peran akses (RBAC 6 tingkat)</flux:label>
                    <x-admin.select id="usr_role" wire:model="role" search-placeholder="Cari peran akses...">
                        @foreach ($roleLabels as $rKey => $rLabel)
                            <option value="{{ $rKey }}">{{ $rLabel }} ({{ $rKey }})</option>
                        @endforeach
                    </x-admin.select>
                    <flux:error name="role" />
                </flux:field>

                <flux:field>
                    <flux:label for="usr_pass">Kata sandi</flux:label>
                    <div x-data="{ show: false }" class="flex items-center gap-2">
                        <input
                            id="usr_pass"
                            x-bind:type="show ? 'text' : 'password'"
                            wire:model="password"
                            autocomplete="new-password"
                            placeholder="{{ $editingId ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' }}"
                            class="min-h-11 w-full rounded-lg border border-[#768780] bg-white px-3 text-sm text-ink"
                        />
                        <button
                            type="button"
                            x-on:click="show = !show"
                            x-bind:aria-pressed="show ? 'true' : 'false'"
                            class="min-h-11 shrink-0 cursor-pointer rounded-lg border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist"
                        >
                            <span x-text="show ? 'Tutup' : 'Lihat'">Lihat</span>
                        </button>
                    </div>
                    <flux:error name="password" />
                </flux:field>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary text-sm font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan Akun' : 'Tambah Pengelola' }}
                </flux:button>
            </form>
        </aside>
    </div>
</div>

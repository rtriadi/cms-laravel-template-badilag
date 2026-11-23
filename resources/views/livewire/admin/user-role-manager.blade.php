<div class="space-y-6">
    @if ($statusNotice)
        <div role="status" class="rounded-lg border border-emerald-200 bg-primary-soft px-4 py-3 text-sm font-medium text-primary">
            {{ $statusNotice }}
        </div>
    @endif

    @error('delete_self')
        <div role="alert" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-xs font-semibold text-red-800">
            {{ $message }}
        </div>
    @enderror

    <!-- 6-Role RBAC Matrix Overview Cards -->
    <section aria-label="Ringkasan Matriks 6 Peran RBAC" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ($roleLabels as $rKey => $rLabel)
            @php
                $roleCount = $users->filter(fn ($u) => $u->roles->contains('name', $rKey))->count();
            @endphp
            <div class="rounded-lg border border-hairline border-t-4 border-t-primary bg-white p-3.5 shadow-2xs">
                <p class="font-mono text-2xs font-bold text-primary">{{ $rKey }}</p>
                <p class="mt-0.5 text-xs font-bold text-ink">{{ $rLabel }}</p>
                <p class="mt-1.5 font-mono text-xs text-ink-muted">{{ $roleCount }} pengguna</p>
            </div>
        @endforeach
    </section>

    <div class="grid gap-6 lg:grid-cols-12">
        <!-- Form Pengguna -->
        <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-5">
            <div class="flex items-center justify-between border-b border-hairline pb-3">
                <h2 class="font-serif text-lg font-bold text-ink">
                    {{ $editingId ? 'Edit Pengguna & Peran' : 'Tambah Admin Baru' }}
                </h2>
                @if ($editingId)
                    <button type="button" wire:click="createNew" class="cursor-pointer text-xs font-semibold text-primary hover:underline">Pengguna Baru</button>
                @endif
            </div>

            <form wire:submit="save" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label for="usr_name">Nama Lengkap</flux:label>
                    <flux:input id="usr_name" wire:model="name" required placeholder="Nama operator atau admin" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label for="usr_email">Alamat Surel</flux:label>
                    <flux:input id="usr_email" type="email" wire:model="email" required placeholder="admin@satker.go.id" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label for="usr_role">Peran Akses (RBAC 6 Tingkat)</flux:label>
                    <flux:select id="usr_role" wire:model="role">
                        @foreach ($roleLabels as $rKey => $rLabel)
                            <option value="{{ $rKey }}">{{ $rLabel }} ({{ $rKey }})</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="role" />
                </flux:field>

                <flux:field>
                    <flux:label for="usr_pass">Kata Sandi {{ $editingId ? '(Kosongkan jika tidak diubah)' : '(Minimal 8 karakter)' }}</flux:label>
                    <div x-data="{ show: false }" class="flex items-center gap-2">
                        <input
                            id="usr_pass"
                            x-bind:type="show ? 'text' : 'password'"
                            wire:model="password"
                            autocomplete="new-password"
                            class="min-h-11 w-full rounded-md border border-hairline bg-white px-3 text-sm text-ink"
                        />
                        <button
                            type="button"
                            x-on:click="show = !show"
                            x-bind:aria-pressed="show ? 'true' : 'false'"
                            class="min-h-11 shrink-0 cursor-pointer rounded-md border border-hairline bg-white px-3 text-xs font-semibold text-ink hover:bg-mist"
                        >
                            <span x-text="show ? 'Sembunyikan' : 'Tampilkan'">Tampilkan</span>
                        </button>
                    </div>
                    <flux:error name="password" />
                </flux:field>

                <flux:button type="submit" variant="primary" class="min-h-11 w-full cursor-pointer bg-primary font-semibold text-white hover:bg-primary-hover">
                    {{ $editingId ? 'Simpan Perubahan Akun' : 'Tambah Pengguna Admin' }}
                </flux:button>
            </form>
        </div>

        <!-- Daftar Pengguna -->
        <div class="rounded-lg border border-hairline bg-white p-5 lg:col-span-7">
            <h2 class="border-b border-hairline pb-3 font-serif text-lg font-bold text-ink">Daftar Pengelola & Status 2FA ({{ $users->count() }})</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-b border-hairline text-ink-muted">
                            <th class="py-2 pr-3 font-semibold">Nama & Surel</th>
                            <th class="py-2 pr-3 font-semibold">Peran</th>
                            <th class="py-2 pr-3 font-semibold">Status 2FA</th>
                            <th class="py-2 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline">
                        @foreach ($users as $usr)
                            @php
                                $roleKey = $usr->roles->first()?->name ?? '-';
                                $has2fa = $usr->two_factor_confirmed_at !== null;
                            @endphp
                            <tr wire:key="usr-{{ $usr->id }}">
                                <td class="py-2.5 pr-3">
                                    <p class="font-semibold text-ink">{{ $usr->name }}</p>
                                    <p class="font-mono text-ink-muted">{{ $usr->email }}</p>
                                </td>
                                <td class="py-2.5 pr-3">
                                    <span class="rounded bg-primary-soft px-2 py-0.5 font-semibold text-primary">
                                        {{ $roleLabels[$roleKey] ?? $roleKey }}
                                    </span>
                                </td>
                                <td class="py-2.5 pr-3">
                                    <span @class([
                                        'rounded px-2 py-0.5 font-semibold',
                                        'bg-emerald-50 text-emerald-800' => $has2fa,
                                        'bg-amber-100 text-ink' => ! $has2fa,
                                    ])>
                                        {{ $has2fa ? 'Aktif (TOTP)' : 'Belum Aktivasi' }}
                                    </span>
                                </td>
                                <td class="py-2.5 text-right">
                                    <div class="inline-flex gap-1.5">
                                        <button type="button" wire:click="edit({{ $usr->id }})" class="min-h-11 cursor-pointer rounded border border-primary px-2.5 py-1 font-semibold text-primary">Edit</button>
                                        @if ($usr->id !== auth()->id())
                                            <button type="button" wire:click="delete({{ $usr->id }})" wire:confirm="Hapus akun admin ini?" class="min-h-11 cursor-pointer rounded border border-red-200 px-2.5 py-1 font-semibold text-red-700">Hapus</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class UserRoleManager extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'admin_konten';

    public ?string $statusNotice = null;

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function createNew(): void
    {
        $this->authorizeAccess();
        $this->reset(['editingId', 'name', 'email', 'password']);
        $this->role = 'admin_konten';
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        $this->authorizeAccess();
        $user = User::query()->with('roles')->findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->roles->first()?->name ?? 'admin_konten';
    }

    public function save(): void
    {
        $this->authorizeAccess();

        $allowedRoles = array_keys(config('satker.roles', []));
        $passwordRules = $this->editingId === null
            ? ['required', 'string', Password::min(8)]
            : ['nullable', 'string', Password::min(8)];

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => $passwordRules,
            'role' => ['required', Rule::in($allowedRoles)],
        ], [
            'name.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Alamat surel wajib diisi.',
            'email.unique' => 'Alamat surel sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi untuk pengguna baru.',
            'role.in' => 'Peran yang dipilih tidak valid.',
        ]);

        if ($this->editingId !== null) {
            $user = User::query()->findOrFail($this->editingId);
            $user->name = trim($this->name);
            $user->email = trim($this->email);
            if ($this->password !== '') {
                $user->password = $this->password;
            }
            $user->save();
            $this->statusNotice = 'Data akun admin dan peran berhasil diperbarui.';
        } else {
            $user = User::query()->create([
                'name' => trim($this->name),
                'email' => trim($this->email),
                'password' => $this->password,
                'email_verified_at' => now(),
            ]);
            $this->statusNotice = 'Pengguna admin baru berhasil ditambahkan. Pengguna wajib mengaktifkan 2FA saat masuk.';
        }

        $user->syncRoles([$this->role]);
        $this->createNew();
    }

    public function delete(int $id): void
    {
        $this->authorizeAccess();

        if ($id === auth()->id()) {
            $this->addError('delete_self', 'Akun yang sedang aktif login tidak dapat dihapus.');

            return;
        }

        User::query()->findOrFail($id)->delete();
        $this->statusNotice = 'Akun pengguna berhasil dihapus.';
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && ($user->hasRole('super_admin') || $user->can('manage-users')),
            403
        );
    }

    public function render(): View
    {
        $users = User::query()->with('roles')->orderBy('id')->get();

        return view('livewire.admin.user-role-manager', [
            'users' => $users,
            'roleLabels' => config('satker.roles', []),
        ])->title('Pengguna, Peran & Status 2FA');
    }
}

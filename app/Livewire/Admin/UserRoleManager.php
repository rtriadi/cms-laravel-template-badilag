<?php

namespace App\Livewire\Admin;

use App\Actions\Security\InvalidateUserSessions;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class UserRoleManager extends Component
{
    #[Locked]
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

    public function save(InvalidateUserSessions $invalidateSessions): void
    {
        $this->authorizeAccess();

        $actor = auth()->user();
        $allowedRoles = array_keys(config('satker.roles', []));
        $this->email = strtolower(trim($this->email));

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

        if ($this->role === 'super_admin' && ! $actor?->hasRole('super_admin')) {
            abort(403, 'Hanya Super Admin yang berwenang menetapkan peran Super Admin.');
        }

        if ($this->editingId !== null) {
            $user = User::query()->with('roles')->findOrFail($this->editingId);
            if ($user->hasRole('super_admin') && ! $actor?->hasRole('super_admin')) {
                abort(403, 'Hanya Super Admin yang berwenang mengubah akun Super Admin.');
            }

            if ($user->hasRole('super_admin') && $this->role !== 'super_admin' && User::role('super_admin')->count() <= 1) {
                $this->addError('role', 'Tidak dapat menurunkan peran Super Admin terakhir di sistem.');

                return;
            }

            $previousRole = $user->roles->first()?->name;
            $passwordChanged = $this->password !== '';
            $roleChanged = $previousRole !== $this->role;

            $user->name = trim($this->name);
            $user->email = $this->email;
            if ($passwordChanged) {
                $user->password = $this->password;
            }
            $user->save();
            $user->syncRoles([$this->role]);

            if ($passwordChanged || $roleChanged) {
                $exceptSession = $user->id === auth()->id() ? request()->session()->getId() : null;
                $invalidateSessions->execute($user, $exceptSession);

                activity('user')
                    ->causedBy($actor)
                    ->performedOn($user)
                    ->withProperties([
                        'previous_role' => $previousRole,
                        'new_role' => $this->role,
                        'password_changed' => $passwordChanged,
                    ])
                    ->log('Memperbarui peran atau kata sandi akun admin');
            }

            $this->statusNotice = 'Data akun admin dan peran berhasil diperbarui.';
        } else {
            $user = User::query()->create([
                'name' => trim($this->name),
                'email' => $this->email,
                'password' => $this->password,
                'email_verified_at' => now(),
            ]);
            $user->syncRoles([$this->role]);

            activity('user')
                ->causedBy($actor)
                ->performedOn($user)
                ->withProperties(['role' => $this->role])
                ->log('Menambahkan akun admin baru');

            $this->statusNotice = 'Pengguna admin baru berhasil ditambahkan. Pengguna wajib mengaktifkan 2FA saat masuk.';
        }

        $this->createNew();
    }

    public function delete(int $id, InvalidateUserSessions $invalidateSessions): void
    {
        $this->authorizeAccess();

        if ($id === auth()->id()) {
            $this->addError('delete_self', 'Akun yang sedang aktif login tidak dapat dihapus.');

            return;
        }

        $target = User::query()->findOrFail($id);
        if ($target->hasRole('super_admin') && ! auth()->user()?->hasRole('super_admin')) {
            abort(403, 'Hanya Super Admin yang berwenang menghapus akun Super Admin.');
        }

        if ($target->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            $this->addError('delete_self', 'Tidak dapat menghapus Super Admin terakhir di sistem.');

            return;
        }

        $invalidateSessions->execute($target);
        $target->delete();
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
        $this->authorizeAccess();

        $users = User::query()->with('roles')->orderBy('id')->get();

        return view('livewire.admin.user-role-manager', [
            'users' => $users,
            'roleLabels' => config('satker.roles', []),
        ])->title('Pengguna, Peran & Status 2FA');
    }
}

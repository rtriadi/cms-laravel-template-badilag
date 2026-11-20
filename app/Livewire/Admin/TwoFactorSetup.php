<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class TwoFactorSetup extends Component
{
    public string $code = '';

    public bool $showRecoveryCodes = false;

    public function mount(EnableTwoFactorAuthentication $enableTwoFactor): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (empty($user->two_factor_secret)) {
            $enableTwoFactor($user);
        }
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactor): void
    {
        $this->validate([
            'code' => ['required', 'string', 'min:6', 'max:10'],
        ], [
            'code.required' => 'Kode 6 digit wajib diisi.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        try {
            $confirmTwoFactor($user, $this->code);
        } catch (ValidationException) {
            $this->addError('code', 'Kode TOTP 6 digit tidak valid atau sudah kedaluwarsa.');

            return;
        }

        session()->flash('status', 'Verifikasi dua faktor (2FA TOTP) berhasil diaktifkan.');
        $this->redirectRoute('admin.dashboard');
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generator): void
    {
        $confirmedAt = (int) session('auth.password_confirmed_at', 0);
        $timeout = (int) config('auth.password_timeout', 10800);

        if ($confirmedAt <= 0 || (time() - $confirmedAt) > $timeout) {
            session()->put('url.intended', route('admin.two-factor.setup'));
            $this->redirectRoute('password.confirm');

            return;
        }

        /** @var User $user */
        $user = Auth::user();
        $generator($user);
        $this->showRecoveryCodes = true;
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user()->fresh();

        return view('livewire.admin.two-factor-setup', [
            'user' => $user,
            'isConfirmed' => $user->two_factor_confirmed_at !== null,
            'qrCodeSvg' => ! empty($user->two_factor_secret) ? $user->twoFactorQrCodeSvg() : null,
            'secretKey' => ! empty($user->two_factor_secret)
                ? Fortify::currentEncrypter()->decrypt($user->two_factor_secret)
                : null,
            'recoveryCodes' => ! empty($user->two_factor_recovery_codes)
                ? $user->recoveryCodes()
                : [],
        ])->title('Aktivasi 2FA');
    }
}

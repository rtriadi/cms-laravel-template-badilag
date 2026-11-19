<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditorMediaUploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user !== null && (
                $user->hasAnyRole([
                    'super_admin',
                    'admin_konten',
                    'operator_ppid_humas',
                    'operator_kepaniteraan',
                    'operator_kesekretariatan',
                ]) ||
                $user->can('manage-pages') ||
                $user->can('manage-ppid-humas') ||
                $user->can('manage-kepaniteraan') ||
                $user->can('manage-kesekretariatan') ||
                $user->can('manage-criteria')
            ),
            403
        );

        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:10240'],
        ], [
            'file.required' => 'Pilih berkas gambar atau PDF terlebih dahulu.',
            'file.mimes' => 'Format berkas harus berupa gambar (JPG, PNG, WEBP, GIF) atau dokumen PDF.',
            'file.max' => 'Ukuran berkas maksimal 10 MB.',
        ]);

        $uploaded = $request->file('file');
        $path = $uploaded->store('editor-media', 'public');
        $url = '/storage/'.ltrim((string) $path, '/');
        $originalName = $uploaded->getClientOriginalName();
        $extension = strtolower($uploaded->getClientOriginalExtension());
        $isPdf = $extension === 'pdf' || $uploaded->getMimeType() === 'application/pdf';

        return response()->json([
            'location' => $url,
            'url' => $url,
            'name' => $originalName,
            'is_pdf' => $isPdf,
        ]);
    }
}


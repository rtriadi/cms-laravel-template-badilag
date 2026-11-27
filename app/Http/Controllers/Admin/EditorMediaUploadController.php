<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageOptimizerService;
use App\Support\SafeUpload;
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
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ], [
            'file.required' => 'Pilih berkas gambar atau PDF terlebih dahulu.',
            'file.mimes' => 'Format berkas harus berupa gambar (JPG, PNG, WEBP) atau dokumen PDF.',
            'file.max' => 'Ukuran berkas maksimal 10 MB.',
        ]);

        $uploaded = $request->file('file');
        $originalName = $uploaded->getClientOriginalName();
        $extension = strtolower($uploaded->getClientOriginalExtension());
        $isPdf = $extension === 'pdf' || $uploaded->getMimeType() === 'application/pdf';

        if ($isPdf) {
            $pdfError = SafeUpload::validatePdf($uploaded);
            if ($pdfError !== null) {
                return response()->json([
                    'message' => $pdfError,
                ], 422);
            }
        } else {
            $realPath = $uploaded->getRealPath();
            $imageInfo = is_string($realPath) && is_file($realPath) ? @getimagesize($realPath) : false;
            if (is_array($imageInfo)) {
                $width = (int) ($imageInfo[0] ?? 0);
                $height = (int) ($imageInfo[1] ?? 0);
                if (
                    $width > ImageOptimizerService::MAX_SOURCE_DIMENSION
                    || $height > ImageOptimizerService::MAX_SOURCE_DIMENSION
                    || ($width * $height) > ImageOptimizerService::MAX_SOURCE_PIXELS
                ) {
                    return response()->json([
                        'message' => 'Dimensi gambar melebihi batas aman maksimal (8000x8000 piksel / 25 megapiksel).',
                    ], 422);
                }
            }
        }

        $path = ! $isPdf
            ? app(ImageOptimizerService::class)->storeOptimized($uploaded, 'editor-media', 1280, 82)
            : $uploaded->store('editor-media', 'public');

        $url = '/storage/'.ltrim((string) $path, '/');
        $uploadedAt = now()->format('d M Y, H:i');

        activity('document')
            ->causedBy($user)
            ->withProperties([
                'name' => $originalName,
                'url' => $url,
                'is_pdf' => $isPdf,
                'uploaded_at' => $uploadedAt,
            ])
            ->log('Mengunggah berkas media pada editor konten');

        return response()->json([
            'location' => $url,
            'url' => $url,
            'name' => $originalName,
            'is_pdf' => $isPdf,
            'uploaded_at' => $uploadedAt,
        ]);
    }
}

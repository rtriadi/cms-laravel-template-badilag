<?php

return [

    'admin_path' => env('ADMIN_PATH', 'panel-admin'),

    'trusted_proxies' => env('TRUSTED_PROXIES', ''),

    'trusted_hosts' => env('TRUSTED_HOSTS', ''),

    'cms_version' => '1.0.0-dev',

    'brand_colors' => [
        'primary' => '#056e4f',
        'secondary' => '#ff9900',
    ],

    'service_hours' => [
        'mon_thu_open' => env('SATKER_HOURS_MON_THU_OPEN', '08:00'),
        'mon_thu_close' => env('SATKER_HOURS_MON_THU_CLOSE', '16:30'),
        'fri_open' => env('SATKER_HOURS_FRI_OPEN', '08:00'),
        'fri_close' => env('SATKER_HOURS_FRI_CLOSE', '17:00'),
        'timezone_label' => env('SATKER_TIMEZONE_LABEL', match (env('APP_TIMEZONE', 'Asia/Makassar')) {
            'Asia/Makassar' => 'WITA',
            'Asia/Jayapura' => 'WIT',
            default => 'WIB',
        }),
    ],

    'jenis_badan' => [
        'PA' => 'Pengadilan Agama',
        'PTA' => 'Pengadilan Tinggi Agama',
        'MS' => "Mahkamah Syar'iyah",
        'MS_ACEH' => "Mahkamah Syar'iyah Aceh",
    ],

    'tingkat' => [
        'pertama' => 'Tingkat Pertama',
        'banding' => 'Tingkat Banding',
    ],

    'roles' => [
        'super_admin' => 'Super Admin',
        'admin_konten' => 'Admin Konten',
        'operator_kepaniteraan' => 'Operator Kepaniteraan',
        'operator_kesekretariatan' => 'Operator Kesekretariatan',
        'operator_ppid_humas' => 'Operator PPID / Humas',
        'penilai_internal' => 'Penilai Internal',
    ],

    'permissions' => [
        'manage-satker-settings',
        'change-satker-tingkat',
        'manage-menus',
        'manage-pages',
        'manage-collections',
        'manage-kepaniteraan',
        'manage-kesekretariatan',
        'manage-ppid-humas',
        'manage-sinova-links',
        'manage-criteria',
        'manage-users',
        'view-compliance-dashboard',
    ],

];

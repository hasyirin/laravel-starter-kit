<?php

declare(strict_types=1);

return [
    'api_token' => 'Token API',
    'ending' => 'berakhir dengan :last_four',
    'last_four' => 'Berakhir dengan',
    'scopes' => 'Skop',
    'no_scopes' => 'Tiada skop',
    'no_declared_scopes' => 'Aplikasi tidak mengisytiharkan sebarang skop, jadi token API hanya boleh memanggil route yang tidak memerlukan skop.',
    'created_at' => 'Dicipta',
    'expires_at' => 'Tamat tempoh',
    'never' => 'Tidak pernah',
    'last_used_at' => 'Kali terakhir digunakan',
    'state' => 'Status',
    'states' => [
        'active' => 'Aktif',
        'expires_soon' => 'Tamat tempoh tidak lama lagi',
        'expired' => 'Tamat tempoh',
        'revoked' => 'Ditarik balik',
    ],
    'never_expires' => 'Tiada tamat tempoh',
    'expiry_description' => 'Apabila tetapan ini tidak aktif, token API tamat tempoh dalam :days hari.',
    'issue' => 'Keluarkan',
    'issued_heading' => 'Salin token API',
    'copy' => 'Salin token API',
    'copied' => 'Token API telah disalin.',
    'issued_warning' => 'Anda tidak dapat melihat token API ini lagi selepas anda menutup dialog ini. Sila salin dan simpan token API ini di tempat yang selamat sekarang.',
    'rotate' => 'Tukar',
    'rotate_heading' => 'Tukar token API ini?',
    'rotate_description' => 'Token API baharu mendapat skop yang sama. Token API lama :ending masih berfungsi selama :days hari lagi, atau sehingga tamat tempohnya jika tarikh itu lebih awal.',
    'revoke' => 'Tarik balik',
    'revoke_heading' => 'Tarik balik token API ini?',
    'revoke_description' => 'Mulai sekarang, API menolak setiap panggilan dengan token API ini.',
    'revoked' => 'Token API telah ditarik balik.',
];

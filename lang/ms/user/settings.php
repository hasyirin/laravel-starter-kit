<?php

declare(strict_types=1);

return [
    'heading' => 'Tetapan',
    'nav' => [
        'user_information' => 'Maklumat pengguna',
        'preferences' => 'Pilihan',
        'security' => 'Keselamatan',
        'appearance' => 'Paparan',
    ],
    'user_information' => [
        'title' => 'Tetapan maklumat pengguna',
        'heading' => 'Maklumat pengguna',
        'unverified' => 'Alamat e-mel anda belum disahkan.',
        'resend_verification' => 'Klik di sini untuk menghantar semula e-mel pengesahan.',
        'verification_sent' => 'Pautan pengesahan baharu telah dihantar ke alamat e-mel anda.',
        'verification_throttled' => 'Terlalu banyak e-mel pengesahan. Cuba lagi dalam :seconds saat.',
        'phone_unverified' => 'Nombor telefon anda belum disahkan.',
        'verify_phone' => 'Sahkan sekarang.',
        'updated' => 'Maklumat pengguna dikemas kini.',
    ],
    'preferences' => [
        'title' => 'Pilihan',
        'heading' => 'Pilihan',
        'updated' => 'Pilihan dikemas kini.',
        'example' => 'Tarikh dan masa dipaparkan seperti ini: :example',
    ],
    'security' => [
        'title' => 'Tetapan keselamatan',
    ],
    'password' => [
        'heading' => 'Kemas kini kata laluan',
        'updated' => 'Kata laluan dikemas kini.',
    ],
    'two_factor' => [
        'heading' => 'Pengesahan dua faktor',
        'enabled_description' => 'Semasa log masuk, anda akan diminta memasukkan pin rawak yang selamat, yang boleh diperoleh daripada aplikasi yang menyokong TOTP pada telefon anda.',
        'disabled_description' => 'Apabila anda mengaktifkan pengesahan dua faktor, anda akan diminta memasukkan pin yang selamat semasa log masuk. Pin ini boleh diperoleh daripada aplikasi yang menyokong TOTP pada telefon anda.',
        'enable' => 'Aktifkan 2FA',
        'disable' => 'Nyahaktifkan 2FA',
        'manual_entry' => 'atau, masukkan kod secara manual',
        'setup_key' => 'Kunci persediaan',
        'modal' => [
            'setup' => [
                'title' => 'Aktifkan pengesahan dua faktor',
                'description' => 'Untuk selesai mengaktifkan pengesahan dua faktor, imbas kod QR atau masukkan kunci persediaan dalam aplikasi pengesah anda.',
            ],
            'verify' => [
                'title' => 'Sahkan kod pengesahan',
                'description' => 'Masukkan kod 6 digit daripada aplikasi pengesah anda.',
            ],
            'enabled' => [
                'title' => 'Pengesahan dua faktor diaktifkan',
                'description' => 'Pengesahan dua faktor kini aktif. Imbas kod QR atau masukkan kunci persediaan dalam aplikasi pengesah anda.',
            ],
        ],
    ],
    'recovery_codes' => [
        'heading' => 'Kod pemulihan 2FA',
        'description' => 'Kod pemulihan membolehkan anda mendapat semula akses jika anda kehilangan peranti 2FA anda. Simpan kod ini dalam pengurus kata laluan yang selamat.',
        'show' => 'Lihat kod pemulihan',
        'hide' => 'Sembunyikan kod pemulihan',
        'regenerate' => 'Jana semula kod',
        'list' => 'Kod pemulihan',
        'help' => 'Setiap kod pemulihan boleh digunakan sekali untuk mengakses akaun anda dan akan dibuang selepas digunakan. Jika anda memerlukan lebih banyak kod, klik Jana semula kod di atas.',
    ],
    'appearance' => [
        'title' => 'Tetapan paparan',
        'heading' => 'Paparan',
        'light' => 'Cerah',
        'dark' => 'Gelap',
        'system' => 'Sistem',
    ],
    'delete_account' => [
        'heading' => 'Padam akaun',
        'button' => 'Padam akaun',
        'confirm' => [
            'heading' => 'Adakah anda pasti mahu memadam akaun anda?',
            'description' => 'Setelah akaun anda dipadam, semua sumber dan datanya akan dipadam secara kekal. Sila masukkan kata laluan anda untuk mengesahkan bahawa anda mahu memadam akaun anda secara kekal.',
        ],
        'submit' => 'Padam akaun',
    ],
];

<?php

declare(strict_types=1);

return [
    'title' => 'Pengguna',
    'user' => 'Pengguna',
    'user_information' => 'Maklumat pengguna',
    'roles' => 'Peranan',
    'organization' => 'Organisasi',
    'placement' => 'Penempatan',
    'branch' => 'Cawangan',
    'department' => 'Jabatan',
    'position' => 'Jawatan',
    'created_at' => 'Dicipta',
    'email_verified_at' => 'E-mel disahkan',
    'phone_verified_at' => 'Telefon disahkan',
    'no_phone' => 'Tiada nombor telefon',
    'unverified' => 'Belum disahkan',
    'mark_as_verified' => 'Tandakan sebagai disahkan',
    'tabs' => [
        'passkeys' => 'Passkey',
        'audit' => 'Audit',
    ],
    'passkeys_tab' => [
        'name' => 'Nama',
        'authenticator' => 'Pengesah',
        'created_at' => 'Dicipta',
        'last_used_at' => 'Kali terakhir digunakan',
        'revoke' => 'Tarik balik',
        'revoke_heading' => 'Tarik balik passkey ini?',
        'revoked' => 'Passkey telah ditarik balik.',
    ],
    'audit' => [
        'by' => 'Oleh pengguna ini',
        'on' => 'Terhadap pengguna ini',
    ],
    'permissions' => [
        'heading' => 'Akses',
        'search' => 'Cari akses',
        'only_granted' => 'Yang diberi sahaja',
        'expand_all' => 'Kembangkan semua',
        'expand' => 'Kembangkan :label',
        'collapse' => 'Kuncupkan :label',
        'from' => 'daripada :role',
        'count' => ':granted daripada :total',
    ],
    'create' => [
        'title' => 'Cipta pengguna',
    ],
    'impersonation' => [
        'action' => 'Menyamar',
        'heading' => 'Menyamar sebagai :name?',
        'description' => 'Anda akan menggunakan aplikasi sebagai pengguna ini. Setiap perubahan yang anda buat merekodkan anda sebagai penyamar. Masukkan kata laluan anda untuk meneruskan.',
        'by' => 'Disamar oleh :name',
        'stop' => 'Berhenti menyamar',
    ],
    'verify' => [
        'heading' => 'Tandakan e-mel sebagai disahkan?',
        'description' => 'Pengguna tidak perlu mengesahkan :email.',
    ],
    'verify_phone' => [
        'heading' => 'Tandakan nombor telefon sebagai disahkan?',
        'description' => 'Pengguna tidak perlu mengesahkan :phone.',
    ],
];

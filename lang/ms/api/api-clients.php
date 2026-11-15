<?php

declare(strict_types=1);

return [
    'token_limit' => 'Klien API boleh mempunyai paling banyak :max token API yang aktif. Sila tarik balik satu token API dahulu.',
    'title' => 'Klien API',
    'api_client' => 'Klien API',
    'details' => 'Butiran klien API',
    'description' => 'Keterangan',
    'enabled' => 'Diaktifkan',
    'enabled_description' => 'Apabila tetapan ini tidak aktif, API menolak setiap panggilan daripada klien API ini. Aplikasi masih memanggil perkhidmatan klien API ini.',
    'rate_limit' => 'Had panggilan',
    'per_minute' => 'panggilan seminit',
    'service' => 'Perkhidmatan',
    'no_service' => 'Tiada perkhidmatan',
    'service_description' => 'Baris log API bagi panggilan kepada perkhidmatan ini juga menunjukkan klien API ini.',
    'allowed_ip_addresses' => 'Alamat IP yang dibenarkan',
    'allowed_ip_addresses_description' => 'Satu alamat IP atau julat CIDR pada setiap baris, seperti 192.0.2.10 atau 198.51.100.0/24.',
    'allowed_ip_address' => 'Alamat IP yang dibenarkan',
    'ip_address' => 'Alamat IP',
    'invalid_ip_address' => 'Masukkan alamat IP atau julat CIDR, seperti 198.51.100.0/24. Untuk membenarkan setiap alamat, aktifkan "Benarkan sebarang alamat IP".',
    'allows_any_ip_address' => 'Benarkan sebarang alamat IP',
    'any_ip_address' => 'Sebarang alamat IP',
    'any_ip_address_warning' => 'Setiap alamat IP boleh memanggil API dengan token API klien API ini.',
    'no_ip_address_note' => 'Senarai ini kosong, jadi API menolak setiap panggilan daripada klien API ini.',
    'active_tokens_count' => 'Token API aktif',
    'last_used_at' => 'Kali terakhir digunakan',
    'tabs' => [
        'tokens' => 'Token API',
        'api_log' => 'Log API',
        'audit' => 'Audit',
    ],
    'create' => [
        'title' => 'Cipta klien API',
    ],
];

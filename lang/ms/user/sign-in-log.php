<?php

declare(strict_types=1);

return [
    'title' => 'Log log masuk',
    'sign_in_entry' => 'Entri log masuk',
    'method' => 'Kaedah',
    'time' => 'Masa',
    'from' => 'Dari',
    'until' => 'Hingga',
    'user' => 'Pengguna',
    'no_user' => 'Tidak diketahui',
    'event' => 'Peristiwa',
    'result' => 'Keputusan',
    'two_factor' => '2FA',
    'impersonator' => 'Penyamar',
    'ip_address' => 'Alamat IP',
    'user_agent' => 'User agent',
    'trace_id' => 'Trace ID',
    'events' => [
        'sign_in' => 'Log masuk',
        'failed_sign_in' => 'Log masuk gagal',
        'sign_out' => 'Log keluar',
        'impersonation_started' => 'Penyamaran bermula',
        'impersonation_stopped' => 'Penyamaran tamat',
    ],
    'results' => [
        'signed_in' => 'Berjaya',
        'wrong_password' => 'Kata laluan salah',
        'no_matching_user' => 'Pengguna tidak ditemui',
        'wrong_two_factor_code' => 'Kod dua faktor salah',
        'passkey_failed' => 'Passkey gagal',
        'locked_out' => 'Terlalu banyak percubaan',
    ],
    'methods' => [
        'password' => 'Kata laluan',
        'passkey' => 'Passkey',
    ],
];

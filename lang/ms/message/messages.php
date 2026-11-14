<?php

declare(strict_types=1);

return [
    'status' => 'Status',
    'channel' => 'Saluran',
    'channels' => [
        'mail' => 'E-mel',
        'sms' => 'SMS',
    ],
    'recipient' => 'Penerima',
    'columns' => [
        'content' => 'Kandungan',
        'opened' => 'Dibuka',
        'clicked' => 'Diklik',
        'viewed' => 'Dilihat',
        'resend' => 'Hantar semula',
    ],
    'marks' => [
        'resend' => 'Hantar semula',
        'resent' => '{1} Dihantar semula|[2,*] Dihantar semula :count kali',
    ],
    'hints' => [
        'opened' => 'Klien e-mel telah memuatkan imej dalam e-mel. Ini bukan bukti bahawa seseorang telah membaca e-mel tersebut.',
        'clicked' => 'Seseorang telah mengikuti pautan dalam e-mel. Pengimbas pautan boleh melakukannya, jadi ini bukan bukti bahawa seseorang telah membaca e-mel tersebut.',
        'viewed' => 'Penerima, selepas log masuk, telah mengikuti pautan dalam e-mel. Ini satu-satunya bukti bahawa penerima telah membaca e-mel tersebut.',
        'resend' => '"Hantar semula" mengulangi mesej yang terdahulu. "Dihantar semula" menandakan mesej yang telah dihantar sekali lagi oleh pegawai.',
        'started' => 'Masa aplikasi mula menghantar mesej.',
        'sent' => 'Masa server e-mel atau gateway SMS menerima mesej. Ini bukan bukti bahawa mesej telah sampai kepada penerima.',
    ],
    'no_subject' => 'Tiada subjek',
    'cc' => 'CC',
    'bcc' => 'BCC',
    'intercepted_to' => 'Intercept kepada',
    'started_at' => 'Dimulakan',
    'sent_at' => 'Dihantar',
    'resend_of' => 'Hantar semula bagi mesej bertarikh',
    'resends' => 'Hantar semula',
    'tracking' => 'Penjejakan',
    'viewed' => 'Dilihat',
    'clicked' => 'Mungkin telah diklik',
    'opened' => 'Mungkin telah dibuka',
    'opened_times' => '{1} :time|[2,*] :time, :count kali',
    'not_yet' => 'Belum',
    'signals' => 'Hanya "Dilihat" membuktikan bahawa penerima telah membaca e-mel. Klien e-mel dan pengimbas pautan boleh menyebabkan "Dibuka" atau "Diklik".',
    'signals_with_copies' => 'Hanya "Dilihat" membuktikan bahawa penerima telah membaca e-mel. Klien e-mel dan pengimbas pautan boleh menyebabkan "Dibuka" atau "Diklik", dan penerima salinan juga boleh menyebabkannya.',
    'attachments' => 'Lampiran',
    'body' => 'Kandungan',
    'no_body' => 'Aplikasi tidak menyimpan kandungan e-mel ini.',
    'text' => 'Teks',
    'no_text' => 'Aplikasi tidak menyimpan teks mesej ini.',
    'resend_to' => 'Hantar kepada',
    'resend' => 'Hantar semula',
    'resent' => 'Mesej telah dimasukkan ke dalam queue untuk dihantar semula.',
    'statuses' => [
        'sending' => 'Sedang dihantar',
        'sent' => 'Dihantar',
        'failed' => 'Gagal',
    ],
    'reasons' => [
        'timeout' => [
            'label' => 'Penghantaran mengambil masa terlalu lama, jadi aplikasi menghentikannya.',
            'suggestion' => 'Mesej mungkin telah sampai sebelum aplikasi berhenti. Tanya penerima dahulu, supaya mereka tidak menerimanya dua kali.',
        ],
        'invalid_recipient' => [
            'label' => 'Alamat e-mel penerima tidak sah atau tidak wujud.',
            'suggestion' => 'Semak ejaan alamat, kemudian hantar semula e-mel ke alamat yang betul.',
        ],
        'mailbox_full' => [
            'label' => 'Peti mel penerima penuh.',
            'suggestion' => 'Minta penerima mengosongkan peti mel mereka, atau hantar semula e-mel ke alamat lain.',
        ],
        'rejected' => [
            'label' => 'Server e-mel penerima menolak e-mel ini.',
            'suggestion' => 'Hantar semula e-mel. Jika gagal lagi, hantar semula ke alamat lain.',
        ],
        'temporarily_rejected' => [
            'label' => 'Server e-mel menolak e-mel ini buat sementara waktu.',
            'suggestion' => 'Ini biasanya sementara. Hantar semula e-mel kemudian.',
        ],
        'attachment_too_large' => [
            'label' => 'Lampiran terlalu besar.',
            'suggestion' => 'Kecilkan saiz fail, kemudian hantar semula e-mel.',
        ],
        'attachment_missing' => [
            'label' => 'Satu lampiran tiada dalam storan aplikasi.',
            'suggestion' => 'Sediakan fail itu semula, kemudian hantar semula e-mel.',
        ],
        'connection_failed' => [
            'label' => 'Aplikasi tidak dapat berhubung dengan server e-melnya.',
            'suggestion' => 'Hantar semula e-mel kemudian. Jika gagal lagi, maklumkan kepada pentadbir sistem.',
        ],
        'authentication_failed' => [
            'label' => 'Server e-mel tidak menerima log masuk aplikasi.',
            'suggestion' => 'Hantar semula akan gagal sehingga pentadbir sistem membetulkan tetapan e-mel.',
        ],
        'dropped' => [
            'label' => 'Aplikasi menghentikan mesej sebelum mesej dihantar.',
            'suggestion' => 'Di luar production, Intercept menggugurkan mesej apabila tiada alamat atau nombor telefon untuk menerimanya.',
        ],
        'no_address' => [
            'label' => 'Penerima tiada alamat e-mel.',
            'suggestion' => 'Tambah alamat e-mel kepada penerima, kemudian hantar e-mel sekali lagi.',
        ],
        'no_phone_number' => [
            'label' => 'Penerima tiada nombor telefon.',
            'suggestion' => 'Tambah nombor telefon kepada penerima, kemudian hantar SMS sekali lagi.',
        ],
        'calling_code_not_allowed' => [
            'label' => 'Aplikasi tidak menghantar SMS ke kod panggilan nombor telefon ini.',
            'suggestion' => 'Gunakan nombor telefon dengan kod panggilan yang diterima oleh aplikasi untuk SMS.',
        ],
        'unknown' => [
            'label' => 'Ralat yang tidak diketahui telah berlaku.',
            'suggestion' => 'Hantar mesej sekali lagi. Jika gagal lagi, berikan mesej teknikal di bawah kepada pentadbir sistem.',
        ],
    ],
];

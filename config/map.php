<?php

declare(strict_types=1);

return [
    'center' => [
        'latitude' => 4.2,
        'longitude' => 109.0,
    ],

    'zoom' => 5,

    'point_zoom' => 17,

    'layers' => [
        'street' => [
            'label' => 'common/map.street',
            'url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            'max_zoom' => 19,
        ],
        'google_street' => [
            'label' => 'common/map.google_street',
            'url' => 'https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
            'subdomains' => ['mt0', 'mt1', 'mt2', 'mt3'],
            'attribution' => '&copy; Google',
            'max_zoom' => 20,
        ],
        'google_satellite' => [
            'label' => 'common/map.google_satellite',
            'url' => 'https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',
            'subdomains' => ['mt0', 'mt1', 'mt2', 'mt3'],
            'attribution' => '&copy; Google',
            'max_zoom' => 20,
        ],
    ],
];

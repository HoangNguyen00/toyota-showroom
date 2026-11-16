<?php

use App\Support\Catalog\CarPresenter;

// Isolated presentation fixtures; never seed these illustrative prices into MySQL.
return [
    'slides' => [
        ['name' => 'Corolla Cross', 'headline' => 'COROLLA CROSS', 'image' => 'corolla.png', 'tagline' => 'Dành cho những hành trình mới.'],
        ['name' => 'Vios', 'headline' => 'VIOS', 'image' => 'vios.png', 'tagline' => 'Đồng hành trên mọi nẻo đường.'],
        ['name' => 'Innova', 'headline' => 'INNOVA', 'image' => 'innova.png', 'tagline' => 'Rộng mở những hành trình bên nhau.'],
    ],
    'cars' => array_map(CarPresenter::fromArray(...), [
        ['id' => 4, 'name' => 'Toyota Vios', 'category' => 'vios', 'code' => 'DEMO-VIOS-001', 'image' => 'vios.png', 'image_path' => 'assets/products/toyota_vios.png', 'price' => 500000000, 'color' => 'Trắng', 'status' => 'available', 'status_label' => 'Có sẵn'],
        ['id' => 6, 'name' => 'Toyota Avanza', 'category' => 'avanza', 'code' => 'DEMO-AV-001', 'image' => 'toyota_avanza.jpg', 'image_path' => 'assets/products/toyota_avanza.jpg', 'price' => 600000000, 'color' => 'Bạc', 'status' => 'available', 'status_label' => 'Có sẵn'],
        ['id' => 7, 'name' => 'Toyota Fortuner', 'category' => 'fortuner', 'code' => 'DEMO-FT-001', 'image' => 'toyota_fotuner-color.png', 'image_path' => 'assets/products/toyota_fotuner-color.png', 'price' => 1200000000, 'color' => 'Trắng', 'status' => 'available', 'status_label' => 'Có sẵn'],
        ['id' => 8, 'name' => 'Toyota Raize', 'category' => 'raize', 'code' => 'DEMO-RZ-001', 'image' => 'toyota_RAIZE.png', 'image_path' => 'assets/products/toyota_RAIZE.png', 'price' => 550000000, 'color' => 'Đỏ', 'status' => 'available', 'status_label' => 'Có sẵn'],
        ['id' => 9, 'name' => 'Toyota Yaris Cross', 'category' => 'yaris-cross', 'code' => 'DEMO-YC-001', 'image' => 'toyota_yaris_cross.webp', 'image_path' => 'assets/products/toyota_yaris_cross.webp', 'price' => 750000000, 'color' => 'Xanh', 'status' => 'available', 'status_label' => 'Có sẵn'],
        ['id' => 2, 'name' => 'Toyota Land Cruiser', 'category' => 'land-cruiser', 'code' => 'DEMO-LC-001', 'image' => 'land-cruiser.png', 'image_path' => 'assets/products/toyota_0002_land-cruiser-color-1.png', 'price' => 4000000000, 'color' => 'Trắng', 'status' => 'reserved', 'status_label' => 'Đã giữ chỗ'],
    ]),
];

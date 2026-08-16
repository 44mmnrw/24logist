<?php

return [
    'yandex_api_key' => env('YANDEX_SEARCH_API_KEY'),
    'yandex_folder_id' => env('YANDEX_SEARCH_FOLDER_ID'),
    'target_host' => env('SEO_TARGET_HOST', '24logist.ru'),
    'default_region_id' => env('SEO_DEFAULT_REGION_ID', '225'),
    'default_device' => env('SEO_DEFAULT_DEVICE', 'DEVICE_ALL'),
    'position_depth' => (int) env('SEO_POSITION_DEPTH', 100),

    'seed_clusters' => [
        'электронная транспортная накладная' => ['name' => 'ЭТрН', 'slug' => 'etrn', 'target' => '/tag/etrn', 'intent' => 'informational'],
        'электронные перевозочные документы' => ['name' => 'ЭПД', 'slug' => 'epd', 'target' => '/tag/epd', 'intent' => 'informational'],
        'минтранс грузоперевозки' => ['name' => 'Минтранс', 'slug' => 'mintrans', 'target' => '/tag/mintrans', 'intent' => 'informational'],
        'логистика' => ['name' => 'Логистика', 'slug' => 'logistika', 'target' => '/tag/logistika', 'intent' => 'informational'],
        'электронный документооборот в грузоперевозках' => ['name' => 'ЭДО', 'slug' => 'edo', 'target' => '/tag/edo', 'intent' => 'informational'],
        'грузоперевозки' => ['name' => 'Грузоперевозки', 'slug' => 'gruzoperevozki', 'target' => '/tag/gruzoperevozki', 'intent' => 'informational'],
        'программа для экспедитора' => ['name' => 'Программа для экспедитора', 'slug' => 'programma-dlia-ekspeditora', 'target' => '/tag/programma-dlia-ekspeditora', 'intent' => 'commercial'],
        'программа для логиста' => ['name' => 'Программы для логистов', 'slug' => 'programmy-dlia-logistov', 'target' => '/tag/programmy-dlia-logistov', 'intent' => 'commercial'],
        'логист ру' => ['name' => 'ЛогистРу', 'slug' => 'logistru', 'target' => '/tag/logistru', 'intent' => 'navigational'],
        'сорм транспорт' => ['name' => 'СОРМ', 'slug' => 'sorm', 'target' => '/tag/sorm', 'intent' => 'informational'],
        'новости логистики' => ['name' => 'Новости логистики', 'slug' => 'novosti-logistiki', 'target' => '/tag/novosti-logistiki', 'intent' => 'informational'],
    ],
];

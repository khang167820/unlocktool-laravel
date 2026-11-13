<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Consolidated blog URLs
    |--------------------------------------------------------------------------
    |
    | These posts overlap a stronger canonical article. Keep the redirects in
    | one place so routes, listings, internal links and sitemaps stay aligned.
    | Targets may be an absolute site path or a full URL.
    |
    */
    'blog_redirects' => [
        // Existing legacy redirects.
        'icloud-iphone-7-bypass' => '/blog/bypass-icloud-iphone-7',
        'icloud-iphone-8-bypass' => '/blog/bypass-icloud-iphone-8',
        'flash-firmware-samsung-odin' => '/blog/flash-samsung-odin',
        'jailbreak-iphone-2026' => '/blog/jailbreak-iphone',
        'bang-gia-dich-vu-2026' => '/blog/bang-gia-dich-vu',
        'dich-vu-sua-chua-dien-thoai' => '/blog/sua-chua-dien-thoai',

        // Duplicate topic clusters identified in the 2026-11 content audit.
        'unlocktool-huong-dan-su-dung' => '/blog/unlocktool-full-guide',
        'cach-su-dung-unlocktool' => '/blog/unlocktool-full-guide',
        'thu-thuat-unlocktool' => '/blog/unlocktool-full-guide',
        'tai-unlocktool-ve-may-tinh' => '/blog/tai-unlocktool',
        'tai-unlocktool-mien-phi' => '/blog/tai-unlocktool',
        'unlocktool-download' => '/blog/tai-unlocktool',
        'unlocktool-latest-version' => '/blog/tai-unlocktool',
        'cau-hoi-thuong-gap-faq' => '/blog/cau-hoi-thuong-gap',
        'bypass-frp-samsung-a54' => '/blog/frp-samsung-a54',
        'brom-mode-mediatek' => '/blog/mediatek-brom-mode',
        'dc-unlocker-la-gi' => '/blog/dc-unlocker',
        'frp-huawei-2026' => '/blog/huawei-frp-bypass',
        'knox-samsung-remove' => '/blog/xoa-knox-samsung',
        'mdm-remove-iphone' => '/blog/bypass-mdm-iphone',
        'unlocktool-icloud-bypass' => '/blog/bypass-icloud-iphone',
        'unlocktool-iphone-bypass-icloud' => '/blog/bypass-icloud-iphone',
        'iphone-ipad-bypass' => '/blog/bypass-icloud-iphone',
        'unlocktool-error-fix' => '/blog/loi-unlocktool',
    ],

    /*
     | Slugs with no useful replacement can be retired with HTTP 410. Posts are
     | never deleted from the database, making this decision easy to reverse.
     */
    'blog_gone' => [],

    /*
     | Search Console showed high impressions but weak CTR for these pages.
     | Keep the promise consistent with the current homepage price (from 8K).
     */
    'blog_overrides' => [
        'thue-tai-khoan-unlocktool' => [
            'title' => 'Thuê Tài Khoản UnlockTool Từ 8K – Tự Động 24/7',
            'meta_title' => 'Thuê Tài Khoản UnlockTool Từ 8K – Tự Động 24/7',
            'meta_description' => 'Thuê tài khoản UnlockTool từ 8.000đ, nhận tự động sau thanh toán. Tài khoản Premium, đầy đủ tính năng và hỗ trợ kỹ thuật khi cần.',
        ],
        'mua-unlocktool' => [
            'title' => 'Mua UnlockTool Chính Hãng: Giá License Và Cách Mua 2026',
            'meta_title' => 'Mua UnlockTool Chính Hãng: Giá License Và Cách Mua 2026',
            'meta_description' => 'Cập nhật giá license UnlockTool, cách mua chính hãng và so sánh mua với thuê theo giờ để chọn phương án phù hợp, tiết kiệm chi phí.',
        ],
    ],
];

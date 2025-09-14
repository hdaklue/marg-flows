<?php

declare(strict_types=1);

return [
    'roles' => [
        'admin' => [
            'label' => 'مدير عام',
            'description' => 'وصول إداري كامل للكيانات المعينة',
        ],
        'manager' => [
            'label' => 'مدير',
            'description' => 'إدارة المحتوى وتعيين الأدوار داخل الكيانات',
        ],
        'editor' => [
            'label' => 'محرر',
            'description' => 'إنشاء وتحرير وحذف المحتوى',
        ],
        'contributor' => [
            'label' => 'مساهم',
            'description' => 'إنشاء وتحرير المحتوى الخاص',
        ],
        'viewer' => [
            'label' => 'مشاهد',
            'description' => 'وصول للقراءة فقط للمحتوى',
        ],
        'guest' => [
            'label' => 'ضيف',
            'description' => 'وصول محدود للمحتوى العام',
        ],
    ],
];

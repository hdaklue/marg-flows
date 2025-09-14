<?php

declare(strict_types=1);

return [
    'components' => [
        'language_switch' => [
            'label' => 'اللغة',
            'placeholder' => 'اختر لغة',
        ],

        'calendar' => [
            'today' => 'اليوم',
            'previous_month' => 'الشهر السابق',
            'next_month' => 'الشهر التالي',
            'no_events' => 'لا توجد أحداث',
        ],

        'file_upload' => [
            'drag_drop' => 'اسحب وأفلت الملفات هنا أو انقر للتصفح',
            'browse' => 'تصفح الملفات',
            'max_size' => 'الحد الأقصى لحجم الملف: :size',
            'supported_formats' => 'التنسيقات المدعومة: :formats',
            'uploading' => 'جاري الرفع...',
            'upload_complete' => 'الملفات المرفوعة',
            'upload_failed' => 'فشل الرفع',
            'file_too_large' => 'الملف كبير جداً',
            'invalid_file_type' => 'نوع ملف غير صالح',
            'video_upload' => 'رفع فيديو',
            'video_file' => 'ملف فيديو',
        ],

        'pagination' => [
            'previous' => 'السابق',
            'next' => 'التالي',
            'showing' => 'عرض :from إلى :to من أصل :total نتيجة',
            'per_page' => 'لكل صفحة',
        ],

        'search' => [
            'placeholder' => 'بحث...',
            'no_results' => 'لم يتم العثور على نتائج',
            'searching' => 'جاري البحث...',
            'clear' => 'مسح البحث',
        ],

        'modal' => [
            'close' => 'إغلاق',
            'confirm' => 'تأكيد',
            'cancel' => 'إلغاء',
        ],

        'tooltip' => [
            'copy' => 'نسخ',
            'copied' => 'تم النسخ!',
            'edit' => 'تحرير',
            'delete' => 'حذف',
            'view' => 'عرض',
        ],

        'side_notes' => [
            'tooltip' => 'الملاحظات الجانبية [Alt + S]',
            'title' => 'الملاحظات الجانبية',
            'subtitle' => 'يمكنك فقط رؤية هذه الملاحظات',
            'placeholder' => 'اكتب ملاحظتك .. واضغط Enter',
        ],
    ],

    'panels' => [
        'admin' => [
            'title' => 'لوحة الإدارة',
            'description' => 'الإدارة وإدارة النظام',
        ],
        'portal' => [
            'title' => 'البوابة',
            'description' => 'بوابة التطبيق الرئيسية',
        ],
    ],

    'themes' => [
        'light' => 'فاتح',
        'dark' => 'داكن',
        'system' => 'النظام',
    ],
];

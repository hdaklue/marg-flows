<?php

declare(strict_types=1);

return [
    'actions' => [
        'create' => 'إنشاء تدفق',
        'edit' => 'تحرير التدفق',
        'delete' => 'حذف التدفق',
        'view' => 'عرض التدفق',
        'duplicate' => 'نسخ التدفق',
        'archive' => 'أرشفة التدفق',
        'restore' => 'استعادة التدفق',
        'publish' => 'نشر التدفق',
        'unpublish' => 'إلغاء نشر التدفق',
    ],

    'labels' => [
        'name' => 'اسم التدفق',
        'description' => 'الوصف',
        'status' => 'الحالة',
        'stage' => 'المرحلة',
        'priority' => 'الأولوية',
        'assigned_to' => 'مُعيَّن إلى',
        'due_date' => 'تاريخ الاستحقاق',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
        'documents' => 'المستندات',
        'participants' => 'المشاركون',
    ],

    // Migrated from app.php flow_stages
    'stages' => [
        'draft' => 'مسودة',
        'active' => 'نشط',
        'paused' => 'متوقف',
        'blocked' => 'محظور',
        'completed' => 'مكتمل',
        'canceled' => 'ملغي',
        'review' => 'مراجعة',
        'approval' => 'موافقة',
        'published' => 'منشور',
        'archived' => 'مؤرشف',
        'in_progress' => 'قيد التنفيذ',
    ],

    'priority' => [
        'low' => 'منخفض',
        'medium' => 'متوسط',
        'high' => 'عالي',
        'urgent' => 'عاجل',
    ],

    'status' => [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'archived' => 'مؤرشف',
        'published' => 'منشور',
        'unpublished' => 'غير منشور',
        'pending' => 'معلق',
        'approved' => 'موافق عليه',
        'rejected' => 'مرفوض',
    ],

    'messages' => [
        'created' => 'تم إنشاء التدفق بنجاح',
        'updated' => 'تم تحديث التدفق بنجاح',
        'deleted' => 'تم حذف التدفق بنجاح',
        'archived' => 'تم أرشفة التدفق بنجاح',
        'restored' => 'تم استعادة التدفق بنجاح',
        'published' => 'تم نشر التدفق بنجاح',
        'unpublished' => 'تم إلغاء نشر التدفق بنجاح',
        'error_creating' => 'خطأ في إنشاء التدفق',
        'error_updating' => 'خطأ في تحديث التدفق',
        'error_deleting' => 'خطأ في حذف التدفق',
    ],

    'documents' => [
        'title' => 'مستندات التدفق',
        'add_document' => 'إضافة مستند',
        'no_documents' => 'لم يتم العثور على مستندات لهذا التدفق',
        'document_count' => '{0} لا توجد مستندات|{1} مستند واحد|[2,*] :count مستندات',
    ],

    'tabs' => [
        'active' => 'نشط',
        'draft' => 'معطل',
        'all' => 'الكل',
    ],

    'table' => [
        'columns' => [
            'title' => 'العنوان',
            'stage' => 'المرحلة',
            'creator_avatar' => 'المنشئ',
            'participant_stack' => 'الأعضاء',
        ],
        'actions' => [
            'view' => 'المستندات',
        ],
    ],
];

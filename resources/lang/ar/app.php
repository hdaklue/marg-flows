<?php

return [
    // Navigation
    'dashboard' => 'لوحة التحكم',
    'flows' => 'سير العمل',
    'documents' => 'المستندات',
    'users' => 'المستخدمون',
    'tenants' => 'المستأجرون',
    'settings' => 'الإعدادات',
    'profile' => 'الملف الشخصي',
    
    // Actions
    'create' => 'إنشاء',
    'edit' => 'تعديل',
    'delete' => 'حذف',
    'save' => 'حفظ',
    'cancel' => 'إلغاء',
    'submit' => 'إرسال',
    'search' => 'بحث',
    'filter' => 'تصفية',
    'view' => 'عرض',
    'download' => 'تحميل',
    'upload' => 'رفع',
    'invite' => 'دعوة',
    'assign' => 'تعيين',
    'approve' => 'موافقة',
    'reject' => 'رفض',
    'publish' => 'نشر',
    'draft' => 'مسودة',
    
    // Labels
    'name' => 'الاسم',
    'email' => 'البريد الإلكتروني',
    'password' => 'كلمة المرور',
    'title' => 'العنوان',
    'description' => 'الوصف',
    'status' => 'الحالة',
    'date' => 'التاريخ',
    'created_at' => 'تاريخ الإنشاء',
    'updated_at' => 'تاريخ التحديث',
    'actions' => 'الإجراءات',
    'role' => 'الدور',
    'permissions' => 'الصلاحيات',
    'account_type' => 'نوع الحساب',
    'last_login' => 'آخر تسجيل دخول',
    'invited_by' => 'دعوة من',
    
    // Status
    'active' => 'نشط',
    'inactive' => 'غير نشط',
    'pending' => 'في الانتظار',
    'approved' => 'موافق عليه',
    'rejected' => 'مرفوض',
    'completed' => 'مكتمل',
    'in_progress' => 'قيد التنفيذ',
    'draft' => 'مسودة',
    'published' => 'منشور',
    
    // Messages
    'success' => 'نجح',
    'error' => 'خطأ',
    'warning' => 'تحذير',
    'info' => 'معلومات',
    'created_successfully' => 'تم الإنشاء بنجاح',
    'updated_successfully' => 'تم التحديث بنجاح',
    'deleted_successfully' => 'تم الحذف بنجاح',
    'operation_completed' => 'تمت العملية بنجاح',
    'no_records_found' => 'لا توجد سجلات',
    'confirm_delete' => 'هل أنت متأكد من أنك تريد حذف هذا العنصر؟',
    
    // Form placeholders
    'enter_name' => 'أدخل الاسم',
    'enter_email' => 'أدخل عنوان البريد الإلكتروني',
    'enter_title' => 'أدخل العنوان',
    'enter_description' => 'أدخل الوصف',
    'search_placeholder' => 'بحث...',
    'select_option' => 'اختر خياراً',
    
    // Flow stages
    'flow_stages' => [
        'draft' => 'مسودة',
        'active' => 'نشط',
        'paused' => 'متوقف',
        'blocked' => 'محظور',
        'completed' => 'مكتمل',
        'canceled' => 'ملغي',
        'review' => 'مراجعة',
        'approval' => 'الموافقة',
        'published' => 'منشور',
        'archived' => 'مؤرشف',
    ],
    
    // Roles
    'roles' => [
        'assignee' => 'المُعيَّن',
        'approver' => 'المُوافِق',
        'reviewer' => 'المُراجِع',
        'observer' => 'المُراقِب',
    ],
    
    // File upload
    'file_upload' => [
        'drag_drop' => 'اسحب وأسقط الملفات هنا أو انقر للتصفح',
        'max_size' => 'الحد الأقصى لحجم الملف: :size',
        'supported_formats' => 'الصيغ المدعومة: :formats',
        'uploading' => 'جاري الرفع...',
        'upload_complete' => 'الملفات المرفوعة',
        'upload_failed' => 'فشل الرفع',
        'video_upload' => 'رفع فيديو',
        'video_file' => 'ملف الفيديو',
    ],
    
    // Account types
    'account_types' => [
        'admin' => 'مدير',
        'manager' => 'مشرف',
        'user' => 'مستخدم',
    ],
    
    // Role descriptions
    'role_descriptions' => [
        'assignee' => 'مسؤول عن إكمال المهمة',
        'approver' => 'يراجع ويوافق على إكمال المهمة',
        'reviewer' => 'يقدم التعليقات والاقتراحات',
        'observer' => 'يراقب التقدم دون تدخل مباشر',
    ],
    
    // Feedback status
    'feedback_status' => [
        'open' => 'مفتوح',
        'running' => 'قيد التشغيل',
        'resolved' => 'تم الحل',
        'rejected' => 'مرفوض',
    ],
    
    // Feedback urgency
    'feedback_urgency' => [
        'normal' => 'عادي',
        'suggestion' => 'اقتراح',
        'urgent' => 'عاجل',
    ],
    
    // System roles
    'system_roles' => [
        'admin' => 'مدير النظام',
        'manager' => 'مدير',
        'editor' => 'محرر',
        'contributor' => 'مساهم',
        'viewer' => 'مشاهد',
        'guest' => 'ضيف',
    ],
    
    // System role descriptions
    'system_role_descriptions' => [
        'admin' => 'وصول إداري كامل للكيانات المعينة',
        'manager' => 'إدارة المحتوى وتعيين الأدوار داخل الكيانات',
        'editor' => 'إنشاء وتعديل وحذف المحتوى',
        'contributor' => 'إنشاء وتعديل المحتوى الخاص',
        'viewer' => 'وصول للقراءة فقط للمحتوى',
        'guest' => 'وصول محدود للمحتوى العام',
    ],
    
    // EditorJS tool titles
    'editor_tools' => [
        'paragraph' => 'نص',
        'header' => 'عنوان',
        'images' => 'صورة',
        'table' => 'جدول',
        'nestedList' => 'قائمة',
        'alert' => 'تنبيه',
        'linkTool' => 'رابط',
        'videoEmbed' => 'تضمين فيديو',
        'videoUpload' => 'رفع فيديو',
        'commentTune' => 'إضافة تعليق',
    ],
    
    // EditorJS interface translations (EditorJS format)
    'editor_ui' => [
        'ui' => [
            'blockTunes' => [
                'toggler' => [
                    'Click to tune' => 'انقر للضبط',
                    'or drag to move' => 'أو اسحب للتحريك',
                ],
            ],
            'inlineToolbar' => [
                'converter' => [
                    'Convert to' => 'تحويل إلى',
                ],
            ],
            'toolbar' => [
                'toolbox' => [
                    'Add' => 'إضافة',
                    'Filter' => 'تصفية',
                    'Nothing found' => 'لا يوجد شيء',
                ],
            ],
            'popover' => [
                'Filter' => 'تصفية',
                'Nothing found' => 'لا يوجد شيء',
            ],
        ],
        'toolNames' => [
            'Text' => 'نص',
            'Heading' => 'عنوان',
            'List' => 'قائمة',
            'Table' => 'جدول',
            'Link' => 'رابط',
            'Bold' => 'عريض',
            'Italic' => 'مائل',
        ],
        'blockTunes' => [
            'delete' => [
                'Delete' => 'حذف',
            ],
            'moveUp' => [
                'Move up' => 'تحريك لأعلى',
            ],
            'moveDown' => [
                'Move down' => 'تحريك لأسفل',
            ],
            'commentTune' => [
                'Add Comment' => 'إضافة تعليق',
                'Comment' => 'تعليق',
                'Add a comment' => 'إضافة تعليق',
            ],
        ],
    ],
];
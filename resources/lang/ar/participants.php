<?php

declare(strict_types=1);

return [
    'labels' => [
        'name' => 'الاسم',
        'username' => 'اسم المستخدم',
        'role' => 'الدور',
        'email' => 'البريد الإلكتروني',
        'participants' => 'المشاركون',
        'member' => 'عضو',
        'members' => 'الأعضاء',
    ],

    'actions' => [
        'add_member' => 'إضافة عضو',
        'remove_member' => 'إزالة عضو',
        'change_role' => 'تغيير الدور',
        'invite_member' => 'دعوة عضو',
        'invite_to_team' => 'دعوة للفريق',
        'manage_participants' => 'إدارة المشاركين',
    ],

    'messages' => [
        'member_added' => 'تم إضافة العضو بنجاح',
        'member_removed' => 'تم إزالة العضو بنجاح',
        'role_changed' => 'تم تغيير الدور بنجاح',
        'invitation_sent' => 'تم إرسال الدعوة بنجاح',
        'operation_failed' => 'فشلت العملية. يرجى المحاولة مرة أخرى.',
        'no_members' => 'لا توجد أعضاء',
        'cannot_remove_yourself' => 'لا يمكنك إزالة نفسك',
        'cannot_change_own_role' => 'لا يمكنك تغيير دورك الخاص',
    ],

    'roles' => [
        'owner' => 'مالك',
        'admin' => 'مدير',
        'member' => 'عضو',
        'viewer' => 'مشاهد',
        'editor' => 'محرر',
        'reviewer' => 'مراجع',
        'approver' => 'معتمد',
        'observer' => 'مراقب',
        'assignee' => 'مكلف',
    ],

    'tooltips' => [
        'add_member' => 'إضافة عضو جديد إلى هذا العنصر',
        'remove_member' => 'إزالة هذا العضو',
        'change_role' => 'تغيير دور العضو',
        'invite_member' => 'إرسال دعوة لعضو جديد',
    ],

    'placeholders' => [
        'search_members' => 'البحث عن الأعضاء...',
        'enter_email' => 'أدخل عنوان البريد الإلكتروني للدعوة',
        'select_role' => 'اختر دورًا',
        'select_member' => 'اختر عضوًا',
    ],

    'confirmations' => [
        'remove_member' => 'هل أنت متأكد من أنك تريد إزالة هذا العضو؟',
        'change_role' => 'هل أنت متأكد من أنك تريد تغيير دور هذا العضو؟',
    ],
];

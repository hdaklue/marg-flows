<?php

return [
    'validation' => [
        'available' => 'اسم المستخدم :attribute مُستخدم بالفعل.',
        'format' => [
            'string' => 'يجب أن يكون :attribute نصاً.',
            'min_length' => 'يجب أن يكون :attribute :min أحرف على الأقل.',
            'max_length' => 'يجب ألا يتجاوز :attribute :max حرفاً.',
            'invalid_chars' => 'يمكن أن يحتوي :attribute على حروف وأرقام وشرطات سفلية فقط.',
            'no_numbers' => 'لا يمكن أن يحتوي :attribute على أرقام.',
            'no_underscores' => 'لا يمكن أن يحتوي :attribute على شرطات سفلية.',
            'lowercase_only' => 'يجب أن يكون :attribute بأحرف صغيرة.',
            'edge_underscores' => 'لا يمكن أن يبدأ أو ينتهي :attribute بشرطة سفلية.',
            'consecutive_underscores' => 'لا يمكن أن يحتوي :attribute على شرطات سفلية متتالية.',
        ],
        'reserved' => ':attribute محجوز ولا يمكن استخدامه.',
    ],

    'generation' => [
        'failed' => 'فشل في إنشاء اسم مستخدم فريد بعد :attempts محاولة.',
        'success' => 'تم إنشاء اسم المستخدم بنجاح.',
    ],

    'errors' => [
        'empty_source' => 'لا يمكن إنشاء اسم مستخدم من مصدر فارغ.',
        'config_disabled' => 'إنشاء اسم المستخدم معطل.',
    ],
];

<?php

// Persian validation messages for the forms this app uses. :attribute is the field's Persian label.
return [
    'required' => '«:attribute» را پر کنید.',
    'string' => '«:attribute» باید متن باشد.',
    'email' => '«:attribute» کامل نیست؛ مثلاً name@example.com',
    'max' => [
        'string' => '«:attribute» حداکثر :max نویسه می‌تواند باشد.',
        'numeric' => '«:attribute» حداکثر :max است.',
        'file' => 'حجم «:attribute» حداکثر :max کیلوبایت است.',
    ],
    'min' => [
        'string' => '«:attribute» دست‌کم :min نویسه باشد.',
        'numeric' => '«:attribute» دست‌کم :min است.',
    ],
    'confirmed' => 'تکرار «:attribute» با خودش یکسان نیست.',
    'regex' => 'قالب «:attribute» درست نیست.',
    'numeric' => '«:attribute» باید عدد باشد.',
    'integer' => '«:attribute» باید عدد صحیح باشد.',
    'unique' => 'این «:attribute» قبلاً ثبت شده است.',
    'in' => 'مقدار «:attribute» مجاز نیست.',
    'boolean' => '«:attribute» باید بله یا خیر باشد.',
    'password' => [
        'letters' => '«:attribute» باید حرف داشته باشد.',
        'mixed' => '«:attribute» باید حرف بزرگ و کوچک داشته باشد.',
        'numbers' => '«:attribute» باید عدد داشته باشد.',
        'symbols' => '«:attribute» باید نماد داشته باشد.',
        'uncompromised' => 'این «:attribute» در نشت‌های اطلاعاتی دیده شده؛ رمز دیگری انتخاب کنید.',
    ],
    'attributes' => [],
];

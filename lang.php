<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$supportedLanguages = ['ar', 'en'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLanguages, true)) {
    $_SESSION['selected_lang'] = $_GET['lang'];
}

$currentLang = $_SESSION['selected_lang'] ?? $_SESSION['lang'] ?? 'ar';
if (!in_array($currentLang, $supportedLanguages, true)) {
    $currentLang = 'ar';
}
$_SESSION['selected_lang'] = $currentLang;
$_SESSION['lang'] = $currentLang;

$isArabic = $currentLang === 'ar';
$dir = $isArabic ? 'rtl' : 'ltr';

$translations = [
    'ar' => [
        'site_name' => 'نظام الغطاء النباتي',
        'home' => 'الرئيسية',
        'login' => 'تسجيل الدخول',
        'signup' => 'إنشاء حساب',
        'logout' => 'تسجيل الخروج',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'full_name' => 'الاسم الكامل',
        'role' => 'نوع الحساب',
        'user' => 'مستخدم',
        'admin' => 'مدير',
        'create_account' => 'إنشاء الحساب',
        'welcome_back' => 'مرحباً بعودتك',
        'login_subtitle' => 'ادخل إلى لوحة التحكم لمتابعة بيانات النباتات والمناطق.',
        'register_subtitle' => 'أنشئ حساباً جديداً للوصول إلى النظام.',
        'invalid_login' => 'اسم المستخدم أو كلمة المرور غير صحيحة.',
        'email_exists' => 'هذا البريد الإلكتروني مسجل مسبقاً.',
        'required_fields' => 'يرجى تعبئة جميع الحقول المطلوبة.',
        'signup_success' => 'تم إنشاء الحساب بنجاح، يمكنك تسجيل الدخول الآن.',
        'dashboard' => 'لوحة المستخدم',
        'plants' => 'النباتات',
        'regions' => 'المناطق',
        'diseases' => 'أمراض النباتات',
        'vegetation_index' => 'مؤشر الغطاء النباتي',
        'search' => 'بحث',
        'export' => 'تصدير',
        'filter' => 'تصفية',
        'reset' => 'إعادة ضبط',
        'all' => 'الكل',
        'welcome' => 'مرحباً',
        'username' => 'اسم المستخدم',
        'confirm_password' => 'تأكيد كلمة المرور',
        'select_role' => 'اختر نوع الحساب',
        'footer_desc' => 'نظام رقمي لمراقبة الغطاء النباتي وإدارة الموارد البيئية والمائية لدعم الاستدامة وتحقيق رؤية المملكة 2030.',
        'contact_title' => 'معلومات التواصل',
        'country' => 'المملكة العربية السعودية',
        'footer_rights' => '© 2026 منصة الغطاء النباتي | جميع الحقوق محفوظة',
        'footer_design' => 'تصميم: طالبات تقنية المعلومات - جامعة تبوك',
        'forgot_password' => 'هل نسيت كلمة المرور؟',
        'username' => 'اسم المستخدم',
'hero_desc' => 'منصة نظام لمتابعة الغطاء النباتي، المناطق، النباتات، والأمراض النباتية من خلال واجهات واضحة وسهلة الاستخدام.',
'reset_password'   => 'إعادة تعيين كلمة المرور',
'new_password'     => 'كلمة المرور الجديدة',
'confirm_password' => 'تأكيد كلمة المرور',
'update_password'  => 'تحديث كلمة المرور',
'back_login' => 'العودة لتسجيل الدخول',
'about' => 'عن الموقع',

    'hero_small' => 'مرحباً بك',
    'hero_title' => 'نظام الغطاء النباتي',
    'hero_desc' => 'منصة لإدارة وتتبع الغطاء النباتي بطريقة ذكية وسهلة.',
    'hero_btn' => 'استكشف',

    'about_small' => 'عن المشروع',
    'about_title' => 'نظام الغطاء النباتي',
    'about_desc' => 'يهدف هذا النظام إلى توفير منصة متكاملة لمتابعة وتحليل الغطاء النباتي في مختلف المناطق.',

    'goals_title' => 'أهدافنا',

    'goal1_title' => 'إدارة النباتات',
    'goal1_desc' => 'تنظيم وتتبع النباتات بسهولة.',

    'goal2_title' => 'تحليل البيانات',
    'goal2_desc' => 'توفير تقارير دقيقة لدعم القرار.',

    'goal3_title' => 'الاستدامة',
    'goal3_desc' => 'المساهمة في الحفاظ على البيئة.',
            'pass_requirements' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل وتحتوي حروف وأرقام',
            'username' => 'اسم المستخدم',
    'confirm_password' => 'تأكيد كلمة المرور',


        ],
    'en' => [
        'site_name' => 'Vegetation System',
        'home' => 'Home',
        'login' => 'Login',
        'signup' => 'Sign Up',
        'logout' => 'Logout',
        'email' => 'Email',
        'password' => 'Password',
        'full_name' => 'Full Name',
        'role' => 'Role',
        'user' => 'User',
        'admin' => 'Admin','create_account' => 'Create Account',
        'welcome_back' => 'Welcome Back',
        'login_subtitle' => 'Access your dashboard to monitor plants and regions.',
        'register_subtitle' => 'Create a new account to access the system.',
        'invalid_login' => 'Invalid username or password.',
        'email_exists' => 'This email is already registered.',
        'required_fields' => 'Please complete all required fields.',
        'signup_success' => 'Account created successfully. You can login now.',
        'dashboard' => 'Dashboard',
        'plants' => 'Plants',
        'regions' => 'Regions',
        'diseases' => 'Plant Diseases',
        'vegetation_index' => 'Vegetation Index',
        'search' => 'Search',
        'export' => 'Export',
        'filter' => 'Filter',
        'reset' => 'Reset',
        'all' => 'All',
        'welcome' => 'Welcome',
        'username' => 'Username',
        'confirm_password' => 'Confirm Password',
        'select_role' => 'Select Role',
        'footer_desc' => 'A digital system for monitoring vegetation cover and managing environmental and water resources to support sustainability and Saudi Vision 2030.',
        'contact_title' => 'Contact Information',
        'country' => 'Kingdom of Saudi Arabia',
        'footer_rights' => '© 2026 Vegetation Platform | All rights reserved',
        'footer_design' => 'Designed by IT Students - University of Tabuk',
        'forgot_password' => 'Forgot your password?',
        'username' => 'Username',
        'hero_desc' => 'A platform for monitoring vegetation cover, regions, plants, and plant diseases through clear and easy-to-use interfaces.',
        'reset_password'   => 'Reset Password',
'new_password'     => 'New Password',
'confirm_password' => 'Confirm Password',
'update_password'  => 'Update Password',
        'back_login' => 'Back to Login',
        'about' => 'About',
        'hero_small' => 'Welcome',
    'hero_title' => 'Vegetation System',
    'hero_desc' => 'A platform to manage and monitor vegetation easily.',
    'hero_btn' => 'Explore',

    'about_small' => 'About',
    'about_title' => 'Vegetation System',
    'about_desc' => 'This system helps monitor and analyze vegetation across regions.',

    'goals_title' => 'Our Goals',

    'goal1_title' => 'Plant Management',
    'goal1_desc' => 'Organize and track plants easily.',

    'goal2_title' => 'Data Analysis',
    'goal2_desc' => 'Provide accurate reports for decisions.',

    'goal3_title' => 'Sustainability',
    'goal3_desc' => 'Support environmental preservation.',
            'pass_requirements' => 'Password must be at least 6 characters and include letters and numbers',
             'username' => 'Username',
    'confirm_password' => 'Confirm Password',

    ],
];

$lang = $translations[$currentLang];

function tr(string $key): string
{
    global $lang;
    return $lang[$key] ?? $key;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
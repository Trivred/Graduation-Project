<?php
/**
 * Shared public footer.
 * Keep this file markup only. Footer styles are in assets/css/footer.css.
 */
if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tr')) {
    function tr(string $key): string
    {
        global $lang, $lang_code;

        $fallback = [
            'site_name' => 'منصة الغطاء النباتي',
            'footer_desc' => 'منصة ذكية لتحليل الغطاء النباتي ومتابعة المؤشرات البيئية.',
            'contact_title' => 'تواصل معنا',
            'country' => 'المملكة العربية السعودية',
            'footer_rights' => 'جميع الحقوق محفوظة © 2026',
            'footer_design' => 'تصميم وتطوير منصة الغطاء النباتي',
        ];

        $dictionary = [];
        if (isset($lang_code, $lang[$lang_code]) && is_array($lang[$lang_code])) {
            $dictionary = $lang[$lang_code];
        }

        return (string)($dictionary[$key] ?? $fallback[$key] ?? $key);
    }
}
?>
<footer class="main-footer" role="contentinfo">
    <div class="footer-container">
        <section class="footer-col footer-about" aria-label="about platform">
            <h2 class="footer-logo"><?= e(tr('site_name')) ?></h2>
            <p><?= e(tr('footer_desc')) ?></p>
        </section>

        <section class="footer-col footer-contact" aria-label="contact information">
            <h3><?= e(tr('contact_title')) ?></h3>
            <p><i class="bi bi-geo-alt-fill" aria-hidden="true"></i><span><?= e(tr('country')) ?></span></p>
            <p><i class="bi bi-telephone-fill" aria-hidden="true"></i><span dir="ltr">+966 504614938</span></p>
            <p><i class="bi bi-envelope-fill" aria-hidden="true"></i><span dir="ltr">info@vegplatform.sa</span></p>
        </section>
    </div>

    <div class="footer-line" aria-hidden="true"></div>

    <div class="footer-bottom">
        <span><?= e(tr('footer_rights')) ?></span>
        <span><?= e(tr('footer_design')) ?></span>
    </div>
</footer>

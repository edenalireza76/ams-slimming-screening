<?php
if (!defined('ABSPATH')) {
    exit;
}

final class AMSF_Screening_Evaluator {

    public static function evaluate(array $data) {
        $reasons = array();

        $age    = isset($data['age']) ? intval(self::normalize_digits($data['age'])) : 0;
        $height = isset($data['height']) ? intval(self::normalize_digits($data['height'])) : 0;
        $weight = isset($data['weight']) ? floatval(self::normalize_digits($data['weight'])) : 0.0;
        $gender = sanitize_text_field($data['gender'] ?? 'female');

        // مرحله اول: محاسبه BMI به یک رقم اعشار
        $height_m = $height / 100.0;
        $bmi = 0.0;
        if ($height_m > 0) {
            $bmi = round($weight / ($height_m * $height_m), 1);
        }

        // قالب‌بندی همیشه با یک رقم اعشار (مثال: 24.0 -> ۲۴٫۰)
        $bmi_en_formatted = number_format($bmi, 1, '.', '');
        $bmi_fa_formatted = self::to_persian_digits(str_replace('.', '٫', $bmi_en_formatted));

        // بررسی شرط سن زیر ۱۸
        if ($age < 18) {
            $reasons[] = 'ایمنی و اثربخشی آمپول‌های لاغری برای مدیریت وزن در افراد زیر ۱۸ سال، به‌طور کلی تأیید نشده است.';
        }

        // بررسی شرط BMI کمتر از ۲۴٫۱ (بر اساس عدد گردشده)
        if ($bmi < 24.1) {
            $reasons[] = 'شما با BMI ' . $bmi_fa_formatted . ' واجد شرایط تزریق آمپول نیستید. پیشنهاد می‌کنیم از سایر خدمات کلینیک ما استفاده کنید.';
        }

        // بررسی ۳ شرط بانوان (فقط اگر جنسیت خانم باشد)
        if ($gender === 'female') {
            if (($data['is_pregnant'] ?? '') === 'yes') {
                $reasons[] = 'طبق دستورالعمل‌های سازمان بهداشت جهانی، افراد باردار امکان استفاده از آمپول‌های لاغری را ندارند.';
            }
            if (($data['planning_pregnancy_6m'] ?? '') === 'yes') {
                $reasons[] = 'طبق دستورالعمل‌های سازمان بهداشت جهانی، افرادی که قصد بارداری در ۶ ماه آینده را دارند، امکان استفاده از آمپول‌های لاغری را ندارند.';
            }
            if (($data['is_breastfeeding'] ?? '') === 'yes') {
                $reasons[] = 'طبق دستورالعمل‌های سازمان بهداشت جهانی، افراد در دوران شیردهی امکان استفاده از آمپول‌های لاغری را ندارند.';
            }
        }

        // سرطان تیروئید در بستگان درجه یک
        if (($data['thyroid_cancer_relatives'] ?? '') === 'yes') {
            $reasons[] = 'طبق دستورالعمل‌های سازمان بهداشت جهانی، افراد دارای سرطان تیروئید در بستگان درجه یک امکان استفاده از آمپول‌های لاغری را ندارند.';
        }

        // سنگ صفرا یا بیماری کیسه صفرا در سؤال ۹
        $diseases = isset($data['current_diseases']) && is_array($data['current_diseases']) ? $data['current_diseases'] : array();
        if (in_array('gallstones', $diseases, true)) {
            $reasons[] = 'طبق دستورالعمل‌های سازمان بهداشت جهانی، افراد دارای سابقه سنگ صفرا یا بیماری کیسه صفرا امکان استفاده از آمپول‌های لاغری را ندارند.';
        }

        // سابقه پانکراتیت
        if (($data['pancreatitis_history'] ?? '') === 'yes') {
            $reasons[] = 'طبق دستورالعمل‌های سازمان بهداشت جهانی، افراد دارای سابقه پانکراتیت در خود یا بستگان درجه یک امکان استفاده از آمپول‌های لاغری را ندارند.';
        }

        // سابقه رتینوپاتی
        if (($data['retinopathy_history'] ?? '') === 'yes') {
            $reasons[] = 'طبق دستورالعمل‌های سازمان بهداشت جهانی، افراد دارای سابقه رتینوپاتی امکان استفاده از آمپول‌های لاغری را ندارند.';
        }

        // افکار خودآسیب‌رسان/خودکشی در یک ماه گذشته
        if (($data['self_harm_thoughts'] ?? '') === 'yes') {
            $reasons[] = 'طبق دستورالعمل‌های سازمان بهداشت جهانی، افراد دارای افکار خودکشی در یک ماه گذشته امکان استفاده از آمپول‌های لاغری را ندارند.';
        }

        $is_eligible = empty($reasons);

        $eligible_msg = 'بر اساس شاخص توده بدنی شما (BMI: ' . $bmi_fa_formatted . ') و سایر مواردی که در این فرم بررسی شد، می‌توانید از آمپول لاغری استفاده کنید.';

        return array(
            'is_eligible'       => $is_eligible,
            'bmi'               => $bmi_en_formatted,
            'bmi_display'       => $bmi_fa_formatted,
            'eligible_message'  => $eligible_msg,
            'rejection_reasons' => $reasons
        );
    }

    public static function normalize_digits($string) {
        $farsi  = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٫', '،');
        $arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '.', '.');
        $latin  = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '.');
        $string = str_replace($farsi, $latin, (string)$string);
        return str_replace($arabic, $latin, $string);
    }

    public static function to_persian_digits($string) {
        $latin = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $farsi = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        return str_replace($latin, $farsi, (string)$string);
    }
}
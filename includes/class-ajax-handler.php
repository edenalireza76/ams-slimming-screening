<?php
if (!defined('ABSPATH')) {
    exit;
}

final class AMSF_Ajax_Handler {

    public function __construct() {
        add_action('wp_ajax_amsf_submit_screening', array($this, 'handle_submission'));
        add_action('wp_ajax_nopriv_amsf_submit_screening', array($this, 'handle_submission'));
    }

    public function handle_submission() {
        $schema = AMSF_Default_Settings::get_fields_schema();
        $clean_data = array();

        $gender = isset($_POST['gender']) ? sanitize_text_field(wp_unslash($_POST['gender'])) : '';
        if (!in_array($gender, array('female', 'male'), true)) {
            wp_send_json_error(array('message' => 'این فیلد الزامی است'));
        }

        foreach ($schema as $idx => $f) {
            $fid = $f['id'];

            // وقتی جنسیت آقا است، پاسخ سؤالات ۱۲ تا ۱۴ باید خالی ذخیره شود، نه «خیر»
            if (isset($f['gender_lock']) && $gender === 'male') {
                $clean_data[$fid] = '';
                continue;
            }

            if ($f['type'] === 'checkbox_group') {
                $vals = isset($_POST[$fid]) && is_array($_POST[$fid]) ? array_map('sanitize_text_field', wp_unslash($_POST[$fid])) : array();
                if (!empty($f['required']) && empty($vals)) {
                    wp_send_json_error(array('message' => 'این فیلد الزامی است'));
                }
                $clean_data[$fid] = $vals;
            } else {
                $val = isset($_POST[$fid]) ? trim(sanitize_text_field(wp_unslash($_POST[$fid]))) : '';
                if (!empty($f['required']) && $val === '') {
                    wp_send_json_error(array('message' => 'این فیلد الزامی است'));
                }
                $clean_data[$fid] = $val;
            }
        }

        // ۱. اعتبارسنجی نام (۳ تا ۶۰ کاراکتر)
        $name_len = mb_strlen($clean_data['full_name'], 'UTF-8');
        if ($name_len < 3 || $name_len > 60) {
            wp_send_json_error(array('message' => 'نام و نام‌خانوادگی باید بین ۳ تا ۶۰ کاراکتر باشد'));
        }

        // ۳. اعتبارسنجی شماره موبایل
        $mobile = AMSF_Screening_Evaluator::normalize_digits($clean_data['mobile']);
        if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
            wp_send_json_error(array('message' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد'));
        }
        $clean_data['mobile'] = $mobile;

        // ۴. اعتبارسنجی سن (۱۰ تا ۱۰۰)
        $raw_age = AMSF_Screening_Evaluator::normalize_digits($clean_data['age']);
        if (!is_numeric($raw_age) || strpos($raw_age, '.') !== false) {
            wp_send_json_error(array('message' => 'سن باید بین ۱۰ تا ۱۰۰ باشد'));
        }
        $age = intval($raw_age);
        if ($age < 10 || $age > 100) {
            wp_send_json_error(array('message' => 'سن باید بین ۱۰ تا ۱۰۰ باشد'));
        }
        $clean_data['age'] = $age;

        // ۵. اعتبارسنجی وزن (۳۰ تا ۳۰۰ کیلوگرم)
        $raw_weight = AMSF_Screening_Evaluator::normalize_digits($clean_data['weight']);
        if (!is_numeric($raw_weight)) {
            wp_send_json_error(array('message' => 'وزن باید بین ۳۰ تا ۳۰۰ کیلوگرم باشد'));
        }
        $weight = round(floatval($raw_weight), 1);
        if ($weight < 30.0 || $weight > 300.0) {
            wp_send_json_error(array('message' => 'وزن باید بین ۳۰ تا ۳۰۰ کیلوگرم باشد'));
        }
        $clean_data['weight'] = $weight;

        // ۶. اعتبارسنجی قد (عدد صحیح، ۱۲۰ تا ۲۲۵ سانتی‌متر)
        $raw_height = AMSF_Screening_Evaluator::normalize_digits($clean_data['height']);
        if (!is_numeric($raw_height)) {
            wp_send_json_error(array('message' => 'قد باید بین ۱۲۰ تا ۲۲۵ سانتی‌متر باشد'));
        }
        $float_height = floatval($raw_height);
        if ($float_height < 10) {
            wp_send_json_error(array('message' => 'قد را به سانتی‌متر وارد کنید، مثلاً ۱۷۰'));
        }
        if (strpos($raw_height, '.') !== false) {
            wp_send_json_error(array('message' => 'قد باید بین ۱۲۰ تا ۲۲۵ سانتی‌متر باشد'));
        }
        $height = intval($raw_height);
        if ($height < 120 || $height > 225) {
            wp_send_json_error(array('message' => 'قد باید بین ۱۲۰ تا ۲۲۵ سانتی‌متر باشد'));
        }
        $clean_data['height'] = $height;

        // محاسبه نتیجه نهایی
        $evaluation = AMSF_Screening_Evaluator::evaluate($clean_data);

        // ذخیره در دیتابیس
        global $wpdb;
        $table_name = $wpdb->prefix . 'amsf_submissions';

        $wpdb->insert(
            $table_name,
            array(
                'created_at'        => current_time('mysql'),
                'full_name'         => $clean_data['full_name'],
                'mobile'            => $clean_data['mobile'],
                'age'               => $age,
                'gender'            => $gender,
                'height'            => $height,
                'weight'            => $weight,
                'bmi'               => $evaluation['bmi'],
                'status'            => $evaluation['is_eligible'] ? 'eligible' : 'ineligible',
                'rejection_reasons' => wp_json_encode($evaluation['rejection_reasons'], JSON_UNESCAPED_UNICODE),
                'all_fields'        => wp_json_encode($clean_data, JSON_UNESCAPED_UNICODE),
            ),
            array('%s', '%s', '%s', '%d', '%s', '%d', '%f', '%s', '%s', '%s', '%s')
        );

        wp_send_json_success(array(
            'is_eligible'       => $evaluation['is_eligible'],
            'bmi'               => $evaluation['bmi_display'],
            'eligible_message'  => $evaluation['eligible_message'],
            'rejection_reasons' => $evaluation['rejection_reasons']
        ));
    }
}

new AMSF_Ajax_Handler();
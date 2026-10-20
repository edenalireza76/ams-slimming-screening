<?php
if (!defined('ABSPATH')) {
    exit;
}

final class AMSF_Admin_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }

    public function add_menu_pages() {
        add_menu_page(
            'غربالگری لاغری',
            'غربالگری لاغری',
            'manage_options',
            'amsf-leads',
            array($this, 'render_leads_router'),
            'dashicons-clipboard',
            26
        );

        add_submenu_page(
            'amsf-leads',
            'پرونده‌های ثبت‌شده',
            'پرونده‌های ثبت‌شده',
            'manage_options',
            'amsf-leads',
            array($this, 'render_leads_router')
        );

        add_submenu_page(
            'amsf-leads',
            'تنظیمات مراحل و فیلدها',
            'تنظیمات مراحل و فیلدها',
            'manage_options',
            'amsf-fields-settings',
            array($this, 'render_fields_settings_page')
        );
    }

    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) return;

        // ۱. خروجی CSV
        if (isset($_GET['action']) && $_GET['action'] === 'amsf_export_csv') {
            check_admin_referer('amsf_export_nonce');
            $this->export_csv();
            exit;
        }

        // ۲. پاکسازی رکوردهای آزمایشی
        if (isset($_POST['amsf_cleanup_tests'])) {
            check_admin_referer('amsf_cleanup_nonce');
            global $wpdb;
            $table = $wpdb->prefix . 'amsf_submissions';
            $wpdb->query("DELETE FROM $table WHERE full_name LIKE '%تست چکلیست%' OR mobile = '09123456789'");
            wp_redirect(admin_url('admin.php?page=amsf-leads&cleaned=1'));
            exit;
        }

        // ۳. حذف تکی رکورد
        if (isset($_GET['action']) && $_GET['action'] === 'amsf_delete_lead' && isset($_GET['lead_id'])) {
            check_admin_referer('amsf_delete_lead_nonce');
            global $wpdb;
            $table = $wpdb->prefix . 'amsf_submissions';
            $wpdb->delete($table, array('id' => intval($_GET['lead_id'])), array('%d'));
            wp_redirect(admin_url('admin.php?page=amsf-leads&deleted=1'));
            exit;
        }

        // ۴. ذخیره تنظیمات مراحل و فیلدها
        if (isset($_POST['amsf_save_fields_settings'])) {
            check_admin_referer('amsf_fields_settings_nonce');
            $base_schema = AMSF_Default_Settings::get_base_fields_schema();
            $base_steps  = AMSF_Default_Settings::get_base_steps_meta();

            $submitted_fields = isset($_POST['fields']) && is_array($_POST['fields']) ? wp_unslash($_POST['fields']) : array();
            $submitted_steps  = isset($_POST['steps']) && is_array($_POST['steps']) ? wp_unslash($_POST['steps']) : array();

            $new_fields = array();
            foreach ($base_schema as $num => $field) {
                $fid = $field['id'];
                if (isset($submitted_fields[$fid])) {
                    $item = array(
                        'label'       => sanitize_text_field($submitted_fields[$fid]['label'] ?? $field['label']),
                        'placeholder' => sanitize_text_field($submitted_fields[$fid]['placeholder'] ?? ($field['placeholder'] ?? '')),
                        'required'    => !empty($submitted_fields[$fid]['required']),
                    );
                    if (!empty($field['options']) && isset($submitted_fields[$fid]['options']) && is_array($submitted_fields[$fid]['options'])) {
                        $item['options'] = array();
                        foreach ($field['options'] as $opt_k => $opt_v) {
                            $item['options'][$opt_k] = sanitize_text_field($submitted_fields[$fid]['options'][$opt_k] ?? $opt_v);
                        }
                    }
                    $new_fields[$fid] = $item;
                }
            }

            $new_steps = array();
            foreach ($base_steps as $s_num => $s_info) {
                if (isset($submitted_steps[$s_num])) {
                    $new_steps[$s_num] = array(
                        'title' => sanitize_text_field($submitted_steps[$s_num]['title'] ?? $s_info['title']),
                        'desc'  => sanitize_text_field($submitted_steps[$s_num]['desc'] ?? $s_info['desc']),
                    );
                }
            }

            update_option('amsf_custom_fields_settings', $new_fields);
            update_option('amsf_custom_steps_settings', $new_steps);
            wp_redirect(admin_url('admin.php?page=amsf-fields-settings&saved=1'));
            exit;
        }

        // ۵. بازگردانی تنظیمات به پیش‌فرض
        if (isset($_POST['amsf_reset_fields_settings'])) {
            check_admin_referer('amsf_fields_settings_nonce');
            delete_option('amsf_custom_fields_settings');
            delete_option('amsf_custom_steps_settings');
            wp_redirect(admin_url('admin.php?page=amsf-fields-settings&reset=1'));
            exit;
        }
    }

    public function render_leads_router() {
        if (!current_user_can('manage_options')) return;

        if (isset($_GET['action']) && $_GET['action'] === 'view_lead' && !empty($_GET['lead_id'])) {
            $this->render_single_lead_page(intval($_GET['lead_id']));
        } else {
            $this->render_leads_list_page();
        }
    }

    private function render_leads_list_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'amsf_submissions';

        $filter = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
        $where = '';
        if ($filter === 'eligible' || $filter === 'ineligible') {
            $where = $wpdb->prepare(" WHERE status = %s", $filter);
        }

        $leads = $wpdb->get_results("SELECT * FROM $table $where ORDER BY id DESC");
        ?>
        <div class="wrap" dir="rtl">
            <h1 class="wp-heading-inline">پرونده‌های ثبت‌شده غربالگری آمپول لاغری</h1>

            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=amsf-leads&action=amsf_export_csv'), 'amsf_export_nonce')); ?>" class="button button-primary" style="margin-right: 10px;">
                دریافت خروجی اکسل (CSV کامل ۲۱ سؤال)
            </a>

            <form method="post" style="display:inline-block; margin-right: 8px;">
                <?php wp_nonce_field('amsf_cleanup_nonce'); ?>
                <button type="submit" name="amsf_cleanup_tests" class="button button-secondary" onclick="return confirm('آیا از حذف فرم‌های آزمایشی مطمئن هستید؟');">
                    پاکسازی فرم‌های آزمایشی
                </button>
            </form>

            <?php if (isset($_GET['cleaned'])): ?>
                <div class="notice notice-success is-dismissible"><p>فرم‌های آزمایشی با موفقیت حذف شدند.</p></div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="notice notice-success is-dismissible"><p>پرونده مورد نظر حذف شد.</p></div>
            <?php endif; ?>

            <ul class="subsubsub" style="float:none; margin: 15px 0;">
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=amsf-leads')); ?>" class="<?php echo empty($filter) ? 'current' : ''; ?>">همه پرونده‌ها</a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=amsf-leads&filter_status=eligible')); ?>" class="<?php echo $filter === 'eligible' ? 'current' : ''; ?>">واجد شرایط</a> |</li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=amsf-leads&filter_status=ineligible')); ?>" class="<?php echo $filter === 'ineligible' ? 'current' : ''; ?>">واجد شرایط نیست</a></li>
            </ul>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="50">شناسه</th>
                        <th width="160">نام و نام‌خانوادگی</th>
                        <th width="110">شماره تماس</th>
                        <th width="80">سن / جنسیت</th>
                        <th width="95">قد / وزن</th>
                        <th width="65">BMI</th>
                        <th width="95">نتیجه نهایی</th>
                        <th>دلایل عدم تأیید</th>
                        <th width="130">تاریخ و ساعت</th>
                        <th width="130">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                        <tr><td colspan="10">هیچ پرونده‌ای یافت نشد.</td></tr>
                    <?php else: foreach ($leads as $l): 
                        $reasons = json_decode($l->rejection_reasons, true);
                        if (!is_array($reasons)) $reasons = maybe_unserialize($l->rejection_reasons);
                        $view_url = admin_url('admin.php?page=amsf-leads&action=view_lead&lead_id=' . $l->id);
                    ?>
                        <tr>
                            <td><?php echo esc_html($l->id); ?></td>
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url($view_url); ?>" title="مشاهده کامل اطلاعات این پرونده">
                                        <?php echo esc_html($l->full_name !== '' ? $l->full_name : 'بدون نام'); ?>
                                    </a>
                                </strong>
                            </td>
                            <td dir="ltr"><?php echo esc_html($l->mobile); ?></td>
                            <td><?php echo esc_html($l->age . ' / ' . ($l->gender === 'female' ? 'خانم' : 'آقا')); ?></td>
                            <td><?php echo esc_html($l->height . 'cm / ' . $l->weight . 'kg'); ?></td>
                            <td><strong><?php echo esc_html(number_format((float)$l->bmi, 1, '.', '')); ?></strong></td>
                            <td>
                                <?php if ($l->status === 'eligible'): ?>
                                    <span style="color:#166534; background:#dcfce7; padding:3px 8px; border-radius:4px; font-weight:bold;">واجد شرایط</span>
                                <?php else: ?>
                                    <span style="color:#991b1b; background:#fee2e2; padding:3px 8px; border-radius:4px; font-weight:bold;">واجد نیست</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($reasons) && is_array($reasons)) {
                                    echo '<ul style="margin:0; padding-right:15px; font-size:12px;">';
                                    foreach ($reasons as $r) echo '<li>' . esc_html($r) . '</li>';
                                    echo '</ul>';
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td dir="ltr"><?php echo esc_html($l->created_at); ?></td>
                            <td>
                                <a href="<?php echo esc_url($view_url); ?>" class="button button-small button-primary">مشاهده جزئیات</a>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=amsf-leads&action=amsf_delete_lead&lead_id=' . $l->id), 'amsf_delete_lead_nonce')); ?>" style="color:#d32f2f; margin-right:6px; vertical-align:middle;" onclick="return confirm('حذف شود؟');">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_single_lead_page($lead_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'amsf_submissions';
        $lead  = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $lead_id));

        if (!$lead) {
            echo '<div class="wrap" dir="rtl"><h2>پرونده مورد نظر یافت نشد.</h2><a href="' . esc_url(admin_url('admin.php?page=amsf-leads')) . '" class="button">بازگشت به لیست</a></div>';
            return;
        }

        $schema     = AMSF_Default_Settings::get_fields_schema();
        $steps_meta = AMSF_Default_Settings::get_steps_meta();

        $all_fields = json_decode($lead->all_fields, true);
        if (!is_array($all_fields)) $all_fields = maybe_unserialize($lead->all_fields);
        if (!is_array($all_fields)) $all_fields = array();

        $reasons = json_decode($lead->rejection_reasons, true);
        if (!is_array($reasons)) $reasons = maybe_unserialize($lead->rejection_reasons);
        ?>
        <div class="wrap" dir="rtl">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                <h1 style="margin:0;">جزئیات پرونده شماره #<?php echo esc_html($lead->id); ?> — <?php echo esc_html($lead->full_name); ?></h1>
                <div>
                    <button type="button" onclick="window.print();" class="button">🖨️ چاپ پرونده</button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=amsf-leads')); ?>" class="button button-primary" style="margin-right: 6px;">بازگشت به لیست پرونده‌ها</a>
                </div>
            </div>

            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:20px; margin-bottom:24px;">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:16px;">
                    <div>
                        <span style="color:#64748b; font-size:13px; display:block;">نتیجه نهایی غربالگری:</span>
                        <?php if ($lead->status === 'eligible'): ?>
                            <span style="display:inline-block; margin-top:4px; color:#166534; background:#dcfce7; padding:4px 12px; border-radius:6px; font-weight:bold; font-size:14px;">✓ واجد شرایط</span>
                        <?php else: ?>
                            <span style="display:inline-block; margin-top:4px; color:#991b1b; background:#fee2e2; padding:4px 12px; border-radius:6px; font-weight:bold; font-size:14px;">✕ واجد شرایط نیست</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span style="color:#64748b; font-size:13px; display:block;">شاخص توده بدنی (BMI):</span>
                        <strong style="font-size:18px; color:#1e293b; display:inline-block; margin-top:4px;"><?php echo esc_html(number_format((float)$lead->bmi, 1, '.', '')); ?></strong>
                    </div>
                    <div>
                        <span style="color:#64748b; font-size:13px; display:block;">شماره تماس (تلگرام):</span>
                        <strong dir="ltr" style="font-size:16px; color:#1e293b; display:inline-block; margin-top:4px;"><?php echo esc_html($lead->mobile); ?></strong>
                    </div>
                    <div>
                        <span style="color:#64748b; font-size:13px; display:block;">تاریخ و ساعت ثبت:</span>
                        <strong dir="ltr" style="font-size:14px; color:#1e293b; display:inline-block; margin-top:4px;"><?php echo esc_html($lead->created_at); ?></strong>
                    </div>
                </div>

                <?php if (!empty($reasons) && is_array($reasons)): ?>
                    <div style="margin-top:20px; padding:16px; background:#fdf2f2; border:1px solid #f87171; border-radius:6px; color:#7f1d1d;">
                        <strong style="display:block; margin-bottom:8px; font-size:14px;">دلایل عدم تأیید در غربالگری:</strong>
                        <ul style="margin:0; padding-right:20px; line-height:1.8;">
                            <?php foreach ($reasons as $r): ?>
                                <li style="font-weight:600;"><?php echo esc_html($r); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; overflow:hidden;">
                <table class="wp-list-table widefat striped" style="border:none;">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th width="180">بخش / مرحله</th>
                            <th width="340">عنوان سؤال</th>
                            <th>پاسخ ثبت‌شده کاربر</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schema as $num => $field): 
                            $fid = $field['id'];
                            $raw_val = $all_fields[$fid] ?? '';
                            $display_val = $this->format_field_value_for_display($field, $raw_val);
                            $step_title = $steps_meta[$field['step']]['title'] ?? ('مرحله ' . $field['step']);
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($num); ?></strong></td>
                                <td style="color:#475569;"><?php echo esc_html($step_title); ?></td>
                                <td><strong><?php echo esc_html($field['label']); ?></strong></td>
                                <td style="font-size:14px;">
                                    <?php if ($display_val === '' || $display_val === '—'): ?>
                                        <span style="color:#94a3b8;">— (بدون پاسخ / غیرفعال)</span>
                                    <?php else: ?>
                                        <span style="color:#0f172a; font-weight:500;"><?php echo nl2br(esc_html($display_val)); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function render_fields_settings_page() {
        if (!current_user_can('manage_options')) return;

        $fields     = AMSF_Default_Settings::get_fields_schema();
        $steps_meta = AMSF_Default_Settings::get_steps_meta();
        ?>
        <div class="wrap" dir="rtl">
            <h1>تنظیمات کامل مراحل و فیلدهای فرم غربالگری (۲۱ سؤال)</h1>
            <p>در این بخش می‌توانید عنوان و توضیح هر مرحله، متن هر سؤال (Label)، متن داخل ورودی (Placeholder)، متن گزینه‌ها و وضعیت <strong>اجباری یا اختیاری بودن</strong> هر فیلد را تغییر دهید.</p>

            <?php if (isset($_GET['saved'])): ?>
                <div class="notice notice-success is-dismissible"><p>تنظیمات مراحل و فیلدها با موفقیت ذخیره شد.</p></div>
            <?php endif; ?>

            <?php if (isset($_GET['reset'])): ?>
                <div class="notice notice-warning is-dismissible"><p>تمامی تنظیمات به حالت پیش‌فرض اولیه بازگردانده شدند.</p></div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('amsf_fields_settings_nonce'); ?>

                <?php foreach ($steps_meta as $s_num => $s_info): ?>
                    <div style="background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:18px; margin-top:20px;">
                        <h2 style="margin-top:0; color:#1e3a8a; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
                            مرحله <?php echo esc_html($s_num); ?>: <?php echo esc_html($s_info['title']); ?>
                        </h2>

                        <div style="display:grid; grid-template-columns: 1fr 2fr; gap:16px; margin-bottom:16px; background:#f8fafc; padding:12px; border-radius:6px;">
                            <div>
                                <label style="font-weight:bold; display:block; margin-bottom:4px;">عنوان مرحله <?php echo esc_html($s_num); ?>:</label>
                                <input type="text" name="steps[<?php echo esc_attr($s_num); ?>][title]" value="<?php echo esc_attr($s_info['title']); ?>" class="regular-text" style="width:100%;" />
                            </div>
                            <div>
                                <label style="font-weight:bold; display:block; margin-bottom:4px;">توضیح مرحله <?php echo esc_html($s_num); ?>:</label>
                                <input type="text" name="steps[<?php echo esc_attr($s_num); ?>][desc]" value="<?php echo esc_attr($s_info['desc']); ?>" class="large-text" style="width:100%;" />
                            </div>
                        </div>

                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th width="45">#</th>
                                    <th width="280">عنوان سؤال (Label)</th>
                                    <th>متن داخل ورودی (Placeholder) / متن گزینه‌ها</th>
                                    <th width="100" style="text-align:center;">اجباری باشد؟</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fields as $num => $f): 
                                    if ((int)$f['step'] !== (int)$s_num) continue;
                                    $fid = $f['id'];
                                    $has_placeholder = array_key_exists('placeholder', $f);
                                ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($num); ?></strong></td>
                                        <td>
                                            <input 
                                                type="text" 
                                                name="fields[<?php echo esc_attr($fid); ?>][label]" 
                                                value="<?php echo esc_attr($f['label']); ?>" 
                                                class="large-text" 
                                                required
                                            />
                                            <?php if (!empty($f['gender_lock'])): ?>
                                                <small style="color:#d97706; display:block; margin-top:4px;">مخصوص بانوان</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($has_placeholder): ?>
                                                <label style="font-size:12px; color:#475569; display:block; margin-bottom:3px;">متن راهنمای داخل فیلد (Placeholder):</label>
                                                <input 
                                                    type="text" 
                                                    name="fields[<?php echo esc_attr($fid); ?>][placeholder]" 
                                                    value="<?php echo esc_attr($f['placeholder']); ?>" 
                                                    class="large-text"
                                                />
                                            <?php elseif (!empty($f['options']) && is_array($f['options'])): ?>
                                                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:8px;">
                                                    <?php foreach ($f['options'] as $opt_k => $opt_v): ?>
                                                        <div>
                                                            <input 
                                                                type="text" 
                                                                name="fields[<?php echo esc_attr($fid); ?>][options][<?php echo esc_attr($opt_k); ?>]" 
                                                                value="<?php echo esc_attr($opt_v); ?>" 
                                                                style="width:100%; font-size:12px;"
                                                            />
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color:#94a3b8;">فیلد بله / خیر</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center; vertical-align:middle;">
                                            <input 
                                                type="checkbox" 
                                                name="fields[<?php echo esc_attr($fid); ?>][required]" 
                                                value="1" 
                                                <?php checked(!empty($f['required'])); ?>
                                            />
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>

                <p class="submit" style="display:flex; gap:10px; align-items:center; margin-top:20px;">
                    <button type="submit" name="amsf_save_fields_settings" class="button button-primary button-large">ذخیره تغییرات تمام مراحل و فیلدها</button>
                    <button type="submit" name="amsf_reset_fields_settings" class="button button-secondary button-large" onclick="return confirm('آیا مطمئن هستید که می‌خواهید تمام عناوین و وضعیت‌های اجباری را به حالت پیش‌فرض استاندارد برگردانید؟');">بازگردانی به پیش‌فرض استاندارد</button>
                </p>
            </form>
        </div>
        <?php
    }

    private function format_field_value_for_display($field, $val) {
        if (is_array($val)) {
            if (empty($val)) return '';
            $translated = array();
            foreach ($val as $v_key) {
                $translated[] = $field['options'][$v_key] ?? $v_key;
            }
            return implode(' ، ', $translated);
        }

        if ($field['type'] === 'toggle') {
            if ($val === 'yes') return 'بله';
            if ($val === 'no') return 'خیر';
            return '';
        }

        if ($field['type'] === 'radio') {
            return $field['options'][$val] ?? $val;
        }

        return (string)$val;
    }

    private function export_csv() {
        global $wpdb;
        $table  = $wpdb->prefix . 'amsf_submissions';
        $rows   = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);
        $schema = AMSF_Default_Settings::get_fields_schema();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ams-screening-' . date('Y-m-d-H-i') . '.csv');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");

        $headers = array('شناسه', 'تاریخ و ساعت ارسال', 'BMI گردشده', 'نتیجه نهایی', 'فهرست دلایل عدم تأیید');
        foreach ($schema as $num => $field) {
            $headers[] = 'سؤال ' . $num . ': ' . $field['label'];
        }
        fputcsv($out, $headers);

        foreach ($rows as $r) {
            $reasons = json_decode($r['rejection_reasons'], true);
            if (!is_array($reasons)) $reasons = maybe_unserialize($r['rejection_reasons']);
            $reason_txt = is_array($reasons) ? implode(' | ', $reasons) : '';

            $all_fields = json_decode($r['all_fields'], true);
            if (!is_array($all_fields)) $all_fields = maybe_unserialize($r['all_fields']);
            if (!is_array($all_fields)) $all_fields = array();

            $line = array(
                $r['id'],
                $r['created_at'],
                number_format((float)$r['bmi'], 1, '.', ''),
                $r['status'] === 'eligible' ? 'واجد شرایط' : 'واجد نیست',
                $reason_txt
            );

            foreach ($schema as $num => $field) {
                $fid = $field['id'];
                $val = $all_fields[$fid] ?? '';
                $line[] = $this->format_field_value_for_display($field, $val);
            }

            fputcsv($out, $line);
        }
        fclose($out);
    }
}

new AMSF_Admin_Settings();
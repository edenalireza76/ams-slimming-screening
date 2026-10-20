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
            array($this, 'render_leads_page'),
            'dashicons-clipboard',
            26
        );
    }

    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) return;

        // خروجی CSV
        if (isset($_GET['action']) && $_GET['action'] === 'amsf_export_csv') {
            check_admin_referer('amsf_export_nonce');
            $this->export_csv();
            exit;
        }

        // پاکسازی رکوردهای آزمایشی طبق سند تست
        if (isset($_POST['amsf_cleanup_tests'])) {
            check_admin_referer('amsf_cleanup_nonce');
            global $wpdb;
            $table = $wpdb->prefix . 'amsf_submissions';
            $wpdb->query("DELETE FROM $table WHERE full_name LIKE '%تست چکلیست%' OR mobile = '09123456789'");
            wp_redirect(admin_url('admin.php?page=amsf-leads&cleaned=1'));
            exit;
        }

        // حذف تکی رکورد
        if (isset($_GET['action']) && $_GET['action'] === 'amsf_delete_lead' && isset($_GET['lead_id'])) {
            check_admin_referer('amsf_delete_lead_nonce');
            global $wpdb;
            $table = $wpdb->prefix . 'amsf_submissions';
            $wpdb->delete($table, array('id' => intval($_GET['lead_id'])), array('%d'));
            wp_redirect(admin_url('admin.php?page=amsf-leads&deleted=1'));
            exit;
        }
    }

    public function render_leads_page() {
        if (!current_user_can('manage_options')) return;
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
                <button type="submit" name="amsf_cleanup_tests" class="button button-secondary" onclick="return confirm('آیا از حذف فرم‌های آزمایشی (تست چکلیست حذف شود / ۰۹۱۲۳۴۵۶۷۸۹) مطمئن هستید؟');">
                    پاکسازی فرم‌های آزمایشی
                </button>
            </form>

            <?php if (isset($_GET['cleaned'])): ?>
                <div class="notice notice-success is-dismissible"><p>فرم‌های آزمایشی با موفقیت حذف شدند.</p></div>
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
                        <th width="140">نام و نام‌خانوادگی</th>
                        <th width="110">شماره تماس</th>
                        <th width="80">سن / جنسیت</th>
                        <th width="90">قد / وزن</th>
                        <th width="65">BMI</th>
                        <th width="95">نتیجه نهایی</th>
                        <th>دلایل عدم تأیید</th>
                        <th width="130">تاریخ و ساعت</th>
                        <th width="60">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                        <tr><td colspan="10">هیچ پرونده‌ای یافت نشد.</td></tr>
                    <?php else: foreach ($leads as $l): 
                        $reasons = json_decode($l->rejection_reasons, true);
                        if (!is_array($reasons)) {
                            $reasons = maybe_unserialize($l->rejection_reasons);
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html($l->id); ?></td>
                            <td><strong><?php echo esc_html($l->full_name); ?></strong></td>
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
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=amsf-leads&action=amsf_delete_lead&lead_id=' . $l->id), 'amsf_delete_lead_nonce')); ?>" style="color:#d32f2f;" onclick="return confirm('حذف شود؟');">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
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
                if (is_array($val)) {
                    $translated = array();
                    foreach ($val as $v_key) {
                        $translated[] = $field['options'][$v_key] ?? $v_key;
                    }
                    $val = implode('، ', $translated);
                } elseif ($field['type'] === 'toggle') {
                    if ($val === 'yes') $val = 'بله';
                    elseif ($val === 'no') $val = 'خیر';
                    else $val = '';
                } elseif ($field['type'] === 'radio') {
                    $val = $field['options'][$val] ?? $val;
                }
                $line[] = $val;
            }

            fputcsv($out, $line);
        }
        fclose($out);
    }
}

new AMSF_Admin_Settings();
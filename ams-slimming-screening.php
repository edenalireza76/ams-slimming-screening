<?php
/**
 * Plugin Name: فرم غربالگری آمپول لاغری
 * Description: پلاگین غربالگری سلامت و آمادگی دریافت آمپول لاغری (نسخه ۲۱ سوالی استاندارد) به همراه ویجت استایل‌دهی المنتور.
 * Version: 2.1.2
 * Author: AlirezaMoghimian.ir
 * Text Domain: ams-screening
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AMSF_VERSION', '2.1.0');
define('AMSF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AMSF_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once AMSF_PLUGIN_DIR . 'includes/class-default-settings.php';
require_once AMSF_PLUGIN_DIR . 'includes/class-screening-evaluator.php';
require_once AMSF_PLUGIN_DIR . 'includes/class-ajax-handler.php';
require_once AMSF_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once AMSF_PLUGIN_DIR . 'includes/class-elementor-integration.php';

final class AMS_Slimming_Screening {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('init', array($this, 'maybe_create_table'));
        add_shortcode('ams_screening_form', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
    }

    public function activate() {
        $this->create_db_table();
    }

    public function maybe_create_table() {
        if (get_option('amsf_db_version') !== AMSF_VERSION) {
            $this->create_db_table();
            update_option('amsf_db_version', AMSF_VERSION);
        }
    }

    private function create_db_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'amsf_submissions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            full_name varchar(191) NOT NULL,
            mobile varchar(20) NOT NULL,
            age int(3) NOT NULL,
            gender varchar(20) NOT NULL,
            height int(4) NOT NULL,
            weight decimal(5,1) NOT NULL,
            bmi varchar(10) NOT NULL,
            status varchar(20) NOT NULL,
            rejection_reasons longtext NULL,
            all_fields longtext NOT NULL,
            PRIMARY KEY  (id),
            KEY mobile (mobile),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function register_assets() {
        wp_register_style(
            'amsf-frontend-css',
            AMSF_PLUGIN_URL . 'assets/css/screening-form.css',
            array(),
            AMSF_VERSION
        );

        wp_register_script(
            'amsf-frontend-js',
            AMSF_PLUGIN_URL . 'assets/js/screening-form.js',
            array(),
            AMSF_VERSION,
            true
        );

        wp_localize_script('amsf-frontend-js', 'amsf_public_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => esc_url_raw(rest_url('amsf/v1/submit')),
            'nonce'    => wp_create_nonce('amsf_submission_nonce')
        ));
    }

    public function render_shortcode($atts) {
        wp_enqueue_style('amsf-frontend-css');
        wp_enqueue_script('amsf-frontend-js');

        $unique_id = 'amsf-' . wp_generate_password(6, false, false);
        ob_start();
        ?>
        <div class="amsf-root" id="<?php echo esc_attr($unique_id); ?>" data-editor-preview="none">
            <?php AMSF_Default_Settings::render_form_html($unique_id, false); ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

add_action('plugins_loaded', array('AMS_Slimming_Screening', 'instance'));
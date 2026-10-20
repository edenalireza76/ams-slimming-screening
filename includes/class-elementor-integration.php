<?php
if (!defined('ABSPATH')) {
    exit;
}

final class AMSF_Elementor_Integration {

    public function __construct() {
        add_action('elementor/elements/categories_registered', array($this, 'add_category'));
        add_action('elementor/widgets/register', array($this, 'register_widgets'));
    }

    public function add_category($elements_manager) {
        $elements_manager->add_category(
            'ams-screening-category',
            array(
                'title' => 'غربالگری آمپول',
                'icon'  => 'eicon-form-horizontal',
            )
        );
    }

    public function register_widgets($widgets_manager) {
        require_once AMSF_PLUGIN_DIR . 'widgets/class-ams-screening-widget.php';
        $widgets_manager->register(new \AMS_Screening_Widget());
    }
}

new AMSF_Elementor_Integration();
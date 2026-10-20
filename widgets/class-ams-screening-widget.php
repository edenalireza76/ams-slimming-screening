<?php
if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

class AMS_Screening_Widget extends Widget_Base {

    public function get_name() {
        return 'ams_screening_form';
    }

    public function get_title() {
        return 'فرم غربالگری آمپول لاغری';
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
        return array('ams-screening-category');
    }

    public function get_script_depends() {
        return array('amsf-frontend-js');
    }

    public function get_style_depends() {
        return array('amsf-frontend-css');
    }

    protected function register_controls() {

        // ==========================================
        // تب ۱: CONTENT (پیش‌نمایش + تنظیمات کامل مراحل و فیلدها)
        // ==========================================
        $this->start_controls_section(
            'section_editor_preview',
            array(
                'label' => 'پیش‌نمایش در ادیتور',
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'editor_preview_state',
            array(
                'label'   => 'وضعیت نمایش در ادیتور',
                'type'    => Controls_Manager::SELECT,
                'default' => 'step_1',
                'options' => array(
                    'step_1'          => 'مرحله ۱: اطلاعات فردی',
                    'step_2'          => 'مرحله ۲: قد و وزن و سابقه',
                    'step_3'          => 'مرحله ۳: بیماری‌های فعلی و داروها',
                    'step_4'          => 'مرحله ۴: بارداری و شیردهی (بانوان)',
                    'step_5'          => 'مرحله ۵: سوابق پزشکی تخصصی',
                    'step_6'          => 'مرحله ۶: وضعیت روانی و رفتار غذایی',
                    'result_eligible' => 'نتیجه: واجد شرایط',
                    'result_rejected' => 'نتیجه: واجد نیست (کادر قرمز)',
                    'show_errors'     => 'نمایش خطاهای اعتبارسنجی',
                ),
                'description' => 'این کنترل فقط در محیط ادیتور المنتور برای مشاهده و استایل‌دهی مراحل اثر دارد.',
            )
        );

        $this->end_controls_section();

        // تولید داینامیک کنترل‌های مراحل ۱ تا ۶ و تمام ۲۱ فیلد در تب Content
        $steps_meta  = AMSF_Default_Settings::get_base_steps_meta();
        $base_fields = AMSF_Default_Settings::get_base_fields_schema();

        foreach ($steps_meta as $step_num => $step_info) {
            $this->start_controls_section(
                'section_content_step_' . $step_num,
                array(
                    'label' => sprintf('فیلدهای مرحله %d: %s', $step_num, $step_info['title']),
                    'tab'   => Controls_Manager::TAB_CONTENT,
                )
            );

            $this->add_control(
                'step_' . $step_num . '_title',
                array(
                    'label'       => 'عنوان مرحله ' . $step_num,
                    'type'        => Controls_Manager::TEXT,
                    'default'     => $step_info['title'],
                    'label_block' => true,
                )
            );

            $this->add_control(
                'step_' . $step_num . '_desc',
                array(
                    'label'       => 'توضیح زیر عنوان مرحله ' . $step_num,
                    'type'        => Controls_Manager::TEXT,
                    'default'     => $step_info['desc'],
                    'label_block' => true,
                    'separator'   => 'after',
                )
            );

            // فیلدهای مربوط به این مرحله
            foreach ($base_fields as $f_num => $field) {
                if ((int)$field['step'] !== (int)$step_num) {
                    continue;
                }

                $fid = $field['id'];

                $this->add_control(
                    'heading_field_' . $fid,
                    array(
                        'label'     => sprintf('سؤال %d: %s', $f_num, $field['label']),
                        'type'      => Controls_Manager::HEADING,
                        'separator' => 'before',
                    )
                );

                // ۱. متن عنوان سؤال (Label)
                $this->add_control(
                    'field_' . $fid . '_label',
                    array(
                        'label'       => 'متن سؤال (Label)',
                        'type'        => Controls_Manager::TEXT,
                        'default'     => $field['label'],
                        'label_block' => true,
                    )
                );

                // ۲. متن Placeholder (اگر فیلد متنی یا عددی است)
                if (array_key_exists('placeholder', $field)) {
                    $this->add_control(
                        'field_' . $fid . '_placeholder',
                        array(
                            'label'       => 'متن داخل فیلد (Placeholder)',
                            'type'        => Controls_Manager::TEXT,
                            'default'     => $field['placeholder'],
                            'label_block' => true,
                        )
                    );
                }

                // ۳. کلید ضروری / غیرضروری بودن
                $this->add_control(
                    'field_' . $fid . '_required',
                    array(
                        'label'        => 'فیلد ضروری (اجباری) باشد؟',
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => 'بله',
                        'label_off'    => 'خیر',
                        'return_value' => 'yes',
                        'default'      => !empty($field['required']) ? 'yes' : '',
                    )
                );

                // ۴. امکان تغییر متن گزینه‌ها (برای فیلدهای رادیویی و چندانتخابی)
                if (!empty($field['options']) && is_array($field['options'])) {
                    foreach ($field['options'] as $opt_key => $opt_label) {
                        $this->add_control(
                            'field_' . $fid . '_opt_' . $opt_key,
                            array(
                                'label'   => 'متن گزینه: ' . $opt_label,
                                'type'    => Controls_Manager::TEXT,
                                'default' => $opt_label,
                            )
                        );
                    }
                }
            }

            $this->end_controls_section();
        }

        // سکشن متون دکمه‌های ناوبری در تب Content
        $this->start_controls_section(
            'section_content_buttons',
            array(
                'label' => 'متن دکمه‌های فرم',
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'btn_next_text',
            array(
                'label'   => 'متن دکمه بعدی',
                'type'    => Controls_Manager::TEXT,
                'default' => 'بعدی',
            )
        );

        $this->add_control(
            'btn_prev_text',
            array(
                'label'   => 'متن دکمه قبلی',
                'type'    => Controls_Manager::TEXT,
                'default' => 'قبلی',
            )
        );

        $this->add_control(
            'btn_submit_text',
            array(
                'label'   => 'متن دکمه ارسال نهایی',
                'type'    => Controls_Manager::TEXT,
                'default' => 'ارسال',
            )
        );

        $this->end_controls_section();

        // ==========================================
        // تب ۲: STYLE
        // ==========================================

        // ۱. تنظیمات کلی
        $this->start_controls_section(
            'section_style_general',
            array(
                'label' => 'کلی (رنگ‌ها و فونت)',
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $colors = array(
            'primary_color' => array('label' => 'رنگ اصلی', 'var' => '--amsf-primary', 'default' => '#1F50AD'),
            'accent_color'  => array('label' => 'رنگ تأکید', 'var' => '--amsf-accent', 'default' => '#FFB617'),
            'text_color'    => array('label' => 'رنگ متن', 'var' => '--amsf-text', 'default' => '#2D3748'),
            'bg_color'      => array('label' => 'رنگ پس‌زمینه کارت', 'var' => '--amsf-bg', 'default' => '#FFFFFF'),
            'error_color'   => array('label' => 'رنگ خطا', 'var' => '--amsf-error', 'default' => '#D32F2F'),
            'success_color' => array('label' => 'رنگ موفقیت', 'var' => '--amsf-success', 'default' => '#2E7D32'),
        );

        foreach ($colors as $c_key => $c_info) {
            $this->add_control(
                $c_key,
                array(
                    'label'     => $c_info['label'],
                    'type'      => Controls_Manager::COLOR,
                    'default'   => $c_info['default'],
                    'selectors' => array(
                        '{{WRAPPER}} .amsf-root' => $c_info['var'] . ': {{VALUE}};',
                    ),
                )
            );
        }

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'general_typography',
                'label'    => 'تایپوگرافی کلی فرم',
                'selector' => '{{WRAPPER}} .amsf-root',
            )
        );

        $this->end_controls_section();

        // ۲. کارت فرم
        $this->start_controls_section(
            'section_style_card',
            array(
                'label' => 'کارت فرم',
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'card_max_width',
            array(
                'label'      => 'حداکثر عرض',
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px', '%'),
                'range'      => array('px' => array('min' => 320, 'max' => 1200)),
                'default'    => array('unit' => 'px', 'size' => 720),
                'selectors'  => array('{{WRAPPER}} .amsf-form' => 'max-width: {{SIZE}}{{UNIT}}; margin-left: auto; margin-right: auto;'),
            )
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name'     => 'card_background',
                'selector' => '{{WRAPPER}} .amsf-form',
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .amsf-form',
            )
        );

        $this->add_responsive_control(
            'card_radius',
            array(
                'label'      => 'گردی گوشه‌ها (Radius)',
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array('{{WRAPPER}} .amsf-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .amsf-form',
            )
        );

        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => 'Padding داخلی',
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array('{{WRAPPER}} .amsf-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'),
            )
        );

        $this->end_controls_section();

        // ۳. نوار پیشرفت
        $this->start_controls_section(
            'section_style_progress',
            array(
                'label' => 'نوار پیشرفت',
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'progress_height',
            array(
                'label'     => 'ارتفاع نوار پیشرفت',
                'type'      => Controls_Manager::SLIDER,
                'range'     => array('px' => array('min' => 4, 'max' => 24)),
                'selectors' => array('{{WRAPPER}} .amsf-progress-track' => 'height: {{SIZE}}px;'),
            )
        );

        $this->add_control(
            'progress_bg_color',
            array(
                'label'     => 'رنگ پس‌زمینه نوار',
                'type'      => Controls_Manager::COLOR,
                'selectors' => array('{{WRAPPER}} .amsf-progress-track' => 'background-color: {{VALUE}};'),
            )
        );

        $this->add_control(
            'progress_fill_color',
            array(
                'label'     => 'رنگ پرشونده نوار',
                'type'      => Controls_Manager::COLOR,
                'selectors' => array('{{WRAPPER}} .amsf-progress-bar' => 'background-color: {{VALUE}};'),
            )
        );

        $this->end_controls_section();

        // ۴. فیلدهای ورودی
        $this->start_controls_section(
            'section_style_inputs',
            array(
                'label' => 'فیلدهای ورودی و کارت‌های انتخاب',
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'choice_grid_cols',
            array(
                'label'     => 'تعداد ستون چک‌باکس‌ها',
                'type'      => Controls_Manager::SELECT,
                'default'   => '2',
                'options'   => array('1' => '۱ ستون', '2' => '۲ ستون', '3' => '۳ ستون'),
                'selectors' => array('{{WRAPPER}} .amsf-checkbox-group' => 'grid-template-columns: repeat({{VALUE}}, 1fr);'),
            )
        );

        $this->add_control(
            'input_border_color',
            array(
                'label'     => 'رنگ کادر فیلدها',
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .amsf-input, {{WRAPPER}} .amsf-textarea, {{WRAPPER}} .amsf-checkbox-card, {{WRAPPER}} .amsf-radio-card, {{WRAPPER}} .amsf-toggle-text' => 'border-color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        // ۵. دکمه‌ها
        $btn_types = array(
            'next'   => 'دکمه بعدی',
            'prev'   => 'دکمه قبلی',
            'submit' => 'دکمه ارسال',
        );

        foreach ($btn_types as $b_key => $b_label) {
            $this->start_controls_section(
                'section_style_btn_' . $b_key,
                array(
                    'label' => $b_label,
                    'tab'   => Controls_Manager::TAB_STYLE,
                )
            );

            $sel = '{{WRAPPER}} .amsf-btn-' . $b_key;

            $this->add_control(
                'btn_' . $b_key . '_color',
                array(
                    'label'     => 'رنگ متن',
                    'type'      => Controls_Manager::COLOR,
                    'selectors' => array($sel => 'color: {{VALUE}};'),
                )
            );

            $this->add_control(
                'btn_' . $b_key . '_bg',
                array(
                    'label'     => 'رنگ پس‌زمینه',
                    'type'      => Controls_Manager::COLOR,
                    'selectors' => array($sel => 'background-color: {{VALUE}};'),
                )
            );

            $this->add_responsive_control(
                'btn_' . $b_key . '_radius',
                array(
                    'label'      => 'گردی گوشه‌ها',
                    'type'       => Controls_Manager::DIMENSIONS,
                    'size_units' => array('px', '%'),
                    'selectors'  => array($sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'),
                )
            );

            $this->end_controls_section();
        }

        // ۶. صفحه نتیجه: واجد شرایط و واجد نیست
        $this->start_controls_section(
            'section_style_results',
            array(
                'label' => 'صفحه نتیجه (واجد / عدم تأیید)',
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'eligible_box_bg',
            array(
                'label'     => 'پس‌زمینه کادر واجد شرایط',
                'type'      => Controls_Manager::COLOR,
                'default'   => '#F0FDF4',
                'selectors' => array('{{WRAPPER}} .amsf-card-success' => 'background-color: {{VALUE}};'),
            )
        );

        $this->add_control(
            'eligible_text_color',
            array(
                'label'     => 'رنگ متن واجد شرایط',
                'type'      => Controls_Manager::COLOR,
                'default'   => '#166534',
                'selectors' => array('{{WRAPPER}} .amsf-eligible-msg' => 'color: {{VALUE}};'),
            )
        );

        $this->add_control(
            'rejected_box_bg',
            array(
                'label'       => 'پس‌زمینه کادر رد (قرمز کمرنگ)',
                'type'        => Controls_Manager::COLOR,
                'default'     => '#FDF2F2',
                'description' => 'هشدار: کنتراست رنگ متن و پس‌زمینه باید حداقل ۴٫۵ به ۱ بماند.',
                'selectors'   => array('{{WRAPPER}} .amsf-card-warning' => 'background-color: {{VALUE}};'),
            )
        );

        $this->add_control(
            'rejected_text_color',
            array(
                'label'       => 'رنگ متن دلایل رد (تیره)',
                'type'        => Controls_Manager::COLOR,
                'default'     => '#7F1D1D',
                'description' => 'برای حفظ خوانایی و کنتراست حداقل ۴٫۵ به ۱ از رنگ تیره استفاده کنید.',
                'selectors'   => array('{{WRAPPER}} .amsf-card-warning, {{WRAPPER}} .amsf-reasons-list li' => 'color: {{VALUE}};'),
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();
        $preview_state = $is_editor ? ($settings['editor_preview_state'] ?? 'step_1') : 'none';
        $instance_id = 'amsf-el-' . $this->get_id();

        // استخراج تنظیمات سفارشی فیلدها و مراحل از ویجت المنتور
        $widget_overrides = AMSF_Default_Settings::extract_widget_overrides($settings);

        // همگام‌سازی تنظیمات با گزینه سراسری تا سرور هم دقیقاً از همین وضعیت (اجباری/اختیاری و لیبل‌ها) استفاده کند
        if (!empty($widget_overrides['fields'])) {
            update_option('amsf_custom_fields_settings', $widget_overrides['fields']);
        }
        if (!empty($widget_overrides['steps'])) {
            update_option('amsf_custom_steps_settings', $widget_overrides['steps']);
        }
        ?>
        <div class="amsf-root" id="<?php echo esc_attr($instance_id); ?>" data-editor-preview="<?php echo esc_attr($preview_state); ?>">
            <?php AMSF_Default_Settings::render_form_html($instance_id, $is_editor, $widget_overrides); ?>
        </div>
        <?php
    }
}
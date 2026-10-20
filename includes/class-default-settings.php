<?php
if (!defined('ABSPATH')) {
    exit;
}

final class AMSF_Default_Settings {

    public static function get_fields_schema() {
        return array(
            1  => array(
                'id' => 'full_name', 'step' => 1, 'type' => 'text', 'required' => true,
                'minlength' => 3, 'maxlength' => 60,
                'label' => 'نام و نام‌خانوادگی',
                'placeholder' => 'نام خود را وارد کنید (۳ تا ۶۰ کاراکتر)'
            ),
            2  => array(
                'id' => 'gender', 'step' => 1, 'type' => 'radio', 'required' => true,
                'label' => 'جنسیت',
                'options' => array('female' => 'خانم', 'male' => 'آقا')
            ),
            3  => array(
                'id' => 'mobile', 'step' => 1, 'type' => 'tel', 'required' => true,
                'label' => 'شماره تماس (دارای تلگرام)',
                'placeholder' => '۰۹۱۲۳۴۵۶۷۸۹'
            ),
            4  => array(
                'id' => 'age', 'step' => 1, 'type' => 'number', 'required' => true,
                'min' => 10, 'max' => 100, 'step_num' => '1',
                'label' => 'سن',
                'placeholder' => 'عدد بین ۱۰ تا ۱۰۰'
            ),
            5  => array(
                'id' => 'weight', 'step' => 2, 'type' => 'number', 'required' => true,
                'min' => 30, 'max' => 300, 'step_num' => '0.1',
                'label' => 'وزن (کیلوگرم)',
                'placeholder' => 'مثال: ۷۵٫۵ (بین ۳۰ تا ۳۰۰)'
            ),
            6  => array(
                'id' => 'height', 'step' => 2, 'type' => 'number', 'required' => true,
                'min' => 120, 'max' => 225, 'step_num' => '1',
                'label' => 'قد (سانتی‌متر)',
                'placeholder' => 'مثال: ۱۷۰ (بین ۱۲۰ تا ۲۲۵)'
            ),
            7  => array(
                'id' => 'weight_loss_history', 'step' => 2, 'type' => 'checkbox_group', 'required' => true,
                'label' => 'سابقه کاهش وزن',
                'exclusive_value' => 'none',
                'options' => array(
                    'diet'      => 'رژیم',
                    'exercise'  => 'ورزش',
                    'surgery'   => 'جراحی چاقی',
                    'injection' => 'تزریق آمپول لاغری',
                    'botox'     => 'بوتاکس',
                    'none'      => 'هیچ‌کدام'
                )
            ),
            8  => array(
                'id' => 'other_weight_loss_methods', 'step' => 2, 'type' => 'textarea', 'required' => false,
                'label' => 'سایر روش‌های کاهش وزن',
                'placeholder' => 'اگر روش دیگری را امتحان کرده‌اید بنویسید (اختیاری)...'
            ),
            9  => array(
                'id' => 'current_diseases', 'step' => 3, 'type' => 'checkbox_group', 'required' => true,
                'label' => 'بیماری‌های فعلی',
                'exclusive_value' => 'none',
                'options' => array(
                    'diabetes'       => 'دیابت',
                    'prediabetes'    => 'پیش‌دیابت/مقاومت به انسولین',
                    'hypertension'   => 'فشارخون بالا',
                    'dyslipidemia'   => 'دیس‌لیپیدمی',
                    'cvd'            => 'بیماری قلبی–عروقی',
                    'kidney_disease' => 'بیماری کلیوی',
                    'fatty_liver'    => 'کبد چرب/MASLD',
                    'sleep_apnea'    => 'آپنه خواب',
                    'pcos'           => 'PCOS',
                    'gi_disease'     => 'بیماری‌های گوارشی',
                    'gallstones'     => 'سابقه سنگ صفرا یا بیماری کیسه صفرا',
                    'none'           => 'هیچ‌کدام'
                )
            ),
            10 => array(
                'id' => 'current_medications', 'step' => 3, 'type' => 'textarea', 'required' => false,
                'label' => 'تمام داروهای مصرفی',
                'placeholder' => 'نام تمام داروهایی که در حال حاضر مصرف می‌کنید (اختیاری)...'
            ),
            11 => array(
                'id' => 'takes_insulin_or_diabetes_meds', 'step' => 3, 'type' => 'toggle', 'required' => true,
                'label' => 'مصرف انسولین یا داروی قند خون'
            ),
            12 => array(
                'id' => 'is_pregnant', 'step' => 4, 'type' => 'toggle', 'required' => true, 'gender_lock' => 'female',
                'label' => 'باردار هستید؟'
            ),
            13 => array(
                'id' => 'planning_pregnancy_6m', 'step' => 4, 'type' => 'toggle', 'required' => true, 'gender_lock' => 'female',
                'label' => 'قصد بارداری در ۶ ماه آینده'
            ),
            14 => array(
                'id' => 'is_breastfeeding', 'step' => 4, 'type' => 'toggle', 'required' => true, 'gender_lock' => 'female',
                'label' => 'دوران شیردهی'
            ),
            15 => array(
                'id' => 'thyroid_cancer_relatives', 'step' => 5, 'type' => 'toggle', 'required' => true,
                'label' => 'سرطان تیروئید در بستگان درجه یک'
            ),
            16 => array(
                'id' => 'thyroid_nodule_history', 'step' => 5, 'type' => 'toggle', 'required' => true,
                'label' => 'سابقه ندول (گره) تیروئید'
            ),
            17 => array(
                'id' => 'nodule_type', 'step' => 5, 'type' => 'text', 'required' => false,
                'label' => 'نوع گره، اگر می‌دانید (طبق سونوگرافی)',
                'placeholder' => 'اختیاری'
            ),
            18 => array(
                'id' => 'pancreatitis_history', 'step' => 5, 'type' => 'toggle', 'required' => true,
                'label' => 'سابقه پانکراتیت'
            ),
            19 => array(
                'id' => 'retinopathy_history', 'step' => 5, 'type' => 'toggle', 'required' => true,
                'label' => 'سابقه رتینوپاتی'
            ),
            20 => array(
                'id' => 'mental_and_eating_status', 'step' => 6, 'type' => 'checkbox_group', 'required' => true,
                'label' => 'کدام‌یک را در حال حاضر دارید؟ (هر چند مورد که هست انتخاب کنید)',
                'exclusive_value' => 'none',
                'options' => array(
                    'depression' => 'در حال حاضر افسردگی دارم',
                    'anxiety'    => 'در حال حاضر اضطراب دارم',
                    'binge'      => 'در حال حاضر پرخوری عصبی دارم',
                    'none'       => 'هیچ‌کدام'
                )
            ),
            21 => array(
                'id' => 'self_harm_thoughts', 'step' => 6, 'type' => 'toggle', 'required' => true,
                'label' => 'افکار خودآسیب‌رسان/خودکشی در یک ماه گذشته'
            )
        );
    }

    public static function get_steps_meta() {
        return array(
            1 => array('title' => 'اطلاعات فردی', 'desc' => 'مشخصات پایه و اطلاعات تماس خود را وارد کنید.'),
            2 => array('title' => 'مشخصات بدنی و سابقه کاهش وزن', 'desc' => 'قد، وزن و روش‌های قبلی کاهش وزن را مشخص کنید.'),
            3 => array('title' => 'بیماری‌های فعلی و داروها', 'desc' => 'وضعیت سلامت عمومی و داروهای مصرفی خود را انتخاب کنید.'),
            4 => array('title' => 'بارداری و شیردهی', 'desc' => 'سؤالات اختصاصی مربوط به بانوان.'),
            5 => array('title' => 'سوابق پزشکی تخصصی', 'desc' => 'بررسی سابقه تیروئید، پانکراتیت و شبکیه چشم.'),
            6 => array('title' => 'وضعیت روانی و رفتار غذایی', 'desc' => 'بررسی وضعیت روحی و الگوی تغذیه.')
        );
    }

    public static function render_form_html($instance_id, $preview_mode = false) {
        $fields = self::get_fields_schema();
        $steps  = self::get_steps_meta();
        ?>
        <form class="amsf-form" data-instance="<?php echo esc_attr($instance_id); ?>" novalidate>
            <!-- نوار پیشرفت -->
            <div class="amsf-progress-wrap" aria-hidden="true">
                <div class="amsf-progress-meta">
                    <span class="amsf-progress-text">مرحله <span class="amsf-curr-num">۱</span> از <span class="amsf-total-num">۶</span></span>
                    <span class="amsf-progress-percent">۱۷٪</span>
                </div>
                <div class="amsf-progress-track">
                    <div class="amsf-progress-bar" style="width: 16.66%;"></div>
                </div>
                <div class="amsf-step-circles">
                    <?php foreach ($steps as $s_num => $s_data): ?>
                        <div class="amsf-step-circle <?php echo $s_num === 1 ? 'is-active' : ''; ?>" data-step="<?php echo esc_attr($s_num); ?>">
                            <span class="amsf-circle-num"><?php echo esc_html(AMSF_Screening_Evaluator::to_persian_digits($s_num)); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- مراحل ۱ تا ۶ -->
            <?php foreach ($steps as $s_num => $s_data): ?>
                <fieldset class="amsf-step-pane <?php echo ($s_num === 1 && !$preview_mode) ? 'is-visible' : ''; ?>" data-step="<?php echo esc_attr($s_num); ?>">
                    <legend class="amsf-screen-reader-text"><?php echo esc_html($s_data['title']); ?></legend>
                    <div class="amsf-step-header">
                        <h3 class="amsf-step-title"><?php echo esc_html($s_data['title']); ?></h3>
                        <p class="amsf-step-desc"><?php echo esc_html($s_data['desc']); ?></p>
                    </div>

                    <div class="amsf-fields-grid">
                        <?php
                        foreach ($fields as $f_idx => $f) {
                            if ($f['step'] !== $s_num) continue;
                            self::render_single_field($f, $instance_id);
                        }
                        ?>
                    </div>
                </fieldset>
            <?php endforeach; ?>

            <!-- دکمه‌های ناوبری -->
            <div class="amsf-navigation-bar">
                <button type="button" class="amsf-btn amsf-btn-prev" style="display:none;">قبلی</button>
                <button type="button" class="amsf-btn amsf-btn-next">بعدی</button>
                <button type="button" class="amsf-btn amsf-btn-submit" style="display:none;">
                    <span class="amsf-btn-text">ارسال</span>
                    <span class="amsf-btn-spinner" aria-hidden="true"></span>
                </button>
            </div>

            <!-- صفحه نتیجه: واجد شرایط (فقط یک جمله بدون دکمه و لینک اضافه) -->
            <div class="amsf-result-screen amsf-result-eligible" style="display: none;" role="region" aria-live="polite">
                <div class="amsf-result-card amsf-card-success">
                    <p class="amsf-eligible-msg"></p>
                </div>
            </div>

            <!-- صفحه نتیجه: واجد شرایط نیست (کادر قرمز کمرنگ با متن تیره + متن زیر کادر) -->
            <div class="amsf-result-screen amsf-result-ineligible" style="display: none;" role="region" aria-live="polite">
                <div class="amsf-result-card amsf-card-warning">
                    <ul class="amsf-reasons-list"></ul>
                </div>
                <p class="amsf-result-safety-notice">شما واجد استفاده از آمپول لاغری نیستید اما می‌توانید از سایر خدمات لاغری کلینیک ما استفاده کنید. کارشناسان ما با شما تماس خواهند گرفت.</p>
            </div>
        </form>
        <?php
    }

    private static function render_single_field($field, $instance_id) {
        $field_id = $field['id'];
        $unique_input_id = $instance_id . '_' . $field_id;
        $gender_attr = isset($field['gender_lock']) ? 'data-gender-lock="' . esc_attr($field['gender_lock']) . '"' : '';
        ?>
        <div class="amsf-field-group amsf-field-<?php echo esc_attr($field['type']); ?>" <?php echo $gender_attr; ?> data-name="<?php echo esc_attr($field_id); ?>">
            <label class="amsf-label" for="<?php echo esc_attr($unique_input_id); ?>">
                <?php echo esc_html($field['label']); ?>
                <?php if (!empty($field['required'])): ?>
                    <span class="amsf-req-star" aria-hidden="true">*</span>
                <?php endif; ?>
            </label>

            <?php if ($field['type'] === 'text' || $field['type'] === 'tel' || $field['type'] === 'number'): ?>
                <input 
                    type="<?php echo esc_attr($field['type']); ?>" 
                    name="<?php echo esc_attr($field_id); ?>" 
                    id="<?php echo esc_attr($unique_input_id); ?>" 
                    class="amsf-input"
                    <?php if (isset($field['min'])) echo 'min="' . esc_attr($field['min']) . '"'; ?>
                    <?php if (isset($field['max'])) echo 'max="' . esc_attr($field['max']) . '"'; ?>
                    <?php if (isset($field['minlength'])) echo 'minlength="' . esc_attr($field['minlength']) . '"'; ?>
                    <?php if (isset($field['maxlength'])) echo 'maxlength="' . esc_attr($field['maxlength']) . '"'; ?>
                    <?php if (isset($field['step_num'])) echo 'step="' . esc_attr($field['step_num']) . '"'; ?>
                    placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                    <?php echo !empty($field['required']) ? 'required' : ''; ?>
                />

            <?php elseif ($field['type'] === 'textarea'): ?>
                <textarea 
                    name="<?php echo esc_attr($field_id); ?>" 
                    id="<?php echo esc_attr($unique_input_id); ?>" 
                    class="amsf-textarea"
                    rows="3"
                    placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"
                    <?php echo !empty($field['required']) ? 'required' : ''; ?>
                ></textarea>

            <?php elseif ($field['type'] === 'radio'): ?>
                <div class="amsf-radio-group" role="radiogroup">
                    <?php foreach ($field['options'] as $opt_val => $opt_label): 
                        $opt_id = $unique_input_id . '_' . $opt_val;
                    ?>
                        <label class="amsf-radio-card" for="<?php echo esc_attr($opt_id); ?>">
                            <input 
                                type="radio" 
                                name="<?php echo esc_attr($field_id); ?>" 
                                id="<?php echo esc_attr($opt_id); ?>" 
                                value="<?php echo esc_attr($opt_val); ?>"
                                class="amsf-radio-input"
                                <?php echo !empty($field['required']) ? 'required' : ''; ?>
                            />
                            <span class="amsf-radio-label"><?php echo esc_html($opt_label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($field['type'] === 'checkbox_group'): ?>
                <div class="amsf-checkbox-group" data-exclusive="<?php echo esc_attr($field['exclusive_value']); ?>">
                    <?php foreach ($field['options'] as $opt_val => $opt_label): 
                        $opt_id = $unique_input_id . '_' . $opt_val;
                    ?>
                        <label class="amsf-checkbox-card" for="<?php echo esc_attr($opt_id); ?>">
                            <input 
                                type="checkbox" 
                                name="<?php echo esc_attr($field_id); ?>[]" 
                                id="<?php echo esc_attr($opt_id); ?>" 
                                value="<?php echo esc_attr($opt_val); ?>"
                                class="amsf-checkbox-input"
                            />
                            <span class="amsf-checkbox-label"><?php echo esc_html($opt_label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($field['type'] === 'toggle'): 
                $yes_id = $unique_input_id . '_yes';
                $no_id  = $unique_input_id . '_no';
            ?>
                <div class="amsf-toggle-group" role="radiogroup">
                    <label class="amsf-toggle-btn amsf-toggle-yes" for="<?php echo esc_attr($yes_id); ?>">
                        <input type="radio" name="<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($yes_id); ?>" value="yes" class="amsf-toggle-input" <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                        <span class="amsf-toggle-text">بله</span>
                    </label>
                    <label class="amsf-toggle-btn amsf-toggle-no" for="<?php echo esc_attr($no_id); ?>">
                        <input type="radio" name="<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($no_id); ?>" value="no" class="amsf-toggle-input" <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                        <span class="amsf-toggle-text">خیر</span>
                    </label>
                </div>
            <?php endif; ?>

            <div class="amsf-field-error" aria-live="polite"></div>
        </div>
        <?php
    }
}
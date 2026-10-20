(function () {
    'use strict';

    function initScreeningForm(rootElement) {
        if (!rootElement) return;

        // جلوگیری از ثبت چندباره رویدادها
        if (rootElement.dataset.amsfReady === 'true') {
            return;
        }
        rootElement.dataset.amsfReady = 'true';

        var form = rootElement.querySelector('.amsf-form');
        if (!form) return;

        var previewMode = rootElement.dataset.editorPreview || 'none';
        var isEditor = (previewMode !== 'none');

        var currentStep = 1;
        var totalSteps = 6;
        var isMale = false;

        var stepPanes = form.querySelectorAll('.amsf-step-pane');
        var btnPrev = form.querySelector('.amsf-btn-prev');
        var btnNext = form.querySelector('.amsf-btn-next');
        var btnSubmit = form.querySelector('.amsf-btn-submit');
        var progressBar = form.querySelector('.amsf-progress-bar');
        var progressTextNum = form.querySelector('.amsf-curr-num');
        var progressTotalNum = form.querySelector('.amsf-total-num');
        var progressPercent = form.querySelector('.amsf-progress-percent');
        var stepCircles = form.querySelectorAll('.amsf-step-circle');
        var genderInputs = form.querySelectorAll('input[name="gender"]');

        // کلاس انتخاب کارت‌های رادیو و چک‌باکس
        form.querySelectorAll('.amsf-radio-input').forEach(function (r) {
            r.addEventListener('change', function () {
                var group = r.closest('.amsf-radio-group');
                if (group) {
                    group.querySelectorAll('.amsf-radio-card').forEach(function (c) {
                        c.classList.remove('is-selected');
                    });
                }
                if (r.checked) {
                    r.closest('.amsf-radio-card').classList.add('is-selected');
                }
            });
        });

        // منطق انحصاری گزینه «هیچ‌کدام» در سؤال‌های ۷ و ۹ و ۲۰
        form.querySelectorAll('[data-exclusive]').forEach(function (container) {
            var exclVal = container.dataset.exclusive;
            var checkboxes = container.querySelectorAll('.amsf-checkbox-input');

            checkboxes.forEach(function (cb) {
                cb.addEventListener('change', function () {
                    if (this.value === exclVal && this.checked) {
                        checkboxes.forEach(function (other) {
                            if (other !== cb) {
                                other.checked = false;
                                other.closest('.amsf-checkbox-card').classList.remove('is-selected');
                            }
                        });
                    } else if (this.value !== exclVal && this.checked) {
                        var exclCb = container.querySelector('input[value="' + exclVal + '"]');
                        if (exclCb) {
                            exclCb.checked = false;
                            exclCb.closest('.amsf-checkbox-card').classList.remove('is-selected');
                        }
                    }

                    checkboxes.forEach(function (item) {
                        item.closest('.amsf-checkbox-card').classList.toggle('is-selected', item.checked);
                    });
                });
            });
        });

        // تبدیل خودکار ارقام فارسی به لاتین هنگام تایپ در موبایل، سن، وزن، قد
        form.querySelectorAll('input[name="mobile"], input[name="age"], input[name="weight"], input[name="height"]').forEach(function (inp) {
            inp.addEventListener('input', function () {
                var normalized = normalizeDigits(this.value);
                if (this.value !== normalized) {
                    this.value = normalized;
                }
            });
        });

        // اگر در محیط پیش‌نمایش ادیتور المنتور هستیم
        if (isEditor) {
            handleEditorPreview(previewMode);
            return;
        }

        // تغییر جنسیت: پنهان کردن دایره مرحله ۴ برای آقا، بازشماری مراحل و خالی کردن فیلدهای ۱۲ تا ۱۴
        genderInputs.forEach(function (radio) {
            radio.addEventListener('change', function () {
                isMale = (this.value === 'male');
                applyGenderRules();
            });
        });

        function applyGenderRules() {
            var step4Circle = form.querySelector('.amsf-step-circle[data-step="4"]');
            var femaleFields = form.querySelectorAll('[data-gender-lock="female"]');

            if (isMale) {
                totalSteps = 5;
                if (progressTotalNum) progressTotalNum.textContent = toPersianDigits(5);
                if (step4Circle) step4Circle.style.display = 'none';

                // پاک کردن کامل پاسخ سؤالات ۱۲ تا ۱۴ (خالی ذخیره شود، نه خیر)
                femaleFields.forEach(function (fg) {
                    fg.querySelectorAll('input[type="radio"]').forEach(function (r) {
                        r.checked = false;
                    });
                });
            } else {
                totalSteps = 6;
                if (progressTotalNum) progressTotalNum.textContent = toPersianDigits(6);
                if (step4Circle) step4Circle.style.display = 'flex';
            }

            renumberCircles();
            updateStepUI();
        }

        function renumberCircles() {
            var visibleIdx = 1;
            stepCircles.forEach(function (c) {
                if (c.style.display !== 'none') {
                    var numEl = c.querySelector('.amsf-circle-num');
                    if (numEl) numEl.textContent = toPersianDigits(visibleIdx);
                    visibleIdx++;
                }
            });
        }

        function getVisibleStepIndex(step) {
            if (isMale && step >= 5) {
                return step - 1;
            }
            return step;
        }

        function updateStepUI() {
            stepPanes.forEach(function (pane) {
                var s = parseInt(pane.dataset.step, 10);
                pane.classList.toggle('is-visible', s === currentStep);
            });

            btnPrev.style.display = (currentStep > 1) ? 'inline-flex' : 'none';
            var isFinal = (currentStep === 6);
            btnNext.style.display = isFinal ? 'none' : 'inline-flex';
            btnSubmit.style.display = isFinal ? 'inline-flex' : 'none';

            var vStep = getVisibleStepIndex(currentStep);
            var pct = Math.round((vStep / totalSteps) * 100);
            if (progressBar) progressBar.style.width = pct + '%';
            if (progressTextNum) progressTextNum.textContent = toPersianDigits(vStep);
            if (progressPercent) progressPercent.textContent = toPersianDigits(pct) + '٪';

            stepCircles.forEach(function (c) {
                var cNum = parseInt(c.dataset.step, 10);
                c.classList.remove('is-active', 'is-completed');
                if (cNum === currentStep) c.classList.add('is-active');
                if (cNum < currentStep) c.classList.add('is-completed');
            });
        }

        function validateStep(step) {
            var activePane = form.querySelector('.amsf-step-pane[data-step="' + step + '"]');
            if (!activePane) return true;

            var isValid = true;
            var groups = activePane.querySelectorAll('.amsf-field-group');

            groups.forEach(function (g) {
                if (g.style.display === 'none') return;
                if (isMale && g.dataset.genderLock === 'female') return;

                var errBox = g.querySelector('.amsf-field-error');
                var textInput = g.querySelector('.amsf-input, .amsf-textarea');
                var radioInputs = g.querySelectorAll('.amsf-radio-input, .amsf-toggle-input');
                var cbInputs = g.querySelectorAll('.amsf-checkbox-input');

                g.classList.remove('has-error');
                if (errBox) errBox.textContent = '';

                // ۱. فیلدهای متنی و عددی
                if (textInput && textInput.hasAttribute('required')) {
                    var rawVal = textInput.value.trim();
                    if (!rawVal) {
                        setFieldError(g, errBox, 'این فیلد الزامی است');
                        isValid = false;
                        return;
                    }

                    var normVal = normalizeDigits(rawVal);

                    // نام و نام‌خانوادگی (۳ تا ۶۰ کاراکتر)
                    if (textInput.name === 'full_name') {
                        if (rawVal.length < 3 || rawVal.length > 60) {
                            setFieldError(g, errBox, 'نام و نام‌خانوادگی باید بین ۳ تا ۶۰ کاراکتر باشد');
                            isValid = false;
                            return;
                        }
                    }

                    // شماره تماس
                    if (textInput.name === 'mobile') {
                        if (!/^09[0-9]{9}$/.test(normVal)) {
                            setFieldError(g, errBox, 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد');
                            isValid = false;
                            return;
                        }
                    }

                    // سن (۱۰ تا ۱۰۰، عدد صحیح)
                    if (textInput.name === 'age') {
                        if (!/^\d+$/.test(normVal)) {
                            setFieldError(g, errBox, 'سن باید بین ۱۰ تا ۱۰۰ باشد');
                            isValid = false;
                            return;
                        }
                        var ageNum = parseInt(normVal, 10);
                        if (ageNum < 10 || ageNum > 100) {
                            setFieldError(g, errBox, 'سن باید بین ۱۰ تا ۱۰۰ باشد');
                            isValid = false;
                            return;
                        }
                    }

                    // وزن (۳۰ تا ۳۰۰، حداکثر ۱ رقم اعشار)
                    if (textInput.name === 'weight') {
                        if (!/^\d+(\.\d{1})?$/.test(normVal)) {
                            var wCheck = parseFloat(normVal);
                            if (isNaN(wCheck) || wCheck < 30 || wCheck > 300) {
                                setFieldError(g, errBox, 'وزن باید بین ۳۰ تا ۳۰۰ کیلوگرم باشد');
                                isValid = false;
                                return;
                            }
                        }
                        var weightNum = parseFloat(normVal);
                        if (isNaN(weightNum) || weightNum < 30 || weightNum > 300) {
                            setFieldError(g, errBox, 'وزن باید بین ۳۰ تا ۳۰۰ کیلوگرم باشد');
                            isValid = false;
                            return;
                        }
                    }

                    // قد (عدد صحیح، ۱۲۰ تا ۲۲۵، هشدار مخصوص زیر ۱۰)
                    if (textInput.name === 'height') {
                        var heightFloat = parseFloat(normVal);
                        if (!isNaN(heightFloat) && heightFloat > 0 && heightFloat < 10) {
                            setFieldError(g, errBox, 'قد را به سانتی‌متر وارد کنید، مثلاً ۱۷۰');
                            isValid = false;
                            return;
                        }
                        if (normVal.indexOf('.') !== -1 || !/^\d+$/.test(normVal)) {
                            setFieldError(g, errBox, 'قد باید بین ۱۲۰ تا ۲۲۵ سانتی‌متر باشد');
                            isValid = false;
                            return;
                        }
                        var heightNum = parseInt(normVal, 10);
                        if (heightNum < 120 || heightNum > 225) {
                            setFieldError(g, errBox, 'قد باید بین ۱۲۰ تا ۲۲۵ سانتی‌متر باشد');
                            isValid = false;
                            return;
                        }
                    }
                }

                // ۲. رادیو و بله/خیر
                if (radioInputs.length > 0 && radioInputs[0].hasAttribute('required')) {
                    var rName = radioInputs[0].name;
                    var isChecked = activePane.querySelector('input[name="' + rName + '"]:checked');
                    if (!isChecked) {
                        setFieldError(g, errBox, 'این فیلد الزامی است');
                        isValid = false;
                        return;
                    }
                }

                // ۳. چندانتخابی (حداقل یک گزینه)
                if (cbInputs.length > 0) {
                    var anyChecked = false;
                    cbInputs.forEach(function (c) {
                        if (c.checked) anyChecked = true;
                    });
                    if (!anyChecked) {
                        setFieldError(g, errBox, 'این فیلد الزامی است');
                        isValid = false;
                        return;
                    }
                }
            });

            return isValid;
        }

        function setFieldError(group, errBox, msg) {
            group.classList.add('has-error');
            if (errBox) errBox.textContent = msg;
        }

        btnNext.addEventListener('click', function () {
            if (!validateStep(currentStep)) return;

            if (currentStep === 3 && isMale) {
                currentStep = 5;
            } else {
                currentStep++;
            }
            updateStepUI();
        });

        btnPrev.addEventListener('click', function () {
            if (currentStep === 5 && isMale) {
                currentStep = 3;
            } else {
                currentStep--;
            }
            updateStepUI();
        });

        btnSubmit.addEventListener('click', function () {
            if (!validateStep(currentStep)) return;

            btnSubmit.disabled = true;
            btnSubmit.querySelector('.amsf-btn-text').textContent = 'در حال ارسال...';

            var formData = new FormData(form);
            formData.append('action', 'amsf_submit_screening');
            if (window.amsf_public_data && amsf_public_data.nonce) {
                formData.append('nonce', amsf_public_data.nonce);
            }

            fetch(amsf_public_data.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                btnSubmit.disabled = false;
                btnSubmit.querySelector('.amsf-btn-text').textContent = 'ارسال';

                if (res.success) {
                    renderFinalResult(res.data);
                } else {
                    alert(res.data && res.data.message ? res.data.message : 'خطایی رخ داد.');
                }
            })
            .catch(function () {
                btnSubmit.disabled = false;
                btnSubmit.querySelector('.amsf-btn-text').textContent = 'ارسال';
                alert('خطا در برقراری ارتباط با سرور.');
            });
        });

        function renderFinalResult(data) {
            form.querySelectorAll('.amsf-step-pane, .amsf-navigation-bar, .amsf-progress-wrap').forEach(function (el) {
                el.style.display = 'none';
            });

            if (data.is_eligible) {
                var eligBox = form.querySelector('.amsf-result-eligible');
                eligBox.querySelector('.amsf-eligible-msg').textContent = data.eligible_message;
                eligBox.style.display = 'block';
            } else {
                var ineligBox = form.querySelector('.amsf-result-ineligible');
                var list = ineligBox.querySelector('.amsf-reasons-list');
                list.innerHTML = '';
                (data.rejection_reasons || []).forEach(function (reason) {
                    var li = document.createElement('li');
                    li.textContent = reason;
                    list.appendChild(li);
                });
                ineligBox.style.display = 'block';
            }
        }

        function handleEditorPreview(state) {
            stepPanes.forEach(function (p) { p.classList.remove('is-visible'); });
            form.querySelector('.amsf-result-eligible').style.display = 'none';
            form.querySelector('.amsf-result-ineligible').style.display = 'none';

            if (state.indexOf('step_') === 0) {
                var stepNum = state.replace('step_', '');
                var target = form.querySelector('.amsf-step-pane[data-step="' + stepNum + '"]');
                if (target) target.classList.add('is-visible');
            } else if (state === 'result_eligible') {
                form.querySelector('.amsf-progress-wrap').style.display = 'none';
                form.querySelector('.amsf-navigation-bar').style.display = 'none';
                var elig = form.querySelector('.amsf-result-eligible');
                elig.querySelector('.amsf-eligible-msg').textContent = 'بر اساس شاخص توده بدنی شما (BMI: ۲۵٫۴) و سایر مواردی که در این فرم بررسی شد، می‌توانید از آمپول لاغری استفاده کنید.';
                elig.style.display = 'block';
            } else if (state === 'result_rejected') {
                form.querySelector('.amsf-progress-wrap').style.display = 'none';
                form.querySelector('.amsf-navigation-bar').style.display = 'none';
                var inelig = form.querySelector('.amsf-result-ineligible');
                var ul = inelig.querySelector('.amsf-reasons-list');
                ul.innerHTML = '<li>شما با BMI ۲۳٫۰ واجد شرایط تزریق آمپول نیستید. پیشنهاد می‌کنیم از سایر خدمات کلینیک ما استفاده کنید.</li><li>طبق دستورالعمل‌های سازمان بهداشت جهانی، افراد دارای سابقه سنگ صفرا یا بیماری کیسه صفرا امکان استفاده از آمپول‌های لاغری را ندارند.</li>';
                inelig.style.display = 'block';
            } else if (state === 'show_errors') {
                var p1 = form.querySelector('.amsf-step-pane[data-step="1"]');
                if (p1) {
                    p1.classList.add('is-visible');
                    p1.querySelectorAll('.amsf-field-group').forEach(function (fg) {
                        fg.classList.add('has-error');
                        var err = fg.querySelector('.amsf-field-error');
                        if (err) err.textContent = 'این فیلد الزامی است';
                    });
                }
            }
        }

        function normalizeDigits(str) {
            return String(str)
                .replace(/[۰-۹]/g, function (d) { return d.charCodeAt(0) - 1776; })
                .replace(/[٠-٩]/g, function (d) { return d.charCodeAt(0) - 1632; })
                .replace(/٫/g, '.');
        }

        function toPersianDigits(str) {
            var farsi = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return String(str).replace(/\d/g, function (d) { return farsi[d]; });
        }
    }

    window.addEventListener('elementor/frontend/init', function () {
        if (window.elementorFrontend && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction('frontend/element_ready/ams_screening_form.default', function ($scope) {
                var root = $scope[0].querySelector('.amsf-root');
                if (root) {
                    root.dataset.amsfReady = 'false';
                    initScreeningForm(root);
                }
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.amsf-root').forEach(function (root) {
            initScreeningForm(root);
        });
    });
})();
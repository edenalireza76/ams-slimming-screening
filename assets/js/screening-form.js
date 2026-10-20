(function () {
    'use strict';

    function initScreeningForm(rootElement) {
        if (!rootElement) return;

        // جلوگیری از ثبت چندباره رویدادها روی یک نمونه
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

        // مدیریت استایل کارت‌های رادیو هنگام انتخاب
        form.querySelectorAll('.amsf-radio-input').forEach(function (r) {
            r.addEventListener('change', function () {
                var group = r.closest('.amsf-radio-group');
                if (group) {
                    group.querySelectorAll('.amsf-radio-card').forEach(function (c) {
                        c.classList.remove('is-selected');
                    });
                }
                if (r.checked) {
                    var card = r.closest('.amsf-radio-card');
                    if (card) card.classList.add('is-selected');
                }

                // پاک کردن خطای فیلد به محض انتخاب گزینه
                var fg = r.closest('.amsf-field-group');
                if (fg) clearFieldError(fg);
            });
        });

        // پاک کردن خطای تاگل‌های بله/خیر به محض انتخاب
        form.querySelectorAll('.amsf-toggle-input').forEach(function (t) {
            t.addEventListener('change', function () {
                var fg = t.closest('.amsf-field-group');
                if (fg) clearFieldError(fg);
            });
        });

        // منطق انحصاری گزینه «هیچ‌کدام» در سؤال‌های چندانتخابی (۷، ۹ و ۲۰)
        form.querySelectorAll('[data-exclusive]').forEach(function (container) {
            var exclVal = container.dataset.exclusive;
            var checkboxes = container.querySelectorAll('.amsf-checkbox-input');

            checkboxes.forEach(function (cb) {
                cb.addEventListener('change', function () {
                    if (this.value === exclVal && this.checked) {
                        checkboxes.forEach(function (other) {
                            if (other !== cb) {
                                other.checked = false;
                                var otherCard = other.closest('.amsf-checkbox-card');
                                if (otherCard) otherCard.classList.remove('is-selected');
                            }
                        });
                    } else if (this.value !== exclVal && this.checked) {
                        var exclCb = container.querySelector('input[value="' + exclVal + '"]');
                        if (exclCb) {
                            exclCb.checked = false;
                            var exclCard = exclCb.closest('.amsf-checkbox-card');
                            if (exclCard) exclCard.classList.remove('is-selected');
                        }
                    }

                    checkboxes.forEach(function (item) {
                        var card = item.closest('.amsf-checkbox-card');
                        if (card) card.classList.toggle('is-selected', item.checked);
                    });

                    var fg = container.closest('.amsf-field-group');
                    if (fg) clearFieldError(fg);
                });
            });
        });

        // تبدیل خودکار ارقام فارسی/عربی به لاتین هنگام تایپ و پاک کردن خطا
        form.querySelectorAll('.amsf-input, .amsf-textarea').forEach(function (inp) {
            inp.addEventListener('input', function () {
                if (this.name === 'mobile' || this.name === 'age' || this.name === 'weight' || this.name === 'height') {
                    var normalized = normalizeDigits(this.value);
                    if (this.value !== normalized) {
                        this.value = normalized;
                    }
                }
                var fg = this.closest('.amsf-field-group');
                if (fg && fg.classList.contains('has-error')) {
                    clearFieldError(fg);
                }
            });
        });

        // اگر در محیط پیش‌نمایش ادیتور المنتور هستیم، فقط وضعیت انتخابی را رندر کن
        if (isEditor) {
            handleEditorPreview(previewMode);
            return;
        }

        // بررسی تغییر جنسیت: حذف مرحله ۴ برای آقا، بازشماری دایره‌ها و خالی کردن سؤالات ۱۲ تا ۱۴
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

                // وقتی کاربر جنسیت را از خانم به آقا عوض می‌کند، پاسخ سؤالات ۱۲ تا ۱۴ باید خالی شود (نه خیر)
                femaleFields.forEach(function (fg) {
                    fg.querySelectorAll('input[type="radio"]').forEach(function (r) {
                        r.checked = false;
                    });
                    clearFieldError(fg);
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

            if (btnPrev) {
                btnPrev.style.display = (currentStep > 1) ? 'inline-flex' : 'none';
            }

            var isFinal = (currentStep === 6);
            if (btnNext) {
                btnNext.style.display = isFinal ? 'none' : 'inline-flex';
            }
            if (btnSubmit) {
                btnSubmit.style.display = isFinal ? 'inline-flex' : 'none';
            }

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
            var firstErrorGroup = null;
            var groups = activePane.querySelectorAll('.amsf-field-group');

            groups.forEach(function (g) {
                if (g.style.display === 'none') return;
                if (isMale && g.dataset.genderLock === 'female') return;

                // خواندن وضعیت اجباری/اختیاری از پنل تنظیمات فیلدها
                var isGroupRequired = (g.dataset.required === 'true');
                var errBox = g.querySelector('.amsf-field-error');
                var textInput = g.querySelector('.amsf-input, .amsf-textarea');
                var radioInputs = g.querySelectorAll('.amsf-radio-input, .amsf-toggle-input');
                var cbInputs = g.querySelectorAll('.amsf-checkbox-input');

                clearFieldError(g);

                // ۱. فیلدهای متنی و عددی
                if (textInput) {
                    var rawVal = textInput.value.trim();

                    if (isGroupRequired && !rawVal) {
                        setFieldError(g, errBox, 'این فیلد الزامی است');
                        if (!firstErrorGroup) firstErrorGroup = g;
                        isValid = false;
                        return;
                    }

                    // اگر کاربر مقداری وارد کرده، بازه و فرمت دقیق آن بررسی شود
                    if (rawVal !== '') {
                        var normVal = normalizeDigits(rawVal);

                        // سؤال ۱: نام و نام‌خانوادگی (۳ تا ۶۰ کاراکتر)
                        if (textInput.name === 'full_name') {
                            if (rawVal.length < 3 || rawVal.length > 60) {
                                setFieldError(g, errBox, 'نام و نام‌خانوادگی باید بین ۳ تا ۶۰ کاراکتر باشد');
                                if (!firstErrorGroup) firstErrorGroup = g;
                                isValid = false;
                                return;
                            }
                        }

                        // سؤال ۳: شماره موبایل (۱۱ رقم با ۰۹)
                        if (textInput.name === 'mobile') {
                            if (!/^09[0-9]{9}$/.test(normVal)) {
                                setFieldError(g, errBox, 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد');
                                if (!firstErrorGroup) firstErrorGroup = g;
                                isValid = false;
                                return;
                            }
                        }

                        // سؤال ۴: سن (عدد صحیح بین ۱۰ تا ۱۰۰)
                        if (textInput.name === 'age') {
                            if (!/^\d+$/.test(normVal)) {
                                setFieldError(g, errBox, 'سن باید بین ۱۰ تا ۱۰۰ باشد');
                                if (!firstErrorGroup) firstErrorGroup = g;
                                isValid = false;
                                return;
                            }
                            var ageNum = parseInt(normVal, 10);
                            if (ageNum < 10 || ageNum > 100) {
                                setFieldError(g, errBox, 'سن باید بین ۱۰ تا ۱۰۰ باشد');
                                if (!firstErrorGroup) firstErrorGroup = g;
                                isValid = false;
                                return;
                            }
                        }

                        // سؤال ۵: وزن (۳۰ تا ۳۰۰ کیلوگرم، تا ۱ رقم اعشار)
                        if (textInput.name === 'weight') {
                            if (!/^\d+(\.\d{1})?$/.test(normVal)) {
                                setFieldError(g, errBox, 'وزن باید بین ۳۰ تا ۳۰۰ کیلوگرم باشد');
                                if (!firstErrorGroup) firstErrorGroup = g;
                                isValid = false;
                                return;
                            }
                            var weightNum = parseFloat(normVal);
                            if (isNaN(weightNum) || weightNum < 30 || weightNum > 300) {
                                setFieldError(g, errBox, 'وزن باید بین ۳۰ تا ۳۰۰ کیلوگرم باشد');
                                if (!firstErrorGroup) firstErrorGroup = g;
                                isValid = false;
                                return;
                            }
                        }

                        // سؤال ۶: قد (عدد صحیح، ۱۲۰ تا ۲۲۵ سانتی‌متر، پیام اختصاصی برای عدد زیر ۱۰)
                        if (textInput.name === 'height') {
                            var heightFloat = parseFloat(normVal);
                            if (!isNaN(heightFloat) && heightFloat > 0 && heightFloat < 10) {
                                setFieldError(g, errBox, 'قد را به سانتی‌متر وارد کنید، مثلاً ۱۷۰');
                                if (!firstErrorGroup) firstErrorGroup = g;
                                isValid = false;
                                return;
                            }
                            if (normVal.indexOf('.') !== -1 || !/^\d+$/.test(normVal)) {
                                setFieldError(g, errBox, 'قد باید بین ۱۲۰ تا ۲۲۵ سانتی‌متر باشد');
                                if (!firstErrorGroup) firstErrorGroup = g;
                                isValid = false;
                                return;
                            }
                            var heightNum = parseInt(normVal, 10);
                            if (heightNum < 120 || heightNum > 225) {
                                setFieldError(g, errBox, 'قد باید بین ۱۲۰ تا ۲۲۵ سانتی‌متر باشد');
                                if (!firstErrorGroup) firstErrorGroup = g;
                                isValid = false;
                                return;
                            }
                        }
                    }
                }

                // ۲. دکمه‌های رادیویی و تاگل‌های بله/خیر
                if (radioInputs.length > 0 && isGroupRequired) {
                    var rName = radioInputs[0].name;
                    var isChecked = activePane.querySelector('input[name="' + rName + '"]:checked');
                    if (!isChecked) {
                        setFieldError(g, errBox, 'این فیلد الزامی است');
                        if (!firstErrorGroup) firstErrorGroup = g;
                        isValid = false;
                        return;
                    }
                }

                // ۳. چک‌باکس‌های چندانتخابی (حداقل یک گزینه در صورت اجباری بودن)
                if (cbInputs.length > 0 && isGroupRequired) {
                    var anyChecked = false;
                    cbInputs.forEach(function (c) {
                        if (c.checked) anyChecked = true;
                    });
                    if (!anyChecked) {
                        setFieldError(g, errBox, 'این فیلد الزامی است');
                        if (!firstErrorGroup) firstErrorGroup = g;
                        isValid = false;
                        return;
                    }
                }
            });

            if (!isValid && firstErrorGroup) {
                firstErrorGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            return isValid;
        }

        function setFieldError(group, errBox, msg) {
            group.classList.add('has-error');
            if (errBox) errBox.textContent = msg;
        }

        function clearFieldError(group) {
            group.classList.remove('has-error');
            var errBox = group.querySelector('.amsf-field-error');
            if (errBox) errBox.textContent = '';
        }

        if (btnNext) {
            btnNext.addEventListener('click', function () {
                if (!validateStep(currentStep)) return;

                if (currentStep === 3 && isMale) {
                    currentStep = 5;
                } else {
                    currentStep++;
                }
                updateStepUI();
                rootElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        if (btnPrev) {
            btnPrev.addEventListener('click', function () {
                if (currentStep === 5 && isMale) {
                    currentStep = 3;
                } else {
                    currentStep--;
                }
                updateStepUI();
                rootElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        if (btnSubmit) {
            btnSubmit.addEventListener('click', function () {
                if (!validateStep(currentStep)) return;

                btnSubmit.disabled = true;
                var btnTextEl = btnSubmit.querySelector('.amsf-btn-text');
                if (btnTextEl) btnTextEl.textContent = 'در حال ارسال...';

                var formData = new FormData(form);
                formData.append('action', 'amsf_submit_screening');
                if (window.amsf_public_data && amsf_public_data.nonce) {
                    formData.append('nonce', amsf_public_data.nonce);
                }

                var ajaxEndpoint = (window.amsf_public_data && amsf_public_data.ajax_url) ? amsf_public_data.ajax_url : '/wp-admin/admin-ajax.php';

                fetch(ajaxEndpoint, {
                    method: 'POST',
                    body: formData
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    btnSubmit.disabled = false;
                    if (btnTextEl) btnTextEl.textContent = 'ارسال';

                    if (res && res.success) {
                        renderFinalResult(res.data);
                    } else {
                        alert(res && res.data && res.data.message ? res.data.message : 'خطایی در بررسی اطلاعات رخ داد.');
                    }
                })
                .catch(function () {
                    btnSubmit.disabled = false;
                    if (btnTextEl) btnTextEl.textContent = 'ارسال';
                    alert('خطا در برقراری ارتباط با سرور. لطفاً اتصال اینترنت خود را بررسی کنید.');
                });
            });
        }

        function renderFinalResult(data) {
            form.querySelectorAll('.amsf-step-pane, .amsf-navigation-bar, .amsf-progress-wrap').forEach(function (el) {
                el.style.display = 'none';
            });

            if (data.is_eligible) {
                var eligBox = form.querySelector('.amsf-result-eligible');
                var msgEl = eligBox.querySelector('.amsf-eligible-msg');
                if (msgEl) msgEl.textContent = data.eligible_message;
                eligBox.style.display = 'block';
            } else {
                var ineligBox = form.querySelector('.amsf-result-ineligible');
                var list = ineligBox.querySelector('.amsf-reasons-list');
                if (list) {
                    list.innerHTML = '';
                    (data.rejection_reasons || []).forEach(function (reason) {
                        var li = document.createElement('li');
                        li.textContent = reason;
                        list.appendChild(li);
                    });
                }
                ineligBox.style.display = 'block';
            }

            rootElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function handleEditorPreview(state) {
            stepPanes.forEach(function (p) { p.classList.remove('is-visible'); });
            var eligScreen = form.querySelector('.amsf-result-eligible');
            var ineligScreen = form.querySelector('.amsf-result-ineligible');
            var progWrap = form.querySelector('.amsf-progress-wrap');
            var navBar = form.querySelector('.amsf-navigation-bar');

            if (eligScreen) eligScreen.style.display = 'none';
            if (ineligScreen) ineligScreen.style.display = 'none';
            if (progWrap) progWrap.style.display = 'block';
            if (navBar) navBar.style.display = 'flex';

            if (state.indexOf('step_') === 0) {
                var stepNum = state.replace('step_', '');
                var target = form.querySelector('.amsf-step-pane[data-step="' + stepNum + '"]');
                if (target) target.classList.add('is-visible');
            } else if (state === 'result_eligible') {
                if (progWrap) progWrap.style.display = 'none';
                if (navBar) navBar.style.display = 'none';
                if (eligScreen) {
                    var msg = eligScreen.querySelector('.amsf-eligible-msg');
                    if (msg) {
                        msg.textContent = 'بر اساس شاخص توده بدنی شما (BMI: ۲۵٫۴) و سایر مواردی که در این فرم بررسی شد، می‌توانید از آمپول لاغری استفاده کنید.';
                    }
                    eligScreen.style.display = 'block';
                }
            } else if (state === 'result_rejected') {
                if (progWrap) progWrap.style.display = 'none';
                if (navBar) navBar.style.display = 'none';
                if (ineligScreen) {
                    var ul = ineligScreen.querySelector('.amsf-reasons-list');
                    if (ul) {
                        ul.innerHTML = '<li>شما با BMI ۲۳٫۰ واجد شرایط تزریق آمپول نیستید. پیشنهاد می‌کنیم از سایر خدمات کلینیک ما استفاده کنید.</li><li>طبق دستورالعمل‌های سازمان بهداشت جهانی، افراد دارای سابقه سنگ صفرا یا بیماری کیسه صفرا امکان استفاده از آمپول‌های لاغری را ندارند.</li>';
                    }
                    ineligScreen.style.display = 'block';
                }
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
                .replace(/٫/g, '.')
                .replace(/،/g, '.');
        }

        function toPersianDigits(str) {
            var farsi = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return String(str).replace(/\d/g, function (d) { return farsi[d]; });
        }
    }

    // اتصال به المنتور در فرانت‌اند و محیط ویرایشگر
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

    // راه‌اندازی برای شورت‌کد و صفحات بدون المنتور
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.amsf-root').forEach(function (root) {
            initScreeningForm(root);
        });
    });
})();
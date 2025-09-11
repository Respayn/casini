<section
    class="early-access container"
    id="early-access"
>
    <div class="early-access__left">
        <h2 class="section__title">Получите ранний доступ</h2>
        <p class="early-access__subtitle">
            Касини в закрытой бете.<br>
            Ищем тех, кто получит ранний доступ.<br>
            <span class="underline-svg">Бесплатно и без ограничений!</span>
        </p>
    </div>
    <div class="early-access__right">
        <form
            class="early-access__form"
            x-data="{
                submitting: false,
                submitted: false,

                get submitButtonText() {
                    if (this.submitted) return 'Заявка отправлена!';
                    else if (this.submitting) return 'Отправка...';
                    
                    return 'Получить ранний доступ';
                },

                submitForm() {
                    this.submitting = true;
                    @if (config('services.yandex.smartcaptcha.enabled'))
                        this.$dispatch('execute-captcha', { captchaId: 'early-access-captcha' });
                    @else
                        $wire.sendEarlyAccessRequestForm();
                    @endif
                },

                onCaptchaSuccess(event) {
                    if (event.detail.captchaId === 'early-access-captcha') {
                        $wire.sendEarlyAccessRequestForm();
                    }
                },

                onFormSubmitted(event) {
                    if (event.detail.id === 'early-access') {
                        this.submitting = false;
                        this.submitted = true;
                    }
                }
            }"
            x-on:submit.prevent="submitForm"
            x-on:captcha-success.window="onCaptchaSuccess"
            x-on:form-submitted.window="onFormSubmitted"
        >
            <x-yandex-smart-captcha
                captcha-id="early-access-captcha"
                wire:model="captchaToken"
            />

            <div class="form__group">
                <label
                    class="form__label"
                    for="team"
                >
                    Тип команды <span class="required">*</span>
                </label>
                <select
                    class="form__select"
                    id="team"
                    name="team"
                    required
                    wire:model="team"
                >
                    <option value="">Выберите тип команды</option>
                    <option>Агентство</option>
                    <option>Фрилансер</option>
                    <option>Другой</option>
                </select>
            </div>
            <div class="form__group">
                <label
                    class="form__label"
                    for="tg"
                >
                    Ваш Telegram <span class="required">*</span>
                </label>
                <input
                    class="form__input"
                    id="tg"
                    name="tg"
                    type="text"
                    placeholder="Введите ник Telegram"
                    required
                    wire:model="telegram"
                >
            </div>
            <div class="form__group">
                <label
                    class="form__label"
                    for="agency"
                >Название агентства</label>
                <input
                    class="form__input"
                    id="agency"
                    name="agency"
                    type="text"
                    placeholder="Введите название агентства"
                    wire:model="agency"
                >
            </div>
            <!-- Скрытое поле для информации о блоке -->
            <input
                id="sourceBlock"
                name="sourceBlock"
                type="hidden"
                value=""
                wire:model="sourceBlock"
            >
            <div class="form__actions">
                <button
                    class="form__btn"
                    type="submit"
                    x-bind:disabled="submitting || submitted"
                    x-text="submitButtonText"
                >
                </button>
                <p class="early-access__disclaimer">
                    Нажимая на кнопку, я согласен(а) с
                    <a
                        class="link"
                        href="{{ route('privacy') }}"
                        wire:navigate
                    >
                        политикой обработки персональных данных
                    </a>
                </p>
            </div>
        </form>
    </div>
</section>

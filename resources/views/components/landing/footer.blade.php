<footer>
    <div class="footer container">
        <div class="footer__col">
            <img
                class="footer__logo"
                src="images/logo.svg"
                alt="Касини"
            >
            <a href="mailto:hello@casini.ru">hello@casini.ru</a>
            <a
                href="{{ route('privacy') }}"
                wire:navigate
            >Политика конфиденциальности</a>
            <div class="footer__socials">
                <a
                    class="footer__social"
                    href="https://vk.ru/kasini"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="ВКонтакте"
                >
                    <img src="{{ asset('images/icons/vk.svg') }}" alt="" width="24" height="24">
                </a>
                <a
                    class="footer__social"
                    href="https://dzen.ru/casini"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="Дзен"
                >
                    <img src="{{ asset('images/icons/dzen.svg') }}" alt="" width="24" height="24">
                </a>
            </div>
        </div>
        <div class="footer__col">
            <h3>Подпишитесь на новости</h3>
            
            <livewire:landing.subscribe-to-news-form />

            <p class="footer__disclaimer">Нажимая на кнопку я соглашаюсь с <a
                    class="link"
                    href="/privacy"
                >политикой обработки персональных данных</a></p>
            <div class="footer__links--mobile">
                <a href="mailto:hello@casini.ru">hello@casini.ru</a>
                <a href="/privacy">Политика конфиденциальности</a>
            </div>
        </div>
    </div>
</footer>

@script
    <script>
        $wire.$js.submit = () => {
            console.log('footer');
        };
    </script>
@endscript

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
        $js('submit', () => {
            console.log('footer');
        })
    </script>
@endscript

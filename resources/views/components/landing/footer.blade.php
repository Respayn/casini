<footer>
  <div class="container footer">
    <div class="footer__col">
      <img src="images/logo.svg" alt="Касини" class="footer__logo">
      <a href="mailto:hello@casini.ru">hello@casini.ru</a>
      <a href="{{ route('privacy') }}" wire:navigate>Политика конфиденциальности</a>
    </div>
    <div class="footer__col">
      <h3>Подпишитесь на новости</h3>
      <form id="subscribeForm">
        <input id="subscribeEmail" name="email" type="email" placeholder="E-mail" required>
        <button type="submit" class="btn btn--primary footer__btn">Подписаться</button>
      </form>
      <!-- Невидимая SmartCaptcha для подписки (один контейнер на странице уже есть; этот дублирующий не обязателен,
           но оставлен для совместимости: execute() сработает на любом контейнере) -->
      <div class="smart-captcha" data-sitekey="ysc1_3LuvZKhTQfG4vq4X6ifUgSm4naKn0DJedoRoAVwa982a6872" data-invisible="true" data-callback="onSmartCaptcha" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;pointer-events:none;margin:0;padding:0;border:0;"></div>
      <p class="footer__disclaimer">Нажимая на кнопку я соглашаюсь с <a href="/privacy" class="link">политикой обработки персональных данных</a></p>
      <div class="footer__links--mobile">
        <a href="mailto:hello@casini.ru">hello@casini.ru</a>
        <a href="/privacy">Политика конфиденциальности</a>
      </div>
    </div>
  </div>
</footer>

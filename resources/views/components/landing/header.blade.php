<!-- Header -->
<header class="header">
  <div class="container header__inner">
    <!-- ЛОГОТИП -->
    <a href="{{ route('landing') }}" wire:navigate class="header__logo">
      <img src="images/logo.svg" alt="КАСИНИ">
    </a>

    <!-- ДЕСКТОП-КНОПКА (на мобилке скрывается в CSS) -->
    <button class="btn btn--primary header__btn" data-scroll-early>Получить ранний доступ</button>

    <!-- БУРГЕР -->
    <button class="header__toggle" id="toggle" aria-label="Открыть меню">
      <span></span><span></span><span></span>
    </button>
  </div>
  <!-- Yandex.Metrika counter -->
<script type="text/javascript">
  (function(m,e,t,r,i,k,a){
      m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
      m[i].l=1*new Date();
      for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
      k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
  })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=103815837', 'ym');

  ym(103815837, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", accurateTrackBounce:true, trackLinks:true});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/103815837" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->
</header>

<!-- Mobile menu overlay (единственный экземпляр) -->
<div id="mobileMenu" class="mm-overlay" aria-hidden="true">
  <!-- Кнопка закрытия меню с анимацией -->
  <button id="menuCloseButton" class="menu-close-btn">
    <span class="burger-line burger-line-1"></span>
    <span class="burger-line burger-line-2"></span>
    <span class="burger-line burger-line-3"></span>
  </button>
  
  <div class="mm-card" role="dialog" aria-modal="true">
    <div class="mm-head">
      <img src="images/logo.svg" alt="КАСИНИ" class="mm-logo">
      <button class="mm-close" id="mmClose" aria-label="Закрыть">×</button>
    </div>

    <button class="btn btn--primary mm-cta" type="button" data-scroll-early>Получить ранний доступ</button>
    <a href="mailto:hello@casini.ru" class="mm-link">hello@casini.ru</a>
    <a href="/privacy" class="mm-link">Политика конфиденциальности</a>
  </div>
</div>

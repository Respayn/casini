// Мобильное меню
const toggle = document.getElementById("toggle"); // бургер
const menu = document.getElementById("mobileMenu"); // overlay
const mmClose = document.getElementById("mmClose"); // крестик

function openMenu() {
    menu.classList.add("open");
    toggle.classList.add("active");
    document.body.style.overflow = "hidden";
    document.body.classList.add("menu-open");
    menu.setAttribute("aria-hidden", "false");
}

function closeMenu() {
    menu.classList.remove("open");
    toggle.classList.remove("active");
    document.body.style.overflow = "";
    document.body.classList.remove("menu-open");
    menu.setAttribute("aria-hidden", "true");
}

toggle?.addEventListener("click", () => {
    menu.classList.contains("open") ? closeMenu() : openMenu();
});
mmClose?.addEventListener("click", closeMenu);

// Обработчик для кнопки закрытия меню
const menuCloseButton = document.getElementById("menuCloseButton");
menuCloseButton?.addEventListener("click", closeMenu);

menu?.addEventListener("click", (e) => {
    if (e.target === menu) closeMenu();
});
window.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && menu?.classList.contains("open")) closeMenu();
});

// Табы
const tabs = document.querySelectorAll(".tab");
const contents = document.querySelectorAll(".tab-content");
const indicator = document.querySelector(".tab-indicator");
const tabsContainer = document.querySelector(".tabs");

function updateIndicator() {
    const active = document.querySelector(".tab.active");
    if (!active || !indicator) return;

    // НИЧЕГО не вычитаем — линия едет вместе с содержимым .tabs
    const left = active.offsetLeft;
    const width = active.offsetWidth;

    indicator.style.transform = `translateX(${left}px)`;
    indicator.style.width = `${width}px`;
}

tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
        tabs.forEach((t) => t.classList.remove("active"));
        contents.forEach((c) => c.classList.remove("active"));

        tab.classList.add("active");
        document.getElementById(tab.dataset.tab).classList.add("active");

        updateIndicator();
    });
});

// при загрузке и ресайзе пересчитать ширину/позицию
window.addEventListener("load", updateIndicator);
window.addEventListener("resize", updateIndicator);

// Функция для проверки, находится ли пользователь на главной странице
function isMainPage() {
    return (
        window.location.pathname === "/" ||
        window.location.pathname === "/index.html" ||
        window.location.pathname.endsWith("/index.html")
    );
}

// Все кнопки «ранний доступ»: плавный скролл к форме + передача информации о блоке
function initEarlyAccessButtons() {
    const earlyButtons = document.querySelectorAll(
        "[data-scroll-early], .feature__link"
    );

    if (earlyButtons.length === 0) {
        // Если кнопки не найдены, пробуем еще раз через небольшое время
        setTimeout(() => {
            initEarlyAccessButtons();
        }, 100);
        return;
    }

    earlyButtons.forEach((btn) => {
        // Удаляем старые обработчики, если они есть
        btn.removeEventListener("click", handleEarlyAccessClick);

        // Добавляем новый обработчик
        btn.addEventListener("click", (e) => {
            e.preventDefault();

            // Если мы не на главной странице, перенаправляем на неё с параметром для скролла
            if (!isMainPage()) {
                window.location.href = "/?scroll_to_form=true";
                return;
            }

            const target = document.getElementById("early-access");
            if (!target) {
                // Если элемент не найден, пробуем еще раз через небольшое время
                setTimeout(() => {
                    const target = document.getElementById("early-access");
                    if (target) {
                        target.scrollIntoView({
                            behavior: "smooth",
                            block: "start",
                        });
                    }
                }, 100);
                return;
            }

            // Определяем источник перехода
            let sourceBlock = "Общий ранний доступ";
            if (btn.classList.contains("feature__link")) {
                // Находим заголовок карточки
                const cardTitle = btn
                    .closest(".feature")
                    ?.querySelector(".feature__title")
                    ?.textContent?.trim();
                if (cardTitle) {
                    sourceBlock = cardTitle;
                }
            }

            // Устанавливаем значение в скрытое поле
            const sourceBlockInput = document.getElementById("sourceBlock");
            if (sourceBlockInput) {
                sourceBlockInput.value = sourceBlock;
            }

            // Скроллим к форме
            target.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });

            // если открыт мобильный overlay — закрываем
            if (
                document
                    .getElementById("mobileMenu")
                    ?.classList.contains("open")
            ) {
                document.getElementById("mobileMenu")?.classList.remove("open");
                document.getElementById("toggle")?.classList.remove("active");
                document.body.style.overflow = "";
                document.body.classList.remove("menu-open");
            }

            // Дополнительная проверка через небольшое время
            setTimeout(() => {
                const target = document.getElementById("early-access");
                if (target) {
                    target.scrollIntoView({
                        behavior: "smooth",
                        block: "start",
                    });
                }
            }, 100);
        });
    });
}

// Инициализируем кнопки раннего доступа при загрузке страницы
document.addEventListener("DOMContentLoaded", () => {
    initEarlyAccessButtons();
    initMobileMenu();
});

// Также инициализируем при полной загрузке страницы
window.addEventListener("load", () => {
    initEarlyAccessButtons();
    initMobileMenu();
});

// Функция для переинициализации скриптов (теперь не нужна, но оставляем для совместимости)
function initScripts() {
    initEarlyAccessButtons();
    initMobileMenu();
}

// Делаем функцию доступной глобально
window.initScripts = initScripts;

// Функция для инициализации мобильного меню
function initMobileMenu() {
    const toggle = document.getElementById("toggle");
    const menu = document.getElementById("mobileMenu");
    const mmClose = document.getElementById("mmClose");
    const menuCloseButton = document.getElementById("menuCloseButton");

    if (toggle && menu) {
        function openMenu() {
            menu.classList.add("open");
            toggle.classList.add("active");
            document.body.style.overflow = "hidden";
            document.body.classList.add("menu-open");
            menu.setAttribute("aria-hidden", "false");
        }

        function closeMenu() {
            menu.classList.remove("open");
            toggle.classList.remove("active");
            document.body.style.overflow = "";
            document.body.classList.remove("menu-open");
            menu.setAttribute("aria-hidden", "true");
        }

        toggle.addEventListener("click", () => {
            menu.classList.contains("open") ? closeMenu() : openMenu();
        });

        if (mmClose) {
            mmClose.addEventListener("click", closeMenu);
        }

        if (menuCloseButton) {
            menuCloseButton.addEventListener("click", closeMenu);
        }

        menu.addEventListener("click", (e) => {
            if (e.target === menu) closeMenu();
        });

        window.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && menu.classList.contains("open")) {
                closeMenu();
            }
        });
    }

    // Вызываем функцию предотвращения горизонтального скролла
    if (typeof preventHorizontalScroll === "function") {
        setTimeout(preventHorizontalScroll, 100);
    }

    // Инициализируем кнопки раннего доступа после загрузки мобильного меню
    initEarlyAccessButtons();

    // Дополнительно инициализируем кнопки в мобильном меню
    const mobileEarlyButtons = menu?.querySelectorAll("[data-scroll-early]");
    if (mobileEarlyButtons) {
        mobileEarlyButtons.forEach((btn) => {
            // Удаляем старые обработчики, если они есть
            btn.removeEventListener("click", handleEarlyAccessClick);
            // Добавляем новый обработчик
            btn.addEventListener("click", handleEarlyAccessClick);
        });
    }
}

// Обработчик для кнопок раннего доступа
function handleEarlyAccessClick(e) {
    e.preventDefault();

    // Если мы не на главной странице, перенаправляем на неё с параметром для скролла
    if (!isMainPage()) {
        window.location.href = "/?scroll_to_form=true";
        return;
    }

    const target = document.getElementById("early-access");
    if (!target) {
        // Если элемент не найден, пробуем еще раз через небольшое время
        setTimeout(() => {
            const target = document.getElementById("early-access");
            if (target) {
                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start",
                });
            }
        }, 100);
        return;
    }

    // Определяем источник перехода
    let sourceBlock = "Мобильное меню - ранний доступ";

    // Устанавливаем значение в скрытое поле
    const sourceBlockInput = document.getElementById("sourceBlock");
    if (sourceBlockInput) {
        sourceBlockInput.value = sourceBlock;
    }

    // Скроллим к форме
    target.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });

    // Закрываем мобильное меню
    const mobileMenu = document.getElementById("mobileMenu");
    if (mobileMenu?.classList.contains("open")) {
        mobileMenu.classList.remove("open");
        const toggle = document.getElementById("toggle");
        if (toggle) toggle.classList.remove("active");
        document.body.style.overflow = "";
        document.body.classList.remove("menu-open");
    }

    // Дополнительная проверка через небольшое время
    setTimeout(() => {
        const target = document.getElementById("early-access");
        if (target) {
            target.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });
        }
    }, 100);
}

// Делаем функцию доступной глобально
window.initMobileMenu = initMobileMenu;

// Функция для предотвращения горизонтального скролла
function preventHorizontalScroll() {
    // Устанавливаем правильный viewport
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
        viewport.setAttribute(
            "content",
            "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
        );
    }

    // Предотвращаем горизонтальный скролл
    document.documentElement.style.overflowX = "hidden";
    document.body.style.overflowX = "hidden";

    // Исправляем ширину body
    document.body.style.width = "100%";
    document.body.style.maxWidth = "100vw";

    // Проверяем и исправляем элементы, которые могут вызывать горизонтальный скролл
    // ИСКЛЮЧАЕМ footer и его дочерние элементы
    const elements = document.querySelectorAll(
        "*:not(footer):not(.footer):not(.footer *):not(.footer__col):not(.footer__logo):not(.footer__links--mobile)"
    );
    elements.forEach((element) => {
        const rect = element.getBoundingClientRect();
        if (rect.right > window.innerWidth || rect.left < 0) {
            // Проверяем, что элемент не является footer
            if (
                !element.closest("footer") &&
                !element.classList.contains("footer")
            ) {
                element.style.maxWidth = "100%";
                element.style.overflow = "hidden";
            }
        }
    });
}

// Делаем функцию доступной глобально
window.preventHorizontalScroll = preventHorizontalScroll;

// Функция для проверки URL параметров и автоматического скролла к форме
function checkUrlParamsAndScroll() {
    const urlParams = new URLSearchParams(window.location.search);
    const scrollToForm = urlParams.get("scroll_to_form");

    if (scrollToForm === "true") {
        // Убираем параметр из URL
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);

        // Ждем немного, чтобы страница полностью загрузилась
        setTimeout(() => {
            const target = document.getElementById("early-access");
            if (target) {
                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start",
                });
            }
        }, 500);

        // Дополнительная проверка через большее время
        setTimeout(() => {
            const target = document.getElementById("early-access");
            if (target) {
                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start",
                });
            }
        }, 1000);
    }
}

// Вызываем функцию при загрузке страницы
window.addEventListener("load", () => {
    preventHorizontalScroll();
    checkUrlParamsAndScroll();
});
window.addEventListener("resize", preventHorizontalScroll);

// Вызываем функцию после загрузки header
window.addEventListener("DOMContentLoaded", () => {
    setTimeout(() => {
        preventHorizontalScroll();
        checkUrlParamsAndScroll();
    }, 100);
});

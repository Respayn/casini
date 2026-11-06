<div
    {{ $attributes->class(['scrollpanel']) }}
    x-data="scrollpanel"
    x-on:mouseup.window="stopDrag"
    x-on:mousemove="handleMouseMove"
    {{ $attributes }}
>
    <div
        class="scrollpanel-content-container"
        x-ref="container"
    >
        <div
            class="scrollpanel-content"
            x-ref="content"
            x-on:scroll="updateScrollbars"
        >
            {{ $slot }}
        </div>
    </div>

    {{-- Horizontal scrollbar --}}
    <div
        class="scrollpanel-bar scrollpanel-bar-x"
        x-ref="barX"
        x-cloak
    >
        <div
            class="scrollpanel-thumb scrollpanel-thumb-x"
            x-ref="thumbX"
            x-on:mousedown="startHorizontalDrag"
        ></div>
    </div>

    {{-- Vertical scrollbar --}}
    <div
        class="scrollpanel-bar scrollpanel-bar-y"
        x-ref="barY"
        x-cloak
    >
        <div
            class="scrollpanel-thumb scrollpanel-thumb-y"
            x-ref="thumbY"
            x-on:mousedown="startVerticalDrag"
        ></div>
    </div>
</div>

@once
    @script
        <script>
            Alpine.data('scrollpanel', () => ({
                horizontalThumbWidth: 0,
                verticalThumbHeight: 0,
                headerOffset: 0,
                isDragging: false,
                dragType: null,
                dragStartX: 0,
                dragStartY: 0,
                contentStartScrollLeft: 0,
                contentStartScrollTop: 0,
                mutationObserver: null,
                resizeObserver: null,

                init() {
                    this.initObservers();
                    this.updateScrollbars();
                },

                initObservers() {
                    this.mutationObserver = new MutationObserver(() => {
                        this.updateScrollbars();
                    });
                    this.mutationObserver.observe(this.$refs.content, {
                        childList: true,
                        subtree: true,
                        attributes: false,
                        charactedData: true
                    });

                    this.resizeObserver = new ResizeObserver(() => {
                        this.updateScrollbars();
                    });
                    this.resizeObserver.observe(this.$refs.container);
                },

                startVerticalDrag(event) {
                    event.preventDefault();
                    this.isDragging = true;
                    this.dragType = 'vertical';
                    this.dragStartY = event.clientY;
                    this.contentStartScrollTop = this.$refs.content.scrollTop;
                },

                startHorizontalDrag(event) {
                    event.preventDefault();
                    this.isDragging = true;
                    this.dragType = 'horizontal';
                    this.dragStartX = event.clientX;
                    this.contentStartScrollLeft = this.$refs.content.scrollLeft;
                },

                stopDrag() {
                    this.isDragging = false;
                    this.dragType = null;
                },

                handleMouseMove(event) {
                    if (!this.isDragging) return;

                    const content = this.$refs.content;
                    const container = this.$refs.container;

                    if (this.dragType === 'vertical') {
                        const deltaY = event.clientY - this.dragStartY;

                        const scrollableHeight = content.scrollHeight - content.clientHeight;
                        const verticalTrackHeight = container.clientHeight - this.headerOffset - this.verticalThumbHeight - 10;
                        const scrollRatio = scrollableHeight / verticalTrackHeight;
                        const newScrollTop = this.contentStartScrollTop + (deltaY * scrollRatio);
                        content.scrollTop = Math.max(0, Math.min(scrollableHeight, newScrollTop));
                    } else if (this.dragType === 'horizontal') {
                        const deltaX = event.clientX - this.dragStartX;

                        const scrollableWidth = content.scrollWidth - content.clientWidth;
                        const horizontalTrackWidth = container.clientWidth - this.horizontalThumbWidth - 10;
                        const scrollRatio = scrollableWidth / horizontalTrackWidth;
                        const newScrollLeft = this.contentStartScrollLeft + (deltaX * scrollRatio);
                        content.scrollLeft = Math.max(0, Math.min(scrollableWidth, newScrollLeft));
                    }
                },

                updateScrollbars() {
                    const content = this.$refs.content;
                    const barX = this.$refs.barX;
                    const barY = this.$refs.barY;
                    const thumbX = this.$refs.thumbX;
                    const thumbY = this.$refs.thumbY;

                    // Calculate scrollbar dimensions
                    const containerWidth = content.clientWidth;
                    const containerHeight = content.clientHeight;
                    const scrollWidth = content.scrollWidth;
                    const scrollHeight = content.scrollHeight;

                    // Calculate scrollbar ratios
                    const scrollRatioX = containerWidth / scrollWidth;
                    const scrollRatioY = containerHeight / scrollHeight;

                    // Set horizontal scrollbar width and position
                    this.horizontalThumbWidth = Math.max(30, containerWidth * scrollRatioX);
                    thumbX.style.width = `${this.horizontalThumbWidth}px`;

                    const maxScrollLeft = scrollWidth - containerWidth;
                    const maxThumbLeft = containerWidth - this.horizontalThumbWidth - 10;
                    thumbX.style.left = `${(content.scrollLeft / maxScrollLeft) * maxThumbLeft}px`;

                    // Полоса начинается под шапкой таблицы, чтобы не закрывать названия колонок.
                    const thead = content.querySelector(':scope > table > thead');
                    this.headerOffset = thead ? thead.offsetHeight : 0;
                    const trackGap = 10;
                    const trackHeight = Math.max(0, containerHeight - this.headerOffset - trackGap);
                    barY.style.top = `${this.headerOffset}px`;
                    barY.style.height = `${trackHeight}px`;

                    this.verticalThumbHeight = Math.min(trackHeight, Math.max(30, trackHeight * scrollRatioY));
                    thumbY.style.height = `${this.verticalThumbHeight}px`;

                    const maxScrollTop = scrollHeight - containerHeight;
                    const maxThumbTop = Math.max(0, trackHeight - this.verticalThumbHeight);
                    thumbY.style.top = maxScrollTop > 0
                        ? `${(content.scrollTop / maxScrollTop) * maxThumbTop}px`
                        : '0px';

                    // Show/hide scrollbars based on content size
                    barX.style.display = scrollWidth > containerWidth ? 'block' : 'none';
                    barY.style.display = scrollHeight > containerHeight ? 'block' : 'none';
                }
            }))
        </script>
    @endscript

    <style>
        .scrollpanel {
            position: relative;
            height: 100%;
            max-height: inherit;
            /* Иначе flex-потомок с длинным контентом раздувает панель и появляется скролл страницы */
            min-height: 0;
            overflow: hidden;
            display: flex;
        }

        .scrollpanel-content-container {
            overflow: hidden;
            width: 100%;
            height: 100%;
            max-height: inherit;
            min-height: 0;
            min-width: 0;
            flex: 1 1 auto;
            position: relative;
            z-index: 1;
        }

        .scrollpanel-content {
            height: 100%;
            /* inherit, не 100%: у родителя высота задана через max-height, процент не считается и таблица обрезается без полосы */
            max-height: inherit;
            min-height: 0;
            padding-inline: 0 calc(2 * var(--scrollpanel-bar-size));
            position: relative;
            overflow: auto;
            box-sizing: border-box;
            scrollbar-width: none;
        }

        /* Chrome/Safari: иначе при появлении overflow нативная полоса сужает контент */
        .scrollpanel-content::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none;
        }

        /* Шапка таблицы остаётся на месте, когда строки уезжают вверх. Только внешняя таблица, не «План / Факт» внутри ячейки. */
        .scrollpanel-content > table > thead th {
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .scrollpanel-bar {
            position: absolute;
            opacity: 0;
            border-radius: var(--scrollpanel-bar-border-radius);
            z-index: 2;
            cursor: pointer;
            outline-color: transparent;
            background: var(--scrollpanel-bar-background);
            border: 0 none;
            transition: outline-color var(--scrollpanel-transition-duration), opacity var(--scrollpanel-transition-duration);
        }

        .scrollpanel-bar-y {
            width: var(--scrollpanel-bar-size);
            right: 0px;
        }

        .scrollpanel-bar-x {
            height: var(--scrollpanel-bar-size);
            width: calc(100% - 10px);
            bottom: 0px;
        }

        .scrollpanel-thumb {
            position: relative;
            border-radius: var(--scrollpanel-bar-border-radius);
            background: var(--scrollpanel-thumb-background);
        }

        .scrollpanel-thumb-y {}

        .scrollpanel-thumb-x {
            height: 100%;
        }

        .scrollpanel:hover .scrollpanel-bar,
        .scrollpanel:active .scrollpanel-bar {
            opacity: 1;
        }
    </style>
@endonce

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
                        const verticalTrackHeight = container.clientHeight - this.verticalThumbHeight - 10;
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

                    // Set vertical scrollbar height and position
                    this.verticalThumbHeight = Math.max(30, containerHeight * scrollRatioY);
                    thumbY.style.height = `${this.verticalThumbHeight}px`;

                    const maxScrollTop = scrollHeight - containerHeight;
                    const maxThumbTop = containerHeight - this.verticalThumbHeight - 10;
                    thumbY.style.top = `${(content.scrollTop / maxScrollTop) * maxThumbTop}px`;

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
            display: flex;
        }

        .scrollpanel-content-container {
            overflow: hidden;
            width: 100%;
            height: 100%;
            max-height: inherit;
            position: relative;
            z-index: 1;
            float: left;
        }

        .scrollpanel-content {
            height: 100%;
            max-height: inherit;
            padding-inline: 0 calc(2 * var(--scrollpanel-bar-size));
            position: relative;
            overflow: auto;
            box-sizing: border-box;
            scrollbar-width: none;
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
            height: calc(100% - 10px);
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

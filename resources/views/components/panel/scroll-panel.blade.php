<div
    class="scrollpanel"
    x-data="{
        scrollRatioX: 0,
        scrollRatioY: 0,
        
        updateScrollbars() {
            const content = this.$refs.content;
            const barX = this.$refs.barX;
            const barY = this.$refs.barY;
    
            // Calculate scrollbar dimensions
            const containerWidth = content.clientWidth;
            const containerHeight = content.clientHeight;
            const scrollWidth = content.scrollWidth;
            const scrollHeight = content.scrollHeight;
    
            // Calculate scrollbar ratios
            this.scrollRatioX = containerWidth / scrollWidth;
            this.scrollRatioY = containerHeight / scrollHeight;
    
            // Set horizontal scrollbar width and position
            const barXWidth = Math.max(30, containerWidth * this.scrollRatioX);
            barX.style.width = `${barXWidth}px`;
            barX.style.left = `${(content.scrollLeft * this.scrollRatioX)}px`;
    
            // Set vertical scrollbar height and position
            const barYHeight = Math.max(30, containerHeight * this.scrollRatioY);
            barY.style.height = `${barYHeight}px`;
            barY.style.top = `${(content.scrollTop * this.scrollRatioY)}px`;
    
            // Show/hide scrollbars based on content size
            barX.style.display = scrollWidth > containerWidth ? 'block' : 'none';
            barY.style.display = scrollHeight > containerHeight ? 'block' : 'none';
        }
    }"
    {{ $attributes }}
>
    <div class="scrollpanel-content-container">
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
    ></div>

    {{-- Vertical scrollbar --}}
    <div
        class="scrollpanel-bar scrollpanel-bar-y"
        x-ref="barY"
    ></div>
</div>

@once
    <style>
        .scrollpanel-content-container {
            overflow: hidden;
            width: 100%;
            height: 100%;
            position: relative;
            z-index: 1;
            float: left;
        }

        .scrollpanel-content {
            height: calc(100% + calc(2 * var(--scrollpanel-bar-size)));
            width: calc(100% + calc(2 * var(--scrollpanel-bar-size)));
            padding-inline: 0 calc(2 * var(--scrollpanel-bar-size));
            padding-block: 0 calc(2 * var(--scrollpanel-bar-size));
            position: relative;
            overflow: auto;
            box-sizing: border-box;
            scrollbar-width: none;
        }

        .scrollpanel-bar {
            position: relative;
            border-radius: var(--scrollpanel-bar-border-radius);
            z-index: 2;
            cursor: pointer;
            opacity: 0;
            outline-color: transparent;
            background: var(--scrollpanel-bar-background);
            border: 0 none;
            transition: outline-color var(--scrollpanel-transition-duration), opacity var(--scrollpanel-transition-duration);
        }

        .scrollpanel-bar-x {
            height: var(--scrollpanel-bar-size);
            inset-block-end: 0;
        }

        .scrollpanel-bar-y {
            width: var(--p-scrollpanel-bar-size);
            inset-block-start: 0;
        }

        .scrollpanel:hover .scrollpanel-bar,
        .scrollpanel:active .scrollpanel-bar {
            opacity: 1;
        }
    </style>
@endonce

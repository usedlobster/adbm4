
class ScrollArrows {
    constructor() {
        this.scrollArrows = document.getElementById('scroll-arrows');
        this.topButton = document.getElementById('scroll-top');
        this.bottomButton = document.getElementById('scroll-bottom');

        let mainContent = document.getElementsByTagName('main');
        if (!mainContent || mainContent.length > 1) {
            console.error('Main content container not found');
            return;
        }
        this.mainContent = mainContent[0];

        // Find the actual scrollable element
        this.scrollElement = this.findScrollableParent(document.querySelector('#comp'));

        if (!this.scrollElement) {
            console.error('No scrollable element found');
            return;
        }

        // Initialize
        this.init();
    }

    findScrollableParent(element) {
        if (!element) return null;

        const style = window.getComputedStyle(element);
        const overflowY = style.getPropertyValue('overflow-y');

        if (
            overflowY === 'auto' ||
            overflowY === 'scroll' ||
            (element.scrollHeight > element.clientHeight && overflowY !== 'hidden')
        ) {
            return element;
        }

        return this.findScrollableParent(element.parentElement);
    }

    init() {
        this.scrollElement.addEventListener('scroll', this.updateArrowsVisibility.bind(this));
        this.topButton.addEventListener('click', () => this.scrollTo('top'));
        this.bottomButton.addEventListener('click', () => this.scrollTo('bottom'));

        // Handle window resize to recheck scroll positions
        window.addEventListener('resize', _wd_debounce(() => {
            this.updateArrowsVisibility();
        }, 150));

        // Initial check
        this.updateArrowsVisibility();
    }

    updateArrowsVisibility() {
        const {
            scrollTop,
            scrollHeight,
            clientHeight
        } = this.scrollElement;

        // Calculate if we can scroll in either direction
        const canScrollUp = scrollTop > 10; // Small threshold for better UX
        const canScrollDown = scrollTop < (scrollHeight - clientHeight - 10 );

        // Update arrows visibility
        this.topButton.classList.toggle('hidden', !canScrollUp);
        this.bottomButton.classList.toggle('hidden', !canScrollDown);

        // Show container only if we can scroll in any direction
        this.scrollArrows.classList.toggle('hidden', !(canScrollUp || canScrollDown));
    }

    scrollTo(direction) {
        const options = {
            behavior: 'smooth'
        };

        if (direction === 'top') {
            this.scrollElement.scrollTo({
                top: 0,
                ...options
            });
        } else {
            this.scrollElement.scrollTo({
                top: this.scrollElement.scrollHeight,
                ...options
            });
        }
    }
}
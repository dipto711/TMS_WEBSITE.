document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('.stat-number');

    counters.forEach(counter => {
        const updateCounter = () => {
            const target = parseInt(counter.dataset.target);
            const count = parseInt(counter.textContent);
            const increment = Math.ceil((target - count) / 20);

            if (count < target) {
                counter.textContent = count + increment;
                setTimeout(updateCounter, 16);
            }
        };
        updateCounter();
    });
});

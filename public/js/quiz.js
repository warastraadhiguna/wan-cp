const form = document.querySelector('[data-answer-form]');
const timer = document.querySelector('[data-timer]');
const clock = document.querySelector('[data-clock]');
const deadline = performance.now() + Number(form.dataset.remaining);
let submitted = false;
let interval;

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        location.reload();
    }
});

form.addEventListener('submit', (event) => {
    if (submitted) {
        event.preventDefault();
        return;
    }

    submitted = true;
    form.setAttribute('aria-busy', 'true');
    clearInterval(interval);
});

function updateTimer() {
    const remaining = Math.max(0, deadline - performance.now());
    timer.textContent = `${Math.ceil(remaining / 1000)} dtk`;
    timer.classList.toggle('quiz-timer-low', remaining <= 5000);
    clock.value = remaining;

    if (remaining === 0) {
        form.requestSubmit();
    }
}

interval = setInterval(updateTimer, 100);
updateTimer();

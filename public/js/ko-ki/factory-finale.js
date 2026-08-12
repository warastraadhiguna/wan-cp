const root = document.querySelector('[data-factory-finale]');

if (root) {
    let state = JSON.parse(root.dataset.state);
    let cardOrder = [];
    let audioContext = null;
    const bot = root.querySelector('[data-demo-bot]');
    const feedback = root.querySelector('[data-finale-feedback]');
    const identifyChallenge = root.querySelector('[data-challenge-identify]');
    const arrangeChallenge = root.querySelector('[data-challenge-arrange]');
    const badge = root.querySelector('[data-final-badge]');

    function tone(frequency, duration = 0.12, delay = 0) {
        try {
            audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            const startsAt = audioContext.currentTime + delay;
            oscillator.frequency.setValueAtTime(frequency, startsAt);
            gain.gain.setValueAtTime(0.0001, startsAt);
            gain.gain.exponentialRampToValueAtTime(0.12, startsAt + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, startsAt + duration);
            oscillator.connect(gain).connect(audioContext.destination);
            oscillator.start(startsAt);
            oscillator.stop(startsAt + duration + 0.02);
        } catch {
            // Demonstrasi tetap dapat dipakai tanpa audio.
        }
    }

    function jump() {
        bot.classList.remove('is-jumping');
        void bot.offsetWidth;
        bot.classList.add('is-jumping');
        tone(520, 0.1);
        tone(760, 0.15, 0.08);
    }

    async function save(payload) {
        const response = await fetch(root.dataset.saveUrl, {
            method: 'PATCH', credentials: 'same-origin',
            headers: {
                Accept: 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok) {
            const detail = Object.values(data.errors ?? {}).flat()[0];
            throw new Error(detail ?? data.message ?? 'Jawaban belum dapat disimpan.');
        }
        state = data.factory_state;
        return data;
    }

    function showFeedback(text, correct) {
        feedback.textContent = text;
        feedback.className = `factory-challenge-feedback ${correct ? 'is-correct' : 'is-wrong'}`;
    }

    async function identify(answer) {
        root.querySelectorAll('[data-identify]').forEach((button) => { button.disabled = true; });
        try {
            const data = await save({ action: 'factory_exit_identify', answer });
            if (!data.result.correct) {
                showFeedback('Coba lihat: bagian mana yang masuk dari pemain?', false);
                root.querySelectorAll('[data-identify]').forEach((button) => { button.disabled = false; });
                return;
            }
            tone(660); tone(880, 0.16, 0.1);
            showFeedback('Benar! SPACE ditekan adalah Input.', true);
            window.setTimeout(() => {
                identifyChallenge.hidden = true;
                arrangeChallenge.hidden = false;
                feedback.textContent = '';
            }, 650);
        } catch (error) {
            showFeedback(error.message, false);
            root.querySelectorAll('[data-identify]').forEach((button) => { button.disabled = false; });
        }
    }

    function renderCardOrder() {
        const slots = root.querySelectorAll('[data-arrange-result] span');
        slots.forEach((slot, index) => { slot.textContent = cardOrder[index]?.toUpperCase() ?? index + 1; });
        root.querySelectorAll('[data-card]').forEach((button) => {
            button.disabled = cardOrder.includes(button.dataset.card);
        });
    }

    function resetCards() {
        cardOrder = [];
        renderCardOrder();
        feedback.textContent = '';
    }

    async function chooseCard(card) {
        if (cardOrder.includes(card)) return;
        cardOrder.push(card);
        renderCardOrder();
        if (cardOrder.length < 3) return;

        try {
            const data = await save({ action: 'factory_exit_arrange', card_order: cardOrder });
            if (!data.result.correct) {
                showFeedback('Urutannya belum tepat. Mulai dari yang masuk.', false);
                window.setTimeout(resetCards, 900);
                return;
            }
            tone(523); tone(659, 0.12, 0.1); tone(784, 0.2, 0.2);
            showFeedback('INPUT → PROSES → OUTPUT. Tepat!', true);
            arrangeChallenge.hidden = true;
            badge.hidden = false;
            badge.classList.add('is-earned');
        } catch (error) {
            showFeedback(error.message, false);
            resetCards();
        }
    }

    root.querySelector('[data-demo-space]').addEventListener('click', jump);
    document.addEventListener('keydown', (event) => {
        if (event.key !== ' ' || event.repeat) return;
        event.preventDefault();
        jump();
    });
    root.querySelectorAll('[data-identify]').forEach((button) => button.addEventListener('click', () => identify(button.dataset.identify)));
    root.querySelectorAll('[data-card]').forEach((button) => button.addEventListener('click', () => chooseCard(button.dataset.card)));
    root.querySelector('[data-reset-cards]').addEventListener('click', resetCards);
}

import { COMMANDS, DIRECTIONS, GridRobot } from './grid-engine.js';

const root = document.querySelector('[data-ko-ki-laboratory]');

if (root) {
    const configuration = parseData('configuration', {});
    let state = parseData('state', {});
    const robot = new GridRobot({
        width: 5,
        height: 1,
        start: { x: 0, y: 0, direction: DIRECTIONS.EAST },
    });
    const grid = root.querySelector('[data-lab-grid]');
    const dialog = root.querySelector('[data-lab-dialog]');
    const questionStage = root.querySelector('[data-strange-question]');
    const questionFeedback = root.querySelector('[data-strange-feedback]');
    const classificationStage = root.querySelector('[data-classification-stage]');
    const classificationFeedback = root.querySelector('[data-classification-feedback]');
    const conclusionStage = root.querySelector('[data-conclusion-stage]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const reflection = root.querySelector('[data-reflection]');
    const reflectionCount = root.querySelector('[data-reflection-count]');
    let requestPending = false;

    buildGrid();
    renderRobot();
    renderCommands();
    renderQuestion();
    renderCurrentStage();
    updateReflectionCount();

    reflection.addEventListener('input', updateReflectionCount);

    root.querySelectorAll('[data-category]').forEach((button) => {
        button.addEventListener('click', async () => {
            const item = configuration.classifications[state.classification_index ?? 0];

            if (!item || requestPending) return;

            const response = await persist({
                action: 'classify',
                classification_item: item.id,
                classification_category: button.dataset.category,
            });

            if (!response) return;

            if (response.result.classification_correct) {
                showFeedback(classificationFeedback, 'Pilihanmu sudah dicatat. Lanjut ke pernyataan berikutnya.', 'success');
            } else {
                showFeedback(classificationFeedback, 'Coba periksa lagi: siapa yang melakukan bagian ini?', 'info');
            }

            window.setTimeout(renderCurrentStage, 450);
        });
    });

    function renderCommands() {
        const container = root.querySelector('[data-strange-commands]');
        container.replaceChildren();

        configuration.commands.forEach((command) => {
            const button = document.createElement('button');
            button.className = command.implemented
                ? 'btn btn-primary koki-btn-primary koki-command-button'
                : 'btn btn-outline-primary koki-command-button';
            button.type = 'button';
            button.textContent = command.label;
            button.addEventListener('click', async () => {
                if (requestPending) return;

                if (command.id === COMMANDS.MOVE_FORWARD) {
                    const result = robot.execute(COMMANDS.MOVE_FORWARD);
                    dialog.textContent = result.blocked
                        ? 'Aku tidak dapat bergerak keluar dari lintasan.'
                        : 'Aku maju karena command itu sudah dibuat.';
                    renderRobot();
                } else {
                    dialog.textContent = 'Aku belum memiliki program untuk melakukan itu.';
                }

                await persist({ action: 'try_command', command: command.id });
                questionStage.hidden = false;
            });
            container.append(button);
        });
    }

    function renderQuestion() {
        root.querySelector('[data-strange-prompt]').textContent = configuration.question.prompt;
        const container = root.querySelector('[data-strange-options]');
        container.replaceChildren();

        configuration.question.options.forEach((option) => {
            const column = document.createElement('div');
            column.className = 'col-sm-6';
            const button = document.createElement('button');
            button.className = 'btn btn-outline-primary koki-command-button w-100';
            button.type = 'button';
            button.textContent = option.label;
            button.addEventListener('click', async () => {
                if (requestPending) return;

                const response = await persist({
                    action: 'answer_question',
                    strange_answer: option.value,
                });

                if (!response) return;

                if (response.result.strange_correct) {
                    showFeedback(questionFeedback, 'Perintahnya belum dibuat.', 'success');
                    window.setTimeout(renderCurrentStage, 650);
                } else {
                    showFeedback(questionFeedback, 'Coba pikirkan lagi. Ko-Ki hanya menjalankan command yang sudah dibuat.', 'info');
                }
            });
            column.append(button);
            container.append(column);
        });
    }

    function renderCurrentStage() {
        questionStage.hidden = (state.tried_commands ?? []).length === 0;

        if (state.completed) {
            root.querySelector('[data-strange-stage]').hidden = true;
            classificationStage.hidden = true;
            conclusionStage.hidden = false;
            conclusionStage.scrollIntoView({ behavior: 'smooth', block: 'start' });

            return;
        }

        if (state.strange_completed) {
            root.querySelector('[data-strange-stage]').hidden = true;
            classificationStage.hidden = false;
            conclusionStage.hidden = true;
            renderClassification();

            return;
        }

        root.querySelector('[data-strange-stage]').hidden = false;
        classificationStage.hidden = true;
        conclusionStage.hidden = true;
    }

    function renderClassification() {
        const index = state.classification_index ?? 0;
        const item = configuration.classifications[index];

        if (!item) {
            renderCurrentStage();

            return;
        }

        root.querySelector('[data-classification-progress]').textContent = `${index + 1} dari ${configuration.classifications.length}`;
        root.querySelector('[data-classification-statement]').textContent = item.statement;
    }

    function buildGrid() {
        grid.style.setProperty('--grid-columns', 5);
        grid.style.aspectRatio = '5 / 1';
        grid.replaceChildren();

        for (let x = 0; x < 5; x++) {
            const cell = document.createElement('div');
            cell.className = 'koki-grid-cell';
            cell.dataset.x = x;
            cell.dataset.y = 0;
            cell.setAttribute('aria-hidden', 'true');
            grid.append(cell);
        }
    }

    function renderRobot() {
        grid.querySelector('.koki-robot')?.remove();
        const cell = grid.querySelector(`[data-x="${robot.state.x}"]`);
        const robotElement = document.createElement('div');
        robotElement.className = 'koki-robot';
        robotElement.innerHTML = '<span aria-hidden="true">🤖</span><span class="koki-direction" aria-hidden="true">→</span>';
        robotElement.setAttribute('role', 'img');
        robotElement.setAttribute('aria-label', `Ko-Ki di kotak ${robot.state.x + 1}`);
        cell.append(robotElement);
    }

    async function persist(payload) {
        requestPending = true;
        setButtonsDisabled(true);

        try {
            const response = await fetch(root.dataset.saveUrl, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const responseData = await response.json();

            if (!response.ok) {
                throw new Error(responseData.message ?? 'Progress tidak dapat disimpan.');
            }

            state = responseData.lab_state;

            return responseData;
        } catch {
            dialog.textContent = 'Progress belum dapat disimpan. Coba lagi.';

            return null;
        } finally {
            requestPending = false;
            setButtonsDisabled(false);
        }
    }

    function setButtonsDisabled(disabled) {
        root.querySelectorAll('button').forEach((button) => {
            button.disabled = disabled;
        });
    }

    function showFeedback(element, message, type) {
        element.className = `alert rounded-4 mt-3 mb-0 alert-${type}`;
        element.textContent = message;
        element.hidden = false;
    }

    function updateReflectionCount() {
        reflectionCount.textContent = reflection.value.length;
    }

    function parseData(key, fallback) {
        try {
            return JSON.parse(root.dataset[key] || JSON.stringify(fallback));
        } catch {
            return fallback;
        }
    }
}

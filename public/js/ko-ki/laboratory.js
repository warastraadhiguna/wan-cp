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
    const commandPicker = root.querySelector('[data-strange-picker]');
    const questionStage = root.querySelector('[data-strange-question]');
    const questionFeedback = root.querySelector('[data-strange-feedback]');
    const classificationStage = root.querySelector('[data-classification-stage]');
    const classificationFeedback = root.querySelector('[data-classification-feedback]');
    const classificationFeedbackMessage = root.querySelector('[data-classification-feedback-message]');
    const classificationNext = root.querySelector('[data-classification-next]');
    const conclusionStage = root.querySelector('[data-conclusion-stage]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const reflection = root.querySelector('[data-reflection]');
    const reflectionCount = root.querySelector('[data-reflection-count]');
    let requestPending = false;

    buildGrid();
    renderRobot();
    renderCommands();
    renderCurrentStage();
    updateReflectionCount();

    window.setInterval(checkProgressExists, 5000);

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
                showClassificationFeedback('Pilihanmu sudah dicatat. Tekan Lanjut untuk melihat pernyataan berikutnya.', 'success', true);
            } else {
                showClassificationFeedback('Coba periksa lagi: siapa yang melakukan bagian ini?', 'info', false);
            }
        });
    });

    classificationNext.addEventListener('click', () => {
        classificationFeedback.hidden = true;
        classificationNext.hidden = true;
        renderCurrentStage();
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

                const response = await persist({ action: 'try_command', command: command.id });

                if (response && !command.implemented) {
                    renderCurrentStage();
                }
            });
            container.append(button);
        });
    }

    function renderQuestion(commandId) {
        const command = configuration.commands.find((item) => item.id === commandId);

        if (!command || command.implemented) return;

        root.querySelector('[data-strange-prompt]').textContent = command.prompt;
        const container = root.querySelector('[data-strange-options]');
        container.replaceChildren();
        questionFeedback.hidden = true;

        configuration.answer_options.forEach((option) => {
            const column = document.createElement('div');
            column.className = 'col-sm-6';
            const button = document.createElement('button');
            button.className = 'btn btn-outline-primary koki-command-button w-100';
            button.type = 'button';
            button.textContent = option.value === 'dislikes_command'
                ? command.dislike_label
                : option.label;
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
        const selectedCommand = state.selected_strange_command;
        const shouldShowQuestion = Boolean(selectedCommand) && !state.strange_completed;

        commandPicker.hidden = shouldShowQuestion;
        questionStage.hidden = !shouldShowQuestion;

        if (shouldShowQuestion) {
            renderQuestion(selectedCommand);
        }

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
        classificationFeedback.hidden = true;
        classificationNext.hidden = true;
    }

    function buildGrid() {
        grid.style.setProperty('--grid-columns', 5);
        grid.style.setProperty('--grid-rows', 1);
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

            if (response.status === 409) {
                window.location.assign(root.dataset.entryUrl);

                return null;
            }

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

    function showClassificationFeedback(message, type, showNext) {
        classificationFeedback.className = `alert rounded-4 text-center mt-4 mb-0 alert-${type}`;
        classificationFeedbackMessage.textContent = message;
        classificationNext.hidden = !showNext;
        classificationFeedback.hidden = false;
    }

    function updateReflectionCount() {
        reflectionCount.textContent = reflection.value.length;
    }

    async function checkProgressExists() {
        try {
            const response = await fetch(root.dataset.statusUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            if (response.status === 409) {
                window.location.assign(root.dataset.entryUrl);
            }
        } catch {
            // Gangguan jaringan sementara tidak mengubah progress yang sedang tampil.
        }
    }

    function parseData(key, fallback) {
        try {
            return JSON.parse(root.dataset[key] || JSON.stringify(fallback));
        } catch {
            return fallback;
        }
    }
}

import { COMMANDS, DIRECTIONS, GridRobot, ProgramInterpreter } from './grid-engine.js';
import { LEVELS, getLevel } from './levels.js';

const root = document.querySelector('[data-ko-ki-game]');

if (root) {
    const commandLabels = {
        [COMMANDS.MOVE_FORWARD]: 'Maju',
        [COMMANDS.TURN_LEFT]: 'Putar Kiri',
        [COMMANDS.TURN_RIGHT]: 'Putar Kanan',
    };
    const directionArrows = {
        [DIRECTIONS.NORTH]: '↑',
        [DIRECTIONS.EAST]: '→',
        [DIRECTIONS.SOUTH]: '↓',
        [DIRECTIONS.WEST]: '←',
    };
    const sandboxConfig = {
        grid: { width: 6, height: 6 },
        start: { x: 2, y: 4, direction: DIRECTIONS.NORTH },
        obstacles: [],
        target: null,
    };
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const grid = root.querySelector('[data-grid]');
    const statusElement = root.querySelector('[data-engine-status]');
    const sandboxElement = root.querySelector('[data-sandbox]');
    const missionsElement = root.querySelector('[data-missions]');
    const programList = root.querySelector('[data-program-list]');
    const emptyProgram = root.querySelector('[data-empty-program]');
    const feedback = root.querySelector('[data-mission-feedback]');
    const feedbackMessages = root.querySelector('[data-feedback-messages]');
    const nextLevelButton = root.querySelector('[data-next-level]');
    const bonusButton = root.querySelector('[data-bonus-retry]');
    const laboratoryButton = root.querySelector('[data-open-laboratory]');
    const predictionPanel = root.querySelector('[data-prediction-panel]');
    const predictionOptions = root.querySelector('[data-prediction-options]');
    const debugPanel = root.querySelector('[data-debug-panel]');
    const grade = Number(root.dataset.grade);
    let currentLevelId = Number(root.dataset.currentLevel || 1);
    let programs = parseData('programs', {});
    let levelAttempts = parseData('attempts', {});
    let predictions = parseData('predictions', {});
    let levelStates = parseData('levelStates', {});
    let completedLevels = parseData('completedLevels', []);
    let approvedLevels = parseData('approvedLevels', []).map(Number);
    let currentConfig = sandboxConfig;
    let currentLevel = null;
    let program = [];
    let robot = null;
    let interpreter = null;
    let activeCommandIndex = null;
    let bonusMode = false;
    let saveQueue = Promise.resolve();

    configureEngine(sandboxConfig);
    renderProgram();
    updateControls();

    root.querySelectorAll('[data-sandbox-command]').forEach((button) => {
        button.addEventListener('click', () => {
            const command = button.dataset.sandboxCommand;
            const result = robot.execute(command);
            renderRobot();
            setStatus(
                result.blocked
                    ? 'Ko-Ki tidak dapat keluar dari grid.'
                    : command === COMMANDS.MOVE_FORWARD
                        ? 'Ko-Ki maju satu kotak.'
                        : 'Ko-Ki berputar di kotak yang sama.',
            );
        });
    });

    root.querySelector('[data-back-to-start]').addEventListener('click', () => {
        resetRobot('Ko-Ki kembali ke posisi awal.');
    });

    root.querySelector('[data-show-missions]').addEventListener('click', () => {
        if (!approvedLevels.includes(1)) {
            checkLevelAccess(true);

            return;
        }

        sandboxElement.hidden = true;
        missionsElement.hidden = false;
        loadLevel(currentLevelId);

        if (window.matchMedia('(max-width: 991.98px)').matches) {
            missionsElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    root.querySelector('[data-show-sandbox]').addEventListener('click', () => {
        interpreter?.stop();
        currentLevel = null;
        currentConfig = sandboxConfig;
        program = [];
        missionsElement.hidden = true;
        sandboxElement.hidden = false;
        configureEngine(sandboxConfig);
        renderProgram();
        setStatus('Coba beri Ko-Ki satu perintah.');
    });

    root.querySelectorAll('[data-add-command]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!canEditProgram()) return;

            if (program.length >= 100) {
                setStatus('Program sudah mencapai batas 100 command.');

                return;
            }

            program.push(button.dataset.addCommand);
            programChanged('Command ditambahkan.');
        });
    });

    root.querySelector('[data-run]').addEventListener('click', () => {
        if (!canExecuteProgram()) return;

        prepareExecution();
        recordAttempt();
        interpreter.run();
    });

    root.querySelector('[data-stop]').addEventListener('click', () => {
        interpreter.stop();
        setStatus('Program dihentikan. Kamu dapat melanjutkan langkah demi langkah atau mereset robot.');
        updateControls();
    });

    root.querySelector('[data-reset-robot]').addEventListener('click', () => {
        resetRobot('Robot direset ke posisi awal.');
    });

    root.querySelector('[data-undo]').addEventListener('click', () => {
        if (!canEditProgram() || program.length === 0) return;
        program.pop();
        programChanged('Command terakhir dihapus.');
    });

    root.querySelector('[data-clear]').addEventListener('click', () => {
        if (!canEditProgram() || program.length === 0) return;
        program = [];
        programChanged('Semua command dihapus.');
    });

    root.querySelector('[data-observe-bug]').addEventListener('click', () => {
        if (currentLevel?.id !== 3) return;

        const state = getCurrentLevelState();

        if (!state.ran_starter) return;

        state.observed_bug = true;
        levelStates[currentLevel.id] = state;
        persist({ level_state: state });
        renderDebugState();
        setStatus('Sekarang periksa dan perbaiki command yang membuat arah Ko-Ki keliru.');
        updateControls();
    });

    nextLevelButton.addEventListener('click', () => {
        if (!currentLevel || currentLevel.id >= LEVELS.length) return;

        const targetLevel = currentLevel.id + 1;

        if (approvedLevels.includes(targetLevel)) {
            enterLevel(targetLevel);

            return;
        }

        checkLevelAccess(true);
    });

    window.setInterval(() => {
        const nextLevel = (currentLevel?.id ?? 0) + 1;

        const waitingForFirstMission = !sandboxElement.hidden && !approvedLevels.includes(1);
        const waitingForNextMission = isCurrentLevelCompleted()
            && nextLevel <= LEVELS.length
            && !approvedLevels.includes(nextLevel);

        if (waitingForFirstMission || waitingForNextMission) {
            checkLevelAccess();
        }
    }, 4000);

    bonusButton.addEventListener('click', () => {
        if (currentLevel?.id !== 4) return;

        bonusMode = true;
        hideFeedback();
        resetRobot('Bonus opsional dimulai. Coba susun program yang lebih singkat.');
        updateControls();
    });

    function loadLevel(levelId) {
        const level = getLevel(levelId) ?? LEVELS[0];
        currentLevelId = level.id;
        currentLevel = level;
        currentConfig = level;
        bonusMode = false;
        activeCommandIndex = null;
        program = Array.isArray(programs[level.id])
            ? [...programs[level.id]]
            : [...level.starterProgram];

        root.querySelector('[data-level-progress]').textContent = `Misi ${level.id} dari ${LEVELS.length}`;
        root.querySelector('[data-level-title]').textContent = level.title;
        root.querySelector('[data-level-instruction]').textContent = level.instruction;
        root.querySelector('[data-level-attempt]').textContent = levelAttempts[level.id] ?? 0;

        configureEngine(level);
        interpreter.setProgram(program);
        renderProgram();
        renderPrediction();
        renderDebugState();
        hideFeedback();

        if (!programs[level.id] && program.length > 0) {
            programs[level.id] = [...program];
            persist({ last_program: program });
        }

        if (isCurrentLevelCompleted()) {
            showSuccess();
        } else {
            setStatus(level.id === 3 && !getCurrentLevelState().ran_starter
                ? 'Jalankan starter program untuk melihat bug.'
                : 'Susun program, lalu jalankan satu per satu.');
        }

        updateControls();
    }

    function configureEngine(config) {
        interpreter?.stop();
        robot = new GridRobot({
            ...config.grid,
            start: config.start,
            obstacles: config.obstacles,
        });
        interpreter = new ProgramInterpreter(robot, {
            onBeforeStep: ({ index }) => {
                activeCommandIndex = index;
                renderProgram();
            },
            onAfterStep: ({ result }) => {
                renderRobot();
                setStatus(result.blocked
                    ? result.blockedBy === 'obstacle'
                        ? 'Ko-Ki berhenti karena ada penghalang.'
                        : 'Ko-Ki berhenti di tepi grid.'
                    : 'Ko-Ki menjalankan command satu per satu.');
            },
            onStatusChange: (status) => {
                if (status === 'complete' && currentLevel) {
                    evaluateMission();
                }

                updateControls();
            },
        });
        interpreter.setProgram(program);
        buildGrid(config);
        renderRobot();
    }

    function buildGrid(config) {
        grid.style.setProperty('--grid-columns', config.grid.width);
        grid.style.aspectRatio = `${config.grid.width} / ${config.grid.height}`;
        grid.replaceChildren();

        for (let y = 0; y < config.grid.height; y++) {
            for (let x = 0; x < config.grid.width; x++) {
                const cell = document.createElement('div');
                cell.className = 'koki-grid-cell';
                cell.dataset.x = x;
                cell.dataset.y = y;
                cell.setAttribute('aria-hidden', 'true');

                if (config.obstacles.some((obstacle) => obstacle.x === x && obstacle.y === y)) {
                    cell.classList.add('is-obstacle');
                    cell.textContent = '🧱';
                }

                if (config.target?.x === x && config.target?.y === y) {
                    const target = document.createElement('span');
                    target.className = 'koki-target';
                    target.textContent = config.target.emoji;
                    target.title = config.target.label;
                    cell.append(target);
                }

                grid.append(cell);
            }
        }
    }

    function renderRobot() {
        grid.querySelector('.koki-robot')?.remove();
        const state = robot.state;
        const cell = grid.querySelector(`[data-x="${state.x}"][data-y="${state.y}"]`);
        const robotElement = document.createElement('div');
        robotElement.className = 'koki-robot';
        robotElement.innerHTML = `<span aria-hidden="true">🤖</span><span class="koki-direction" aria-hidden="true">${directionArrows[state.direction]}</span>`;
        robotElement.setAttribute('role', 'img');
        robotElement.setAttribute('aria-label', `Ko-Ki di posisi ${state.x + 1}, ${state.y + 1}, menghadap ${state.direction}`);
        cell.append(robotElement);
    }

    function renderProgram() {
        programList.replaceChildren();
        emptyProgram.hidden = program.length !== 0;

        program.forEach((command, index) => {
            const item = document.createElement('li');
            item.className = 'koki-program-command';
            item.classList.toggle('is-active', index === activeCommandIndex);
            item.innerHTML = `<span>${index + 1}</span><strong>${commandLabels[command]}</strong><code>${command}</code>`;
            programList.append(item);
        });

        updateControls();
    }

    function renderPrediction() {
        predictionPanel.hidden = !currentLevel?.prediction;
        predictionOptions.replaceChildren();

        if (!currentLevel?.prediction) return;

        root.querySelector('[data-prediction-prompt]').textContent = currentLevel.prediction.prompt;

        currentLevel.prediction.options.forEach((option) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'koki-answer';
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'mission-prediction';
            input.id = `prediction-${option.value}`;
            input.value = option.value;
            input.checked = predictions[currentLevel.id] === option.value;
            input.disabled = (levelAttempts[currentLevel.id] ?? 0) > 0;
            const label = document.createElement('label');
            label.htmlFor = input.id;
            label.textContent = option.label;
            input.addEventListener('change', () => {
                predictions[currentLevel.id] = option.value;
                persist({ prediction: option.value });
                setStatus('Prediksimu sudah disimpan. Sekarang jalankan program untuk mengamatinya.');
                updateControls();
            });
            wrapper.append(input, label);
            predictionOptions.append(wrapper);
        });
    }

    function renderDebugState() {
        debugPanel.hidden = currentLevel?.id !== 3;

        if (currentLevel?.id !== 3) return;

        const state = getCurrentLevelState();
        const prompt = root.querySelector('[data-debug-prompt]');
        const observeButton = root.querySelector('[data-observe-bug]');

        if (!state.ran_starter) {
            prompt.textContent = 'Langkah 1: jalankan starter program yang tersedia.';
            observeButton.hidden = true;
        } else if (!state.observed_bug) {
            prompt.textContent = currentLevel.debugging.observePrompt;
            observeButton.hidden = false;
        } else {
            prompt.textContent = 'Sekarang perbaiki command yang keliru, lalu jalankan kembali.';
            observeButton.hidden = true;
        }
    }

    function evaluateMission() {
        const reachedTarget = robot.state.x === currentLevel.target.x && robot.state.y === currentLevel.target.y;

        if (currentLevel.id === 3) {
            const state = getCurrentLevelState();

            if (!state.ran_starter) {
                state.ran_starter = true;
                levelStates[currentLevel.id] = state;
                persist({ level_state: state });
                renderDebugState();
                showMessages(currentLevel.failureMessages, 'info');
                updateControls();

                return;
            }

            if (!state.observed_bug) {
                renderDebugState();

                return;
            }

            if (reachedTarget) {
                state.corrected_bug = true;
                levelStates[currentLevel.id] = state;
                persist({ level_state: state });
                completeCurrentLevel();

                return;
            }
        } else if (reachedTarget) {
            completeCurrentLevel();

            return;
        }

        showMessages(currentLevel.failureMessages, 'info');
        updateControls();
    }

    function completeCurrentLevel() {
        bonusMode = false;

        if (!completedLevels.includes(currentLevel.id)) {
            completedLevels.push(currentLevel.id);
            persist({ completed_level: currentLevel.id });
        }

        showSuccess();
    }

    function showSuccess() {
        const messages = [...currentLevel.successMessages];
        const bonus = currentLevel.bonus && grade === currentLevel.bonus.grade;
        const bonusAchieved = bonus && program.length > 0 && program.length <= currentLevel.bonus.maxCommands;

        if (bonusAchieved) {
            messages.push('Bonus tercapai: programmu menggunakan lebih sedikit command.');
        } else if (bonus) {
            messages.push(currentLevel.bonus.prompt);
            messages.push('Bonus ini opsional dan tidak menghambat penyelesaian misi.');
        }

        const nextLevel = currentLevel.id + 1;
        const nextLevelIsApproved = approvedLevels.includes(nextLevel);

        if (nextLevel <= LEVELS.length && !nextLevelIsApproved) {
            messages.push(`Tunggu admin mengizinkan Misi ${nextLevel} dari halaman admin.`);
        }

        showMessages(messages, 'success');
        nextLevelButton.hidden = currentLevel.id >= LEVELS.length;
        root.querySelector('[data-next-level-label]').textContent = nextLevelIsApproved
            ? 'Lanjut ke Misi Berikutnya'
            : `Cek Izin Admin untuk Misi ${nextLevel}`;
        nextLevelButton.querySelector('i').className = nextLevelIsApproved
            ? 'bi bi-arrow-right-circle-fill me-1'
            : 'bi bi-hourglass-split me-1';
        bonusButton.hidden = !bonus || bonusAchieved;
        laboratoryButton.hidden = currentLevel.id < LEVELS.length;
        setStatus('Misi selesai. Progress sudah disimpan.');
    }

    async function checkLevelAccess(announce = false) {
        try {
            const response = await fetch(root.dataset.levelStatusUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) throw new Error();

            const responseData = await response.json();
            const previousApprovals = [...approvedLevels];
            approvedLevels = responseData.approved_levels.map(Number);
            const targetLevel = currentLevel ? currentLevel.id + 1 : 1;

            if (approvedLevels.includes(targetLevel) && !previousApprovals.includes(targetLevel)) {
                if (targetLevel === 1 && !sandboxElement.hidden) {
                    root.querySelector('[data-show-missions-label]').textContent = 'Masuk ke Misi 1';
                    root.querySelector('[data-show-missions-icon]').className = 'bi bi-arrow-right-circle-fill me-1';
                    setStatus('Izin admin diterima. Tekan Masuk ke Misi 1.');
                } else {
                    showSuccess();
                    setStatus(`Izin admin diterima. Misi ${targetLevel} sudah dapat dibuka.`);
                }
            } else if (announce && !approvedLevels.includes(targetLevel)) {
                setStatus(`Misi ${targetLevel} masih menunggu izin admin.`);
            }
        } catch {
            if (announce) setStatus('Status izin belum dapat diperiksa. Coba lagi.');
        }
    }

    function enterLevel(levelId) {
        currentLevelId = levelId;
        persist({ current_level: currentLevelId });
        loadLevel(currentLevelId);
        setStatus(`Izin diterima. Misi ${currentLevelId} sudah dibuka.`);
    }

    function showMessages(messages, type) {
        feedback.className = `alert rounded-4 mt-3 alert-${type === 'success' ? 'success' : 'info'}`;
        feedback.hidden = false;
        feedbackMessages.replaceChildren();

        messages.forEach((message) => {
            const paragraph = document.createElement('p');
            paragraph.className = 'mb-1 fw-semibold';
            paragraph.textContent = message;
            feedbackMessages.append(paragraph);
        });
    }

    function hideFeedback() {
        feedback.hidden = true;
        feedbackMessages.replaceChildren();
        nextLevelButton.hidden = true;
        bonusButton.hidden = true;
        laboratoryButton.hidden = true;
    }

    function prepareExecution() {
        interpreter.resetExecution();
        robot.reset();
        activeCommandIndex = null;
        renderRobot();
        renderProgram();
        hideFeedback();
    }

    function resetRobot(message) {
        interpreter.resetExecution();
        robot.reset();
        activeCommandIndex = null;
        renderRobot();
        renderProgram();
        setStatus(message);
    }

    function programChanged(message) {
        interpreter.setProgram(program);
        robot.reset();
        activeCommandIndex = null;
        programs[currentLevel.id] = [...program];
        renderRobot();
        renderProgram();
        hideFeedback();
        setStatus(message);
        persist({ last_program: program });
    }

    function recordAttempt() {
        levelAttempts[currentLevel.id] = (levelAttempts[currentLevel.id] ?? 0) + 1;
        root.querySelector('[data-level-attempt]').textContent = levelAttempts[currentLevel.id];
        root.querySelector('[data-attempt-count]').textContent = Number(root.querySelector('[data-attempt-count]').textContent) + 1;
        persist({ last_program: program, increment_attempt: true });
    }

    function persist(data) {
        const levelId = currentLevel?.id ?? currentLevelId;
        const payload = {
            last_phase: 'activity',
            current_level: levelId,
            ...structuredClone(data),
        };

        saveQueue = saveQueue.then(async () => {
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

            if (!response.ok) {
                throw new Error('Progress tidak dapat disimpan.');
            }

            const responseData = await response.json();
            root.querySelector('[data-attempt-count]').textContent = responseData.progress.attempt_count;
        }).catch(() => setStatus('Progress belum dapat disimpan. Coba lagi.'));
    }

    function canExecuteProgram() {
        if (program.length === 0) {
            setStatus('Tambahkan command sebelum menjalankan program.');

            return false;
        }

        if (currentLevel?.prediction && !predictions[currentLevel.id]) {
            setStatus('Pilih prediksi sebelum menjalankan program.');

            return false;
        }

        if (currentLevel?.id === 3) {
            const state = getCurrentLevelState();

            if (state.ran_starter && !state.observed_bug) {
                setStatus('Amati hasil starter program terlebih dahulu.');

                return false;
            }
        }

        return !interpreter.isRunning && (!isCurrentLevelCompleted() || bonusMode);
    }

    function canEditProgram() {
        if (!currentLevel || interpreter.isRunning || (isCurrentLevelCompleted() && !bonusMode)) {
            return false;
        }

        if (currentLevel.id === 3) {
            const state = getCurrentLevelState();

            return state.ran_starter && state.observed_bug;
        }

        return true;
    }

    function updateControls() {
        if (!missionsElement || missionsElement.hidden || !interpreter) return;

        const editable = canEditProgram();
        const executable = program.length > 0
            && !interpreter.isRunning
            && (!currentLevel?.prediction || Boolean(predictions[currentLevel.id]))
            && (!isCurrentLevelCompleted() || bonusMode)
            && !(currentLevel?.id === 3 && getCurrentLevelState().ran_starter && !getCurrentLevelState().observed_bug);

        root.querySelectorAll('[data-add-command]').forEach((button) => {
            button.disabled = !editable;
        });
        root.querySelector('[data-run]').disabled = !executable;
        root.querySelector('[data-stop]').disabled = !interpreter.isRunning;
        root.querySelector('[data-undo]').disabled = !editable || program.length === 0;
        root.querySelector('[data-clear]').disabled = !editable || program.length === 0;
    }

    function isCurrentLevelCompleted() {
        return currentLevel ? completedLevels.includes(currentLevel.id) : false;
    }

    function getCurrentLevelState() {
        return {
            ran_starter: false,
            observed_bug: false,
            corrected_bug: false,
            ...(levelStates[currentLevel?.id] ?? {}),
        };
    }

    function setStatus(message) {
        statusElement.textContent = message;
    }

    function parseData(key, fallback) {
        try {
            return JSON.parse(root.dataset[key] || JSON.stringify(fallback));
        } catch {
            return fallback;
        }
    }
}

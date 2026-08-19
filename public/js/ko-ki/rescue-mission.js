import { calculateStars, RescueGame, samePosition } from './rescue-engine.js';
import { getRescueLevel } from './rescue-levels.js';

const root = document.querySelector('[data-rescue-game]');
const COMMAND_META = {
    MOVE_FORWARD: ['⬆️', 'MAJU'], TURN_LEFT: ['↶', 'PUTAR KIRI'], TURN_RIGHT: ['↷', 'PUTAR KANAN'],
    TAKE_WATER: ['💧', 'AMBIL AIR'], WATER: ['🌧️', 'SIRAM'],
};
const DIRECTION_ICONS = { NORTH: '▲', EAST: '▶', SOUTH: '▼', WEST: '◀' };

if (root) {
    const level = getRescueLevel(root.dataset.mission);
    const state = JSON.parse(root.dataset.state || '{}');
    if (!level || !(state.approved_levels || []).map(Number).includes(level.id)) {
        window.location.replace(root.dataset.mapUrl);
    } else {
        boot(level);
    }
}

function boot(level) {
    const game = new RescueGame(level);
    const grid = root.querySelector('[data-grid]');
    const status = root.querySelector('[data-status]');
    const programList = root.querySelector('[data-program]');
    const runButton = root.querySelector('[data-run]');
    const result = root.querySelector('[data-result]');
    let program = [];
    let prediction = null;
    let running = false;
    let runToken = 0;
    let activeIndex = null;
    let errorIndexes = new Set();
    let bonkPosition = null;

    root.querySelector('[data-level-label]').textContent = level.final ? 'FINAL' : `LEVEL ${level.id} DARI 5`;
    root.querySelector('[data-level-title]').textContent = level.name;
    root.querySelector('[data-instruction]').textContent = level.instruction;
    root.querySelector('[data-tip]').textContent = `Petunjuk: ${level.tip}`;
    root.querySelector('[data-command-limit]').textContent = level.commandLimit;
    grid.style.setProperty('--cols', level.grid.width);
    grid.style.setProperty('--rows', level.grid.height);

    function renderGrid() {
        grid.innerHTML = '';
        for (let y = 0; y < level.grid.height; y++) {
            for (let x = 0; x < level.grid.width; x++) {
                const tile = document.createElement('button');
                tile.type = 'button';
                tile.className = 'rescue-tile';
                tile.dataset.x = x;
                tile.dataset.y = y;
                tile.setAttribute('aria-label', `Kotak ${x + 1}, ${y + 1}`);
                if (samePosition(prediction, { x, y })) tile.classList.add('is-predicted');
                if (samePosition(bonkPosition, { x, y })) tile.classList.add('is-active-error');

                const objects = [];
                const obstacle = level.obstacles.find((item) => samePosition(item, { x, y }));
                if (obstacle) objects.push(`<span class="rescue-object" title="${obstacle.type === 'puddle' ? 'Genangan' : 'Batu'}">${obstacle.type === 'puddle' ? '💦' : '🪨'}</span>`);
                if (samePosition(level.base, { x, y })) objects.push('<span class="rescue-object" title="Base">🏠<small>BASE</small></span>');
                if (samePosition(level.waterStation, { x, y })) objects.push('<span class="rescue-object" title="Stasiun air">🚰<small>AIR</small></span>');
                level.plants.forEach((plant, index) => {
                    if (samePosition(plant, { x, y })) {
                        const watered = game.wateredPlants.has(index);
                        objects.push(`<span class="rescue-object rescue-plant ${watered ? 'is-watered' : ''}" title="Tanaman">${watered ? '🌻' : '🥀'}</span>`);
                    }
                });
                tile.innerHTML = objects.join('');
                grid.append(tile);
            }
        }
        const robot = document.createElement('div');
        robot.className = `rescue-robot ${bonkPosition ? 'is-bonk' : ''}`;
        robot.style.setProperty('--robot-x', game.robot.state.x);
        robot.style.setProperty('--robot-y', game.robot.state.y);
        robot.innerHTML = `🤖<i>${DIRECTION_ICONS[game.robot.state.direction]}</i>`;
        grid.append(robot);
        root.querySelector('[data-water]').textContent = `💧 ×${game.water}`;
    }

    function renderProgram() {
        programList.innerHTML = '';
        if (!program.length) programList.innerHTML = '<li class="is-empty">Belum ada command.</li>';
        program.forEach((command, index) => {
            const item = document.createElement('li');
            item.dataset.index = index + 1;
            if (index === activeIndex) item.classList.add('is-active');
            if (errorIndexes.has(index)) item.classList.add('is-error');
            item.innerHTML = `<span>${COMMAND_META[command][0]} ${COMMAND_META[command][1]}</span>`;
            programList.append(item);
        });
        programList.querySelector('.is-active')?.scrollIntoView({ block: 'nearest' });
        root.querySelector('[data-command-count]').textContent = program.length;
        root.querySelector('[data-program-size]').textContent = `${program.length} command`;
        root.querySelectorAll('[data-add-command]').forEach((button) => { button.disabled = running || program.length >= level.commandLimit; });
        root.querySelector('[data-undo]').disabled = running || !program.length;
        root.querySelector('[data-clear]').disabled = running || !program.length;
        runButton.disabled = running || !program.length || !prediction;
    }

    function setStatus(message, tone = '') {
        status.className = `rescue-world-status ${tone ? `is-${tone}` : ''}`;
        status.textContent = message;
    }

    function resetRobot(message = 'Robot kembali ke awal. Programmu tetap ada dan dapat diperbaiki.') {
        runToken++;
        running = false;
        activeIndex = null;
        errorIndexes = new Set();
        bonkPosition = null;
        game.reset();
        root.classList.remove('is-running');
        renderGrid(); renderProgram();
        setStatus(message);
    }

    async function runProgram() {
        if (running || !program.length || !prediction) return;
        const token = ++runToken;
        running = true;
        errorIndexes = new Set();
        bonkPosition = null;
        game.reset();
        root.classList.add('is-running');
        setStatus('Program dikunci. Ko-Ki menjalankan command satu per satu…');
        renderGrid(); renderProgram();

        for (let index = 0; index < program.length; index++) {
            if (token !== runToken) return;
            activeIndex = index;
            bonkPosition = null;
            renderProgram();
            await wait(430);
            const step = game.execute(program[index], index);
            if (step.event === 'BONK') {
                errorIndexes.add(index);
                bonkPosition = { ...game.robot.state };
                setStatus(`BONK! Periksa command ${index + 1}: ${COMMAND_META[program[index]][1]}.`, 'danger');
            } else if (step.event === 'WATER_TAKEN') setStatus(`Command ${index + 1}: tangki air terisi.`, 'success');
            else if (step.event === 'PLANT_WATERED') setStatus(`Command ${index + 1}: tanaman berhasil disiram!`, 'success');
            else if (step.success === false) {
                errorIndexes.add(index);
                setStatus(`Command ${index + 1} belum dapat dilakukan di kotak ini.`, 'danger');
            }
            renderGrid(); renderProgram();
            await wait(430);
        }

        if (token !== runToken) return;
        running = false;
        activeIndex = null;
        root.classList.remove('is-running');
        renderProgram();
        const complete = game.isComplete();
        if (!complete) {
            setStatus(level.returnToBase && game.wateredPlants.size === level.plants.length
                ? 'Semua tanaman selamat, tetapi Ko-Ki belum kembali ke BASE. Perbaiki programmu.'
                : 'Program selesai, tetapi masih ada tanaman kering. Periksa command yang ditandai.', 'danger');
            return;
        }

        const calculatedStars = calculateStars({
            complete: true, prediction, finalPosition: game.robot.state,
            commandCount: program.length, efficientCommands: level.efficientCommands,
        });
        setStatus('Misi selesai. Menyimpan hasil…', 'success');
        runButton.disabled = true;
        let response;
        try {
            response = await saveCompletion(level.id, program, prediction);
        } catch (error) {
            setStatus(error.message || 'Hasil belum dapat disimpan. Coba RUN kembali.', 'danger');
            renderProgram();
            return;
        }
        const stars = Number(response.stars || calculatedStars);
        result.querySelector('[data-result-icon]').textContent = level.final ? '🏆' : '🎉';
        result.querySelector('[data-result-title]').textContent = level.final ? 'Taman Berhasil Diselamatkan!' : 'Misi Selesai!';
        result.querySelector('[data-result-stars]').textContent = `${'⭐'.repeat(stars)}${'☆'.repeat(3 - stars)}`;
        result.querySelector('[data-result-message]').textContent = (stars === 3
            ? 'Programmu tepat, prediksimu benar, dan rutenya efisien!'
            : stars === 2 ? 'Prediksimu benar! Kamu boleh lanjut atau mencoba rute lebih singkat.'
                : 'Tanaman selamat! Misi berhasil disimpan.') + (level.final ? ' Lanjutkan ke evaluasi.' : ' Tunggu admin mengizinkan level berikutnya.');
        const nextLink = result.querySelector('[data-result-next]');
        nextLink.href = level.final ? response.evaluation_url : response.map_url;
        nextLink.textContent = level.final ? 'Lanjut ke Evaluasi' : 'Kembali ke Peta';
        result.hidden = false;
    }

    grid.addEventListener('click', (event) => {
        if (running) return;
        const tile = event.target.closest('[data-x]');
        if (!tile) return;
        prediction = { x: Number(tile.dataset.x), y: Number(tile.dataset.y) };
        root.querySelector('[data-prediction]').textContent = `Kotak ${prediction.x + 1}, ${prediction.y + 1}`;
        renderGrid(); renderProgram();
        setStatus('Prediksi sudah dipilih. Tekan RUN saat programmu siap.', 'success');
    });
    root.querySelectorAll('[data-add-command]').forEach((button) => button.addEventListener('click', () => {
        if (running || program.length >= level.commandLimit) return;
        program.push(button.dataset.addCommand);
        errorIndexes = new Set(); bonkPosition = null;
        renderProgram();
    }));
    root.querySelector('[data-undo]').addEventListener('click', () => { if (!running) { program.pop(); renderProgram(); } });
    root.querySelector('[data-clear]').addEventListener('click', () => { if (!running) { program = []; renderProgram(); } });
    root.querySelector('[data-reset]').addEventListener('click', () => resetRobot());
    root.querySelector('[data-run]').addEventListener('click', runProgram);
    root.querySelector('[data-result-retry]').addEventListener('click', () => { result.hidden = true; resetRobot('Coba lagi. Kamu dapat memperbaiki program yang sama.'); });
    renderGrid(); renderProgram();
}

async function saveCompletion(level, program, prediction) {
    const response = await fetch(root.dataset.saveUrl, {
        method: 'PATCH',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ level, program, prediction }),
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const validation = payload.errors ? Object.values(payload.errors).flat()[0] : null;
        throw new Error(validation || payload.message || 'Hasil belum dapat disimpan.');
    }
    return payload;
}

function wait(milliseconds) { return new Promise((resolve) => window.setTimeout(resolve, milliseconds)); }

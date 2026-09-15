import { DetectiveCaseEngine } from './detective-engine.js';
import { getDetectiveCase } from './detective-cases.js';
import {
    completeLocalCase, installDetectiveExitSync, loadDetectiveProgress, queueDetectiveSync,
    resetLocalCase, saveCaseDraft, setSaveIndicator,
} from './detective-storage.js';

const root = document.querySelector('[data-detective-game]');
const directionIcon = { NORTH: '▲', EAST: '▶', SOUTH: '▼', WEST: '◀' };
const navigationLifecycle = new AbortController();
window.addEventListener('koki:before-soft-navigation', () => navigationLifecycle.abort(), { once: true });

if (root) boot(getDetectiveCase(root.dataset.case));

function boot(caseConfig) {
    if (!caseConfig) {
        window.location.replace(root.dataset.mapUrl);
        return;
    }

    const engine = new DetectiveCaseEngine(caseConfig);
    const localProgress = loadDetectiveProgress(root.dataset.progressId, JSON.parse(root.dataset.state || '{}'));
    const prerequisite = caseConfig.id === 1 || localProgress.completedCases.includes(caseConfig.id - 1);
    const unlocked = prerequisite && (localProgress.approvedCases || [1]).includes(caseConfig.id);
    if (!unlocked) {
        window.location.replace(root.dataset.mapUrl);
        return;
    }
    const savedCase = localProgress.cases[caseConfig.id] || {};
    if (Array.isArray(savedCase.program)) engine.program = [...savedCase.program];
    const timeline = root.querySelector('[data-timeline]');
    const status = root.querySelector('[data-status]');
    const tracePanel = root.querySelector('[data-trace]');
    const traceList = root.querySelector('[data-trace-list]');
    const repairPanel = root.querySelector('[data-repair-panel]');
    const controls = root.querySelector('[data-repair-controls]');
    const saveIndicator = root.querySelector('[data-save-status]');
    const actions = Object.fromEntries([...root.querySelectorAll('[data-action]')].map((button) => [button.dataset.action, button]));
    let replayed = false;
    let repaired = engine.program.length !== caseConfig.initialProgram.length
        || engine.program.some((command, index) => command !== caseConfig.initialProgram[index]);
    let busy = false;
    let markMode = false;
    let swapMode = false;
    let swapSelection = [];
    let suspects = new Set();
    const allSuspects = new Set(savedCase.suspects || []);
    let hintCount = Number(savedCase.hintCount || 0);
    let hintCandidates = new Set();
    let testAttempts = Number(savedCase.testAttempts || 0);
    let confirmedFinalBugs = Number(savedCase.finalFixed || 0);
    let stepEvidence = !caseConfig.stepRequired || Boolean(savedCase.stepEvidence);
    let controllerKeysTested = new Set(savedCase.controllerKeysTested || []);
    let controllerTestActive = Boolean(savedCase.controllerTestActive || controllerKeysTested.size);
    let controllerLastAction = null;
    let controllerOffset = 0;
    const syncOptions = {
        progress: localProgress,
        url: root.dataset.syncUrl,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
        onStatus: (state) => setSaveIndicator(saveIndicator, state),
    };

    root.querySelector('[data-case-code]').textContent = caseConfig.code;
    root.querySelector('[data-case-title]').textContent = caseConfig.title;
    root.querySelector('[data-case-icon]').textContent = caseConfig.icon;
    root.querySelector('[data-case-subtitle]').textContent = caseConfig.subtitle;
    root.querySelector('[data-case-concept]').textContent = `Berkas: ${caseConfig.concept}`;
    root.querySelector('[data-local-collection]').textContent = `${localProgress.bugCollection.length}/15`;
    if (caseConfig.type === 'mapping') actions.test.querySelector('span').textContent = controllerKeysTested.size === 3 ? 'Selesaikan Case' : 'Test Controller';
    setSaveIndicator(saveIndicator, localProgress.pendingSync ? 'device' : 'saved');
    if (hintCount > 0) {
        const hintText = root.querySelector('[data-hint-text]');
        hintText.hidden = false;
        hintText.textContent = `Petunjuk ${hintCount}: ${caseConfig.hints[hintCount - 1]}`;
        root.querySelector('[data-hint]').textContent = hintCount < 3 ? `💡 Minta Petunjuk (${hintCount}/3)` : '💡 Semua petunjuk terbuka';
        root.querySelector('[data-hint]').disabled = hintCount >= 3;
        if (hintCount >= 3) hintCandidates = new Set(thirdHintCandidates());
    }
    if (caseConfig.final) {
        root.querySelector('[data-boss-bugs]').hidden = false;
        renderBossBugs();
    }
    renderAll();
    if (repaired) actions.test.disabled = caseConfig.type === 'mapping' && controllerTestActive && controllerKeysTested.size < 3;

    actions.replay.addEventListener('click', () => play(false));
    actions.step.addEventListener('click', stepOnce);
    actions.trace.addEventListener('click', () => {
        tracePanel.hidden = !tracePanel.hidden;
        actions.trace.classList.toggle('is-on', !tracePanel.hidden);
    });
    actions.mark.addEventListener('click', () => {
        if (!replayed || busy) return;
        markMode = !markMode;
        swapMode = false;
        actions.mark.classList.toggle('is-on', markMode);
        setStatus(markMode ? 'Klik command yang menurutmu menyebabkan program aneh.' : 'Mode tandai suspect ditutup.', 'clue');
    });
    actions.repair.addEventListener('click', openRepair);
    actions.test.addEventListener('click', () => caseConfig.type === 'mapping' ? testController() : play(true));
    root.querySelector('[data-hint]').addEventListener('click', showNextHint);
    root.querySelector('[data-restart-case]').addEventListener('click', restartCase);
    const removeExitSync = installDetectiveExitSync(syncOptions);
    navigationLifecycle.signal.addEventListener('abort', removeExitSync, { once: true });
    window.addEventListener('online', () => { if (localProgress.pendingSync) queueDetectiveSync(syncOptions); }, { signal: navigationLifecycle.signal });
    if (caseConfig.type === 'input') {
        window.addEventListener('keydown', (event) => {
            if (event.code !== 'Space' || busy || ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target?.tagName)) return;
            event.preventDefault();
            engine.reset();
            const entry = engine.step();
            replayed = true;
            actions.mark.disabled = false;
            renderAll(entry.index);
            renderTrace();
            setStatus(entry.message, engine.state.jumped ? 'good' : 'clue');
        }, { signal: navigationLifecycle.signal });
    }
    if (caseConfig.type === 'mapping') {
        window.addEventListener('keydown', (event) => {
            if (!repaired || busy || ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target?.tagName)) return;
            const input = { Space: 'SPACE', ArrowLeft: 'LEFT', ArrowRight: 'RIGHT' }[event.code];
            if (!input) return;
            event.preventDefault();
            useControllerInput(input);
        }, { signal: navigationLifecycle.signal });
        root.querySelector('[data-scene]').addEventListener('click', (event) => {
            const button = event.target.closest('[data-controller-key]');
            if (button) useControllerInput(button.dataset.controllerKey);
        });
    }

    timeline.addEventListener('click', (event) => {
        const item = event.target.closest('[data-command-index]');
        if (!item || busy) return;
        const index = Number(item.dataset.commandIndex);
        if (swapMode) {
            if (index >= engine.program.length) return;
            swapSelection.push(index);
            item.classList.add('is-swap-choice');
            if (swapSelection.length === 2) {
                engine.swap(swapSelection[0], swapSelection[1]);
                swapSelection = [];
                swapMode = false;
                repaired = true;
                repairPanel.hidden = true;
                setStatus('Urutan command sudah ditukar. Sekarang tekan TEST FIX.', 'good');
                renderAll();
            }
            return;
        }
        if (!markMode) {
            if (!replayed) return;
            markMode = true;
            swapMode = false;
            actions.mark.classList.add('is-on');
        }

        if (!activeSuspectIndexes().includes(index)) {
            setStatus('Hmm... bagian ini bekerja sesuai perintah. Cari petunjuk lain.', 'clue');
            item.animate([{ transform: 'translateX(-4px)' }, { transform: 'translateX(4px)' }, { transform: 'none' }], { duration: 260 });
            return;
        }
        if (suspects.has(index)) suspects.delete(index); else { suspects.add(index); allSuspects.add(index); }
        saveDraft();
        actions.repair.disabled = !canRepair();
        if (['order', 'cinema', 'packing'].includes(caseConfig.type) && suspects.size === 1) {
            setStatus('Satu suspect ditandai. Pilih satu command lagi yang perlu ditukar.', 'clue');
        } else if (caseConfig.type === 'mapping' && suspects.size < 3) {
            setStatus(`${suspects.size} dari 3 mapping ditandai. Periksa semua kabel yang kacau.`, 'clue');
        } else {
            setStatus(suspects.size ? 'Suspect ditandai. Gunakan REPAIR untuk memperbaikinya.' : 'Belum ada command yang ditandai.', 'clue');
        }
        renderTimeline();
    });

    async function play(testing) {
        if (busy) return;
        if (testing) {
            testAttempts++;
            saveDraft();
        }
        busy = true;
        markMode = false;
        actions.mark.classList.remove('is-on');
        engine.reset();
        renderAll();
        setStatus(testing ? 'Menguji program yang sudah diperbaiki…' : 'REPLAY berjalan. Amati command dan hasilnya.');
        setButtonsDisabled(true);
        while (engine.cursor < engine.program.length) {
            await wait(430);
            engine.step();
            renderAll(engine.cursor - 1);
        }
        busy = false;
        replayed = true;
        setButtonsDisabled(false);
        actions.mark.disabled = false;
        renderTrace();

        if (!testing) {
            if (engine.isSolved()) {
                markMode = false;
                actions.mark.classList.remove('is-on');
                setStatus(
                    caseConfig.type === 'mapping'
                        ? 'Mapping sudah benar. Coba tombol SPACE, ←, dan → di layar atau keyboard.'
                        : caseConfig.type === 'direction'
                        ? 'Ko-Ki sudah sampai ke bendera! Tekan TEST FIX untuk mengonfirmasi perbaikan.'
                        : 'Program hasil perbaikan sudah berhasil! Tekan TEST FIX untuk mengonfirmasi perbaikan.',
                    'good',
                );
                return;
            }
            markMode = true;
            actions.mark.classList.add('is-on');
            setStatus(`${failureMessage(caseConfig.type, engine.state)} Klik command yang dicurigai.`, 'clue');
            return;
        }
        if (!engine.isSolved()) {
            if (caseConfig.final) {
                const fixedNow = countFixedFinalBugs(engine.program);
                if (fixedNow > confirmedFinalBugs) {
                    confirmedFinalBugs = fixedNow;
                    saveDraft();
                    renderBossBugs();
                    repaired = false;
                    suspects = new Set();
                    actions.repair.disabled = true;
                    actions.test.disabled = true;
                    setStatus(`BUG ${confirmedFinalBugs} berhasil diperbaiki! Replay program untuk mencari bug berikutnya.`, 'good');
                    renderTimeline();
                    return;
                }
            }
            repaired = false;
            suspects = new Set();
            actions.repair.disabled = true;
            actions.test.disabled = true;
            setStatus('Perbaikannya belum menyelesaikan kejadian aneh. Replay lagi dan periksa jejaknya.', 'clue');
            renderTimeline();
            return;
        }

        if (caseConfig.final) {
            confirmedFinalBugs = 3;
            renderBossBugs();
        }
        completeLocalCase(localProgress, caseConfig.id, {
            program: engine.program,
            suspects: [...allSuspects],
            hintCount,
            testAttempts,
            finalFixed: confirmedFinalBugs,
        });
        setSaveIndicator(saveIndicator, 'device');
        root.querySelector('[data-local-collection]').textContent = `${localProgress.bugCollection.length}/15`;
        setStatus('CASE selesai. Progress aman di perangkat.', 'good');
        showSolved(localProgress.cases[caseConfig.id].stars);
        queueDetectiveSync(syncOptions);
    }

    function testController() {
        if (busy || !repaired) return;
        if (!controllerTestActive) {
            controllerTestActive = true;
            controllerKeysTested = new Set();
            actions.test.disabled = true;
            saveDraft();
            renderScene();
            setStatus('Mode TEST CONTROLLER aktif. Coba SPACE, LEFT, dan RIGHT pada keyboard.', 'good');
            return;
        }
        if (controllerKeysTested.size === 3) play(true);
    }

    function useControllerInput(input) {
        if (!repaired || busy || !['SPACE', 'LEFT', 'RIGHT'].includes(input)) return;
        if (!controllerTestActive) {
            controllerTestActive = true;
            controllerKeysTested = new Set();
        }
        controllerKeysTested.add(input);
        controllerLastAction = input === 'SPACE' ? 'JUMP' : input;
        if (input === 'LEFT') controllerOffset = Math.max(-2, controllerOffset - 1);
        if (input === 'RIGHT') controllerOffset = Math.min(2, controllerOffset + 1);
        renderScene();
        saveDraft();
        const remaining = 3 - controllerKeysTested.size;
        actions.test.disabled = remaining > 0;
        if (!remaining) actions.test.querySelector('span').textContent = 'Selesaikan Case';
        setStatus(remaining
            ? `${input} bekerja! Coba ${remaining} tombol lagi.`
            : 'Semua tombol bekerja! Tekan SELESAIKAN CASE.', 'good');
    }

    function stepOnce() {
        if (busy) return;
        if (engine.cursor >= engine.program.length) engine.reset();
        const entry = engine.step();
        replayed = true;
        if (caseConfig.stepRequired && engine.cursor >= engine.program.length) {
            stepEvidence = true;
            saveDraft();
        }
        actions.mark.disabled = false;
        renderAll(entry?.index ?? null);
        renderTrace();
        setStatus(
            stepEvidence && caseConfig.stepRequired
                ? `${entry?.message || 'Program selesai.'} Semua langkah sudah diamati. Tandai command tambahan.`
                : (entry?.message ?? 'Program sudah mencapai akhir.'),
            stepEvidence && caseConfig.stepRequired ? 'clue' : (entry ? '' : 'clue'),
        );
    }

    function openRepair() {
        if (!canRepair() || busy) return;
        markMode = false;
        actions.mark.classList.remove('is-on');
        controls.innerHTML = '';
        controls.className = 'd-grid gap-2';
        repairPanel.hidden = false;
        root.querySelector('[data-repair-title]').textContent = `Repair: ${caseConfig.concept}`;

        if (caseConfig.type === 'extra') {
            root.querySelector('[data-repair-help]').textContent = 'Hapus command berlebih yang sudah kamu tandai.';
            [...suspects].sort((a, b) => b - a).forEach((index) => { if (index < engine.program.length) engine.remove(index); });
            finishRepair('Command berlebih dihapus. Tekan TEST FIX.');
            return;
        }
        if (caseConfig.type === 'missing') {
            root.querySelector('[data-repair-help]').textContent = 'Tambahkan command yang hilang di akhir program.';
            engine.add('UP');
            finishRepair('Command NAIK ditambahkan. Tekan TEST FIX.');
            return;
        }
        if (['order', 'cinema', 'packing'].includes(caseConfig.type)) {
            const [first, second] = [...suspects].sort((a, b) => a - b);
            engine.swap(first, second);
            finishRepair('Dua command sudah ditukar. Tekan TEST FIX.');
            return;
        }
        if (caseConfig.type === 'door') {
            engine.remove([...suspects][0]);
            finishRepair('Command BUKA PINTU tambahan dihapus. Tekan TEST FIX.');
            return;
        }
        if (caseConfig.type === 'copier') {
            engine.remove([...suspects][0]);
            finishRepair('Satu command CETAK berlebih dihapus. Tekan TEST FIX.');
            return;
        }
        if (caseConfig.type === 'drone') {
            openDroneRepair();
            return;
        }
        if (caseConfig.type === 'courier') {
            openCourierRepair();
            return;
        }
        if (caseConfig.type === 'mapping') {
            openMappingRepair();
            return;
        }

        if (caseConfig.type === 'delivery') {
            const bug = currentFinalBug(engine.program);
            if (bug?.kind === 'extra') {
                root.querySelector('[data-repair-help]').textContent = 'Hapus command berlebih yang sudah ditandai.';
                engine.remove(bug.index);
                finishRepair('Satu command berlebih dihapus. Tekan TEST FIX.');
                return;
            }
            if (bug?.kind === 'missing') {
                root.querySelector('[data-repair-help]').textContent = 'Tambahkan aksi yang belum ada di akhir program.';
                engine.add('DELIVERY');
                finishRepair('Command DELIVERY ditambahkan. Tekan TEST FIX.');
                return;
            }
            root.querySelector('[data-repair-help]').textContent = 'Pilih command belok pengganti.';
            ['TURN_LEFT', 'TURN_RIGHT'].forEach((command) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = caseConfig.labels[command];
                button.addEventListener('click', () => {
                    engine.replace(bug.index, command);
                    finishRepair(`${caseConfig.labels[command]} dipasang. Tekan TEST FIX.`);
                });
                controls.append(button);
            });
            return;
        }

        root.querySelector('[data-repair-help]').textContent = caseConfig.type === 'input' ? 'Pilih input yang harus didengarkan program.' : 'Pilih command pengganti.';
        caseConfig.repairChoices.forEach((command) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = caseConfig.labels[command];
            button.addEventListener('click', () => {
                engine.replace([...suspects][0], command);
                finishRepair(`${caseConfig.labels[command]} dipasang. Tekan TEST FIX.`);
            });
            controls.append(button);
        });
    }

    function finishRepair(message) {
        repaired = true;
        suspects = new Set();
        markMode = false;
        actions.mark.classList.remove('is-on');
        actions.repair.disabled = true;
        repairPanel.hidden = true;
        actions.test.disabled = false;
        renderAll();
        saveDraft();
        setStatus(message, 'good');
    }

    function openCourierRepair() {
        root.querySelector('[data-repair-help]').textContent = 'Pilih posisi untuk menyisipkan AMBIL PAKET.';
        ['Sebelum PERGI KE GUDANG', 'Setelah PERGI KE GUDANG', 'Setelah PERGI KE RUMAH', 'Setelah ANTAR PAKET']
            .forEach((label, position) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = `${position + 1}. ${label}`;
                button.addEventListener('click', () => {
                    if (position !== 1) {
                        setStatus('Ko-Ki harus mengambil paket saat masih berada di gudang. Coba posisi lain.', 'clue');
                        button.animate([{ transform: 'translateX(-4px)' }, { transform: 'translateX(4px)' }, { transform: 'none' }], { duration: 260 });
                        return;
                    }
                    engine.insert(position, 'TAKE_PACKAGE');
                    finishRepair('AMBIL PAKET disisipkan setelah tiba di gudang. Tekan TEST FIX.');
                });
                controls.append(button);
            });
    }

    function openDroneRepair() {
        root.querySelector('[data-repair-help]').textContent = 'Pilih posisi untuk menyisipkan AMBIL FOTO.';
        ['Sebelum TERBANG KE LOKASI', 'Setelah sampai di lokasi', 'Setelah KEMBALI KE BASE', 'Setelah KIRIM FOTO']
            .forEach((label, position) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = `${position + 1}. ${label}`;
                button.addEventListener('click', () => {
                    if (position !== 1) {
                        setStatus('Foto harus diambil ketika drone masih berada di lokasi. Coba posisi lain.', 'clue');
                        button.animate([{ transform: 'translateX(-4px)' }, { transform: 'translateX(4px)' }, { transform: 'none' }], { duration: 260 });
                        return;
                    }
                    engine.insert(position, 'TAKE_PHOTO');
                    finishRepair('AMBIL FOTO disisipkan sebelum drone pulang. Tekan TEST FIX.');
                });
                controls.append(button);
            });
    }

    function openMappingRepair() {
        root.querySelector('[data-repair-help]').textContent = 'Pilih tombol di kiri, lalu sambungkan ke gerakan di kanan.';
        controls.className = 'detective-wire-board';
        let selectedInput = null;
        const expected = { SPACE: 'JUMP', LEFT: 'LEFT', RIGHT: 'RIGHT' };
        const renderWires = () => {
            const mappings = Object.fromEntries(engine.program.map((command) => command.split('_TO_')));
            controls.innerHTML = `<div class="detective-wire-column"><strong>INPUT</strong>${['SPACE', 'LEFT', 'RIGHT'].map((input) => `<button type="button" data-wire-input="${input}" class="${selectedInput === input ? 'is-selected' : ''}">${input}</button>`).join('')}</div><div class="detective-wire-links">${['SPACE', 'LEFT', 'RIGHT'].map((input) => `<span class="${mappings[input] === expected[input] ? 'is-correct' : ''}">${input} ⟶ ${mappings[input] || '?'}</span>`).join('')}</div><div class="detective-wire-column"><strong>OUTPUT</strong>${['JUMP', 'LEFT', 'RIGHT'].map((output) => `<button type="button" data-wire-output="${output}">${output}</button>`).join('')}</div>`;
        };
        controls.addEventListener('click', (event) => {
            const inputButton = event.target.closest('[data-wire-input]');
            const outputButton = event.target.closest('[data-wire-output]');
            if (inputButton) {
                selectedInput = inputButton.dataset.wireInput;
                renderWires();
                return;
            }
            if (!outputButton || !selectedInput) return;
            const index = { SPACE: 0, LEFT: 1, RIGHT: 2 }[selectedInput];
            engine.replace(index, `${selectedInput}_TO_${outputButton.dataset.wireOutput}`);
            selectedInput = null;
            saveDraft();
            renderTimeline();
            renderWires();
            if (engine.program.every((command, programIndex) => command === caseConfig.solution[programIndex])) {
                repaired = true;
                suspects = new Set();
                actions.repair.disabled = true;
                repairPanel.hidden = true;
                controls.className = 'd-grid gap-2';
                controllerKeysTested = new Set();
                controllerTestActive = true;
                actions.test.disabled = true;
                renderAll();
                saveDraft();
                setStatus('Semua kabel terpasang. Coba SPACE, ←, dan → di layar atau keyboard.', 'good');
            }
        });
        renderWires();
    }

    function showNextHint() {
        if (hintCount >= caseConfig.hints.length) return;
        const text = root.querySelector('[data-hint-text]');
        text.hidden = false;
        text.textContent = `Petunjuk ${hintCount + 1}: ${caseConfig.hints[hintCount]}`;
        hintCount++;
        if (hintCount === 3) {
            hintCandidates = new Set(thirdHintCandidates());
            renderTimeline();
        }
        const button = root.querySelector('[data-hint]');
        button.textContent = hintCount < caseConfig.hints.length ? `💡 Minta Petunjuk (${hintCount}/3)` : '💡 Semua petunjuk terbuka';
        button.disabled = hintCount >= caseConfig.hints.length;
        saveDraft();
    }

    function renderAll(activeIndex = null) {
        renderTimeline(activeIndex);
        renderScene();
        root.querySelector('[data-step-counter]').textContent = `${engine.cursor} / ${engine.program.length}`;
    }

    function renderTimeline(activeIndex = null) {
        timeline.innerHTML = '';
        engine.program.forEach((command, index) => timeline.append(commandItem(index, commandLabel(command), activeIndex)));
        const finalMissing = caseConfig.type === 'delivery' && currentFinalBug(engine.program)?.kind === 'missing';
        if ((caseConfig.type === 'missing' && engine.program.length < caseConfig.solution.length) || finalMissing) {
            const missing = commandItem(engine.program.length, '+ COMMAND HILANG', activeIndex);
            missing.classList.add('is-missing');
            timeline.append(missing);
        }
    }

    function commandItem(index, label, activeIndex) {
        const item = document.createElement('li');
        item.className = 'detective-command';
        item.dataset.commandIndex = index;
        if (activeIndex === index) item.classList.add('is-active');
        if (suspects.has(index)) item.classList.add('is-suspect');
        if (hintCandidates.has(index)) item.classList.add('is-hint-candidate');
        item.innerHTML = `<b>${index + 1}</b><span>${label}</span><small>${caseConfig.type.toUpperCase()}</small>`;
        return item;
    }

    function renderTrace() {
        traceList.innerHTML = engine.trace.map((entry) => `<li>#${entry.index + 1} ${commandLabel(entry.command)}: ${entry.message}</li>`).join('');
    }

    function renderScene() {
        const scene = root.querySelector('[data-scene]');
        const state = engine.state;
        if (caseConfig.type === 'direction') {
            scene.innerHTML = `<div class="direction-scene">${Array.from({ length: 20 }, (_, index) => { const x = index % 5; const y = Math.floor(index / 5); return `<div class="direction-cell ${x === 2 && y === 1 ? 'is-target' : ''}">${x === 0 && y === 1 ? '🌳' : x === 2 && y === 1 ? '🚩' : ''}</div>`; }).join('')}<div class="scene-robot ${state.bonk ? 'is-bonk' : ''}" style="left:${((state.x + .5) / 5) * 100}%;top:${((state.y + .5) / 4) * 100}%">🤖<i>${directionIcon[state.direction]}</i>${state.bonk ? '<span class="scene-impact">💥 BONK!</span>' : ''}</div></div>`;
        } else if (caseConfig.type === 'extra') {
            scene.innerHTML = `<div class="drink-machine"><div class="drink-box">🤖🥤</div><div class="drink-glass"><div class="drink-liquid" style="--fill:${Math.min(100, state.level)}%"></div>${state.overflow ? '<span class="drink-overflow">💦😵</span>' : ''}</div></div>`;
        } else if (caseConfig.type === 'missing') {
            scene.innerHTML = `<div class="lift-building">${[3, 2, 1, 0].map((floor) => `<div class="lift-floor">LANTAI ${floor}</div>`).join('')}<div class="lift-car" style="bottom:${(state.floor * 4.7) + .45}rem">🤖 ${state.floor}</div></div>`;
        } else if (caseConfig.type === 'order') {
            const turning = state.moves.at(-1) === 'TURN';
            const jump = state.moves.at(-1) === 'JUMP' ? '-2.5rem' : '0';
            scene.innerHTML = `<div class="dance-stage"><div class="dance-target"><span>👏 TEPUK</span><span>🔄 PUTAR</span><span>⬆️ LOMPAT</span></div><div class="dance-bot ${turning ? 'is-turning' : ''}" style="--jump:${jump}">🤖</div><div class="dance-moves">${state.moves.map((move) => caseConfig.labels[move]).join(' → ') || 'Target gerakan ada di atas.'}</div></div>`;
        } else if (caseConfig.type === 'input') {
            scene.innerHTML = `<div class="controller-scene"><div><div class="keyboard-key ${state.listened ? 'is-pressed' : ''}">SPACE</div><p class="text-center fw-bold mt-3">Tekan SPACE untuk melompat</p></div><div class="game-character ${state.jumped ? 'is-jump' : ''}">🤖</div></div>`;
        } else if (caseConfig.type === 'traffic') {
            scene.innerHTML = `<div class="traffic-scene"><div class="traffic-ipo"><span><small>INPUT</small>🚦 Lampu Merah</span><i>→</i><span><small>PROCESS</small>👁️ Baca warna</span><i>→</i><span class="${state.car === 'moving' ? 'is-bug' : state.car === 'stopped' ? 'is-safe' : ''}"><small>OUTPUT</small>${state.car === 'stopped' ? '🛑 Mobil Berhenti' : '🚗 Mobil Jalan'}</span></div><div class="traffic-road"><span class="traffic-light">🔴</span><span class="traffic-car ${state.car === 'moving' ? 'is-moving' : state.car === 'stopped' ? 'is-stopped' : ''}">🚗</span><span class="traffic-crossing">▌ ▌ ▌</span></div></div>`;
        } else if (caseConfig.type === 'garden') {
            scene.innerHTML = `<div class="garden-case-scene"><div class="garden-cloud">${state.hasWater ? '🪣 Air dibawa' : state.dumped ? '💦 Air terbuang!' : state.watered ? '✨ Berhasil disiram!' : '🔎 Tanaman terdeteksi'}</div><div class="garden-bot">🤖${state.hasWater ? '<em>🪣</em>' : ''}</div><div class="garden-plant ${state.watered ? 'is-fresh' : 'is-dry'}">${state.watered ? '🌻' : '🥀'}<small>${state.watered ? 'SEGAR' : 'KERING'}</small></div>${state.dumped ? '<div class="garden-puddle">💦💦</div>' : ''}</div>`;
        } else if (caseConfig.type === 'door') {
            scene.innerHTML = `<div class="door-case-scene"><div class="door-person is-${state.personStage}">🧒</div><div class="auto-door ${state.door === 'open' ? 'is-open' : ''}"><span></span><span></span></div><div class="door-sensor">Sensor 🟢</div><strong>${state.personStage === 'passed' ? '✅ ORANG SUDAH LEWAT' : state.door === 'open' ? 'SILAKAN LEWAT' : 'PINTU TERTUTUP'}</strong></div>`;
        } else if (caseConfig.type === 'courier') {
            const positions = { agency: 10, warehouse: 48, home: 86 };
            scene.innerHTML = `<div class="courier-scene"><div class="courier-stop"><span>🏢</span><small>AGENSI</small></div><div class="courier-path"></div><div class="courier-stop"><span>🏭</span><small>GUDANG</small></div><div class="courier-path"></div><div class="courier-stop"><span>🏠</span><small>RUMAH</small></div><div class="courier-bot" style="left:${positions[state.location]}%">🤖${state.package ? '<em>📦</em>' : ''}</div>${state.delivered ? '<div class="courier-delivered">✅ PAKET DITERIMA</div>' : state.location === 'home' && !state.package ? '<div class="courier-empty">🤲 Paketnya mana?</div>' : ''}</div>`;
        } else if (caseConfig.type === 'mapping') {
            const mappings = Object.fromEntries(engine.program.map((command) => command.split('_TO_')));
            scene.innerHTML = `<div class="controller-lab"><div class="controller-map-preview">${['SPACE', 'LEFT', 'RIGHT'].map((input) => `<span class="${mappings[input] === ({ SPACE: 'JUMP', LEFT: 'LEFT', RIGHT: 'RIGHT' })[input] ? 'is-correct' : 'is-wrong'}"><b>${input}</b> ⟶ ${mappings[input]}</span>`).join('')}</div><div class="controller-test-stage"><div class="controller-test-keys">${[['SPACE', 'SPACE'], ['LEFT', '←'], ['RIGHT', '→']].map(([input, label]) => `<button type="button" data-controller-key="${input}" class="${controllerKeysTested.has(input) ? 'is-tested' : ''}" ${repaired ? '' : 'disabled'}>${label}${controllerKeysTested.has(input) ? ' ✓' : ''}</button>`).join('')}</div><div class="controller-test-bot ${controllerLastAction === 'JUMP' ? 'is-jump' : ''}" style="--controller-x:${controllerOffset * 3}rem">🤖</div><small>${!repaired ? 'Perbaiki kabel controller' : `Coba tombol: ${controllerKeysTested.size}/3`}</small></div></div>`;
        } else if (caseConfig.type === 'freezer') {
            scene.innerHTML = `<div class="detective-new-scene freezer-case"><div class="new-scene-display">🌡️ <b>${state.temperature === 'hot' ? 'PANAS' : '--'}</b></div><div class="new-scene-main ${state.ice === 'melting' ? 'is-trouble' : ''}">${state.ice === 'melting' ? '🫠💧' : '🧊'}</div><div class="new-scene-machine">${state.cooler === 'on' ? '❄️ PENDINGIN MENYALA' : state.cooler === 'off' ? '⚠️ PENDINGIN MATI' : '⚙️ MENUNGGU SENSOR'}</div></div>`;
        } else if (caseConfig.type === 'cinema') {
            scene.innerHTML = `<div class="detective-new-scene cinema-case ${state.lights === 'off' ? 'lights-off' : ''}"><div class="cinema-screen">${state.movie === 'playing' ? '🎞️ FILM DIPUTAR' : '📽️ LAYAR SIAP'}</div><div class="cinema-seats">💺 💺 💺</div><div class="new-scene-machine">${state.movie === 'playing' && !state.watchedInDark ? '💡 Terlalu terang!' : state.watchedInDark ? '✨ Film terlihat jelas' : 'Lampu masih menyala'}</div></div>`;
        } else if (caseConfig.type === 'copier') {
            scene.innerHTML = `<div class="detective-new-scene copier-case"><div class="new-scene-display">PESANAN: <b>2 LEMBAR</b></div><div class="new-scene-main">🖨️</div><div class="copy-stack">${Array.from({ length: state.copies }, (_, index) => `<span class="${index >= 2 ? 'is-extra' : ''}">📄</span>`).join('') || '<small>Belum mencetak</small>'}</div><div class="new-scene-machine">HASIL: ${state.copies} / 2 ${state.extra ? '⚠️ BERLEBIH' : ''}</div></div>`;
        } else if (caseConfig.type === 'drone') {
            const dronePosition = state.location === 'target' ? 72 : 12;
            scene.innerHTML = `<div class="detective-new-scene drone-case"><div class="drone-route"><span>🏠<small>BASE</small></span><i>············</i><span>🏔️<small>LOKASI</small></span><b style="left:${dronePosition}%">🚁${state.photo ? '<em>📸</em>' : ''}</b></div><div class="new-scene-machine">${state.sent ? '✅ FOTO TERKIRIM' : state.location === 'base' && !state.photo ? '🖼️ GALERI KOSONG' : state.photo ? '📷 FOTO TERSIMPAN' : 'MENCARI LOKASI'}</div></div>`;
        } else if (caseConfig.type === 'packing') {
            scene.innerHTML = `<div class="detective-new-scene packing-case"><div class="packing-line"><span class="${state.item ? 'is-done' : ''}">📦<small>AMBIL</small></span><i>→</i><span class="${state.packed ? 'is-done' : ''}">🎁<small>BUNGKUS</small></span><i>→</i><span class="${state.label ? 'is-done' : ''}">🏷️<small>LABEL</small></span><i>→</i><span class="${state.shipped ? 'is-done' : ''}">🚚<small>KIRIM</small></span></div><div class="new-scene-main">${state.damaged ? '💥📦' : state.shipped ? '✅🚚' : '🏭'}</div><div class="new-scene-machine">${state.damaged ? 'PAKET DIKIRIM TERLALU CEPAT!' : state.shipped ? 'PAKET AMAN TERKIRIM' : 'MESIN SEDANG BEKERJA'}</div></div>`;
        } else {
            scene.innerHTML = `<div class="delivery-scene">${Array.from({ length: 24 }, (_, index) => { const x = index % 6; const y = Math.floor(index / 6); return `<div class="delivery-cell ${x === 4 && y === 1 ? 'is-delivery' : ''}">${x === 1 && y === 1 && !state.package && !state.delivered ? '📦' : x === 4 && y === 1 ? '🏁<small>DELIVERY</small>' : ''}</div>`; }).join('')}<div class="scene-robot" style="left:${((state.x + .5) / 6) * 100}%;top:${((state.y + .5) / 4) * 100}%">🤖${state.package ? '<em>📦</em>' : ''}<i>${directionIcon[state.direction]}</i></div></div>`;
        }
    }

    function commandLabel(command) {
        return caseConfig.labels[command] || command.replace('_TO_', ' → ').replaceAll('_', ' ');
    }

    function setButtonsDisabled(value) {
        Object.values(actions).forEach((button) => { button.disabled = value; });
        if (!value) {
            actions.mark.disabled = !replayed;
            actions.repair.disabled = !canRepair();
            actions.test.disabled = !repaired || (caseConfig.type === 'mapping' && controllerTestActive && controllerKeysTested.size < 3);
        }
    }

    function canRepair() {
        if (['order', 'cinema', 'packing'].includes(caseConfig.type)) return suspects.size >= 2;
        if (caseConfig.type === 'mapping') return suspects.size >= 3;
        return suspects.size > 0;
    }

    function setStatus(message, tone = '') {
        status.className = `detective-status ${tone ? `is-${tone}` : ''}`;
        status.textContent = message;
    }

    function showSolved(stars) {
        const result = root.querySelector('[data-case-result]');
        result.querySelector('[data-solved-title]').textContent = caseConfig.title;
        result.querySelector('[data-solved-card]').textContent = caseConfig.concept.replace(' Bug', '');
        result.querySelector('[data-solved-label]').textContent = caseConfig.final ? 'CASE CLOSED!' : 'CASE SOLVED!';
        stars = Number(stars || 1);
        result.querySelector('[data-result-stars]').textContent = `${'⭐'.repeat(stars)}${'☆'.repeat(3 - stars)}`;
        result.querySelector('[data-unlocked-card]').hidden = caseConfig.final;
        result.querySelector('[data-solved-message]').textContent = caseConfig.final
            ? 'Semua bug besar sudah diperbaiki. Detective Notebook sudah terbuka.'
            : 'Case selesai. Kembali ke Papan Kasus dan tunggu admin mengizinkan case berikutnya.';
        const rankReward = result.querySelector('[data-rank-reward]');
        const ranks = { 3: '🕵️ Junior Detective', 6: '🔎 Bug Investigator', 9: '🎖️ Senior Bug Detective', 10: '🏆 Master Detective', 15: '🌟 Chief Bug Detective' };
        rankReward.hidden = !ranks[caseConfig.id];
        rankReward.textContent = ranks[caseConfig.id] ? `RANK BARU: ${ranks[caseConfig.id]}` : '';
        if (caseConfig.final) launchConfetti(result.querySelector('[data-confetti]'));
        const next = result.querySelector('[data-next-case]');
        next.href = caseConfig.final ? root.dataset.notebookUrl : root.dataset.mapUrl;
        next.textContent = caseConfig.final ? 'Buka Detective Notebook' : 'Kembali ke Papan Kasus';
        result.hidden = false;
    }

    function saveDraft() {
        saveCaseDraft(localProgress, caseConfig.id, {
            program: [...engine.program],
            suspects: [...allSuspects],
            hintCount,
            testAttempts,
            finalFixed: confirmedFinalBugs,
            stepEvidence,
            controllerKeysTested: [...controllerKeysTested],
            controllerTestActive,
        });
        setSaveIndicator(saveIndicator, 'device');
    }

    function restartCase() {
        if (busy) return;
        engine.restoreInitialProgram();
        suspects = new Set();
        allSuspects.clear();
        hintCount = 0;
        hintCandidates = new Set();
        testAttempts = 0;
        confirmedFinalBugs = 0;
        stepEvidence = !caseConfig.stepRequired;
        controllerKeysTested = new Set();
        controllerTestActive = false;
        controllerLastAction = null;
        controllerOffset = 0;
        if (caseConfig.type === 'mapping') actions.test.querySelector('span').textContent = 'Test Controller';
        replayed = false;
        repaired = false;
        markMode = false;
        swapMode = false;
        repairPanel.hidden = true;
        root.querySelector('[data-hint-text]').hidden = true;
        const hintButton = root.querySelector('[data-hint]');
        hintButton.disabled = false;
        hintButton.textContent = '💡 Minta Petunjuk';
        resetLocalCase(localProgress, caseConfig.id, caseConfig.initialProgram);
        setSaveIndicator(saveIndicator, 'device');
        renderAll();
        if (caseConfig.final) renderBossBugs();
        setButtonsDisabled(false);
        setStatus('Case diulang dari awal. Tekan REPLAY untuk menyelidiki lagi.', 'clue');
    }

    function activeSuspectIndexes() {
        return caseConfig.final ? [currentFinalBug(engine.program)?.index].filter(Number.isInteger) : caseConfig.suspectIndexes;
    }

    function thirdHintCandidates() {
        const target = activeSuspectIndexes()[0] ?? 0;
        return [...new Set([Math.max(0, target - 1), target, Math.min(engine.program.length, target + 1)])];
    }

    function renderBossBugs() {
        for (let number = 1; number <= 3; number++) {
            const badge = root.querySelector(`[data-bug-status="${number}"]`);
            badge.textContent = number <= confirmedFinalBugs ? `✅ BUG ${number}` : `🐞 BUG ${number}`;
            badge.classList.toggle('is-fixed', number <= confirmedFinalBugs);
        }
    }
}

function failureMessage(type, state) {
    if (type === 'direction') {
        if (state.reached) return 'Ko-Ki sudah sampai ke bendera.';
        return state.bonk ? 'Ko-Ki berbelok ke pohon. Investigate timeline dan tandai command penyebabnya.' : 'Ko-Ki belum sampai ke bendera.';
    }
    if (type === 'extra') return state.overflow ? 'Gelas meluap! Command tetap dijalankan walaupun gelas sudah penuh.' : 'Gelas terisi.';
    if (type === 'missing') return `Program berhenti di lantai ${state.floor}, belum mencapai lantai 3.`;
    if (type === 'order') return `Urutan hasil: ${state.moves.join(' → ')}. Bandingkan dengan target.`;
    if (type === 'traffic') return state.car === 'moving' ? 'Lampu merah, tetapi mobil tetap berjalan. Periksa OUTPUT.' : 'Amati hubungan INPUT, PROCESS, dan OUTPUT.';
    if (type === 'garden') return state.dumped ? 'Air terbuang dan tanaman masih kering. Periksa aksi terakhir.' : 'Tanaman belum kembali segar.';
    if (type === 'door') return state.door === 'open' ? 'Pintu membuka lagi. Gunakan STEP untuk menemukan aksi tambahan.' : 'Amati setiap perubahan pintu.';
    if (type === 'courier') return state.location === 'home' && !state.delivered ? 'Kurir sampai tanpa paket. Cari langkah yang hilang di tengah.' : 'Paket belum terkirim.';
    if (type === 'mapping') return 'Gerakan tidak sesuai tombol. Tandai ketiga mapping lalu perbaiki kabelnya.';
    if (type === 'freezer') return state.ice === 'melting' ? 'Es mencair karena pendingin dimatikan. Periksa output terakhir.' : 'Periksa hubungan sensor suhu dan pendingin.';
    if (type === 'cinema') return state.movie === 'playing' && !state.watchedInDark ? 'Film diputar saat lampu masih terang. Periksa urutan persiapannya.' : 'Urutan kegiatan bioskop belum tepat.';
    if (type === 'copier') return state.extra ? `Mesin menghasilkan ${state.copies} lembar, padahal hanya diminta 2.` : 'Hitung setiap lembar yang keluar.';
    if (type === 'drone') return state.location === 'base' && !state.sent ? 'Drone sudah pulang, tetapi galerinya kosong. Cari langkah yang hilang.' : 'Foto belum berhasil dikirim.';
    if (type === 'packing') return state.damaged ? 'Paket dikirim sebelum siap. Bandingkan urutan bungkus dan kirim.' : 'Urutan mesin paket belum aman.';
    if (type === 'delivery') return state.package ? 'Ko-Ki membawa paket, tetapi belum menyerahkannya tepat di Delivery.' : 'Paket belum berhasil dikirim. Periksa jejak dari kejadian aneh pertama.';

    return state.jumped ? 'Karakter melompat.' : 'SPACE ditekan, tetapi program mendengarkan ARROW UP.';
}

function wait(milliseconds) { return new Promise((resolve) => window.setTimeout(resolve, milliseconds)); }

function currentFinalBug(program) {
    if (program[3] !== 'TURN_RIGHT') return { kind: 'direction', index: 3 };
    const deliveryIndex = program.indexOf('DELIVERY');
    const end = deliveryIndex >= 0 ? deliveryIndex : program.length;
    const movesAfterTurn = program.slice(4, end).map((command, offset) => ({ command, index: offset + 4 })).filter((item) => item.command === 'MOVE_FORWARD');
    if (movesAfterTurn.length > 3) return { kind: 'extra', index: movesAfterTurn.at(-1).index };
    if (deliveryIndex < 0) return { kind: 'missing', index: program.length };

    return null;
}

function countFixedFinalBugs(program) {
    if (program[3] !== 'TURN_RIGHT') return 0;
    if (currentFinalBug(program)?.kind === 'extra') return 1;
    if (!program.includes('DELIVERY')) return 2;

    return 3;
}

function launchConfetti(container) {
    const colors = ['#3157d5', '#ffd65c', '#27b3c2', '#ef476f', '#8b5cf6'];
    for (let index = 0; index < 42; index++) {
        const piece = document.createElement('i');
        piece.style.setProperty('--x', `${Math.random() * 100}vw`);
        piece.style.setProperty('--delay', `${Math.random() * .5}s`);
        piece.style.setProperty('--color', colors[index % colors.length]);
        container.append(piece);
    }
    window.setTimeout(() => { container.innerHTML = ''; }, 2600);
}

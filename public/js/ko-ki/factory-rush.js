import { facingStation, ingredientCapacity, nextPosition } from './factory-rush-engine.js';

const root = document.querySelector('[data-factory-rush]');

if (root) {
    const config = JSON.parse(root.dataset.config);
    const level = Number(root.dataset.level);
    const levelDefinition = config.levels.find((item) => item.number === level);
    const systems = new Set(levelDefinition.systems);
    let state = JSON.parse(root.dataset.state);
    let runtime = state.level_progress?.[level]?.runtime ?? null;
    let busy = false;
    let collectingPowerup = false;
    let timerHandled = false;
    let requestQueue = Promise.resolve();
    let lastMoveAt = 0;
    let conveyorIndex = 0;
    let conveyorItem = systems.has('conveyor') ? { type: 'apple', x: 2, y: 0 } : null;
    let cleaner = systems.has('obstacle') ? { x: 2, y: 4, direction: 1 } : null;
    let stunnedUntil = 0;
    let audioContext = null;

    const arena = root.querySelector('[data-arena]');
    const playerElement = root.querySelector('[data-player]');
    const heldIcons = root.querySelector('[data-held-icons]');
    const inventoryElement = root.querySelector('[data-inventory]');
    const inventoryLabel = root.querySelector('[data-inventory-label]');
    const directionElement = root.querySelector('[data-direction]');
    const actionHint = root.querySelector('[data-action-hint]');
    const blenderSlots = root.querySelector('[data-blender-slots]');
    const blenderStatus = root.querySelector('[data-blender-status]');
    const blenderFill = root.querySelector('[data-blender-fill]');
    const message = root.querySelector('[data-message]');
    const errorBox = root.querySelector('[data-factory-error]');
    const saveState = root.querySelector('[data-save-state]');
    const celebration = root.querySelector('[data-celebration]');
    const startOverlay = root.querySelector('[data-start-overlay]');
    const resultOverlay = root.querySelector('[data-result-overlay]');
    const trainingReplay = root.querySelector('[data-training-replay]');
    const pauseOverlay = root.querySelector('[data-pause-overlay]');
    const conveyorElement = root.querySelector('[data-conveyor-item]');
    const cleanerElement = root.querySelector('[data-cleaner]');
    const powerupElement = root.querySelector('[data-powerup]');
    const blocked = config.stations
        .filter((station) => !(systems.has('conveyor') && station.type === 'ingredient'))
        .filter((station) => station.type !== 'fridge' || systems.has('double_order'))
        .map(({ x, y }) => ({ x, y }));
    if (systems.has('conveyor')) {
        for (let x = 2; x <= 7; x++) blocked.push({ x, y: 0 });
    }

    const labels = { ...config.ingredients, ...config.products };
    const directionIcons = { north: '▲', east: '▶', south: '▼', west: '◀' };
    const conveyorSequence = ['apple', 'banana', 'water', 'banana', 'apple', 'water'];

    function tone(frequency, duration = 0.1, type = 'sine', delay = 0) {
        try {
            audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            const startsAt = audioContext.currentTime + delay;
            oscillator.type = type;
            oscillator.frequency.setValueAtTime(frequency, startsAt);
            gain.gain.setValueAtTime(0.0001, startsAt);
            gain.gain.exponentialRampToValueAtTime(0.12, startsAt + 0.015);
            gain.gain.exponentialRampToValueAtTime(0.0001, startsAt + duration);
            oscillator.connect(gain).connect(audioContext.destination);
            oscillator.start(startsAt);
            oscillator.stop(startsAt + duration + 0.02);
        } catch {
            // Permainan tetap berjalan jika audio browser tidak tersedia.
        }
    }

    function successSound() {
        tone(523, 0.12);
        tone(659, 0.12, 'sine', 0.1);
        tone(784, 0.18, 'sine', 0.2);
    }

    function snapshot() {
        return {
            level,
            player: runtime.player,
            inventory: runtime.inventory,
            blender: runtime.blender,
        };
    }

    function availableStations() {
        return config.stations
            .filter((station) => !systems.has('conveyor') || station.type !== 'ingredient')
            .filter((station) => station.type !== 'fridge' || systems.has('double_order'));
    }

    function orderStatuses() {
        return runtime.active_orders.map((order) => {
            if (runtime.fridge?.slots.includes(order)) return 'stored';
            if (runtime.inventory.includes(order)) return 'ready';
            return 'making';
        });
    }

    function doubleOrderReady() {
        if (!systems.has('double_order') || runtime.fridge.slots.some(Boolean)) return false;
        return [...runtime.active_orders].sort().join('|') === [...runtime.inventory].sort().join('|');
    }

    async function send(payload) {
        saveState.textContent = 'Menyimpan…';
        errorBox.hidden = true;
        const response = await fetch(root.dataset.saveUrl, {
            method: 'PATCH', credentials: 'same-origin',
            headers: {
                Accept: 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (response.status === 409) {
            window.location.assign(root.dataset.entryUrl);
            return null;
        }
        if (!response.ok) {
            const detail = Object.values(data.errors ?? {}).flat()[0];
            throw new Error(detail ?? data.message ?? 'Progress belum dapat disimpan.');
        }
        saveState.textContent = 'Progress tersimpan otomatis.';
        return data;
    }

    function save(action, extra = {}, includeSnapshot = true) {
        const payload = { action, level, ...(includeSnapshot && runtime ? snapshot() : {}), ...extra };
        const request = requestQueue.then(() => send(payload), () => send(payload));
        requestQueue = request.catch(() => null);
        return request;
    }

    function markLocalChange() {
        saveState.textContent = 'Akan disimpan pada checkpoint berikutnya.';
    }

    function showError(error) {
        errorBox.textContent = error.message;
        errorBox.hidden = false;
        saveState.textContent = 'Progress belum tersimpan.';
    }

    function setMessage(text, tone = '') {
        message.className = `factory-message ${tone ? `is-${tone}` : ''}`;
        message.textContent = text;
    }

    function isEffectActive(type) {
        const clock = runtime?.paused && runtime.paused_at ? Date.parse(runtime.paused_at) : Date.now();
        return runtime?.effects?.[type] && Date.parse(runtime.effects[type]) > clock;
    }

    function bagCapacity() {
        return ingredientCapacity(isEffectActive('big_bag'));
    }

    function renderOrders() {
        const container = root.querySelector('[data-orders]');
        container.innerHTML = '';
        for (const [index, productId] of runtime.active_orders.entries()) {
            const product = config.products[productId];
            const card = document.createElement('div');
            card.className = 'factory-order';
            card.dataset.orderSlot = index;
            const status = orderStatuses()[index];
            const statusLabels = { making: '⚙ MAKING', stored: '❄ STORED', ready: '✓ READY' };
            card.classList.add(`is-${status}`);
            card.innerHTML = `<small>ORDER ${runtime.active_orders.length > 1 ? String.fromCharCode(65 + index) : ''}</small><strong><span>${product.icon}</span> ${product.label}</strong><em>${statusLabels[status]}</em>`;
            container.append(card);
        }
        const ready = doubleOrderReady();
        root.classList.toggle('is-double-ready', ready);
        root.querySelector('[data-station="delivery"]')?.classList.toggle('is-ready', ready);
    }

    function renderStats() {
        const score = Number(runtime.score ?? 0);
        const combo = Number(runtime.combo ?? 0);
        const comboBox = root.querySelector('.factory-combo');
        root.querySelector('[data-score]').textContent = systems.has('double_order')
            ? `${score} / ${levelDefinition.target_score}`
            : score;
        root.querySelector('[data-combo]').textContent = `×${combo}`;
        comboBox?.classList.toggle('is-hot', level === 5 && combo >= 2);

        if (systems.has('double_order')) {
            const batches = Number(runtime.batch_count ?? 0);
            root.querySelector('[data-score-progress]').textContent = `${score} / ${levelDefinition.target_score}`;
            root.querySelector('[data-batches-done]').textContent = batches;
            root.querySelector('[data-score-target-progress]').style.width = `${Math.min(100, score / levelDefinition.target_score * 100)}%`;
            root.querySelector('[data-batch-target-progress]').style.width = `${Math.min(100, batches / levelDefinition.minimum_batches * 100)}%`;
            return;
        }

        root.querySelector('[data-orders-done]').textContent = runtime.orders_completed;
        root.querySelector('[data-target-progress]').style.width = `${Math.min(100, runtime.orders_completed / levelDefinition.target * 100)}%`;
    }

    function renderPlayer() {
        playerElement.style.setProperty('--player-x', runtime.player.x);
        playerElement.style.setProperty('--player-y', runtime.player.y);
        directionElement.textContent = directionIcons[runtime.player.direction];
        const items = runtime.inventory.map((item) => labels[item]);
        heldIcons.hidden = items.length === 0;
        heldIcons.textContent = items.map((item) => item.icon).join(' ');
        inventoryElement.innerHTML = '';
        const capacity = Math.max(bagCapacity(), runtime.inventory.length);
        for (let index = 0; index < capacity; index++) {
            const slot = document.createElement('span');
            slot.textContent = items[index]?.icon ?? '＋';
            inventoryElement.append(slot);
        }
        inventoryLabel.textContent = items.length ? items.map((item) => item.label).join(' + ') : 'Kosong';

        const station = facingStation(runtime.player, availableStations());
        const facesConveyor = systems.has('conveyor') && runtime.player.direction === 'north' && runtime.player.y === 1 && conveyorItem?.x === runtime.player.x;
        actionHint.hidden = !(station || facesConveyor);
        if (!actionHint.hidden) {
            actionHint.style.setProperty('--hint-x', runtime.player.x);
            actionHint.style.setProperty('--hint-y', runtime.player.y);
        }
    }

    function renderBlender() {
        const contents = runtime.blender.status === 'ready' && runtime.blender.result
            ? [runtime.blender.result] : runtime.blender.ingredients;
        blenderSlots.innerHTML = '';
        for (let index = 0; index < 3; index++) {
            const item = contents[index] ? labels[contents[index]] : null;
            const slot = document.createElement('span');
            slot.textContent = item?.icon ?? '＋';
            blenderSlots.append(slot);
        }
        const statusLabels = { empty: 'Kosong', loaded: 'Siap diproses', blending: 'Memproses…', ready: 'Hasil siap' };
        blenderStatus.textContent = statusLabels[runtime.blender.status];
        if (blenderFill) {
            blenderFill.textContent = contents.map((item) => labels[item]?.icon ?? '').join('');
            blenderFill.closest('.factory-station')?.classList.toggle('is-blending', runtime.blender.status === 'blending');
        }
    }

    function renderFridge() {
        if (!systems.has('double_order')) return;
        const slots = runtime.fridge?.slots ?? [null, null];
        root.querySelectorAll('[data-fridge-slots] span').forEach((slot, index) => {
            slot.textContent = slots[index] ? labels[slots[index]].icon : '＋';
            slot.classList.toggle('is-filled', Boolean(slots[index]));
        });
        root.querySelectorAll('[data-fridge-mini-slot]').forEach((slot) => {
            const item = slots[Number(slot.dataset.fridgeMiniSlot)];
            slot.textContent = item ? labels[item].icon : '＋';
        });
    }

    function renderConveyor() {
        if (!conveyorElement) return;
        if (!conveyorItem) {
            conveyorElement.hidden = true;
            return;
        }
        conveyorElement.hidden = false;
        conveyorElement.textContent = labels[conveyorItem.type].icon;
        conveyorElement.style.setProperty('--conveyor-x', conveyorItem.x);
    }

    function renderCleaner() {
        if (!cleanerElement || !cleaner) return;
        cleanerElement.style.setProperty('--cleaner-x', cleaner.x);
        cleanerElement.style.setProperty('--cleaner-y', cleaner.y);
    }

    function renderPowerup() {
        if (!powerupElement || !runtime.powerup) return;
        const available = Date.parse(runtime.powerup.available_at) <= Date.now();
        powerupElement.hidden = !available;
        powerupElement.style.setProperty('--powerup-x', runtime.powerup.x);
        powerupElement.style.setProperty('--powerup-y', runtime.powerup.y);
        powerupElement.textContent = config.powerups[runtime.powerup.type].icon;
        powerupElement.title = config.powerups[runtime.powerup.type].label;
    }

    function renderEffects() {
        const container = root.querySelector('[data-effects]');
        container.innerHTML = '';
        for (const type of ['speed_boost', 'big_bag']) {
            if (!isEffectActive(type)) continue;
            const clock = runtime.paused && runtime.paused_at ? Date.parse(runtime.paused_at) : Date.now();
            const seconds = Math.max(0, Math.ceil((Date.parse(runtime.effects[type]) - clock) / 1000));
            const badge = document.createElement('span');
            badge.className = 'factory-effect-badge';
            badge.textContent = `${config.powerups[type].icon} ${config.powerups[type].label} ${seconds}s`;
            container.append(badge);
        }
    }

    function render() {
        if (!runtime) return;
        renderOrders(); renderStats(); renderPlayer(); renderBlender(); renderFridge();
        renderConveyor(); renderCleaner(); renderPowerup(); renderEffects();
    }

    async function startLevel() {
        busy = true;
        try {
            const data = await save('factory_start_level', {}, false);
            state = data.factory_state;
            runtime = state.level_progress[level].runtime;
            timerHandled = false;
            conveyorIndex = 0;
            conveyorItem = systems.has('conveyor') ? { type: 'apple', x: 2, y: 0 } : null;
            cleaner = systems.has('obstacle') ? { x: 2, y: 4, direction: 1 } : null;
            startOverlay.hidden = true;
            resultOverlay.hidden = true;
            pauseOverlay.hidden = true;
            setMessage(level === 1 ? 'Mulai dengan mengambil Apel. Bergerak mendekat lalu tekan SPACE.' : 'Shift dimulai! Perhatikan order di bagian atas.', 'success');
            render();
        } catch (error) {
            showError(error);
        } finally {
            busy = false;
        }
    }

    function move(direction) {
        if (!runtime?.active || runtime.paused || busy || Date.now() < stunnedUntil) return;
        const cooldown = isEffectActive('speed_boost') ? 45 : 110;
        if (Date.now() - lastMoveAt < cooldown) return;
        lastMoveAt = Date.now();
        runtime.player.direction = direction;
        const position = nextPosition(runtime.player, direction, config.grid, blocked);
        runtime.player.x = position.x;
        runtime.player.y = position.y;
        playerElement.classList.remove('is-walking');
        void playerElement.offsetWidth;
        playerElement.classList.add('is-walking');
        checkCleanerCollision();
        checkPowerupCollision();
        renderPlayer();
    }

    async function takeIngredient(id, sourceLabel) {
        if (runtime.inventory.length >= bagCapacity()) {
            setMessage('Tas Ko-Ki penuh. Masukkan bahan ke blender dahulu.', 'warning');
            return;
        }
        runtime.inventory.push(id);
        tone(520, 0.09);
        setMessage(`${labels[id].label} diambil dari ${sourceLabel}.`, 'success');
        render();
        markLocalChange();
    }

    async function useBlender() {
        if (runtime.blender.status === 'ready') {
            const resultCapacity = systems.has('double_order') ? 2 : bagCapacity();
            if (runtime.inventory.length >= resultCapacity) {
                setMessage('Tas penuh. Kosongkan satu tempat sebelum mengambil hasil.', 'warning');
                return;
            }
            const resultId = runtime.blender.result;
            runtime.inventory.push(resultId);
            runtime.blender = { ingredients: [], status: 'empty', result: null };
            const product = labels[resultId];
            setMessage(`${product.label} diambil. Antar ke delivery!`, resultId === 'mystery' ? 'danger' : 'success');
            render();
            markLocalChange();
            return;
        }

        const ingredientIndex = runtime.inventory.findIndex((item) => config.ingredients[item]);
        if (ingredientIndex >= 0) {
            if (runtime.blender.ingredients.length >= 4) {
                setMessage('Blender sudah terlalu penuh. Jalankan sekarang.', 'warning');
                return;
            }
            const [ingredient] = runtime.inventory.splice(ingredientIndex, 1);
            runtime.blender.ingredients.push(ingredient);
            runtime.blender.status = 'loaded';
            setMessage(`${labels[ingredient].label} masuk ke blender.`, 'success');
            render();
            markLocalChange();
            return;
        }
        if (runtime.inventory.length) {
            setMessage('Jus yang sudah jadi tidak dapat dimasukkan kembali ke blender.', 'warning');
            return;
        }
        if (runtime.blender.ingredients.length === 0) {
            setMessage('Blender masih kosong. Ambil bahan dahulu.', 'warning');
            return;
        }

        runtime.blender.status = 'blending';
        tone(180, 0.35, 'sawtooth');
        render();
        setMessage('Blender bekerja… Bzzzz!', 'working');
        const data = await save('factory_blend', { ingredients: [...runtime.blender.ingredients] });
        if (data.result.time_expired) return showTimeExpired();
        await new Promise((resolve) => window.setTimeout(resolve, 700));
        state = data.factory_state;
        runtime = state.level_progress[level].runtime;
        render();
        const isMystery = data.result.output === 'mystery';
        setMessage(isMystery ? 'Resep salah! Ambil Gumpalan Misterius lalu buang.' : `${data.result.output_label} siap diambil!`, isMystery ? 'danger' : 'success');
    }

    async function useDelivery() {
        if (systems.has('double_order')) {
            return useDoubleDelivery();
        }
        const product = runtime.inventory.find((item) => config.products[item] && item !== 'mystery');
        if (!product) {
            setMessage(runtime.inventory.includes('mystery') ? 'Gumpalan Misterius harus dibuang.' : 'Delivery menunggu jus sesuai order.', 'warning');
            return;
        }
        const data = await save('factory_deliver', { item: product });
        if (data.result.time_expired) return showTimeExpired();
        state = data.factory_state;
        runtime = state.level_progress[level].runtime;
        render();
        if (!data.result.correct) {
            setMessage('Jus ini belum cocok dengan order yang aktif.', 'warning');
            return;
        }
        celebration.querySelector('span').textContent = `+${data.result.points}`;
        successSound();
        celebration.hidden = false;
        celebration.classList.add('is-showing');
        setMessage(data.result.combo_bonus ? `Order terkirim! Bonus combo +${data.result.combo_bonus}.` : 'Order berhasil dikirim!', 'success');
        window.setTimeout(() => { celebration.hidden = true; celebration.classList.remove('is-showing'); }, 900);
        if (data.result.level_completed) showLevelComplete();
    }

    async function useDoubleDelivery() {
        if (!doubleOrderReady()) {
            setMessage(
                runtime.fridge.slots.some(Boolean)
                    ? 'Pesanannya harus dikirim bersama! Ambil jus yang tersimpan dari kulkas.'
                    : 'Pesanannya harus dikirim bersama! Simpan dulu pesanan yang sudah selesai di kulkas.',
                'warning',
            );
            tone(150, 0.16, 'square');
            return;
        }

        const data = await save('factory_double_deliver');
        if (data.result.time_expired) return showTimeExpired();
        state = data.factory_state;
        runtime = state.level_progress[level].runtime;
        render();
        celebration.querySelector('span').textContent = `+${data.result.delivery_points}`;
        celebration.querySelector('strong').textContent = level === 5 && data.result.combo > 1
            ? 'COMBO DELIVERY!'
            : 'DOUBLE DELIVERY!';
        celebration.hidden = false;
        celebration.classList.add('is-showing', 'is-double');
        successSound();
        tone(988, 0.2, 'sine', 0.28);
        const rewards = ['DOUBLE DELIVERY! +300'];
        if (data.result.perfect_bonus) rewards.push(`PERFECT BATCH +${data.result.perfect_bonus}`);
        if (data.result.combo_bonus) rewards.push(`BONUS COMBO +${data.result.combo_bonus}`);
        setMessage(rewards.join(' · '), 'success');
        window.setTimeout(() => {
            celebration.hidden = true;
            celebration.classList.remove('is-showing', 'is-double');
        }, 1100);
        if (data.result.level_completed) showLevelComplete();
    }

    async function useFridge() {
        const slots = runtime.fridge.slots;
        const storedSlot = slots.findIndex(Boolean);
        const carriedProduct = runtime.inventory.find((item) => runtime.active_orders.includes(item));
        const shouldTake = storedSlot >= 0 && (!carriedProduct || runtime.inventory.length < 2);

        if (shouldTake) {
            const data = await save('factory_fridge_take', { fridge_slot: storedSlot });
            state = data.factory_state;
            runtime = state.level_progress[level].runtime;
            render();
            root.querySelector('[data-station="fridge"]')?.classList.add('is-cold-flash');
            window.setTimeout(() => root.querySelector('[data-station="fridge"]')?.classList.remove('is-cold-flash'), 450);
            setMessage(`${labels[data.result.taken].label} diambil dari kulkas.`, 'success');
            tone(620, 0.12);
            return;
        }

        if (!carriedProduct) {
            setMessage('Bawa jus yang sesuai order untuk disimpan di kulkas.', 'warning');
            return;
        }
        const data = await save('factory_fridge_store', { item: carriedProduct });
        state = data.factory_state;
        runtime = state.level_progress[level].runtime;
        render();
        root.querySelector('[data-station="fridge"]')?.classList.add('is-cold-flash');
        window.setTimeout(() => root.querySelector('[data-station="fridge"]')?.classList.remove('is-cold-flash'), 450);
        setMessage(`❄ ${labels[data.result.stored].label} tersimpan. Sekarang buat order berikutnya.`, 'success');
        tone(740, 0.12); tone(920, 0.15, 'sine', 0.08);
    }

    async function useTrash() {
        if (!runtime.inventory.length) return setMessage('Tas Ko-Ki kosong.');
        const item = runtime.inventory.includes('mystery') ? 'mystery' : runtime.inventory[0];
        const data = await save('factory_discard', { item });
        if (data.result.time_expired) return showTimeExpired();
        state = data.factory_state;
        runtime = state.level_progress[level].runtime;
        render();
        setMessage(`${labels[item].label} sudah dibuang.`, 'success');
    }

    async function interact() {
        if (!runtime?.active || runtime.paused || busy || Date.now() < stunnedUntil) return;
        const facesConveyor = systems.has('conveyor') && runtime.player.direction === 'north' && runtime.player.y === 1 && conveyorItem?.x === runtime.player.x;
        const station = facingStation(runtime.player, availableStations());
        if (!station && !facesConveyor) return setMessage('Hadapkan Ko-Ki ke bahan atau mesin, lalu tekan SPACE.');

        busy = true;
        try {
            if (facesConveyor) {
                const ingredient = conveyorItem.type;
                await takeIngredient(ingredient, 'conveyor');
                conveyorItem = null;
                window.setTimeout(spawnConveyorItem, 500);
            } else if (station.type === 'ingredient') await takeIngredient(station.id, station.label);
            else if (station.type === 'blender') await useBlender();
            else if (station.type === 'delivery') await useDelivery();
            else if (station.type === 'trash') await useTrash();
            else if (station.type === 'fridge') await useFridge();
        } catch (error) {
            showError(error);
        } finally {
            busy = false;
        }
    }

    function spawnConveyorItem() {
        if (!runtime?.active || runtime.paused || !systems.has('conveyor') || conveyorItem) return;
        conveyorItem = { type: conveyorSequence[conveyorIndex++ % conveyorSequence.length], x: 2, y: 0 };
        renderConveyor();
    }

    function tickConveyor() {
        if (!runtime?.active || runtime.paused || !systems.has('conveyor')) return;
        if (!conveyorItem) return spawnConveyorItem();
        conveyorItem.x++;
        if (conveyorItem.x > 7) conveyorItem = null;
        renderConveyor(); renderPlayer();
    }

    function tickCleaner() {
        if (!runtime?.active || runtime.paused || !cleaner) return;
        cleaner.x += cleaner.direction;
        if (cleaner.x >= 7 || cleaner.x <= 2) cleaner.direction *= -1;
        renderCleaner();
        checkCleanerCollision();
    }

    function checkCleanerCollision() {
        if (!cleaner || runtime.player.x !== cleaner.x || runtime.player.y !== cleaner.y || Date.now() < stunnedUntil) return;
        stunnedUntil = Date.now() + 900;
        tone(120, 0.22, 'square');
        playerElement.classList.add('is-spinning');
        setMessage('Wusss! Robot pembersih membuat Ko-Ki berputar sebentar.', 'warning');
        window.setTimeout(() => playerElement.classList.remove('is-spinning'), 900);
    }

    async function checkPowerupCollision() {
        if (!runtime?.powerup || collectingPowerup || Date.parse(runtime.powerup.available_at) > Date.now()) return;
        if (runtime.player.x !== runtime.powerup.x || runtime.player.y !== runtime.powerup.y) return;
        collectingPowerup = true;
        try {
            const data = await save('factory_powerup', { powerup: runtime.powerup.type });
            if (data.result.time_expired) return showTimeExpired();
            state = data.factory_state;
            runtime = state.level_progress[level].runtime;
            tone(880, 0.12);
            tone(1100, 0.16, 'sine', 0.1);
            setMessage(`${data.result.label} aktif!`, 'success');
            render();
        } catch (error) {
            showError(error);
        } finally {
            collectingPowerup = false;
        }
    }

    function showLevelComplete() {
        if (level === 1 && !state.training_replay_seen) {
            trainingReplay.hidden = false;
            successSound();
            return;
        }
        root.querySelector('[data-result-icon]').textContent = '🎉';
        root.querySelector('[data-result-title]').textContent = 'Level Selesai!';
        root.querySelector('[data-result-message]').textContent = 'Target berhasil dicapai. Tunggu admin mengizinkan level berikutnya.';
        if (level === 5) {
            root.querySelector('[data-result-message]').textContent = 'Delapan order selesai. Final Reveal sudah terbuka!';
            const primaryLink = resultOverlay.querySelector('a');
            primaryLink.href = root.dataset.finaleUrl;
            primaryLink.textContent = 'Buka Final Reveal';
        }
        root.querySelector('[data-result-score]').textContent = runtime.score;
        root.querySelector('[data-result-combo]').textContent = `×${runtime.best_combo}`;
        resultOverlay.hidden = false;
    }

    async function finishTrainingReplay() {
        busy = true;
        try {
            await save('factory_training_replay', {}, false);
            window.location.assign(root.dataset.mapUrl);
        } catch (error) {
            showError(error);
            busy = false;
        }
    }

    async function pauseGame() {
        if (!runtime?.active || runtime.paused || busy) return;
        busy = true;
        try {
            const data = await save('factory_pause');
            state = data.factory_state;
            runtime = state.level_progress[level].runtime;
            pauseOverlay.hidden = false;
            setMessage('Permainan dijeda.');
        } catch (error) {
            showError(error);
        } finally {
            busy = false;
        }
    }

    async function resumeGame() {
        if (!runtime?.paused || busy) return;
        busy = true;
        try {
            const data = await save('factory_resume');
            state = data.factory_state;
            runtime = state.level_progress[level].runtime;
            pauseOverlay.hidden = true;
            setMessage('Shift dilanjutkan!', 'success');
            render();
        } catch (error) {
            showError(error);
        } finally {
            busy = false;
        }
    }

    function showTimeExpired() {
        if (timerHandled) return;
        timerHandled = true;
        if (runtime) runtime.active = false;
        root.querySelector('[data-result-icon]').textContent = '⏰';
        root.querySelector('[data-result-title]').textContent = 'Waktu Habis';
        root.querySelector('[data-result-message]').textContent = 'Tidak apa-apa. Progress terbaikmu tetap tersimpan. Coba shift ini lagi!';
        root.querySelector('[data-result-score]').textContent = runtime?.score ?? 0;
        root.querySelector('[data-result-combo]').textContent = `×${runtime?.best_combo ?? 0}`;
        resultOverlay.hidden = false;
    }

    function tickTimer() {
        renderEffects(); renderPowerup(); renderPlayer();
        const timer = root.querySelector('[data-timer]');
        if (!timer || !runtime?.active || !runtime.ends_at) return;
        const clock = runtime.paused && runtime.paused_at ? Date.parse(runtime.paused_at) : Date.now();
        const seconds = Math.max(0, Math.ceil((Date.parse(runtime.ends_at) - clock) / 1000));
        timer.querySelector('strong').textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        timer.classList.toggle('is-urgent', seconds <= 30);
        if (seconds === 0 && !timerHandled) {
            save('factory_sync').then((data) => {
                if (data?.result.time_expired) showTimeExpired();
            }).catch(showError);
        }
    }

    const keyDirections = {
        ArrowUp: 'north', w: 'north', W: 'north', ArrowRight: 'east', d: 'east', D: 'east',
        ArrowDown: 'south', s: 'south', S: 'south', ArrowLeft: 'west', a: 'west', A: 'west',
    };
    document.addEventListener('keydown', (event) => {
        if (keyDirections[event.key]) {
            event.preventDefault(); move(keyDirections[event.key]);
        } else if (event.key === ' ' && !event.repeat) {
            event.preventDefault(); interact();
        }
    });
    root.querySelectorAll('[data-move]').forEach((button) => button.addEventListener('click', () => move(button.dataset.move)));
    root.querySelector('[data-interact]').addEventListener('click', interact);
    root.querySelector('[data-start-level]').addEventListener('click', startLevel);
    root.querySelector('[data-replay-level]').addEventListener('click', startLevel);
    root.querySelector('[data-finish-training-replay]').addEventListener('click', finishTrainingReplay);
    root.querySelector('[data-pause]').addEventListener('click', pauseGame);
    root.querySelector('[data-resume]').addEventListener('click', resumeGame);
    root.querySelector('[data-restart]').addEventListener('click', startLevel);

    window.setInterval(tickConveyor, 850);
    window.setInterval(tickCleaner, 620);
    window.setInterval(tickTimer, 250);
    if (runtime?.active) {
        startOverlay.hidden = true;
        setMessage('Shift dilanjutkan. Progress terakhir sudah dimuat.', 'success');
        pauseOverlay.hidden = !runtime.paused;
        render(); tickTimer();
    }
    arena.focus?.();
}

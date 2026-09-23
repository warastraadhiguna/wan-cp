import { CreatorGameEngine, CREATOR_OPTIONS, JUMP_DURATION_MS, COLLECTIBLE_RESPAWN_MS, controlMappingValid, normalizeCreatorConfig } from './creator-engine.js?v=dragon-intensity-12';
import { PLAYTEST_CHECK_KEYS, emptyPlaytestChecks, nextDebugStage, playtestPassed, updatePlaytestChecks } from './creator-playtest.js';
import { FRIEND_FEEDBACK, addFriendFeedback, creatorCheckpoint, restoreCreatorDraft } from './creator-persistence.js';
import { CREATOR_CAMPAIGN_LEVELS, campaignGameConfig, campaignLevel, completeCampaignLevel, normalizeCampaignProgress } from './creator-campaign.js?v=dragon-intensity-12';

const root = document.querySelector('[data-creator-lab]');
if (root) boot();

function boot() {
    const storageKey = `wan-ko-ki.creator.progress.${root.dataset.progressId}`;
    const serverState = JSON.parse(root.dataset.state || '{}');
    const serverAdventure = JSON.parse(root.dataset.adventureState || '{}');
    let stored = null;
    try { stored = JSON.parse(localStorage.getItem(storageKey) || 'null'); } catch { /* Abaikan data lama yang rusak. */ }
    const restored = restoreCreatorDraft(serverState, stored);
    let config = normalizeCreatorConfig(restored.config);
    ['moving_obstacle', 'moving_obstacles_together', 'moving_world', 'aerial_obstacles', 'dragon_boss']
        .forEach((key) => delete config.bonuses[key]);
    let pending = restored.pending;
    let evidence = stored?.evidence || { controls: [], collected: false, obstacle: false };
    let runChecks = config.playtested ? { ...config.playtest_checks } : { ...config.playtest_checks, ...(stored?.runChecks || {}) };
    let friendFeedback = Array.isArray(stored?.friendFeedback)
        ? stored.friendFeedback.filter((item) => FRIEND_FEEDBACK.includes(item?.label)).slice(-20) : [];
    let campaign = normalizeCampaignProgress(stored?.campaign);
    const localFinale = stored?.creatorFinale;
    let creatorFinale = serverAdventure?.completed
        ? serverAdventure
        : (localFinale || serverAdventure || { completed: false, answers: {}, medal: null });
    let playtestApproved = root.dataset.playtestApproved === '1' || Boolean(serverState.playtested);
    if (creatorFinale.completed) campaign = normalizeCampaignProgress({ unlocked: 10, completed: CREATOR_CAMPAIGN_LEVELS.map((level) => level.id) });
    let activeCampaignLevel = null;
    let debugMode = false;
    let activeStage = Math.min(config.stage, 3);
    let playing = false;
    let syncing = false;
    let gameMode = null;
    let collectPopTimer = null;
    const currentGameConfig = () => gameMode === 'campaign' && activeCampaignLevel
        ? campaignGameConfig(config, activeCampaignLevel)
        : { ...config, stage: Math.max(config.stage, activeStage + 1) };
    const newGame = () => new CreatorGameEngine(currentGameConfig());
    let game = newGame();

    const editor = root.querySelector('[data-editor-body]');
    const feedback = root.querySelector('[data-editor-feedback]');
    const arena = root.querySelector('[data-arena]');
    const preview = root.querySelector('.creator-preview');
    const status = root.querySelector('[data-save-status]');
    const titles = ['Control', 'Collectible', 'Obstacle', 'Win Condition'];
    const checkLabels = { control: 'Character Control', collectible: 'Collectible', score: 'Score', obstacle: 'Obstacle', win: 'Win Condition' };
    const bonusCards = [
        ['second_collectible', '✨ Tambah collectible kedua', 'Dua item muncul di arena.'],
        ['special_plus_three', '💎 Collectible khusus +3', 'Item kedua memberi 3 poin.'],
        ['second_obstacle', '🪨 Tambah obstacle kedua', 'Ada rintangan baru di jalur.'],
        ['sound', '🔊 Sound effect', 'Efek suara lokal saat bermain.'],
        ['timer', '⏱ Timer 30 detik', 'Coba capai target sebelum waktu habis.'],
        ['target', '🏆 Naikkan target kemenangan', 'Target berikutnya: 5, 10, atau 15.'],
    ];
    let audioContext = null;
    const saveLocal = () => {
        try {
            localStorage.setItem(storageKey, JSON.stringify({ config, pending, evidence, runChecks, friendFeedback, campaign, creatorFinale }));
            status.textContent = syncing ? '⟳ Menyinkronkan' : '✓ Tersimpan di perangkat';
        } catch { status.textContent = 'Penyimpanan perangkat tidak tersedia'; }
    };

    const queueCheckpoint = () => {
        const snapshot = JSON.parse(JSON.stringify(config));
        pending = [snapshot];
        saveLocal();
        void sync();
    };

    function syncOnExit() {
        pending = [JSON.parse(JSON.stringify(config))];
        saveLocal();
        try {
            void fetch(root.dataset.saveUrl, {
                method: 'PATCH', keepalive: true, credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify(config),
            }).catch(() => {});
        } catch { /* Snapshot lokal tetap tersedia untuk dicoba lagi. */ }
    }

    async function sync() {
        if (syncing || !pending.length) return;
        syncing = true;
        status.textContent = '⟳ Menyinkronkan';
        while (pending.length) {
            const snapshot = pending[0];
            try {
                const response = await fetch(root.dataset.saveUrl, {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    credentials: 'same-origin',
                    body: JSON.stringify(snapshot),
                });
                if (!response.ok) throw new Error('Belum dapat menyinkronkan.');
                if (pending[0] === snapshot) pending.shift();
                saveLocal();
            } catch {
                status.textContent = '✓ Tersimpan di perangkat';
                break;
            }
        }
        syncing = false;
        status.textContent = pending.length ? '✓ Tersimpan di perangkat' : '✓ Tersinkron';
    }

    async function checkPlaytestPermission() {
        const button = root.querySelector('[data-check-playtest-permission]');
        if (button) {
            button.disabled = true;
            button.textContent = '⟳ Memeriksa izin...';
        }
        if (pending.length) await sync();

        try {
            const response = await fetch(root.dataset.playtestStatusUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Status izin belum tersedia.');
            const permission = await response.json();
            playtestApproved = Boolean(permission.approved);
            if (playtestApproved) {
                setFeedback('Izin diterima! Playtest dan Creator Adventure sudah terbuka.', true);
                render();
                return;
            }
            setFeedback('Playtest masih menunggu izin admin. Minta admin membuka izin dari halaman admin.');
        } catch {
            setFeedback('Status izin belum dapat diperiksa. Progress setup tetap tersimpan di perangkat.');
        }

        if (button) {
            button.disabled = false;
            button.textContent = 'Cek Izin Admin';
        }
    }

    const setFeedback = (message, good = false) => {
        feedback.textContent = message;
        feedback.classList.toggle('is-good', good);
    };

    const stageAction = (label, ready) => debugMode
        ? `<button type="button" class="creator-advance" data-debug-test>${ready ? '✓ DEBUG BERHASIL — LANJUT KE PLAYTEST' : '▶ TEST LAGI'}</button>`
        : `<button type="button" class="creator-advance" data-advance ${!ready ? 'disabled' : ''}>${label}</button>`;

    function playtestPanel() {
        if (!playtestApproved) {
            return `<div class="creator-playtest-wait"><span>🔒</span><p class="creator-kicker">SETUP 4 / 4 SELESAI</p><h3>Menunggu Izin Playtest</h3><p>Setup game-mu sudah tersimpan. Minta admin membuka Playtest sebelum melanjutkan.</p><button type="button" data-check-playtest-permission>⏳ Cek Izin Admin</button><small>Game tidak perlu diulang setelah izin diberikan.</small></div>`;
        }
        const checks = PLAYTEST_CHECK_KEYS.map((key) => `<li class="${runChecks[key] ? 'is-done' : ''}"><span>${runChecks[key] ? '✓' : '○'}</span>${checkLabels[key]}</li>`).join('');
        const bonusMarkup = config.playtested ? `<div class="creator-ready"><strong>GAME READY!</strong><span>🏅 Badge: Game Creator</span><p>Gim intimu sudah selesai. Bonus di bawah ini bebas kamu coba.</p></div><h3 class="creator-bonus-heading">CREATOR CHALLENGES <small>OPSIONAL</small></h3><div class="creator-bonus-grid">${bonusCards.map(([key, label, detail]) => `<button type="button" data-bonus="${key}" class="creator-bonus-card ${config.bonuses[key] ? 'is-selected' : ''}" ${key === 'special_plus_three' && !config.bonuses.second_collectible || key === 'target' && config.target_score >= 15 ? 'disabled' : ''}><b>${label}</b><span>${detail}</span><em>${key === 'target' && config.target_score >= 15 ? 'Maksimal 15' : config.bonuses[key] ? '✓ Aktif' : 'Coba bonus'}</em></button>`).join('')}</div>` : '';
        const friendSummary = friendFeedback.length ? `<p class="creator-friend-summary">🤝 Kesan teman terakhir: <strong>${friendFeedback.at(-1).label}</strong> · tersimpan di perangkat</p>` : '';
        const campaignMarkup = `<section class="creator-campaign"><div class="creator-campaign-heading"><div><span>SETELAH SETUP GAME</span><h3>🎮 CREATOR ADVENTURE</h3><p>Mainkan ${CREATOR_CAMPAIGN_LEVELS.length} level dengan karakter dan tema buatanmu. Sekitar 18–25 menit.</p></div><strong>${campaign.completed.length}/${CREATOR_CAMPAIGN_LEVELS.length}</strong></div><div class="creator-level-list">${CREATOR_CAMPAIGN_LEVELS.map((level) => { const locked = level.id > campaign.unlocked; const done = campaign.completed.includes(level.id); return `<button type="button" data-campaign-level="${level.id}" class="creator-level-card ${done ? 'is-done' : ''}" ${locked ? 'disabled' : ''}><span class="creator-level-number">${done ? '✓' : locked ? '🔒' : level.id}</span><span class="creator-level-icon">${level.icon}</span><span><b>LEVEL ${level.id} — ${level.name}</b><small>${level.description}</small><em>Target ${level.target} poin</em></span><strong>${done ? 'Main Lagi' : locked ? 'Terkunci' : 'Mainkan'}</strong></button>`; }).join('')}</div>${campaign.completed.length === CREATOR_CAMPAIGN_LEVELS.length ? `<div class="creator-campaign-complete">🏆 CREATOR ADVENTURE SELESAI! Kamu berhasil menamatkan gim buatanmu.<button type="button" data-open-finale>${creatorFinale.completed ? 'Lihat Medali' : 'Lanjut ke Penutup'} →</button></div>` : ''}</section>`;
        const checkButton = playtestPassed(runChecks)
            ? '🎮 GAME SIAP — BUKA 10 LEVEL'
            : '🔍 PERIKSA GAME';
        const adventureButton = '<button type="button" class="creator-adventure-button" data-open-adventure>🎮 LANJUT KE 10 LEVEL</button>';
        return `<div class="creator-playtest"><div class="creator-playtest-intro"><span>🎮</span><div><h3>PLAYTEST TIME!</h3><p>Setup game selesai. Kamu boleh melanjutkan ke level atau melengkapi Game Check.</p></div></div>${adventureButton}<h4>GAME CHECK <small>OPSIONAL SEBELUM LEVEL</small></h4><ul class="creator-checklist">${checks}<li class="${config.playtested ? 'is-done' : ''}"><span>${config.playtested ? '✓' : '○'}</span>Playtested</li></ul>${config.playtested ? '' : `<button type="button" class="creator-advance" data-check-game>${checkButton}</button>`}${campaignMarkup}${bonusMarkup}${friendSummary}</div>`;
    }

    function playSound(kind) {
        if (!config.bonuses.sound) return;
        try {
            audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = kind === 'win' ? 880 : kind === 'obstacle' ? 230 : 620;
            gain.gain.setValueAtTime(0.09, audioContext.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.16);
            oscillator.connect(gain).connect(audioContext.destination);
            oscillator.start();
            oscillator.stop(audioContext.currentTime + 0.17);
        } catch { /* Gim tetap berjalan tanpa audio. */ }
    }

    function render() {
        root.querySelector('[data-stage-count]').textContent = debugMode ? 'DEBUG GAME' : config.stage >= 4 ? 'SETELAH EMPAT TANTANGAN' : `TANTANGAN ${activeStage + 1} DARI 4`;
        root.querySelector('[data-stage-title]').textContent = debugMode ? `Periksa ${titles[activeStage]}` : config.stage >= 4 ? 'Playtest Gim Buatanmu' : titles[activeStage];
        root.querySelector('[data-progress-label]').textContent = config.playtested ? 'GAME READY!' : `${config.stage} / 4 selesai`;
        root.querySelector('[data-stage-tabs]').innerHTML = titles.map((title, index) => `<button type="button" data-stage="${index}" class="${index === activeStage && (debugMode || config.stage < 4) ? 'is-active' : ''} ${index < config.stage ? 'is-complete' : ''}" ${!debugMode && (index !== activeStage || config.stage >= 4) ? 'disabled' : ''}>${index < config.stage ? '✓' : index + 1} ${title}</button>`).join('');
        root.querySelector('[data-badge]').hidden = !config.playtested;
        root.querySelector('[data-mode-launch]').hidden = config.stage < 4 || !playtestApproved || Boolean(gameMode);
        root.querySelector('[data-mode-bar]').hidden = !gameMode;
        root.querySelector('[data-mode-label]').textContent = gameMode === 'campaign' && activeCampaignLevel
            ? `LEVEL ${activeCampaignLevel} — ${campaignLevel(activeCampaignLevel).name}`
            : gameMode === 'friend' ? 'MAIN BERGANTIAN' : 'MAIN GAME-KU';
        root.querySelector('[data-finish-friend]').hidden = gameMode !== 'friend';
        root.querySelector('[data-game-name]').textContent = CREATOR_OPTIONS.themes[config.theme][0];
        root.querySelector('[data-avatar]').textContent = CREATOR_OPTIONS.characters[config.character][1];
        root.querySelector('[data-item]').textContent = CREATOR_OPTIONS.collectibles[config.collectible][1];
        root.querySelector('[data-second-item]').textContent = config.collectible === 'gem' ? '⭐' : '💎';
        root.querySelector('[data-obstacle]').textContent = CREATOR_OPTIONS.obstacles[config.obstacle][1];
        arena.dataset.theme = config.theme;

        if (config.stage >= 4 && !debugMode) {
            editor.innerHTML = playtestPanel();
        } else if (activeStage === 0) {
            editor.innerHTML = `<p class="creator-instruction">Pilih tokoh dan tema. Sambungkan tiap tombol ke gerakan yang tepat, lalu coba ketiganya!</p>
                ${optionGroup('Pilih karakter', 'character', CREATOR_OPTIONS.characters, config.character)}
                ${optionGroup('Pilih tema', 'theme', CREATOR_OPTIONS.themes, config.theme)}
                <div class="creator-block-stack"><div class="creator-block-title">🎮 BLOK KONTROL</div>
                ${[['left', '← Bergerak kiri'], ['right', '→ Bergerak kanan'], ['jump', '↑ Lompat']].map(([action, label]) => `<label class="creator-mapping"><span>${label}</span><select data-control="${action}">${['LEFT', 'RIGHT', 'SPACE'].map((key) => `<option value="${key}" ${config.controls[action] === key ? 'selected' : ''}>${key}</option>`).join('')}</select></label>`).join('')}</div>
                ${stageAction('✓ Kontrol Berhasil — Lanjut', controlReady())}`;
        } else if (activeStage === 1) {
            editor.innerHTML = `<p class="creator-instruction">Pilih item untuk dikumpulkan. Pasang blok aturan, lalu sentuh item di arena.</p>
                ${optionGroup('Pilih collectible', 'collectible', CREATOR_OPTIONS.collectibles, config.collectible)}
                <div class="creator-block-stack"><div class="creator-block-title">✨ BLOK COLLECTIBLE</div><button type="button" data-rule="collect_rule" data-value="score_move" class="creator-rule ${config.collect_rule === 'score_move' ? 'is-selected' : ''}">MENYENTUH ITEM <b>→</b> SCORE +1 <b>→</b> ITEM PINDAH</button></div>
                ${stageAction('✓ Collectible Berhasil — Lanjut', collectReady())}`;
        } else if (activeStage === 2) {
            editor.innerHTML = `<p class="creator-instruction">Pilih rintangan dan akibatnya. Sentuh rintangan untuk menguji aturan.</p>
                ${optionGroup('Pilih obstacle', 'obstacle', CREATOR_OPTIONS.obstacles, config.obstacle)}
                <div class="creator-block-stack"><div class="creator-block-title">🪨 BLOK OBSTACLE</div><div class="creator-choice-row"><button type="button" data-rule="obstacle_rule" data-value="start" class="creator-rule ${config.obstacle_rule === 'start' ? 'is-selected' : ''}">MENYENTUH RINTANGAN → KEMBALI KE START</button><button type="button" data-rule="obstacle_rule" data-value="minus_one" class="creator-rule ${config.obstacle_rule === 'minus_one' ? 'is-selected' : ''}">MENYENTUH RINTANGAN → SKOR -1</button></div></div>
                ${stageAction('✓ Rintangan Berhasil — Lanjut', obstacleReady())}`;
        } else {
            editor.innerHTML = `<p class="creator-instruction">Pilih skor untuk menang. Mainkan gim sampai target tercapai!</p>
                <div class="creator-block-stack"><div class="creator-block-title">🏆 BLOK MENANG</div><div class="creator-choice-row">${[3, 5, 10].map((value) => `<button type="button" data-target="${value}" class="creator-rule ${config.target_score === value ? 'is-selected' : ''}">Target ${value} poin</button>`).join('')}</div><div class="creator-rule creator-rule-preview">SKOR MENCAPAI ${config.target_score} → MENANG 🎉</div></div>
                ${stageAction('🏆 Lanjut ke Playtest', game.won)}`;
        }
        renderArena();
    }

    function renderArena() {
        const snapshot = game.snapshot();
        const scrollingWorld = Boolean(game.config.bonuses.moving_world);
        const avatar = root.querySelector('[data-avatar]');
        const item = root.querySelector('[data-item]');
        const secondItem = root.querySelector('[data-second-item]');
        const obstacle = root.querySelector('[data-obstacle]');
        const secondObstacle = root.querySelector('[data-second-obstacle]');
        const thirdObstacle = root.querySelector('[data-third-obstacle]');
        const fourthObstacle = root.querySelector('[data-fourth-obstacle]');
        const dragon = root.querySelector('[data-dragon]');
        const sky = root.querySelector('[data-sky]');
        arena.classList.toggle('is-scrolling-world', scrollingWorld);
        arena.dataset.worldStage = scrollingWorld ? String(snapshot.worldStage) : '';
        sky.textContent = scrollingWorld ? ({ 1: '☁️   🌳   ☀️   ☁️', 2: '⛰️   ☁️   🌤️   ⛰️', 3: '🌙   ✨   🪐   ⭐' }[snapshot.worldStage] || '') : '';
        if (scrollingWorld && gameMode === 'campaign' && activeCampaignLevel) {
            const phase = game.config.bonuses.dragon_boss ? `KECEPATAN ${snapshot.speedLevel || 1}/4 · ${snapshot.dragonObstacleCount} RINTANGAN` : `STAGE ${snapshot.worldStage || 1}/3`;
            root.querySelector('[data-mode-label]').textContent = `LEVEL ${activeCampaignLevel} — ${campaignLevel(activeCampaignLevel).name} · ${phase}`;
        }
        [avatar, item, secondItem, obstacle, secondObstacle, thirdObstacle, fourthObstacle].forEach((actor) => {
            Object.assign(actor.style, { position: 'absolute', bottom: '30%', transform: 'translateX(-50%)', zIndex: '2' });
        });
        Object.assign(avatar.style, {
            bottom: '31%',
            zIndex: '3',
            transform: config.character === 'car' ? 'translateX(-50%) scaleX(-1)' : 'translateX(-50%)',
        });
        avatar.textContent ||= CREATOR_OPTIONS.characters[config.character]?.[1] || '🤖';
        item.textContent ||= CREATOR_OPTIONS.collectibles[config.collectible]?.[1] || '⭐';
        secondItem.textContent ||= '💎';
        obstacle.textContent ||= CREATOR_OPTIONS.obstacles[config.obstacle]?.[1] || '🪨';
        secondObstacle.textContent ||= CREATOR_OPTIONS.obstacles[config.obstacle]?.[1] || '🪨';
        thirdObstacle.textContent ||= '🔥';
        fourthObstacle.textContent ||= '🪨';
        root.querySelector('[data-score]').textContent = `${snapshot.score} / ${game.config.target_score}`;
        avatar.hidden = false;
        avatar.style.left = `${(snapshot.x + .5) * 10}%`;
        avatar.style.visibility = 'visible';
        avatar.classList.toggle('is-jumping', snapshot.jumping);
        item.style.left = `${(snapshot.itemX + .5) * 10}%`;
        secondItem.style.left = `${(snapshot.secondItemX + .5) * 10}%`;
        obstacle.style.left = `${(snapshot.obstacleX + .5) * 10}%`;
        secondObstacle.style.left = `${(snapshot.secondObstacleX + .5) * 10}%`;
        thirdObstacle.style.left = `${(snapshot.thirdObstacleX + .5) * 10}%`;
        fourthObstacle.style.left = `${(snapshot.fourthObstacleX + .5) * 10}%`;
        obstacle.style.bottom = snapshot.obstacleLane === 'air' ? '58%' : '30%';
        secondObstacle.style.bottom = snapshot.secondObstacleLane === 'air' ? '58%' : '30%';
        thirdObstacle.style.bottom = snapshot.thirdObstacleLane === 'air' ? '58%' : '30%';
        fourthObstacle.style.bottom = snapshot.fourthObstacleLane === 'air' ? '58%' : '30%';
        obstacle.classList.toggle('is-air-obstacle', snapshot.obstacleLane === 'air');
        secondObstacle.classList.toggle('is-air-obstacle', snapshot.secondObstacleLane === 'air');
        thirdObstacle.classList.toggle('is-air-obstacle', snapshot.thirdObstacleLane === 'air');
        fourthObstacle.classList.toggle('is-air-obstacle', snapshot.fourthObstacleLane === 'air');
        dragon.hidden = !game.config.bonuses.dragon_boss || snapshot.won;
        dragon.style.visibility = dragon.hidden ? 'hidden' : 'visible';
        arena.dataset.dragonIntensity = game.config.bonuses.dragon_boss ? String(snapshot.speedLevel) : '';
        dragon.querySelector('i').style.animationDuration = `${Math.max(.28, .68 - ((snapshot.speedLevel || 1) - 1) * .12)}s`;
        if (game.config.bonuses.dragon_boss) {
            obstacle.textContent = '🔥';
            secondObstacle.textContent = '🪨';
        }
        item.hidden = game.config.stage < 2 || snapshot.won || !snapshot.itemVisible;
        secondItem.hidden = game.config.stage < 2 || !game.config.bonuses.second_collectible || snapshot.won || !snapshot.secondItemVisible;
        item.style.visibility = item.hidden ? 'hidden' : 'visible';
        secondItem.style.visibility = secondItem.hidden ? 'hidden' : 'visible';
        obstacle.hidden = game.config.stage < 3;
        secondObstacle.hidden = game.config.stage < 3 || !game.config.bonuses.second_obstacle || (game.config.bonuses.dragon_boss && snapshot.dragonObstacleCount < 2);
        thirdObstacle.hidden = !game.config.bonuses.dragon_boss || snapshot.dragonObstacleCount < 3 || snapshot.won;
        fourthObstacle.hidden = !game.config.bonuses.dragon_boss || snapshot.dragonObstacleCount < 4 || snapshot.won;
        obstacle.style.visibility = obstacle.hidden ? 'hidden' : 'visible';
        secondObstacle.style.visibility = secondObstacle.hidden ? 'hidden' : 'visible';
        thirdObstacle.style.visibility = thirdObstacle.hidden ? 'hidden' : 'visible';
        fourthObstacle.style.visibility = fourthObstacle.hidden ? 'hidden' : 'visible';
        root.querySelector('[data-win]').hidden = !snapshot.won;
        root.querySelector('[data-timer]').hidden = !game.config.bonuses.timer;
        root.querySelector('[data-timer]').textContent = `⏱ ${snapshot.remainingSeconds} detik`;
        root.querySelector('[data-game-message]').textContent = playing ? snapshot.event : 'Tekan TEST GAME, lalu coba tombolnya.';
        root.querySelectorAll('[data-play-key]').forEach((button) => { button.disabled = !playing || snapshot.won || snapshot.timedOut; });
    }

    function controlReady() {
        return controlMappingValid(config.controls)
            && config.controls.left === 'LEFT' && config.controls.right === 'RIGHT' && config.controls.jump === 'SPACE'
            && ['LEFT', 'RIGHT', 'SPACE'].every((key) => evidence.controls.includes(key));
    }
    function collectReady() { return config.collect_rule === 'score_move' && evidence.collected; }
    function obstacleReady() { return evidence.obstacle; }

    function startTest() {
        if (config.stage >= 4 && !playtestApproved) {
            setFeedback('Setup selesai. Tunggu izin admin sebelum masuk ke Playtest.');
            return;
        }
        const nextGameConfig = currentGameConfig();
        if (!controlMappingValid(nextGameConfig.controls) && gameMode === 'campaign') {
            nextGameConfig.controls = { left: 'LEFT', right: 'RIGHT', jump: 'SPACE' };
        } else if (!controlMappingValid(nextGameConfig.controls)) {
            setFeedback('Setiap gerakan harus memakai tombol yang berbeda.');
            return;
        }
        game = new CreatorGameEngine(nextGameConfig);
        playing = true;
        if (!gameMode) evidence = { ...evidence, controls: [], collected: false, obstacle: false };
        root.querySelector('[data-friend-feedback]').hidden = true;
        root.querySelector('[data-level-result]').hidden = true;
        root.querySelector('[data-creator-finale]').hidden = true;
        root.querySelector('[data-feedback-options]').hidden = false;
        root.querySelector('[data-feedback-thanks]').hidden = true;
        if (collectPopTimer) window.clearTimeout(collectPopTimer);
        root.querySelector('[data-collect-pop]').hidden = true;
        arena.focus();
        setFeedback('Gim berjalan! Coba tombol keyboard atau tombol besar di bawah arena.', true);
        if (!gameMode) saveLocal();
        render();
    }

    function restartCurrentGame(event = null) {
        event?.preventDefault();
        event?.stopPropagation();
        playing = false;
        if (collectPopTimer) window.clearTimeout(collectPopTimer);
        root.querySelector('[data-collect-pop]').hidden = true;
        root.querySelector('[data-level-result]').hidden = true;
        startTest();
    }

    function handleGameResult(before, after, key = null) {
        const stateChanged = after.event !== before.event || after.score !== before.score || after.x !== before.x || after.won !== before.won || after.timedOut !== before.timedOut;
        const worldWrapped = game.config.bonuses.moving_world && (
            after.itemX > before.itemX || after.secondItemX > before.secondItemX
            || after.obstacleX > before.obstacleX || after.secondObstacleX > before.secondObstacleX
            || after.thirdObstacleX > before.thirdObstacleX || after.fourthObstacleX > before.fourthObstacleX
        );
        if (worldWrapped) arena.classList.add('is-world-wrapping');
        if (!gameMode && activeStage === 0 && key) evidence.controls = after.usedKeys;
        if (!gameMode && activeStage === 1 && after.score > before.score) evidence.collected = true;
        if (!gameMode && activeStage === 2 && after.event.startsWith('Tersentuh rintangan')) evidence.obstacle = true;
        if (!gameMode && config.stage >= 4 && !debugMode) {
            runChecks = updatePlaytestChecks(runChecks, config, before, after);
        }
        if (stateChanged && after.event.startsWith('Tersentuh rintangan')) playSound('obstacle');
        else if (after.won && !before.won) playSound('win');
        else if (after.score > before.score) playSound('item');
        if (after.won && !before.won) setFeedback('Hebat! Target skor tercapai. Gim buatanmu berhasil dimainkan!', true);
        else if (after.score > before.score) setFeedback(`Item diambil! Skor +${after.score - before.score}. Item baru segera muncul.`, true);
        else if (stateChanged && after.event.startsWith('Tersentuh')) setFeedback(after.event, true);
        else if (activeStage === 0 && controlReady()) setFeedback('Kiri, kanan, dan lompat sudah bekerja! Klik Lanjut.', true);
        else if (activeStage === 1 && collectReady()) setFeedback('Skor bertambah dan item berpindah! Klik Lanjut.', true);
        else if (activeStage === 2 && obstacleReady()) setFeedback('Aturan rintangan bekerja! Klik Lanjut.', true);
        if (!gameMode) saveLocal();
        if (gameMode) renderArena(); else render();
        if (worldWrapped) {
            void arena.offsetWidth;
            window.requestAnimationFrame(() => arena.classList.remove('is-world-wrapping'));
        }
        if (gameMode === 'friend' && (after.won || after.timedOut)) finishFriendGame();
        if (gameMode === 'campaign' && after.won) finishCampaignLevel();
        if (gameMode === 'campaign' && after.timedOut) finishCampaignTimeout();
        if (after.score > before.score) {
            const pop = root.querySelector('[data-collect-pop]');
            pop.textContent = `+${after.score - before.score}`;
            pop.style.left = `${(after.x + .5) * 10}%`;
            pop.hidden = false;
            pop.classList.remove('is-visible');
            void pop.offsetWidth;
            pop.classList.add('is-visible');
            if (collectPopTimer) window.clearTimeout(collectPopTimer);
            collectPopTimer = window.setTimeout(() => { pop.hidden = true; }, 650);
            const currentGame = game;
            window.setTimeout(() => { if (game === currentGame) renderArena(); }, COLLECTIBLE_RESPAWN_MS + 20);
        }
        if (key && after.event === 'Lompat!') {
            const avatar = root.querySelector('[data-avatar]');
            avatar.classList.remove('is-jumping');
            void avatar.offsetWidth;
            avatar.classList.add('is-jumping');
            const currentGame = game;
            window.setTimeout(() => { if (game === currentGame) renderArena(); }, JUMP_DURATION_MS + 30);
        }
    }

    function useKey(key) {
        if (!playing) return;
        const before = game.snapshot();
        const after = game.useKey(key);
        handleGameResult(before, after, key);
    }

    function clearPlaytestEvidence() {
        if (config.stage < 4) return;
        runChecks = emptyPlaytestChecks();
        config.playtest_checks = { ...runChecks };
    }

    function openGameMode(mode, level = null) {
        if (config.stage < 4 || !playtestApproved) return;
        gameMode = mode;
        activeCampaignLevel = mode === 'campaign' ? Number(level) : null;
        root.classList.add('is-game-mode');
        document.body.classList.add('creator-page-playing');
        try { void preview.requestFullscreen?.()?.catch?.(() => {}); } catch { /* Tampilan tetap menutup viewport. */ }
        startTest();
        if (mode === 'campaign' && !playing) {
            game = new CreatorGameEngine(campaignGameConfig(config, activeCampaignLevel));
            playing = true;
            render();
        }
    }

    function closeGameMode() {
        if (!gameMode) return;
        gameMode = null;
        activeCampaignLevel = null;
        playing = false;
        game = newGame();
        root.classList.remove('is-game-mode');
        document.body.classList.remove('creator-page-playing');
        root.querySelector('[data-friend-feedback]').hidden = true;
        root.querySelector('[data-level-result]').hidden = true;
        root.querySelector('[data-creator-finale]').hidden = true;
        if (document.fullscreenElement === preview) {
            try { void document.exitFullscreen?.()?.catch?.(() => {}); } catch { /* Tombol kembali tetap bekerja. */ }
        }
        render();
        saveLocal();
    }

    function finishFriendGame() {
        if (gameMode !== 'friend' || !root.querySelector('[data-friend-feedback]').hidden) return;
        playing = false;
        root.querySelector('[data-friend-feedback]').hidden = false;
        root.querySelector('[data-feedback-options]').hidden = false;
        root.querySelector('[data-feedback-thanks]').hidden = true;
        renderArena();
    }

    function finishCampaignLevel() {
        if (gameMode !== 'campaign' || !activeCampaignLevel || !root.querySelector('[data-level-result]').hidden) return;
        const completedLevel = activeCampaignLevel;
        campaign = completeCampaignLevel(campaign, completedLevel);
        playing = false;
        saveLocal();
        const finalLevel = completedLevel >= CREATOR_CAMPAIGN_LEVELS.length;
        root.querySelector('[data-level-result-title]').textContent = finalLevel ? '10 LEVEL SELESAI!' : 'LEVEL SELESAI!';
        root.querySelector('.creator-level-result-icon').textContent = '⭐';
        root.querySelector('[data-level-result-text]').textContent = finalLevel
            ? 'Dragon Rush selesai. Lanjutkan ke evaluasi untuk mendapatkan medali kelulusan.'
            : `${campaignLevel(completedLevel).name} selesai. Level berikutnya sudah terbuka.`;
        root.querySelector('[data-level-next]').hidden = finalLevel;
        root.querySelector('[data-level-finale]').hidden = !finalLevel;
        root.querySelector('[data-level-result]').hidden = false;
        renderArena();
    }

    function renderFinale() {
        const finale = root.querySelector('[data-creator-finale]');
        finale.hidden = false;
        root.querySelector('[data-finale-evaluation]').hidden = Boolean(creatorFinale.completed);
        root.querySelector('[data-finale-medal]').hidden = !creatorFinale.completed;
    }

    function openFinale() {
        if (campaign.completed.length < CREATOR_CAMPAIGN_LEVELS.length) return;
        playing = false;
        gameMode = 'finale';
        activeCampaignLevel = null;
        root.classList.add('is-game-mode');
        document.body.classList.add('creator-page-playing');
        root.querySelector('[data-level-result]').hidden = true;
        root.querySelector('[data-friend-feedback]').hidden = true;
        renderFinale();
    }

    async function completeFinale() {
        const creatorAnswer = root.querySelector('input[name="creator_answer"]:checked')?.value;
        const debugAnswer = root.querySelector('input[name="debug_answer"]:checked')?.value;
        const finaleFeedback = root.querySelector('[data-finale-feedback]');
        if (!creatorAnswer || !debugAnswer) {
            finaleFeedback.textContent = 'Jawab kedua pertanyaan terlebih dahulu.';
            return;
        }
        if (creatorAnswer !== 'mengatur_game' || debugAnswer !== 'uji_perbaiki') {
            finaleFeedback.textContent = 'Belum tepat. Ingat kembali bagaimana kamu membuat dan memperbaiki game.';
            return;
        }

        creatorFinale = { completed: true, answers: { creator: creatorAnswer, debug: debugAnswer }, medal: 'Ko-Ki Game Creator' };
        saveLocal();
        renderFinale();
        status.textContent = '⟳ Menyinkronkan';
        try {
            const response = await fetch(root.dataset.completeUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ completed_levels: CREATOR_CAMPAIGN_LEVELS.map((level) => level.id), answers: creatorFinale.answers }),
            });
            if (!response.ok) throw new Error('Sinkronisasi gagal.');
            status.textContent = '✓ Tersinkron';
        } catch {
            status.textContent = '✓ Medali tersimpan di perangkat';
        }
    }

    function finishCampaignTimeout() {
        if (gameMode !== 'campaign' || !root.querySelector('[data-level-result]').hidden) return;
        playing = false;
        root.querySelector('[data-level-result-title]').textContent = 'WAKTU HABIS';
        root.querySelector('.creator-level-result-icon').textContent = '⏱️';
        root.querySelector('[data-level-result-text]').textContent = 'Hampir berhasil! Coba lagi dan cari rute yang lebih cepat.';
        root.querySelector('[data-level-next]').hidden = true;
        root.querySelector('[data-level-result]').hidden = false;
        renderArena();
    }

    function startCampaignLevel(level) {
        const requested = Number(level);
        if (requested > campaign.unlocked) return;
        openGameMode('campaign', requested);
    }

    editor.addEventListener('click', (event) => {
        const choice = event.target.closest('[data-option]');
        const rule = event.target.closest('[data-rule]');
        const target = event.target.closest('[data-target]');
        const bonus = event.target.closest('[data-bonus]');
        const campaignButton = event.target.closest('[data-campaign-level]');
        if (event.target.closest('[data-check-playtest-permission]')) {
            void checkPlaytestPermission();
        } else if (campaignButton) {
            startCampaignLevel(campaignButton.dataset.campaignLevel);
        } else if (event.target.closest('[data-open-finale]')) {
            openFinale();
        } else if (event.target.closest('[data-open-adventure]')) {
            root.querySelector('.creator-campaign')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (event.target.closest('[data-check-game]')) {
            if (playtestPassed(runChecks)) {
                const previous = { stage: config.stage, playtested: config.playtested };
                config.playtested = true;
                config.playtest_checks = { ...runChecks };
                if (creatorCheckpoint(previous, config) === 'meeting') queueCheckpoint(); else saveLocal();
                setFeedback('GAME READY! Semua bagian gim sudah berhasil kamu uji.', true);
                render();
                window.requestAnimationFrame(() => root.querySelector('.creator-campaign')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
            } else {
                const missing = PLAYTEST_CHECK_KEYS.filter((key) => !runChecks[key]);
                debugMode = true;
                activeStage = nextDebugStage(runChecks);
                playing = false;
                game = newGame();
                render();
                setFeedback(`DEBUG GAME: ${missing.map((key) => checkLabels[key]).join(', ')} belum terbukti bekerja. Periksa bloknya, lalu TEST LAGI.`);
            }
        } else if (event.target.closest('[data-debug-test]')) {
            const ready = [controlReady(), collectReady(), obstacleReady(), game.won][activeStage];
            if (!ready) {
                startTest();
                setFeedback(`Coba lagi ${titles[activeStage]}. Setelah berhasil, tombol hijau akan membawamu ke Playtest.`);
                return;
            }
            debugMode = false;
            activeStage = 3;
            startTest();
            setFeedback('Debug berhasil! Tekan LANJUT KE 10 LEVEL untuk mulai Creator Adventure.', true);
        } else if (bonus && config.playtested) {
            const key = bonus.dataset.bonus;
            if (key === 'target') {
                config.bonuses.target = true;
                config.target_score = ({ 3: 5, 5: 10, 10: 15 })[config.target_score] || 15;
            } else {
                config.bonuses[key] = !config.bonuses[key];
                if (key === 'second_collectible' && !config.bonuses.second_collectible) config.bonuses.special_plus_three = false;
            }
            playing = false;
            game = newGame();
            saveLocal();
            setFeedback('Bonus diperbarui. Tekan TEST GAME untuk mencobanya.', true);
            render();
        } else if (choice) {
            config[choice.dataset.option] = choice.dataset.value;
            game = newGame();
            playing = false;
            evidence = { controls: [], collected: false, obstacle: false };
            clearPlaytestEvidence();
            saveLocal(); render();
        } else if (rule) {
            config[rule.dataset.rule] = rule.dataset.value;
            game = newGame();
            playing = false;
            evidence.collected = false;
            evidence.obstacle = false;
            clearPlaytestEvidence();
            saveLocal(); render();
        } else if (target) {
            config.target_score = Number(target.dataset.target);
            game = newGame();
            playing = false;
            clearPlaytestEvidence();
            saveLocal(); render();
        } else if (event.target.closest('[data-advance]')) {
            const ready = [controlReady(), collectReady(), obstacleReady(), game.won][activeStage];
            if (!ready) return;
            const previous = { stage: config.stage, playtested: config.playtested };
            config.stage = Math.max(config.stage, activeStage + 1);
            config.tested_win = config.stage === 4;
            if (creatorCheckpoint(previous, config) === 'core') queueCheckpoint(); else saveLocal();
            playing = false;
            game = newGame();
            activeStage = Math.min(config.stage, 3);
            setFeedback(config.stage === 4 ? 'Empat tantangan selesai! Sekarang PLAYTEST gim utuhmu.' : `Tantangan ${config.stage} selesai. Sekarang buat aturan berikutnya.`, true);
            render();
        }
    });

    editor.addEventListener('change', (event) => {
        if (!event.target.matches('[data-control]')) return;
        config.controls[event.target.dataset.control] = event.target.value;
        game = newGame();
        playing = false;
        evidence.controls = [];
        clearPlaytestEvidence();
        saveLocal(); render();
    });

    root.querySelector('[data-stage-tabs]').addEventListener('click', (event) => {
        const button = event.target.closest('[data-stage]');
        if (!debugMode || !button) return;
        activeStage = Number(button.dataset.stage);
        render();
    });

    root.querySelector('[data-test-game]').addEventListener('click', startTest);
    root.querySelector('[data-reset-game]').addEventListener('click', restartCurrentGame);
    root.querySelectorAll('[data-open-mode]').forEach((button) => button.addEventListener('click', () => openGameMode(button.dataset.openMode)));
    root.querySelector('[data-exit-mode]').addEventListener('click', closeGameMode);
    root.querySelector('[data-finish-friend]').addEventListener('click', finishFriendGame);
    root.querySelector('[data-friend-replay]').addEventListener('click', startTest);
    root.querySelector('[data-level-replay]').addEventListener('click', restartCurrentGame);
    root.querySelector('[data-level-finale]').addEventListener('click', openFinale);
    root.querySelector('[data-check-finale]').addEventListener('click', () => void completeFinale());
    root.querySelector('[data-level-next]').addEventListener('click', () => {
        if (!activeCampaignLevel || activeCampaignLevel >= CREATOR_CAMPAIGN_LEVELS.length) return;
        activeCampaignLevel++;
        startTest();
    });
    root.querySelectorAll('[data-friend-choice]').forEach((button) => button.addEventListener('click', () => {
        if (gameMode !== 'friend') return;
        friendFeedback = addFriendFeedback(friendFeedback, button.dataset.friendChoice);
        saveLocal();
        root.querySelector('[data-feedback-options]').hidden = true;
        root.querySelector('[data-feedback-thanks]').hidden = false;
    }));
    root.querySelectorAll('[data-play-key]').forEach((button) => button.addEventListener('click', () => useKey(button.dataset.playKey)));
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && gameMode) { closeGameMode(); return; }
        if (!playing || ['INPUT', 'SELECT', 'TEXTAREA'].includes(event.target?.tagName)) return;
        const key = { ArrowLeft: 'LEFT', ArrowRight: 'RIGHT', Space: 'SPACE' }[event.code];
        if (!key) return;
        event.preventDefault();
        if (key === 'SPACE' && event.repeat) return;
        useKey(key);
    });
    window.addEventListener('online', () => void sync());
    window.addEventListener('pagehide', syncOnExit);
    window.setInterval(() => { if (pending.length) void sync(); }, 15000);
    window.setInterval(() => {
        if (!playing || (!game.config.bonuses.timer && !game.config.bonuses.moving_obstacle && !game.config.bonuses.moving_obstacles_together && !game.config.bonuses.moving_world)) return;
        const before = game.snapshot();
        const after = game.tick();
        handleGameResult(before, after);
    }, 40);

    saveLocal(); render(); void sync();
}

function optionGroup(title, field, options, selected) {
    return `<div class="creator-option-group"><h3>${title}</h3><div class="creator-choice-row">${Object.entries(options).map(([value, [label, icon]]) => `<button type="button" data-option="${field}" data-value="${value}" class="creator-choice ${selected === value ? 'is-selected' : ''}"><span>${icon}</span><b>${label}</b></button>`).join('')}</div></div>`;
}

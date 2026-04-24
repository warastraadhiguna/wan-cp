
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Petualangan Hati Dito - SM GKJ Salib Putih</title>
    <style>
        body {
            margin: 0; padding: 0;
            background: linear-gradient(135deg, #74b9ff, #a29bfe);
            color: #fff;
            font-family: 'Comic Sans MS', 'Chalkboard SE', sans-serif;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            height: 100vh; overflow: hidden;
            touch-action: none;
        }

        /* TOMBOL MENU ATAS */
        .top-menu {
            position: absolute; top: 15px; left: 15px; right: 15px;
            display: flex; justify-content: space-between; z-index: 100;
        }

        .menu-btn {
            background: #f39c12; color: white;
            border: 3px solid #e67e22; border-radius: 20px;
            padding: 8px 15px; font-weight: bold; font-family: inherit;
            cursor: pointer; font-size: clamp(0.8em, 2vw, 1em);
            box-shadow: 0 4px 0 #d35400; transition: transform 0.1s;
        }
        .menu-btn:active { transform: translateY(4px); box-shadow: 0 0 0 #d35400; }

        .header-title { text-align: center; margin-bottom: 5px; padding: 0 10px; z-index: 10;}
        .header-title h1 { margin: 0; font-size: clamp(1.2em, 4vw, 1.8em); text-shadow: 2px 2px 0px #2d3436; color: #fdcb6e; }
        .header-title h2 { margin: 5px 0 10px 0; font-size: clamp(0.9em, 2vw, 1.1em); text-shadow: 1px 1px 0px #2d3436; font-weight: normal; }

        #game-wrapper {
            position: relative; width: 95vw; max-width: 800px; aspect-ratio: 4 / 3;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            border: 5px solid #fff; border-radius: 15px;
            background-color: #55efc4; overflow: hidden;
            transition: transform 0.1s;
        }

        .shake { animation: shake 0.3s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake {
            10%, 90% { transform: translate3d(-3px, 0, 0); }
            20%, 80% { transform: translate3d(4px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-6px, 0, 0); }
            40%, 60% { transform: translate3d(6px, 0, 0); }
        }

        canvas { width: 100%; height: 100%; display: block; }

        @keyframes popIn { 0% { transform: translate(-50%, 100%) scale(0.5); opacity: 0; } 70% { transform: translate(-50%, -10%) scale(1.05); opacity: 1; } 100% { transform: translate(-50%, 0) scale(1); opacity: 1; } }
        @keyframes popCenter { 0% { transform: translate(-50%, 50%) scale(0.5); opacity: 0; } 70% { transform: translate(-50%, -60%) scale(1.05); opacity: 1; } 100% { transform: translate(-50%, -50%) scale(1); opacity: 1; } }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

        #dialogue-box {
            position: absolute; bottom: 5%; left: 50%; transform: translateX(-50%);
            width: 90%; background: rgba(255, 255, 255, 0.95); color: #2d3436;
            padding: 15px; border-radius: 15px; border: 4px solid #fdcb6e;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2); display: none; text-align: center;
            animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; box-sizing: border-box; z-index: 10;
        }

        #dialogue-text { font-size: clamp(1em, 3vw, 1.2em); margin-bottom: 10px; font-weight: bold; line-height: 1.4; }

        button.btn-lanjut {
            background-color: #ff7675; color: white; border: none; padding: 10px 25px;
            font-size: clamp(0.9em, 2vw, 1.1em); font-family: inherit; border-radius: 50px;
            cursor: pointer; font-weight: bold; box-shadow: 0 4px 0px #d63031; transition: transform 0.2s, background-color 0.2s;
        }
        button.btn-lanjut:active { transform: translateY(3px); box-shadow: 0 1px 0px #d63031; }

        #hud {
            position: absolute; top: 10px; right: 10px; display: flex; gap: 15px;
            color: #2d3436; font-weight: bold; font-size: clamp(0.8em, 2vw, 1.1em);
            pointer-events: none; background: rgba(255, 255, 255, 0.8); padding: 8px 15px; border-radius: 10px; border: 2px solid #fff; z-index: 5;
        }

        /* --- PERBAIKAN REWARD SCREEN --- */
        #reward-screen {
            position: absolute; top: 50%; left: 50%; 
            width: 90%; max-height: 90%; /* Supaya tidak melebihi layar */
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px; border: 5px solid #f1c40f; box-shadow: 0 15px 30px rgba(0,0,0,0.3); 
            display: none; flex-direction: column; align-items: center; justify-content: center; 
            z-index: 20; text-align: center;
            padding: clamp(10px, 3vw, 20px); box-sizing: border-box; 
            overflow-y: auto; /* Bisa discroll kalau layar HP terlalu pendek */
            animation: popCenter 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            gap: 5px; /* Jarak antar elemen agar rapi */
        }

        #reward-screen h1 { color: #f39c12; font-size: clamp(1.5em, 6vw, 2.5em); margin: 5px 0; text-shadow: 1px 1px 0 #fff, 3px 3px 0 #f1c40f; line-height: 1.1; }
        .trophy { font-size: clamp(2.5em, 10vw, 4em); animation: bounce 2s infinite ease-in-out; margin: 0; }
        #reward-screen p { color: #2c3e50; font-size: clamp(0.9em, 3vw, 1.2em); margin: 2px 0; font-weight: bold; }
        
        .verse-box { 
            background: #fff3e0; border-left: 5px solid #e67e22; 
            padding: clamp(8px, 2vw, 15px); margin: 10px 0; border-radius: 8px; 
            color: #d35400; font-style: italic; font-size: clamp(0.8em, 2.5vw, 1.1em); 
            width: 100%; box-sizing: border-box; line-height: 1.3;
        }

        /* TAMPILAN KONTROL SENTUH */
        #touch-controls { width: 95vw; max-width: 800px; margin-top: 15px; display: flex; justify-content: space-between; align-items: center; }
        @media (min-width: 850px) { #touch-controls { display: none; } .hide-on-desktop { display: none; } }
        @media (max-width: 849px) { .hide-on-mobile { display: none; } .top-menu { top: 5px; } }
        
        .d-pad { display: grid; grid-template-columns: repeat(3, 50px); grid-template-rows: repeat(3, 50px); gap: 5px; }
        .d-btn { background: rgba(255, 255, 255, 0.7); border: 2px solid #2d3436; border-radius: 12px; font-size: 24px; display: flex; align-items: center; justify-content: center; user-select: none; touch-action: none; color: #2d3436; }
        .d-btn:active { background: #fdcb6e; }
        #btn-up { grid-column: 2; grid-row: 1; } #btn-left { grid-column: 1; grid-row: 2; } #btn-down { grid-column: 2; grid-row: 2; } #btn-right { grid-column: 3; grid-row: 2; }
        
        .action-btn { background: #ff7675; border: 3px solid #d63031; border-radius: 50%; width: 80px; height: 80px; font-size: 40px; display: flex; align-items: center; justify-content: center; user-select: none; touch-action: none; box-shadow: 0 6px 0 #d63031; }
        .action-btn:active { transform: translateY(6px); box-shadow: 0 0 0 #d63031; }
    </style>
</head>
<body>

    <div class="top-menu">
        <button id="music-toggle" class="menu-btn" onclick="toggleMusic()">🔇 Musik</button>
        <button class="menu-btn hide-on-desktop" onclick="toggleFullScreen()">🔲 Layar Penuh</button>
    </div>

    <div class="header-title">
        <h1>⛪ SM GKJ Salib Putih ⛪</h1>
        <h2 class="hide-on-mobile">🎮 WASD/Panah = Gerak | SPASI = Tembak Kasih 💖</h2>
    </div>
    
    <div id="game-wrapper">
        <canvas id="gameCanvas" width="800" height="600"></canvas>
        <div id="hud">
            <span id="level-display">Lvl: 1</span>
            <span id="ammo-display">Kasih: 💖💖</span>
        </div>
        <div id="dialogue-box">
            <div id="dialogue-text">Halo!</div>
            <button class="btn-lanjut" onclick="nextDialogue()">Lanjut!</button>
        </div>
        <div id="reward-screen">
            <div class="trophy">🏆</div>
            <h1>SELAMAT!</h1>
            <p>Kamu adalah Anak Hebat</p>
            <p style="color: #e74c3c; font-size: clamp(1.1em, 4vw, 1.4em);">Sekolah Minggu GKJ Salib Putih!</p>
            <div class="verse-box">
                "Kita mengasihi, karena Allah lebih dahulu mengasihi kita."<br>- 1 Yohanes 4:19
            </div>
            <button class="btn-lanjut" onclick="restartGame()" style="background-color: #2ecc71; box-shadow: 0 4px 0px #27ae60;">Main Lagi 🔄</button>
        </div>
    </div>

    <div id="touch-controls">
        <div class="d-pad">
            <div class="d-btn" id="btn-up">🔺</div>
            <div class="d-btn" id="btn-left">◀️</div>
            <div class="d-btn" id="btn-down">🔻</div>
            <div class="d-btn" id="btn-right">▶️</div>
        </div>
        <div class="action-btn" id="btn-shoot">💖</div>
    </div>

    <script>
        // --- FULLSCREEN ---
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => { console.log(err.message); });
            } else { if (document.exitFullscreen) document.exitFullscreen(); }
        }

        // --- AUDIO ---
        let audioCtx; let isMusicOn = false; let musicInitialized = false; let noteIndex = 0; let nextNoteTime = 0;
        const bgmMelody = [ 261.63, 329.63, 392.00, 329.63, 349.23, 440.00, 523.25, 440.00, 261.63, 329.63, 392.00, 329.63, 293.66, 392.00, 493.88, 392.00 ];

        function initAudio() {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();
        }

        function scheduleBGMNote(freq, time) {
            if (!audioCtx) return;
            const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain();
            osc.type = 'sine'; osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.015, time); gain.gain.exponentialRampToValueAtTime(0.001, time + 0.25);
            osc.connect(gain); gain.connect(audioCtx.destination); osc.start(time); osc.stop(time + 0.25);
        }

        function bgmScheduler() {
            if(!isMusicOn) return;
            if (nextNoteTime < audioCtx.currentTime) nextNoteTime = audioCtx.currentTime + 0.1;
            while (nextNoteTime < audioCtx.currentTime + 0.1) {
                scheduleBGMNote(bgmMelody[noteIndex], nextNoteTime); nextNoteTime += 0.3; noteIndex = (noteIndex + 1) % bgmMelody.length;
            }
            setTimeout(bgmScheduler, 25);
        }

        function toggleMusic() {
            initAudio(); isMusicOn = !isMusicOn; const btn = document.getElementById("music-toggle");
            if (isMusicOn) {
                btn.innerText = "🔊 Musik"; btn.style.backgroundColor = "#2ecc71"; btn.style.borderColor = "#27ae60"; btn.style.boxShadow = "0 4px 0 #27ae60";
                if (!musicInitialized) { nextNoteTime = audioCtx.currentTime + 0.1; musicInitialized = true; } bgmScheduler();
            } else { btn.innerText = "🔇 Musik"; btn.style.backgroundColor = "#f39c12"; btn.style.borderColor = "#e67e22"; btn.style.boxShadow = "0 4px 0 #d35400"; }
        }

        function playTone(frequency, type, duration, volume = 0.1) {
            if (!audioCtx) return;
            const osc = audioCtx.createOscillator(); const gainNode = audioCtx.createGain();
            osc.type = type; osc.frequency.setValueAtTime(frequency, audioCtx.currentTime);
            gainNode.gain.setValueAtTime(volume, audioCtx.currentTime); gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration);
            osc.connect(gainNode); gainNode.connect(audioCtx.destination); osc.start(); osc.stop(audioCtx.currentTime + duration);
        }

        const sfx = {
            shoot: () => {
                if(!audioCtx) return; const osc = audioCtx.createOscillator(); const gain = audioCtx.createGain();
                osc.type = 'square'; osc.frequency.setValueAtTime(800, audioCtx.currentTime); osc.frequency.exponentialRampToValueAtTime(300, audioCtx.currentTime + 0.1);
                gain.gain.setValueAtTime(0.05, audioCtx.currentTime); gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);
                osc.connect(gain); gain.connect(audioCtx.destination); osc.start(); osc.stop(audioCtx.currentTime + 0.1);
            },
            error: () => playTone(150, 'sawtooth', 0.3, 0.05),
            hit: () => playTone(1200, 'sine', 0.1, 0.1),
            holy: () => { playTone(523.25, 'triangle', 0.8, 0.08); setTimeout(() => playTone(659.25, 'triangle', 0.7, 0.08), 50); setTimeout(() => playTone(783.99, 'triangle', 0.6, 0.08), 100); setTimeout(() => playTone(1046.50, 'triangle', 0.9, 0.08), 150); },
            win: () => { let time = 0; const notes = [{f: 523.25, d: 0.15}, {f: 659.25, d: 0.15}, {f: 783.99, d: 0.15}, {f: 1046.50, d: 0.4}]; notes.forEach(n => { setTimeout(() => playTone(n.f, 'square', n.d, 0.05), time * 1000); time += n.d; }); }
        };

        // --- GAME ENGINE ---
        const canvas = document.getElementById("gameCanvas"); const ctx = canvas.getContext("2d");
        const dialogueBox = document.getElementById("dialogue-box"); const dialogueText = document.getElementById("dialogue-text");
        const ammoDisplay = document.getElementById("ammo-display"); const levelDisplay = document.getElementById("level-display");
        const rewardScreen = document.getElementById("reward-screen"); const gameWrapper = document.getElementById("game-wrapper");

        ctx.textAlign = "center"; ctx.textBaseline = "middle";
        let gameState = "playing"; let currentLevel = 1; let infiniteAmmo = false;
        let dialogueCallback = null; let isFirstInteraction = true;
        const keys = { w:false, a:false, s:false, d:false, space:false };

        window.addEventListener("keydown", (e) => {
            initAudio(); 
            if(e.key === "w" || e.key === "ArrowUp") keys.w = true;
            if(e.key === "a" || e.key === "ArrowLeft") keys.a = true;
            if(e.key === "s" || e.key === "ArrowDown") keys.s = true;
            if(e.key === "d" || e.key === "ArrowRight") keys.d = true;
            if(e.key === " " && gameState === "playing") { if(!keys.space) shoot(); keys.space = true; }
        });
        window.addEventListener("keyup", (e) => {
            if(e.key === "w" || e.key === "ArrowUp") keys.w = false;
            if(e.key === "a" || e.key === "ArrowLeft") keys.a = false;
            if(e.key === "s" || e.key === "ArrowDown") keys.s = false;
            if(e.key === "d" || e.key === "ArrowRight") keys.d = false;
            if(e.key === " ") keys.space = false;
        });

        const bindTouch = (id, keyChar) => {
            const el = document.getElementById(id);
            el.addEventListener('touchstart', (e) => { e.preventDefault(); initAudio(); keys[keyChar] = true; if(keyChar === 'space' && gameState === "playing") shoot(); });
            el.addEventListener('touchend', (e) => { e.preventDefault(); keys[keyChar] = false; });
        };
        bindTouch('btn-up', 'w'); bindTouch('btn-down', 's'); bindTouch('btn-left', 'a'); bindTouch('btn-right', 'd'); bindTouch('btn-shoot', 'space');

        const player = { x: 400, y: 500, size: 30, speed: 6, emoji: "👦", ammo: 2, faceDir: "up" };
        let projectiles = []; let npcs = []; let interactables = []; let particles = []; let obstacles = [];

        function triggerShake() { gameWrapper.classList.remove("shake"); void gameWrapper.offsetWidth; gameWrapper.classList.add("shake"); }

        function createParticles(x, y, type = "normal") {
            let count = type === "confetti" ? 8 : 10; 
            let emojis = ["✨", "💖"]; if (type === "holy") emojis = ["✨", "🌟"]; if (type === "confetti") emojis = ["🎉", "🎊", "🎈", "✨", "💖", "⭐"];
            for (let i = 0; i < count; i++) {
                let vx = (Math.random() - 0.5) * 8; let vy = (Math.random() - 0.5) * 8;
                if (type === "confetti") { vx = (Math.random() - 0.5) * 10; vy = Math.random() * 5 + 3; }
                particles.push({ x: x, y: y, vx: vx, vy: vy, alpha: 1, emoji: emojis[Math.floor(Math.random() * emojis.length)], size: Math.random() * 20 + 10 });
            }
        }

        function generateObstacles(level) {
            obstacles = [];
            if(level === 1 || level === 5) {
                obstacles.push({x: 150, y: 250, size: 45, emoji: "🌳"});
                obstacles.push({x: 650, y: 250, size: 45, emoji: "🌳"});
                obstacles.push({x: 400, y: 350, size: 45, emoji: "🌳"});
            }
        }

        function checkCollision(obj1, obj2, buffer = 0) {
            let dx = obj1.x - obj2.x; let dy = obj1.y - obj2.y;
            return Math.sqrt(dx*dx + dy*dy) < (obj1.size/2 + obj2.size/2 + buffer);
        }

        function shoot() {
            if (player.ammo > 0 || infiniteAmmo) {
                if (!infiniteAmmo) { player.ammo--; updateHUD(); } sfx.shoot(); 
                let vx = 0, vy = 0;
                if(player.faceDir === "up") vy = -8; if(player.faceDir === "down") vy = 8;
                if(player.faceDir === "left") vx = -8; if(player.faceDir === "right") vx = 8;
                projectiles.push({ x: player.x, y: player.y, vx: vx, vy: vy, size: 20 });
            } else if (currentLevel === 1 && player.ammo === 0) {
                keys.space = false; sfx.error(); triggerShake();
                showDialogue("Oh tidak! Kamu kehabisan tenaga.\nHatimu terasa lelah karena mencoba mengasihi pakai kekuatan sendiri.\n(Kita butuh bantuan Tuhan!)", () => { startLevel(2); });
            }
        }

        function showDialogue(text, callback) {
            gameState = "dialogue"; dialogueText.innerText = text;
            dialogueBox.style.animation = 'none'; dialogueBox.offsetHeight; 
            dialogueBox.style.animation = null; dialogueBox.style.display = "block";
            dialogueCallback = callback;
        }

        function nextDialogue() {
            initAudio(); 
            if(isFirstInteraction) { isFirstInteraction = false; if(!isMusicOn) toggleMusic(); }
            dialogueBox.style.display = "none"; gameState = "playing";
            if (dialogueCallback) dialogueCallback();
        }

        function updateHUD() {
            levelDisplay.innerText = "Lvl: " + currentLevel;
            ammoDisplay.innerText = infiniteAmmo ? "Kasih: 💖 FULL" : "Kasih: " + "💖".repeat(player.ammo);
        }

        function startLevel(level) {
            currentLevel = level; projectiles = []; npcs = []; interactables = []; particles = [];
            generateObstacles(level); keys.space = false;

            if (level === 1) {
                player.x = 400; player.y = 500; player.ammo = 2; infiniteAmmo = false; player.emoji = "👦";
                // Di level 1 semua hp = 1 (Kecil) agar skenario peluru habis bekerja sempurna
                npcs.push({x: 200, y: 120, size: 30, emoji: "😡", status: "angry", hp: 1});
                npcs.push({x: 400, y: 80, size: 30, emoji: "😡", status: "angry", hp: 1});
                npcs.push({x: 600, y: 120, size: 30, emoji: "😡", status: "angry", hp: 1});
                updateHUD();
                showDialogue("Halo anak-anak Sekolah Minggu GKJ Salib Putih!\nNamamu Dito. Teman-temanmu sedang marah (😡). Bagikan kasihmu (Tembak 💖)! Ingat, amunisimu terbatas.", null);
            }
            else if (level === 2) {
                player.x = 400; player.y = 500; player.ammo = 0;
                interactables.push({x: 400, y: 150, size: 50, emoji: "👩‍🏫", type: "guru"}); updateHUD();
                showDialogue("LEVEL 2: Gelas yang Bocor.\nKamu tidak bisa mengasihi sendiri. Temui Guru Sekolah Minggumu (👩‍🏫) di depan.");
            }
            else if (level === 3) {
                player.x = 400; player.y = 500;
                interactables.push({x: 400, y: 150, size: 60, emoji: "✝️", type: "salib"}); updateHUD();
                showDialogue("LEVEL 3: Kasih yang Sejati.\nDosa kita harus dihukum, tapi Allah mengasihi kita. Temui Salib Kristus (✝️).");
            }
            else if (level === 4) {
                infiniteAmmo = true; player.emoji = "😇"; updateHUD();
                createParticles(400, 300, "holy"); sfx.holy(); 
                showDialogue("LEVEL 4: Hati yang Baru ✨\n'Kita mengasihi, karena Allah lebih dahulu mengasihi kita.' \nBerkat Yesus, kamu mendapat hati baru dengan kasih TAK TERBATAS!", () => { startLevel(5); });
            }
            else if (level === 5) {
                // Di level 5 musuh bervariasi HP dan ukurannya!
                const enemyTypes = [
                    { emoji: "😡", size: 30, hp: 1, speed: 1.5 },
                    { emoji: "👿", size: 45, hp: 2, speed: 1.2 },
                    { emoji: "👹", size: 60, hp: 3, speed: 0.9 }
                ];
                for(let i=0; i<6; i++) {
                    let type = enemyTypes[Math.floor(Math.random() * enemyTypes.length)];
                    npcs.push({ x: Math.random() * 600 + 100, y: Math.random() * 200 + 50, size: type.size, emoji: type.emoji, status: "angry", speed: type.speed + Math.random(), hp: type.hp });
                }
                updateHUD();
                showDialogue("LEVEL 5: Hati Keras Tunduk Oleh Kasih.\nAda teman yang SANGAT MARAH (👹 Besar, butuh 3 tembakan). \nBuktikan bahwa Kasih Tuhan mampu melembutkan hati yang paling keras sekalipun 🥰!");
            }
        }

        function restartGame() { rewardScreen.style.display = "none"; startLevel(1); }

        function update() {
            if (gameState === "playing") {
                let oldX = player.x; let oldY = player.y;

                if (keys.w && player.y > player.size) { player.y -= player.speed; player.faceDir = "up"; }
                if (keys.s && player.y < canvas.height - player.size) { player.y += player.speed; player.faceDir = "down"; }
                if (keys.a && player.x > player.size) { player.x -= player.speed; player.faceDir = "left"; }
                if (keys.d && player.x < canvas.width - player.size) { player.x += player.speed; player.faceDir = "right"; }

                obstacles.forEach(obs => { if (checkCollision(player, obs, -10)) { player.x = oldX; player.y = oldY; } });

                for (let i = projectiles.length - 1; i >= 0; i--) {
                    let p = projectiles[i]; p.x += p.vx; p.y += p.vy;
                    let hitObstacle = obstacles.some(obs => checkCollision(p, obs, -10));
                    if (p.x < 0 || p.x > canvas.width || p.y < 0 || p.y > canvas.height || hitObstacle) projectiles.splice(i, 1);
                }

                for (let i = projectiles.length - 1; i >= 0; i--) {
                    let p = projectiles[i]; let hit = false;
                    for (let j = 0; j < npcs.length; j++) {
                        let npc = npcs[j];
                        // Hitbox otomatis mengikuti ukuran npc.size
                        if (npc.status === "angry" && checkCollision(p, npc, 5)) { 
                            npc.hp--;
                            createParticles(npc.x, npc.y, "normal"); 
                            sfx.hit(); 
                            projectiles.splice(i, 1); hit = true; 
                            
                            // MEKANIK BARU: Turunkan tingkat kemarahan & ukuran
                            if(npc.hp <= 0) {
                                npc.status = "happy"; npc.emoji = "🥰"; npc.size = 35; 
                                checkLevel5Win();
                            } else if (npc.hp === 2) {
                                npc.emoji = "👿"; npc.size = 45; npc.speed += 0.3;
                                npc.x += (p.vx > 0 ? 5 : -5); npc.y += (p.vy > 0 ? 5 : -5); // Efek terdorong sedikit
                            } else if (npc.hp === 1) {
                                npc.emoji = "😡"; npc.size = 30; npc.speed += 0.3;
                                npc.x += (p.vx > 0 ? 5 : -5); npc.y += (p.vy > 0 ? 5 : -5);
                            }
                            break;
                        }
                    }
                }

                if (currentLevel === 5) {
                    npcs.forEach(npc => {
                        if (npc.status === "angry") {
                            npc.x += (Math.random() - 0.5) * npc.speed * 2; npc.y += (Math.random() - 0.5) * npc.speed * 2;
                            if(npc.x < 50) npc.x = 50; if(npc.x > 750) npc.x = 750;
                            if(npc.y < 50) npc.y = 50; if(npc.y > 550) npc.y = 550;
                            
                            obstacles.forEach(obs => {
                                if(checkCollision(npc, obs)) {
                                    npc.x += (npc.x > obs.x ? 5 : -5); npc.y += (npc.y > obs.y ? 5 : -5);
                                }
                            });
                        }
                    });
                }

                interactables.forEach((item, index) => {
                    if (checkCollision(player, item)) {
                        if (item.type === "guru" && currentLevel === 2) startLevel(3);
                        else if (item.type === "salib" && currentLevel === 3) {
                            interactables.splice(index, 1); createParticles(item.x, item.y, "holy"); sfx.holy(); 
                            keys.w = keys.a = keys.s = keys.d = false; 
                            showDialogue("Di kayu salib, Yesus menanggung dosa kita.\nCerita tidak berhenti di sini! Temui Makam Kosong (🌅) di sebelah kanan.", () => {
                                interactables.push({x: 600, y: 150, size: 60, emoji: "🌅", type: "makam"});
                            });
                        } else if (item.type === "makam" && currentLevel === 3) {
                            interactables.splice(index, 1); createParticles(item.x, item.y, "holy"); sfx.holy(); 
                            keys.w = keys.a = keys.s = keys.d = false;
                            showDialogue("KEBANGKITAN KRISTUS! 🌅\nYesus bangkit mengalahkan maut! Ia berkuasa memberimu hati yang baru.", () => { startLevel(4); });
                        }
                    }
                });

                for (let i = particles.length - 1; i >= 0; i--) {
                    particles[i].x += particles[i].vx; particles[i].y += particles[i].vy; particles[i].alpha -= 0.03; 
                    if (particles[i].alpha <= 0) particles.splice(i, 1);
                }
            }
            
            if (gameState === "reward") {
                if(Math.random() < 0.2) createParticles(Math.random() * canvas.width, -20, "confetti");
                for (let i = particles.length - 1; i >= 0; i--) {
                    particles[i].x += particles[i].vx; particles[i].y += particles[i].vy; particles[i].alpha -= 0.01; 
                    if (particles[i].alpha <= 0) particles.splice(i, 1);
                }
            }
        }

        function checkLevel5Win() {
            if (currentLevel === 5) {
                let allHappy = npcs.every(n => n.status === "happy");
                if (allHappy) {
                    setTimeout(() => {
                        keys.w = keys.a = keys.s = keys.d = keys.space = false; gameState = "reward"; rewardScreen.style.display = "flex";
                        sfx.win(); for(let i=0; i<3; i++) createParticles(Math.random() * canvas.width, -20, "confetti");
                    }, 500); 
                }
            }
        }

        function draw() {
            ctx.fillStyle = "#55efc4"; ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = "#00b894";
            for(let i=0; i<30; i++) {
                let x = (Math.sin(i * 123) * 0.5 + 0.5) * canvas.width; let y = (Math.cos(i * 321) * 0.5 + 0.5) * canvas.height;
                ctx.fillRect(x, y, 10, 4); ctx.fillRect(x + 2, y - 4, 4, 8);
            }

            if(gameState !== "reward") {
                obstacles.forEach(obs => { ctx.font = obs.size + "px Arial"; ctx.fillText(obs.emoji, obs.x, obs.y); });

                interactables.forEach(item => {
                    if (item.type === "salib" || item.type === "makam") {
                        let gradient = ctx.createRadialGradient(item.x, item.y, 10, item.x, item.y, 60);
                        gradient.addColorStop(0, "rgba(253, 203, 110, 0.8)"); gradient.addColorStop(1, "rgba(253, 203, 110, 0)");
                        ctx.fillStyle = gradient; ctx.beginPath(); ctx.arc(item.x, item.y, 60, 0, Math.PI*2); ctx.fill();
                    }
                    ctx.font = item.size + "px Arial"; ctx.fillText(item.emoji, item.x, item.y);
                });

                npcs.forEach(npc => { ctx.font = npc.size + "px Arial"; ctx.fillText(npc.emoji, npc.x, npc.y); });
                projectiles.forEach(p => { ctx.font = p.size + "px Arial"; ctx.fillText("💖", p.x, p.y); });
                
                ctx.font = "40px Arial"; ctx.fillText(player.emoji, player.x, player.y);
                ctx.fillStyle = "#2d3436"; let faceX = player.x, faceY = player.y;
                if(player.faceDir === "up") { faceY -= 30; ctx.fillText("🔺", faceX, faceY); }
                if(player.faceDir === "down") { faceY += 30; ctx.fillText("🔻", faceX, faceY); }
                if(player.faceDir === "left") { faceX -= 30; ctx.fillText("👈", faceX, faceY); }
                if(player.faceDir === "right") { faceX += 30; ctx.fillText("👉", faceX, faceY); }
            }

            particles.forEach(p => { ctx.globalAlpha = p.alpha; ctx.font = p.size + "px Arial"; ctx.fillText(p.emoji, p.x, p.y); });
            ctx.globalAlpha = 1.0; 
        }

        function gameLoop() { update(); draw(); requestAnimationFrame(gameLoop); }

        startLevel(1); gameLoop();
    </script>
</body>
</html>
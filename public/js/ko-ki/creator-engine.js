export const CREATOR_DEFAULTS = Object.freeze({
    stage: 0,
    character: 'koki',
    theme: 'green',
    controls: { left: 'SPACE', right: 'LEFT', jump: 'RIGHT' },
    collectible: 'leaf',
    collect_rule: 'none',
    obstacle: 'rock',
    obstacle_rule: 'start',
    target_score: 3,
    tested_win: false,
    playtested: false,
    playtest_checks: { control: false, collectible: false, score: false, obstacle: false, win: false },
    bonuses: { second_collectible: false, special_plus_three: false, second_obstacle: false, sound: false, timer: false, target: false },
});

export const CREATOR_OPTIONS = Object.freeze({
    characters: { koki: ['Ko-Ki Bot', '🤖'], cat: ['Robo Cat', '🐱'], car: ['Eco Car', '🚙'] },
    themes: { green: ['Green Adventure', '🌿'], space: ['Space Adventure', '🪐'], treasure: ['Treasure Hunt', '🏴‍☠️'] },
    collectibles: { leaf: ['Daun / Bibit', '🌱'], star: ['Bintang', '⭐'], gem: ['Permata', '💎'] },
    obstacles: { rock: ['Batu', '🪨'], puddle: ['Genangan', '💦'], crate: ['Kotak', '📦'] },
});

const ITEM_POSITIONS = [6, 2, 8, 3, 7, 1, 9, 5, 2, 8];
const SECOND_ITEM_POSITIONS = [8, 3, 9, 2, 6, 1, 7, 5];
export const JUMP_DURATION_MS = 850;
export const COLLECTIBLE_RESPAWN_MS = 900;
export const MOVING_OBSTACLE_STEP_MS = 700;
export const MOVING_WORLD_STEP_MS = 600;
const MOVING_OBSTACLE_PATH = [3, 4, 5, 6, 5, 4];
const TWIN_OBSTACLE_LEFT_PATH = [2, 3, 4, 5, 4, 3];
const TWIN_OBSTACLE_RIGHT_PATH = [6, 7, 8, 9, 8, 7];

export function normalizeCreatorConfig(input = {}) {
    return {
        ...CREATOR_DEFAULTS,
        ...input,
        controls: { ...CREATOR_DEFAULTS.controls, ...(input.controls || {}) },
        playtest_checks: { ...CREATOR_DEFAULTS.playtest_checks, ...(input.playtest_checks || {}) },
        bonuses: { ...CREATOR_DEFAULTS.bonuses, ...(input.bonuses || {}) },
        target_score: Number(input.target_score || 3),
        stage: Math.max(0, Math.min(4, Number(input.stage || 0))),
        tested_win: Boolean(input.tested_win),
        playtested: Boolean(input.playtested),
    };
}

export function controlMappingValid(controls) {
    return Object.values(controls).length === 3 && new Set(Object.values(controls)).size === 3
        && ['LEFT', 'RIGHT', 'SPACE'].every((key) => Object.values(controls).includes(key));
}

export class CreatorGameEngine {
    constructor(config = {}, clock = () => Date.now()) {
        this.config = normalizeCreatorConfig(config);
        this.clock = clock;
        this.reset();
    }

    reset() {
        this.x = 1;
        this.score = 0;
        this.itemIndex = 0;
        this.itemX = ITEM_POSITIONS[0];
        this.itemHiddenUntil = 0;
        this.secondItemIndex = 0;
        this.secondItemX = SECOND_ITEM_POSITIONS[0];
        this.secondItemHiddenUntil = 0;
        this.obstacleX = this.config.bonuses.dragon_boss ? 9 : 4;
        this.secondObstacleX = this.config.bonuses.dragon_boss ? 6.5 : 7;
        this.thirdObstacleX = 8.5;
        this.fourthObstacleX = 7.5;
        this.obstacleLane = 'ground';
        this.secondObstacleLane = this.config.bonuses.aerial_obstacles ? 'air' : 'ground';
        this.thirdObstacleLane = 'ground';
        this.fourthObstacleLane = 'air';
        this.lastDragonIntensity = 1;
        this.jumpingUntil = 0;
        this.won = false;
        this.timedOut = false;
        this.startedAt = this.clock();
        this.lastWorldMoveAt = this.startedAt;
        this.lastWorldObstacleHitAt = Number.NEGATIVE_INFINITY;
        this.usedKeys = new Set();
        this.lastEvent = this.config.bonuses.dragon_boss
            ? 'Naga mengirim rintangan! Lompat untuk yang bawah, tetap di tanah untuk yang atas.'
            : this.config.bonuses.aerial_obstacles
                ? 'Lompati rintangan bawah. Tetap di tanah saat rintangan di atas!'
            : 'Mulai bermain!';
        return this.snapshot();
    }

    useKey(key) {
        this.updateTimer();
        const action = Object.entries(this.config.controls).find(([, mapped]) => mapped === key)?.[0];
        if (!action || this.won || this.timedOut) return this.snapshot();
        this.usedKeys.add(key);
        if (action === 'jump') {
            if (this.isJumping()) {
                this.lastEvent = 'Ko-Ki sedang melompat.';
                return this.snapshot();
            }
            this.jumpingUntil = this.clock() + JUMP_DURATION_MS;
            this.lastEvent = 'Lompat!';
            return this.snapshot();
        }
        this.x = Math.max(0, Math.min(9, this.x + (action === 'left' ? -1 : 1)));
        this.updateMovingObstacle();
        this.updateMovingWorld();
        if (this.resolveContacts()) return this.snapshot();
        this.lastEvent = action === 'left' ? 'Bergerak ke kiri.' : 'Bergerak ke kanan.';
        return this.snapshot();
    }

    resolveContacts() {
        const touches = (position) => this.config.bonuses.moving_world
            ? Math.abs(this.x - position) <= .32
            : this.x === position;
        const hitsLane = (lane) => lane === 'air' ? this.isJumping() : !this.isJumping();
        const dragonCount = this.dragonObstacleCount();
        const hitObstacle = touches(this.obstacleX) && hitsLane(this.obstacleLane)
            || (this.config.bonuses.second_obstacle && (!this.config.bonuses.dragon_boss || dragonCount >= 2) && touches(this.secondObstacleX) && hitsLane(this.secondObstacleLane))
            || (this.config.bonuses.dragon_boss && dragonCount >= 3 && touches(this.thirdObstacleX) && hitsLane(this.thirdObstacleLane))
            || (this.config.bonuses.dragon_boss && dragonCount >= 4 && touches(this.fourthObstacleX) && hitsLane(this.fourthObstacleLane));
        const obstacleReady = !this.config.bonuses.moving_world
            || this.clock() - this.lastWorldObstacleHitAt >= this.worldStepMs() * .8;
        if (this.config.stage >= 3 && hitObstacle && obstacleReady) {
            this.lastWorldObstacleHitAt = this.clock();
            if (this.config.obstacle_rule === 'minus_one') {
                this.score = Math.max(0, this.score - 1);
                this.lastEvent = 'Tersentuh rintangan: skor -1.';
            } else {
                this.x = 1;
                this.lastEvent = 'Tersentuh rintangan: kembali ke start.';
            }
            return true;
        }
        const firstItem = touches(this.itemX) && this.clock() >= this.itemHiddenUntil;
        const secondItem = this.config.bonuses.second_collectible && touches(this.secondItemX) && this.clock() >= this.secondItemHiddenUntil;
        if (this.config.stage >= 2 && (firstItem || secondItem) && this.config.collect_rule !== 'score_move') {
            this.lastEvent = 'Item tersentuh, tetapi aturan collectible belum dipasang.';
            return true;
        }
        if (this.config.stage >= 2 && (firstItem || secondItem) && this.config.collect_rule === 'score_move') {
            const points = secondItem && this.config.bonuses.special_plus_three ? 3 : 1;
            this.score += points;
            if (secondItem) {
                this.secondItemHiddenUntil = this.clock() + COLLECTIBLE_RESPAWN_MS;
                this.secondItemIndex++;
                this.secondItemX = this.config.bonuses.moving_world ? 8 : SECOND_ITEM_POSITIONS[this.secondItemIndex % SECOND_ITEM_POSITIONS.length];
                if (this.secondItemX === this.x || this.secondItemX === this.itemX) this.secondItemX = (this.secondItemX + 1) % 10;
            } else {
                this.itemHiddenUntil = this.clock() + COLLECTIBLE_RESPAWN_MS;
                this.itemIndex++;
                this.itemX = this.config.bonuses.moving_world ? 9 : ITEM_POSITIONS[this.itemIndex % ITEM_POSITIONS.length];
                if (this.itemX === this.x || this.itemX === this.secondItemX) this.itemX = (this.itemX + 2) % 10;
            }
            this.won = this.config.stage >= 4 && this.score >= this.config.target_score;
            this.lastEvent = this.won ? 'MENANG! Gim buatanmu berhasil!' : `Item didapat! Skor +${points}, item pindah.`;
            return true;
        }
        return false;
    }

    isJumping() {
        return this.clock() < this.jumpingUntil;
    }

    updateTimer() {
        if (!this.config.bonuses.timer || this.won || this.timedOut) return;
        if (this.clock() - this.startedAt >= 30000) {
            this.timedOut = true;
            this.lastEvent = 'Waktu habis! Ulangi gim untuk mencoba lagi.';
        }
    }

    updateMovingObstacle() {
        const elapsed = Math.max(0, this.clock() - this.startedAt);
        const step = Math.floor(elapsed / MOVING_OBSTACLE_STEP_MS);
        if (this.config.bonuses.moving_obstacles_together) {
            this.obstacleX = TWIN_OBSTACLE_LEFT_PATH[step % TWIN_OBSTACLE_LEFT_PATH.length];
            this.secondObstacleX = TWIN_OBSTACLE_RIGHT_PATH[step % TWIN_OBSTACLE_RIGHT_PATH.length];
        } else if (this.config.bonuses.moving_obstacle) {
            this.obstacleX = MOVING_OBSTACLE_PATH[step % MOVING_OBSTACLE_PATH.length];
        }
    }

    updateMovingWorld() {
        if (!this.config.bonuses.moving_world) return false;
        const now = this.clock();
        const elapsed = Math.max(0, now - this.lastWorldMoveAt);
        if (elapsed < 16) return false;
        const distance = Math.min(12, elapsed / this.worldStepMs());
        const wrap = (position) => ((position - distance) % 10 + 10) % 10;
        const previousObstacleX = this.obstacleX;
        const previousSecondObstacleX = this.secondObstacleX;
        const previousThirdObstacleX = this.thirdObstacleX;
        const previousFourthObstacleX = this.fourthObstacleX;
        this.itemX = wrap(this.itemX);
        this.secondItemX = wrap(this.secondItemX);
        this.obstacleX = wrap(this.obstacleX);
        this.secondObstacleX = wrap(this.secondObstacleX);
        this.thirdObstacleX = wrap(this.thirdObstacleX);
        this.fourthObstacleX = wrap(this.fourthObstacleX);
        if (this.config.bonuses.aerial_obstacles) {
            if (this.obstacleX > previousObstacleX) this.obstacleLane = this.obstacleLane === 'ground' ? 'air' : 'ground';
            if (this.secondObstacleX > previousSecondObstacleX) this.secondObstacleLane = this.secondObstacleLane === 'ground' ? 'air' : 'ground';
            if (this.thirdObstacleX > previousThirdObstacleX) this.thirdObstacleLane = this.thirdObstacleLane === 'ground' ? 'air' : 'ground';
            if (this.fourthObstacleX > previousFourthObstacleX) this.fourthObstacleLane = this.fourthObstacleLane === 'ground' ? 'air' : 'ground';
        }
        const dragonIntensity = this.dragonObstacleCount();
        if (this.config.bonuses.dragon_boss && dragonIntensity > this.lastDragonIntensity) {
            if (dragonIntensity === 2) this.secondObstacleX = 9.5;
            if (dragonIntensity === 3) this.thirdObstacleX = 9.5;
            if (dragonIntensity === 4) this.fourthObstacleX = 9.5;
            this.lastDragonIntensity = dragonIntensity;
        }
        this.lastWorldMoveAt = now;
        return true;
    }

    worldStepMs() {
        if (this.config.bonuses.dragon_boss) {
            return Math.max(360, MOVING_WORLD_STEP_MS - Math.floor(this.score / 10) * 80);
        }
        return this.score >= 12 ? 420 : this.score >= 6 ? 500 : MOVING_WORLD_STEP_MS;
    }

    dragonObstacleCount() {
        return this.config.bonuses.dragon_boss ? Math.min(4, Math.floor(this.score / 10) + 1) : 2;
    }

    tick() {
        if (this.won || this.timedOut) return this.snapshot();
        this.updateTimer();
        const moved = this.updateMovingWorld();
        if (moved) {
            this.resolveContacts();
        }
        return this.snapshot();
    }

    snapshot() {
        this.updateTimer();
        this.updateMovingObstacle();
        return {
            x: this.x, score: this.score, itemX: this.itemX, secondItemX: this.secondItemX,
            itemVisible: this.clock() >= this.itemHiddenUntil,
            secondItemVisible: this.clock() >= this.secondItemHiddenUntil,
            obstacleX: this.obstacleX, secondObstacleX: this.secondObstacleX,
            obstacleLane: this.obstacleLane, secondObstacleLane: this.secondObstacleLane,
            thirdObstacleX: this.thirdObstacleX, fourthObstacleX: this.fourthObstacleX,
            thirdObstacleLane: this.thirdObstacleLane, fourthObstacleLane: this.fourthObstacleLane,
            dragonObstacleCount: this.dragonObstacleCount(),
            worldStage: this.config.bonuses.moving_world
                ? Math.min(this.config.bonuses.dragon_boss ? 4 : 3, Math.floor(this.score / (this.config.bonuses.dragon_boss ? 10 : 6)) + 1)
                : null,
            speedLevel: this.config.bonuses.dragon_boss ? Math.min(4, Math.floor(this.score / 10) + 1) : null,
            jumping: this.isJumping(), won: this.won, timedOut: this.timedOut,
            remainingSeconds: this.config.bonuses.timer ? Math.max(0, Math.ceil((30000 - (this.clock() - this.startedAt)) / 1000)) : null,
            usedKeys: [...this.usedKeys], event: this.lastEvent,
        };
    }
}

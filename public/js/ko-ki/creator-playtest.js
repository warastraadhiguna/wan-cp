export const PLAYTEST_CHECK_KEYS = ['control', 'collectible', 'score', 'obstacle', 'win'];

export function emptyPlaytestChecks() {
    return { control: false, collectible: false, score: false, obstacle: false, win: false };
}

export function updatePlaytestChecks(checks, config, before, after) {
    const correctControls = config.controls.left === 'LEFT'
        && config.controls.right === 'RIGHT'
        && config.controls.jump === 'SPACE';

    return {
        control: checks.control || (correctControls && ['LEFT', 'RIGHT', 'SPACE'].every((key) => after.usedKeys.includes(key))),
        collectible: checks.collectible || (after.score > before.score && (after.itemX !== before.itemX || after.secondItemX !== before.secondItemX)),
        score: checks.score || after.score > before.score,
        obstacle: checks.obstacle || after.event.startsWith('Tersentuh rintangan'),
        win: checks.win || after.won,
    };
}

export function playtestPassed(checks) {
    return PLAYTEST_CHECK_KEYS.every((key) => checks[key]);
}

export function nextDebugStage(checks) {
    if (!checks.control) return 0;
    if (!checks.collectible || !checks.score) return 1;
    if (!checks.obstacle) return 2;
    return 3;
}

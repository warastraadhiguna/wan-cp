import test from 'node:test';
import assert from 'node:assert/strict';
import { CreatorGameEngine } from '../../public/js/ko-ki/creator-engine.js';
import { emptyPlaytestChecks, nextDebugStage, playtestPassed, updatePlaytestChecks } from '../../public/js/ko-ki/creator-playtest.js';

const config = { stage: 4, controls: { left: 'LEFT', right: 'RIGHT', jump: 'SPACE' }, collect_rule: 'score_move', obstacle_rule: 'start', target_score: 3 };

test('playtest observes real control, collectible, score, obstacle, and win events', () => {
    let time = 0;
    const game = new CreatorGameEngine(config, () => time);
    let checks = emptyPlaytestChecks();
    const press = (key) => {
        time += 100;
        const before = game.snapshot();
        const after = game.useKey(key);
        checks = updatePlaytestChecks(checks, config, before, after);
    };

    press('LEFT');
    press('RIGHT');
    press('RIGHT');
    press('RIGHT');
    press('RIGHT');
    assert.equal(checks.obstacle, true);
    assert.equal(playtestPassed(checks), false);

    game.reset();
    press('SPACE');
    for (let step = 0; step < 5; step++) press('RIGHT');
    press('LEFT');
    assert.equal(checks.control, true);
    assert.equal(checks.collectible, true);
    assert.equal(checks.score, true);

    for (let step = 0; step < 30 && !game.won; step++) {
        press('SPACE');
        press(game.x < game.itemX ? 'RIGHT' : 'LEFT');
    }
    assert.equal(checks.win, true);
    assert.equal(playtestPassed(checks), true);
});

test('debug points to a rule without revealing its answer', () => {
    assert.equal(nextDebugStage(emptyPlaytestChecks()), 0);
    assert.equal(nextDebugStage({ control: true, collectible: false, score: false, obstacle: false, win: false }), 1);
    assert.equal(nextDebugStage({ control: true, collectible: true, score: true, obstacle: false, win: false }), 2);
    assert.equal(nextDebugStage({ control: true, collectible: true, score: true, obstacle: true, win: false }), 3);
});

test('using all keys does not approve an incorrect control mapping', () => {
    const wrong = { ...config, controls: { left: 'SPACE', right: 'LEFT', jump: 'RIGHT' } };
    const before = { score: 0, itemX: 6, secondItemX: 8 };
    const after = { ...before, usedKeys: ['LEFT', 'RIGHT', 'SPACE'], event: 'Lompat!', won: false };
    assert.equal(updatePlaytestChecks(emptyPlaytestChecks(), wrong, before, after).control, false);
});

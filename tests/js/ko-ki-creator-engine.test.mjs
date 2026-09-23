import test from 'node:test';
import assert from 'node:assert/strict';
import { COLLECTIBLE_RESPAWN_MS, MOVING_OBSTACLE_STEP_MS, MOVING_WORLD_STEP_MS, CreatorGameEngine, controlMappingValid } from '../../public/js/ko-ki/creator-engine.js';

const controls = { left: 'LEFT', right: 'RIGHT', jump: 'SPACE' };
const setup = (overrides = {}, clock = () => 0) => new CreatorGameEngine({ controls, ...overrides }, clock);

test('challenge one maps left right and space to real movement and jump', () => {
    const game = setup({ stage: 1 });
    assert.equal(controlMappingValid(controls), true);
    assert.equal(game.useKey('RIGHT').x, 2);
    assert.equal(game.useKey('LEFT').x, 1);
    assert.equal(game.useKey('SPACE').jumping, true);
    assert.deepEqual(game.snapshot().usedKeys, ['RIGHT', 'LEFT', 'SPACE']);
});

test('challenge two collects item, adds one point, and moves item', () => {
    let time = 0;
    const game = setup({ stage: 2, collect_rule: 'score_move' }, () => time);
    for (let step = 0; step < 5; step++) game.useKey('RIGHT');
    assert.equal(game.snapshot().score, 1);
    assert.equal(game.snapshot().itemX, 2);
    assert.equal(game.snapshot().itemVisible, false);
    assert.match(game.snapshot().event, /Item didapat/);
    for (let step = 0; step < 4; step++) game.useKey('LEFT');
    assert.equal(game.snapshot().score, 1);
    time = COLLECTIBLE_RESPAWN_MS + 1;
    assert.equal(game.snapshot().itemVisible, true);
    game.useKey('RIGHT');
    assert.equal(game.useKey('LEFT').score, 2);
});

test('touching an item before installing its rule explains why it remains', () => {
    const game = setup({ stage: 2, collect_rule: 'none' });
    for (let step = 0; step < 5; step++) game.useKey('RIGHT');
    assert.equal(game.snapshot().score, 0);
    assert.equal(game.snapshot().itemVisible, true);
    assert.match(game.snapshot().event, /aturan collectible belum dipasang/);
});

test('challenge three applies selected obstacle rule without game over', () => {
    const restart = setup({ stage: 3, obstacle_rule: 'start' });
    for (let step = 0; step < 3; step++) restart.useKey('RIGHT');
    assert.equal(restart.snapshot().x, 1);
    assert.equal(restart.snapshot().won, false);

    const minusOne = setup({ stage: 3, obstacle_rule: 'minus_one' });
    for (let step = 0; step < 3; step++) minusOne.useKey('RIGHT');
    assert.equal(minusOne.snapshot().x, 4);
    assert.equal(minusOne.snapshot().score, 0);
    assert.match(minusOne.snapshot().event, /skor -1/);
});

test('jump lets player cross an obstacle and later lands', () => {
    let time = 0;
    const game = setup({ stage: 3 }, () => time);
    game.useKey('RIGHT');
    game.useKey('RIGHT');
    game.useKey('SPACE');
    assert.equal(game.useKey('RIGHT').x, 4);
    time = 400;
    game.useKey('SPACE');
    assert.match(game.snapshot().event, /sedang melompat/);
    time = 900;
    assert.equal(game.snapshot().jumping, false);
});

test('challenge four wins exactly when chosen target score is reached', () => {
    let time = 0;
    const game = setup({ stage: 4, collect_rule: 'score_move', obstacle_rule: 'minus_one', target_score: 3 }, () => time);
    const collect = () => {
        const destination = game.itemX;
        while (game.x !== destination) {
            game.useKey('SPACE');
            game.useKey(game.x < destination ? 'RIGHT' : 'LEFT');
        }
        time += COLLECTIBLE_RESPAWN_MS + 1;
    };
    collect();
    assert.equal(game.snapshot().won, false);
    collect();
    assert.equal(game.snapshot().won, false);
    collect();
    assert.equal(game.snapshot().score, 3);
    assert.equal(game.snapshot().won, true);
    assert.equal(game.useKey('LEFT').x, game.snapshot().x);
});

test('target five, ten, and bonus fifteen stay configurable and reset clears score and position', () => {
    for (const target of [5, 10, 15]) {
        const game = setup({ stage: 4, collect_rule: 'score_move', target_score: target });
        assert.equal(game.config.target_score, target);
        game.useKey('RIGHT');
        assert.equal(game.reset().x, 1);
        assert.equal(game.snapshot().score, 0);
    }
});

test('second collectible and special item create two independent scoring rules', () => {
    let time = 0;
    const game = setup({ stage: 4, collect_rule: 'score_move', target_score: 10, bonuses: { second_collectible: true, special_plus_three: true } }, () => time);
    game.useKey('SPACE');
    for (let step = 0; step < 5; step++) game.useKey('RIGHT');
    assert.equal(game.snapshot().score, 1);
    assert.equal(game.snapshot().itemX, 2);
    for (let step = 0; step < 2; step++) game.useKey('RIGHT');
    assert.equal(game.snapshot().score, 4);
    assert.notEqual(game.snapshot().secondItemX, 8);
    assert.equal(game.snapshot().secondItemVisible, false);
    assert.match(game.snapshot().event, /\+3/);
    time = COLLECTIBLE_RESPAWN_MS + 1;
    assert.equal(game.snapshot().secondItemVisible, true);
});

test('second obstacle applies the selected rule and can be jumped', () => {
    let time = 0;
    const game = setup({ stage: 4, bonuses: { second_obstacle: true }, obstacle_rule: 'start' }, () => time);
    game.useKey('SPACE');
    for (let step = 0; step < 6; step++) game.useKey('RIGHT');
    assert.equal(game.snapshot().x, 7);
    game.useKey('LEFT');
    time = 2500;
    assert.equal(game.useKey('RIGHT').x, 1);
});

test('optional thirty-second timer pauses play at zero and resets cleanly', () => {
    let time = 0;
    const game = setup({ stage: 4, bonuses: { timer: true } }, () => time);
    assert.equal(game.snapshot().remainingSeconds, 30);
    time = 30000;
    assert.equal(game.snapshot().timedOut, true);
    assert.equal(game.useKey('RIGHT').x, 1);
    time = 31000;
    assert.equal(game.reset().remainingSeconds, 30);
    assert.equal(game.useKey('RIGHT').x, 2);
});

test('moving obstacle follows a fixed timed patrol independent of player input', () => {
    let time = 0;
    const game = setup({ stage: 4, bonuses: { moving_obstacle: true }, obstacle_rule: 'minus_one' }, () => time);
    assert.equal(game.snapshot().obstacleX, 3);
    game.useKey('SPACE');
    assert.equal(game.snapshot().obstacleX, 3);
    time = MOVING_OBSTACLE_STEP_MS;
    assert.equal(game.snapshot().obstacleX, 4);
    time = MOVING_OBSTACLE_STEP_MS * 2;
    assert.equal(game.snapshot().obstacleX, 5);
    time = MOVING_OBSTACLE_STEP_MS * 3;
    assert.equal(game.snapshot().obstacleX, 6);
    time = MOVING_OBSTACLE_STEP_MS * 6;
    assert.equal(game.snapshot().obstacleX, 3);
});

test('twin obstacles patrol together on two fixed parallel paths', () => {
    let time = 0;
    const game = setup({ stage: 4, bonuses: { second_obstacle: true, moving_obstacles_together: true }, obstacle_rule: 'minus_one' }, () => time);
    assert.deepEqual([game.snapshot().obstacleX, game.snapshot().secondObstacleX], [2, 6]);
    time = MOVING_OBSTACLE_STEP_MS;
    assert.deepEqual([game.snapshot().obstacleX, game.snapshot().secondObstacleX], [3, 7]);
    time = MOVING_OBSTACLE_STEP_MS * 3;
    assert.deepEqual([game.snapshot().obstacleX, game.snapshot().secondObstacleX], [5, 9]);
    time = MOVING_OBSTACLE_STEP_MS * 6;
    assert.deepEqual([game.snapshot().obstacleX, game.snapshot().secondObstacleX], [2, 6]);
});

test('moving world sends scenery objects left and wraps them to the right', () => {
    let time = 0;
    const game = setup({
        stage: 1, collect_rule: 'score_move', obstacle_rule: 'minus_one', target_score: 18,
        bonuses: { second_collectible: true, second_obstacle: true, moving_world: true },
    }, () => time);
    assert.deepEqual([game.snapshot().itemX, game.snapshot().secondItemX, game.snapshot().obstacleX, game.snapshot().secondObstacleX], [6, 8, 4, 7]);
    time = MOVING_WORLD_STEP_MS;
    game.tick();
    assert.deepEqual([game.snapshot().itemX, game.snapshot().secondItemX, game.snapshot().obstacleX, game.snapshot().secondObstacleX], [5, 7, 3, 6]);
    time = MOVING_WORLD_STEP_MS * 7;
    game.tick();
    assert.deepEqual([game.snapshot().itemX, game.snapshot().secondItemX, game.snapshot().obstacleX, game.snapshot().secondObstacleX], [9, 1, 7, 0]);
});

test('moving world collects items automatically and advances its visual stage', () => {
    let time = 0;
    const game = setup({
        stage: 4, collect_rule: 'score_move', obstacle_rule: 'minus_one', target_score: 18,
        bonuses: { second_collectible: true, special_plus_three: true, second_obstacle: true, moving_world: true },
    }, () => time);
    time = MOVING_WORLD_STEP_MS * 5;
    const collected = game.tick();
    assert.equal(collected.score, 1);
    assert.equal(collected.itemVisible, false);
    game.score = 6;
    assert.equal(game.snapshot().worldStage, 2);
    game.score = 12;
    assert.equal(game.snapshot().worldStage, 3);
});

test('reset starts the moving world again from stage one with fresh objects', () => {
    let time = 0;
    const game = setup({
        stage: 4, collect_rule: 'score_move', target_score: 18,
        bonuses: { second_collectible: true, second_obstacle: true, moving_world: true },
    }, () => time);
    time = MOVING_WORLD_STEP_MS * 2;
    game.tick();
    game.score = 8;
    time += 100;
    const reset = game.reset();
    assert.equal(reset.score, 0);
    assert.equal(reset.worldStage, 1);
    assert.deepEqual([reset.x, reset.itemX, reset.secondItemX, reset.obstacleX, reset.secondObstacleX], [1, 6, 8, 4, 7]);
});

test('aerial obstacle hits while jumping but safely passes above a grounded player', () => {
    let time = 0;
    const config = {
        stage: 4, collect_rule: 'none', obstacle_rule: 'minus_one', target_score: 22,
        bonuses: { second_collectible: true, second_obstacle: true, moving_world: true, aerial_obstacles: true },
    };
    const grounded = setup(config, () => time);
    grounded.score = 2;
    time = MOVING_WORLD_STEP_MS * 6;
    assert.equal(grounded.tick().secondObstacleX, 1);
    assert.equal(grounded.snapshot().secondObstacleLane, 'air');
    assert.equal(grounded.snapshot().score, 2);

    time = 0;
    const jumping = setup(config, () => time);
    jumping.score = 2;
    time = MOVING_WORLD_STEP_MS * 5;
    jumping.tick();
    jumping.useKey('SPACE');
    time = MOVING_WORLD_STEP_MS * 6;
    const hit = jumping.tick();
    assert.equal(hit.jumping, true);
    assert.equal(hit.score, 1);
    assert.match(hit.event, /rintangan/);
});

test('ground obstacle is avoided by jumping and swaps lane after wrapping', () => {
    let time = 0;
    const game = setup({
        stage: 4, obstacle_rule: 'minus_one', target_score: 22,
        bonuses: { second_obstacle: true, moving_world: true, aerial_obstacles: true },
    }, () => time);
    game.score = 2;
    time = MOVING_WORLD_STEP_MS * 2;
    game.tick();
    game.useKey('SPACE');
    time = MOVING_WORLD_STEP_MS * 3;
    assert.equal(game.tick().score, 2);
    time = MOVING_WORLD_STEP_MS * 5;
    const wrapped = game.tick();
    assert.equal(wrapped.obstacleLane, 'air');
});

test('dragon boss targets forty points and becomes faster every ten points', () => {
    let time = 0;
    const game = setup({
        stage: 4, target_score: 40,
        bonuses: { second_obstacle: true, moving_world: true, aerial_obstacles: true, dragon_boss: true },
    }, () => time);
    assert.equal(game.config.target_score, 40);
    assert.equal(game.worldStepMs(), 600);
    assert.equal(game.snapshot().speedLevel, 1);
    assert.equal(game.snapshot().dragonObstacleCount, 1);
    assert.deepEqual([game.snapshot().obstacleX, game.snapshot().secondObstacleX], [9, 6.5]);
    game.score = 10;
    assert.equal(game.worldStepMs(), 520);
    assert.equal(game.snapshot().speedLevel, 2);
    assert.equal(game.snapshot().dragonObstacleCount, 2);
    game.score = 20;
    assert.equal(game.worldStepMs(), 440);
    assert.equal(game.snapshot().speedLevel, 3);
    assert.equal(game.snapshot().dragonObstacleCount, 3);
    game.score = 30;
    assert.equal(game.worldStepMs(), 360);
    assert.equal(game.snapshot().speedLevel, 4);
    assert.equal(game.snapshot().dragonObstacleCount, 4);
});

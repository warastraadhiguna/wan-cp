import assert from 'node:assert/strict';
import test from 'node:test';
import { calculateStars, RescueGame, RESCUE_COMMANDS } from '../../public/js/ko-ki/rescue-engine.js';
import { RESCUE_LEVELS } from '../../public/js/ko-ki/rescue-levels.js';

test('all configured rescue solutions water every plant and meet the final base rule', () => {
    assert.equal(RESCUE_LEVELS.length, 6);

    for (const level of RESCUE_LEVELS) {
        const game = new RescueGame(level);
        game.run(level.solution);

        assert.equal(game.isComplete(), true, level.name);
        assert.equal(game.errors.length, 0, `${level.name} has no command error`);
        assert.ok(level.solution.length <= level.commandLimit, `${level.name} fits command limit`);
        assert.ok(level.solution.length <= level.efficientCommands, `${level.name} earns efficiency star`);
    }
});

test('taking water only works at the water station', () => {
    const game = new RescueGame(RESCUE_LEVELS[0]);

    const result = game.execute(RESCUE_COMMANDS.TAKE_WATER, 0);

    assert.equal(result.success, false);
    assert.equal(game.water, 0);
    assert.equal(game.errors[0].type, 'NO_WATER_STATION');
});

test('wrong turn level blocks the upper shortcut and requires the winding gap', () => {
    const level = RESCUE_LEVELS[1];
    assert.ok(level.obstacles.some(({ x, y }) => x === 3 && y === 4));
    const shortcut = [
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.TAKE_WATER,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.TURN_RIGHT,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.WATER,
    ];
    const shortcutGame = new RescueGame(level);
    shortcutGame.run(shortcut);

    assert.equal(shortcutGame.isComplete(), false);
    assert.ok(shortcutGame.errors.some((error) => error.type === 'BONK'));

    const validGame = new RescueGame(level);
    validGame.run(level.solution);
    assert.equal(validGame.isComplete(), true);
    assert.equal(validGame.errors.length, 0);
    assert.ok(level.solution.filter((command) => command.includes('TURN')).length >= 5);
});

test('watering needs water and a dry plant on the robot tile', () => {
    const game = new RescueGame(RESCUE_LEVELS[0]);
    game.execute(RESCUE_COMMANDS.WATER, 0);

    assert.equal(game.wateredPlants.size, 0);
    assert.equal(game.errors[0].type, 'NO_PLANT');
});

test('battery challenge forces the alternating corridor within a tight command budget', () => {
    const level = RESCUE_LEVELS[2];
    const oldUpperRoute = [
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.TAKE_WATER,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.TURN_RIGHT,
        ...Array(6).fill(RESCUE_COMMANDS.MOVE_FORWARD),
        RESCUE_COMMANDS.TURN_RIGHT,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.WATER,
    ];
    const shortcutGame = new RescueGame(level);
    shortcutGame.run(oldUpperRoute);

    assert.equal(shortcutGame.isComplete(), false);
    assert.ok(shortcutGame.errors.some((error) => error.type === 'BONK'));
    assert.equal(level.solution.length, 20);
    assert.equal(level.commandLimit, 21);
    assert.deepEqual(
        [1, 3, 5].map((x) => level.obstacles.filter((obstacle) => obstacle.x === x).length),
        [5, 5, 5],
    );
});

test('BONK records the exact command and later commands can still run', () => {
    const level = RESCUE_LEVELS[4];
    const game = new RescueGame(level);
    const program = [
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.TAKE_WATER,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.TURN_RIGHT,
        RESCUE_COMMANDS.MOVE_FORWARD,
        RESCUE_COMMANDS.TURN_LEFT,
        RESCUE_COMMANDS.MOVE_FORWARD,
    ];

    const results = game.run(program);

    assert.equal(results[2].event, 'BONK');
    assert.equal(game.errors[0].index, 2);
    assert.deepEqual(game.robot.state, { x: 1, y: 3, direction: 'NORTH' });
});

test('danger zone uses the marked removals and added obstacles', () => {
    const level = RESCUE_LEVELS[4];
    const hasObstacle = (x, y) => level.obstacles.some((obstacle) => obstacle.x === x && obstacle.y === y);

    assert.equal(hasObstacle(3, 1), false, 'circled puddle is removed');
    assert.equal(hasObstacle(1, 3), false, 'circled rock is removed');
    assert.equal(hasObstacle(5, 1), true, 'right marked obstacle is added');
    assert.equal(hasObstacle(0, 3), true, 'left marked obstacle is added');

    const game = new RescueGame(level);
    game.run(level.solution);
    assert.equal(game.isComplete(), true);
    assert.equal(game.errors.length, 0);
});

test('stars reward completion, correct prediction, and efficiency independently', () => {
    const base = { complete: true, finalPosition: { x: 2, y: 2 }, efficientCommands: 7 };

    assert.equal(calculateStars({ ...base, prediction: { x: 0, y: 0 }, commandCount: 8 }), 1);
    assert.equal(calculateStars({ ...base, prediction: { x: 2, y: 2 }, commandCount: 8 }), 2);
    assert.equal(calculateStars({ ...base, prediction: { x: 2, y: 2 }, commandCount: 7 }), 3);
    assert.equal(calculateStars({ ...base, complete: false, prediction: { x: 2, y: 2 }, commandCount: 7 }), 0);
});

test('double rescue carries enough water for two plants in one program', () => {
    const level = RESCUE_LEVELS[3];
    const game = new RescueGame(level);

    assert.ok(level.obstacles.some(({ x, y }) => x === 4 && y === 3));
    assert.ok(level.obstacles.some(({ x, y }) => x === 5 && y === 1));

    game.run(level.solution);

    assert.equal(game.wateredPlants.size, 2);
    assert.equal(game.water, 0);
});

test('emergency garden requires returning to base after all plants are watered', () => {
    const level = RESCUE_LEVELS[5];
    const game = new RescueGame(level);
    const lastWaterCommand = level.solution.lastIndexOf(RESCUE_COMMANDS.WATER);
    const withoutReturn = level.solution.slice(0, lastWaterCommand + 1);

    assert.ok(level.obstacles.some(({ x, y }) => x === 5 && y === 2));
    assert.ok(level.obstacles.some(({ x, y }) => x === 6 && y === 4));

    game.run(withoutReturn);

    assert.equal(game.wateredPlants.size, 3);
    assert.equal(game.isComplete(), false);
});

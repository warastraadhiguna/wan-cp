import test from 'node:test';
import assert from 'node:assert/strict';
import { DetectiveCaseEngine, simulateDetectiveCase } from '../../public/js/ko-ki/detective-engine.js';
import { DETECTIVE_CASES, getDetectiveCase } from '../../public/js/ko-ki/detective-cases.js';

test('all fifteen cases and Final Case visibly reproduce their bug', () => {
    const results = DETECTIVE_CASES.map((item) => simulateDetectiveCase(item.id));
    assert.equal(results.length, 16);
    results.forEach((result) => assert.equal(result.solved, false));
    assert.equal(results[0].state.bonk, true);
    assert.deepEqual(
        { x: results[0].state.x, y: results[0].state.y, direction: results[0].state.direction },
        { x: 0, y: 1, direction: 'WEST' },
    );
    assert.equal(results[1].state.overflow, true);
    assert.equal(results[2].state.floor, 2);
    assert.deepEqual(results[3].state.moves, ['CLAP', 'JUMP', 'TURN']);
    assert.equal(results[4].state.jumped, false);
    assert.equal(results[5].state.car, 'moving');
    assert.equal(results[6].state.dumped, true);
    assert.deepEqual(results[7].state.changes, ['OPEN', 'CLOSE', 'OPEN']);
    assert.equal(results[7].state.personStage, 'passed');
    assert.equal(results[8].state.delivered, false);
    assert.notEqual(results[9].state.mappings.SPACE, 'JUMP');
    assert.equal(results[10].state.ice, 'melting');
    assert.equal(results[11].state.watchedInDark, false);
    assert.equal(results[12].state.extra, true);
    assert.equal(results[13].state.sent, false);
    assert.equal(results[14].state.damaged, true);
    assert.equal(results[15].state.delivered, false);
});

test('the configured repair solves every case', () => {
    DETECTIVE_CASES.forEach((item) => {
        const result = simulateDetectiveCase(item.id, item.solution);
        assert.equal(result.solved, true, `${item.title} seharusnya selesai`);
    });
});

test('case one repairs the wrong turn by replacing one command', () => {
    const engine = new DetectiveCaseEngine(getDetectiveCase(1));
    engine.replace(2, 'TURN_RIGHT');
    assert.equal(engine.run().solved, true);
});

test('case two removes both extra fill commands', () => {
    const engine = new DetectiveCaseEngine(getDetectiveCase(2));
    engine.remove(2);
    assert.equal(engine.run().solved, false);
    engine.remove(1);
    assert.equal(engine.run().solved, true);
});

test('case three adds the missing UP command', () => {
    const engine = new DetectiveCaseEngine(getDetectiveCase(3));
    engine.add('UP');
    assert.equal(engine.run().state.floor, 3);
    assert.equal(engine.isSolved(), true);
});

test('case four swaps the two commands in the wrong order', () => {
    const engine = new DetectiveCaseEngine(getDetectiveCase(4));
    engine.swap(1, 2);
    assert.deepEqual(engine.program, ['CLAP', 'TURN', 'JUMP']);
    assert.equal(engine.run().solved, true);
});

test('case five changes the listened input from Arrow Up to Space', () => {
    const engine = new DetectiveCaseEngine(getDetectiveCase(5));
    engine.replace(0, 'SPACE_TO_JUMP');
    const result = engine.run();
    assert.equal(result.state.listened, 'SPACE');
    assert.equal(result.state.jumped, true);
});

test('case six stops the car for a red light', () => {
    const result = simulateDetectiveCase(6, getDetectiveCase(6).solution);
    assert.equal(result.state.light, 'red');
    assert.equal(result.state.car, 'stopped');
    assert.equal(result.solved, true);
});

test('case seven waters the plant instead of dumping water', () => {
    const result = simulateDetectiveCase(7, getDetectiveCase(7).solution);
    assert.equal(result.state.watered, true);
    assert.equal(result.state.dry, false);
});

test('case eight removes the final extra door action', () => {
    const engine = new DetectiveCaseEngine(getDetectiveCase(8));
    engine.remove(3);
    const result = engine.run();
    assert.deepEqual(result.state.changes, ['OPEN', 'CLOSE']);
    assert.equal(result.state.personStage, 'passed');
    assert.equal(result.state.door, 'closed');
    assert.equal(result.solved, true);
});

test('case nine inserts TAKE_PACKAGE in the middle at the warehouse', () => {
    const engine = new DetectiveCaseEngine(getDetectiveCase(9));
    engine.insert(1, 'TAKE_PACKAGE');
    const result = engine.run();
    assert.equal(result.state.delivered, true);
    assert.equal(result.solved, true);
});

test('case ten repairs all three controller mappings', () => {
    const result = simulateDetectiveCase(10, getDetectiveCase(10).solution);
    assert.deepEqual(result.state.mappings, { SPACE: 'JUMP', LEFT: 'LEFT', RIGHT: 'RIGHT' });
    assert.equal(result.solved, true);
});

test('cases eleven through fifteen use five distinct repair patterns', () => {
    const freezer = new DetectiveCaseEngine(getDetectiveCase(11));
    freezer.replace(2, 'COOLER_ON');
    assert.equal(freezer.run().solved, true);

    const cinema = new DetectiveCaseEngine(getDetectiveCase(12));
    cinema.swap(0, 1);
    assert.equal(cinema.run().solved, true);

    const copier = new DetectiveCaseEngine(getDetectiveCase(13));
    copier.remove(2);
    assert.equal(copier.run().solved, true);

    const drone = new DetectiveCaseEngine(getDetectiveCase(14));
    drone.insert(1, 'TAKE_PHOTO');
    assert.equal(drone.run().solved, true);

    const packing = new DetectiveCaseEngine(getDetectiveCase(15));
    packing.swap(1, 3);
    assert.equal(packing.run().solved, true);
});

test('step and trace preserve the active command order', () => {
    const engine = new DetectiveCaseEngine(getDetectiveCase(1));
    assert.equal(engine.step().index, 0);
    assert.equal(engine.step().index, 1);
    assert.equal(engine.trace.length, 2);
    assert.equal(engine.snapshot().cursor, 2);
});

test('the big bug needs direction, extra command, and missing delivery repaired', () => {
    const engine = new DetectiveCaseEngine(getDetectiveCase(16));
    engine.replace(3, 'TURN_RIGHT');
    assert.equal(engine.run().solved, false);
    engine.remove(7);
    assert.equal(engine.program[3], 'TURN_RIGHT');
    assert.equal(engine.program.filter((command) => command === 'MOVE_FORWARD').length, 5);
    assert.equal(engine.run().solved, false);
    engine.add('DELIVERY');
    const result = engine.run();
    assert.equal(result.state.delivered, true);
    assert.equal(result.solved, true);
});

test('expanded case timing provides a full classroom session', () => {
    const gameplayMinutes = DETECTIVE_CASES.reduce((sum, item) => sum + item.minutes, 0);
    assert.equal(gameplayMinutes, 70);
    assert.ok(gameplayMinutes >= 60);
});

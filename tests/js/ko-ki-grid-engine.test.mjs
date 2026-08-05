import assert from 'node:assert/strict';
import test from 'node:test';
import { COMMANDS, DIRECTIONS, GridRobot, ProgramInterpreter } from '../../public/js/ko-ki/grid-engine.js';
import { LEVELS } from '../../public/js/ko-ki/levels.js';

globalThis.window = globalThis;

test('MOVE_FORWARD moves one cell in the current direction', () => {
    const robot = new GridRobot({ width: 4, height: 4, start: { x: 1, y: 2, direction: DIRECTIONS.NORTH } });

    const result = robot.execute(COMMANDS.MOVE_FORWARD);

    assert.deepEqual(result.state, { x: 1, y: 1, direction: DIRECTIONS.NORTH });
    assert.equal(result.moved, true);
});

test('turn commands rotate without changing position', () => {
    const robot = new GridRobot({ width: 4, height: 4, start: { x: 1, y: 2, direction: DIRECTIONS.NORTH } });

    robot.execute(COMMANDS.TURN_LEFT);
    assert.deepEqual(robot.state, { x: 1, y: 2, direction: DIRECTIONS.WEST });

    robot.execute(COMMANDS.TURN_RIGHT);
    assert.deepEqual(robot.state, { x: 1, y: 2, direction: DIRECTIONS.NORTH });
});

test('robot cannot leave the grid', () => {
    const robot = new GridRobot({ width: 3, height: 3, start: { x: 0, y: 0, direction: DIRECTIONS.NORTH } });

    const result = robot.execute(COMMANDS.MOVE_FORWARD);

    assert.equal(result.blocked, true);
    assert.deepEqual(robot.state, { x: 0, y: 0, direction: DIRECTIONS.NORTH });
});

test('robot cannot move through configured obstacles', () => {
    const robot = new GridRobot({
        width: 3,
        height: 3,
        start: { x: 0, y: 1, direction: DIRECTIONS.EAST },
        obstacles: [{ x: 1, y: 1 }],
    });

    const result = robot.execute(COMMANDS.MOVE_FORWARD);

    assert.equal(result.blockedBy, 'obstacle');
    assert.deepEqual(robot.state, { x: 0, y: 1, direction: DIRECTIONS.EAST });
});

test('interpreter executes commands one at a time', () => {
    const robot = new GridRobot({ width: 4, height: 4, start: { x: 1, y: 2, direction: DIRECTIONS.NORTH } });
    const activeCommands = [];
    const interpreter = new ProgramInterpreter(robot, {
        onBeforeStep: ({ command }) => activeCommands.push(command),
    });

    interpreter.setProgram([COMMANDS.MOVE_FORWARD, COMMANDS.TURN_RIGHT, COMMANDS.MOVE_FORWARD]);
    interpreter.step();
    assert.deepEqual(robot.state, { x: 1, y: 1, direction: DIRECTIONS.NORTH });

    interpreter.step();
    assert.deepEqual(robot.state, { x: 1, y: 1, direction: DIRECTIONS.EAST });

    interpreter.step();
    assert.deepEqual(robot.state, { x: 2, y: 1, direction: DIRECTIONS.EAST });
    assert.deepEqual(activeCommands, [COMMANDS.MOVE_FORWARD, COMMANDS.TURN_RIGHT, COMMANDS.MOVE_FORWARD]);
});

test('all configured minimum solutions reach their targets', () => {
    assert.equal(LEVELS.length, 7);

    for (const level of LEVELS) {
        const robot = new GridRobot({ ...level.grid, start: level.start, obstacles: level.obstacles });

        level.minimumSolution.forEach((command) => robot.execute(command));

        assert.equal(robot.state.x, level.target.x, `${level.title} target x`);
        assert.equal(robot.state.y, level.target.y, `${level.title} target y`);
    }
});

test('missions five to seven increase the route complexity', () => {
    const advancedMissions = LEVELS.slice(4);

    assert.deepEqual(advancedMissions.map((level) => level.id), [5, 6, 7]);
    assert.ok(advancedMissions[0].minimumSolution.length > LEVELS[3].minimumSolution.length);
    assert.ok(advancedMissions[1].minimumSolution.length > advancedMissions[0].minimumSolution.length);
    assert.ok(advancedMissions.every((level) => level.obstacles.length > 0));
});

test('mission 5 blocks straight shortcuts and requires a winding route', () => {
    const level = LEVELS[4];
    const run = (program) => {
        const robot = new GridRobot({ ...level.grid, start: level.start, obstacles: level.obstacles });
        program.forEach((command) => robot.execute(command));

        return robot.state;
    };
    const directRightRoute = [
        COMMANDS.TURN_RIGHT,
        ...Array(4).fill(COMMANDS.MOVE_FORWARD),
        COMMANDS.TURN_LEFT,
        ...Array(3).fill(COMMANDS.MOVE_FORWARD),
    ];
    const upperShortcut = [
        ...Array(3).fill(COMMANDS.MOVE_FORWARD),
        COMMANDS.TURN_RIGHT,
        ...Array(4).fill(COMMANDS.MOVE_FORWARD),
    ];

    assert.notDeepEqual(
        { x: run(directRightRoute).x, y: run(directRightRoute).y },
        { x: level.target.x, y: level.target.y },
    );
    assert.notDeepEqual(
        { x: run(upperShortcut).x, y: run(upperShortcut).y },
        { x: level.target.x, y: level.target.y },
    );
    assert.equal(
        level.minimumSolution.filter((command) => command !== COMMANDS.MOVE_FORWARD).length,
        4,
    );
});

test('mission 3 starter program misses the target', () => {
    const level = LEVELS[2];
    const robot = new GridRobot({ ...level.grid, start: level.start });

    level.starterProgram.forEach((command) => robot.execute(command));

    assert.notDeepEqual(
        { x: robot.state.x, y: robot.state.y },
        { x: level.target.x, y: level.target.y },
    );
});

test('mission 4 has two valid routes and one is shorter', () => {
    const level = LEVELS[3];
    const lowerRoute = [
        COMMANDS.TURN_RIGHT,
        COMMANDS.MOVE_FORWARD,
        COMMANDS.MOVE_FORWARD,
        COMMANDS.MOVE_FORWARD,
        COMMANDS.TURN_LEFT,
        COMMANDS.MOVE_FORWARD,
        COMMANDS.MOVE_FORWARD,
        COMMANDS.MOVE_FORWARD,
        COMMANDS.MOVE_FORWARD,
        COMMANDS.TURN_LEFT,
        COMMANDS.MOVE_FORWARD,
        COMMANDS.MOVE_FORWARD,
        COMMANDS.MOVE_FORWARD,
    ];

    const runRoute = (program) => {
        const robot = new GridRobot({ ...level.grid, start: level.start, obstacles: level.obstacles });
        program.forEach((command) => robot.execute(command));

        return robot.state;
    };

    const upperResult = runRoute(level.minimumSolution);
    const lowerResult = runRoute(lowerRoute);

    assert.deepEqual(
        { x: upperResult.x, y: upperResult.y },
        { x: level.target.x, y: level.target.y },
    );
    assert.deepEqual(
        { x: lowerResult.x, y: lowerResult.y },
        { x: level.target.x, y: level.target.y },
    );
    assert.ok(level.minimumSolution.length < lowerRoute.length);
});

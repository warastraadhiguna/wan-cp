export const COMMANDS = Object.freeze({
    MOVE_FORWARD: 'MOVE_FORWARD',
    TURN_LEFT: 'TURN_LEFT',
    TURN_RIGHT: 'TURN_RIGHT',
});

export const DIRECTIONS = Object.freeze({
    NORTH: 'NORTH',
    EAST: 'EAST',
    SOUTH: 'SOUTH',
    WEST: 'WEST',
});

const DIRECTION_ORDER = Object.freeze([
    DIRECTIONS.NORTH,
    DIRECTIONS.EAST,
    DIRECTIONS.SOUTH,
    DIRECTIONS.WEST,
]);

const MOVE_DELTAS = Object.freeze({
    [DIRECTIONS.NORTH]: { x: 0, y: -1 },
    [DIRECTIONS.EAST]: { x: 1, y: 0 },
    [DIRECTIONS.SOUTH]: { x: 0, y: 1 },
    [DIRECTIONS.WEST]: { x: -1, y: 0 },
});

export class GridRobot {
    constructor({ width = 6, height = 6, start = { x: 2, y: 4, direction: DIRECTIONS.NORTH }, obstacles = [] } = {}) {
        if (!Number.isInteger(width) || !Number.isInteger(height) || width < 1 || height < 1) {
            throw new RangeError('Ukuran grid harus berupa bilangan bulat positif.');
        }

        this.width = width;
        this.height = height;
        this.obstacles = new Set(obstacles.map((obstacle) => {
            const validated = this.#validatedCoordinate(obstacle);

            return `${validated.x},${validated.y}`;
        }));
        this.start = this.#validatedState(start);

        if (this.obstacles.has(`${this.start.x},${this.start.y}`)) {
            throw new RangeError('Posisi awal robot tidak boleh berada pada penghalang.');
        }

        this.reset();
    }

    get state() {
        return { x: this.x, y: this.y, direction: this.direction };
    }

    reset() {
        ({ x: this.x, y: this.y, direction: this.direction } = this.start);

        return this.state;
    }

    execute(command) {
        if (!Object.values(COMMANDS).includes(command)) {
            throw new RangeError(`Command tidak dikenal: ${command}`);
        }

        if (command === COMMANDS.MOVE_FORWARD) {
            return this.#moveForward();
        }

        const turnOffset = command === COMMANDS.TURN_LEFT ? -1 : 1;
        const currentIndex = DIRECTION_ORDER.indexOf(this.direction);
        this.direction = DIRECTION_ORDER[(currentIndex + turnOffset + DIRECTION_ORDER.length) % DIRECTION_ORDER.length];

        return { command, moved: false, blocked: false, state: this.state };
    }

    #moveForward() {
        const delta = MOVE_DELTAS[this.direction];
        const nextX = this.x + delta.x;
        const nextY = this.y + delta.y;
        const outsideGrid = nextX < 0 || nextX >= this.width || nextY < 0 || nextY >= this.height;
        const obstacle = !outsideGrid && this.obstacles.has(`${nextX},${nextY}`);
        const blocked = outsideGrid || obstacle;

        if (!blocked) {
            this.x = nextX;
            this.y = nextY;
        }

        return {
            command: COMMANDS.MOVE_FORWARD,
            moved: !blocked,
            blocked,
            blockedBy: outsideGrid ? 'boundary' : obstacle ? 'obstacle' : null,
            state: this.state,
        };
    }

    #validatedState(state) {
        if (
            !Number.isInteger(state.x)
            || !Number.isInteger(state.y)
            || state.x < 0
            || state.x >= this.width
            || state.y < 0
            || state.y >= this.height
            || !DIRECTION_ORDER.includes(state.direction)
        ) {
            throw new RangeError('Posisi awal robot tidak valid.');
        }

        return { x: state.x, y: state.y, direction: state.direction };
    }

    #validatedCoordinate(coordinate) {
        if (
            !Number.isInteger(coordinate.x)
            || !Number.isInteger(coordinate.y)
            || coordinate.x < 0
            || coordinate.x >= this.width
            || coordinate.y < 0
            || coordinate.y >= this.height
        ) {
            throw new RangeError('Posisi penghalang tidak valid.');
        }

        return { x: coordinate.x, y: coordinate.y };
    }
}

export class ProgramInterpreter {
    constructor(robot, callbacks = {}) {
        if (!(robot instanceof GridRobot)) {
            throw new TypeError('ProgramInterpreter membutuhkan GridRobot.');
        }

        this.robot = robot;
        this.onBeforeStep = callbacks.onBeforeStep ?? (() => {});
        this.onAfterStep = callbacks.onAfterStep ?? (() => {});
        this.onStatusChange = callbacks.onStatusChange ?? (() => {});
        this.program = [];
        this.pointer = 0;
        this.timer = null;
        this.running = false;
    }

    get isRunning() {
        return this.running;
    }

    setProgram(program) {
        if (!Array.isArray(program) || program.some((command) => !Object.values(COMMANDS).includes(command))) {
            throw new RangeError('Program berisi command yang tidak valid.');
        }

        this.stop();
        this.program = [...program];
        this.pointer = 0;
    }

    run(interval = 650) {
        if (this.isRunning || this.program.length === 0) {
            return false;
        }

        if (this.pointer >= this.program.length) {
            this.pointer = 0;
        }

        this.running = true;
        this.onStatusChange('running');
        this.#executeNext();

        if (this.running && this.pointer < this.program.length) {
            this.timer = window.setInterval(() => this.#executeNext(), interval);
        }

        return true;
    }

    step() {
        this.stop();

        if (this.program.length === 0) {
            return null;
        }

        if (this.pointer >= this.program.length) {
            this.pointer = 0;
        }

        return this.#executeNext();
    }

    stop() {
        const wasRunning = this.running;

        if (this.timer !== null) {
            window.clearInterval(this.timer);
            this.timer = null;
        }

        this.running = false;

        if (wasRunning) {
            this.onStatusChange('stopped');
        }
    }

    resetExecution() {
        this.stop();
        this.pointer = 0;
    }

    #executeNext() {
        if (this.pointer >= this.program.length) {
            this.stop();

            return null;
        }

        const index = this.pointer;
        const command = this.program[index];
        this.onBeforeStep({ command, index });
        const result = this.robot.execute(command);
        this.pointer++;
        this.onAfterStep({ command, index, result, complete: this.pointer >= this.program.length });

        if (this.pointer >= this.program.length) {
            this.stop();
            this.onStatusChange('complete');
        }

        return result;
    }
}

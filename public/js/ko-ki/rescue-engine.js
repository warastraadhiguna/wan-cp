import { COMMANDS, DIRECTIONS, GridRobot } from './grid-engine.js';

export const RESCUE_COMMANDS = Object.freeze({
    MOVE_FORWARD: COMMANDS.MOVE_FORWARD,
    TURN_LEFT: COMMANDS.TURN_LEFT,
    TURN_RIGHT: COMMANDS.TURN_RIGHT,
    TAKE_WATER: 'TAKE_WATER',
    WATER: 'WATER',
});

const VALID_COMMANDS = new Set(Object.values(RESCUE_COMMANDS));

export class RescueGame {
    constructor(level) {
        this.level = level;
        this.robot = new GridRobot({
            ...level.grid,
            start: level.start,
            obstacles: level.obstacles.map(({ x, y }) => ({ x, y })),
        });
        this.reset();
    }

    get state() {
        return {
            robot: this.robot.state,
            water: this.water,
            wateredPlants: [...this.wateredPlants],
            errors: [...this.errors],
        };
    }

    reset() {
        this.robot.reset();
        this.water = 0;
        this.wateredPlants = new Set();
        this.errors = [];

        return this.state;
    }

    execute(command, index = 0) {
        if (!VALID_COMMANDS.has(command)) throw new RangeError(`Command tidak dikenal: ${command}`);

        if ([COMMANDS.MOVE_FORWARD, COMMANDS.TURN_LEFT, COMMANDS.TURN_RIGHT].includes(command)) {
            const result = this.robot.execute(command);
            if (result.blocked) this.errors.push({ index, command, type: 'BONK', blockedBy: result.blockedBy });

            return { ...result, event: result.blocked ? 'BONK' : 'MOVE', state: this.state };
        }

        if (command === RESCUE_COMMANDS.TAKE_WATER) {
            const atStation = samePosition(this.robot.state, this.level.waterStation);
            if (atStation) this.water = this.level.waterSupply;
            else this.errors.push({ index, command, type: 'NO_WATER_STATION' });

            return { command, success: atStation, event: atStation ? 'WATER_TAKEN' : 'NO_WATER_STATION', state: this.state };
        }

        const plantIndex = this.level.plants.findIndex((plant) => samePosition(this.robot.state, plant));
        const canWater = plantIndex >= 0 && this.water > 0 && !this.wateredPlants.has(plantIndex);
        if (canWater) {
            this.water--;
            this.wateredPlants.add(plantIndex);
        } else {
            this.errors.push({
                index,
                command,
                type: plantIndex < 0 ? 'NO_PLANT' : this.water <= 0 ? 'NO_WATER' : 'ALREADY_WATERED',
            });
        }

        return { command, success: canWater, event: canWater ? 'PLANT_WATERED' : 'WATER_FAILED', state: this.state };
    }

    run(program) {
        if (!Array.isArray(program) || program.some((command) => !VALID_COMMANDS.has(command))) {
            throw new RangeError('Program Rescue Mission tidak valid.');
        }

        return program.map((command, index) => this.execute(command, index));
    }

    isComplete() {
        const plantsDone = this.wateredPlants.size === this.level.plants.length;
        const atBase = !this.level.returnToBase || samePosition(this.robot.state, this.level.base);

        return plantsDone && atBase;
    }
}

export function calculateStars({ complete, prediction, finalPosition, commandCount, efficientCommands }) {
    if (!complete) return 0;

    let stars = 1;
    if (prediction && samePosition(prediction, finalPosition)) stars++;
    if (commandCount <= efficientCommands) stars++;

    return stars;
}

export function samePosition(first, second) {
    return Number(first?.x) === Number(second?.x) && Number(first?.y) === Number(second?.y);
}

export { DIRECTIONS };

import { getDetectiveCase } from './detective-cases.js';

const DIRECTIONS = ['NORTH', 'EAST', 'SOUTH', 'WEST'];

export class DetectiveCaseEngine {
    constructor(caseConfig) {
        if (!caseConfig) throw new RangeError('Kasus detektif tidak ditemukan.');
        this.case = caseConfig;
        this.program = [...caseConfig.initialProgram];
        this.reset();
    }

    reset() {
        this.cursor = 0;
        this.trace = [];
        this.state = initialState(this.case.type);

        return this.snapshot();
    }

    restoreInitialProgram() {
        this.program = [...this.case.initialProgram];

        return this.reset();
    }

    step() {
        if (this.cursor >= this.program.length) return null;
        const index = this.cursor;
        const command = this.program[this.cursor++];
        const outcome = execute(this.case.type, this.state, command);
        this.state = outcome.state;
        const entry = { index, command, message: outcome.message, state: clone(this.state) };
        this.trace.push(entry);

        return entry;
    }

    run() {
        this.reset();
        const steps = [];
        while (this.cursor < this.program.length) steps.push(this.step());

        return { steps, state: clone(this.state), solved: this.isSolved() };
    }

    replace(index, command) {
        this.assertIndex(index);
        this.program[index] = command;
        this.reset();
    }

    remove(index) {
        this.assertIndex(index);
        this.program.splice(index, 1);
        this.reset();
    }

    add(command) {
        this.program.push(command);
        this.reset();
    }

    insert(index, command) {
        if (!Number.isInteger(index) || index < 0 || index > this.program.length) throw new RangeError('Posisi command tidak valid.');
        this.program.splice(index, 0, command);
        this.reset();
    }

    swap(first, second) {
        this.assertIndex(first);
        this.assertIndex(second);
        [this.program[first], this.program[second]] = [this.program[second], this.program[first]];
        this.reset();
    }

    isSolved() {
        return arraysEqual(this.program, this.case.solution) && outcomeIsCorrect(this.case.type, this.state);
    }

    snapshot() {
        return { cursor: this.cursor, program: [...this.program], trace: clone(this.trace), state: clone(this.state) };
    }

    assertIndex(index) {
        if (!Number.isInteger(index) || index < 0 || index >= this.program.length) throw new RangeError('Index command tidak valid.');
    }
}

export function simulateDetectiveCase(caseId, program = null) {
    const engine = new DetectiveCaseEngine(getDetectiveCase(caseId));
    if (program) engine.program = [...program];

    return engine.run();
}

function initialState(type) {
    if (type === 'direction') return { x: 1, y: 3, direction: 'NORTH', bonk: false, reached: false };
    if (type === 'extra') return { fills: 0, level: 0, overflow: false };
    if (type === 'missing') return { floor: 0, target: 3 };
    if (type === 'order') return { moves: [], target: ['CLAP', 'TURN', 'JUMP'] };
    if (type === 'traffic') return { light: null, processed: false, car: 'waiting', carPosition: 0 };
    if (type === 'garden') return { dry: true, detected: false, hasWater: false, dumped: false, watered: false };
    if (type === 'door') return { personNear: false, personStage: 'waiting', door: 'closed', changes: [] };
    if (type === 'courier') return { location: 'agency', package: false, delivered: false };
    if (type === 'mapping') return { mappings: {}, tested: false };
    if (type === 'freezer') return { temperature: null, read: false, cooler: 'idle', ice: 'solid' };
    if (type === 'cinema') return { lights: 'on', movie: 'stopped', watchedInDark: false, finished: false };
    if (type === 'copier') return { requested: 2, copies: 0, extra: false };
    if (type === 'drone') return { location: 'base', photo: false, sent: false };
    if (type === 'packing') return { item: false, packed: false, label: false, shipped: false, damaged: false, actions: [] };
    if (type === 'delivery') return { x: 1, y: 3, direction: 'NORTH', package: false, delivered: false, bonk: false };

    return { pressed: 'SPACE', listened: null, jumped: false };
}

function execute(type, current, command) {
    const state = clone(current);
    if (type === 'direction') {
        if (command === 'TURN_LEFT') state.direction = DIRECTIONS[(DIRECTIONS.indexOf(state.direction) + 3) % 4];
        if (command === 'TURN_RIGHT') state.direction = DIRECTIONS[(DIRECTIONS.indexOf(state.direction) + 1) % 4];
        if (command === 'MOVE_FORWARD') {
            const delta = { NORTH: [0, -1], EAST: [1, 0], SOUTH: [0, 1], WEST: [-1, 0] }[state.direction];
            const next = { x: state.x + delta[0], y: state.y + delta[1] };
            if (next.x === 0 && next.y === 1) {
                state.x = next.x;
                state.y = next.y;
                state.bonk = true;
            }
            else { state.x = next.x; state.y = next.y; }
        }
        state.reached = state.x === 2 && state.y === 1;

        return { state, message: state.bonk ? 'BONK! Ko-Ki menabrak pohon.' : `${command}: posisi (${state.x + 1}, ${state.y + 1}).` };
    }
    if (type === 'extra') {
        state.fills++;
        state.level = state.fills === 1 ? 100 : 100 + ((state.fills - 1) * 35);
        state.overflow = state.fills > 1;

        return { state, message: state.overflow ? 'Gelas meluap karena ISI dijalankan lagi.' : 'Gelas terisi penuh.' };
    }
    if (type === 'missing') {
        if (command === 'UP') state.floor++;

        return { state, message: `Lift tiba di lantai ${state.floor}.` };
    }
    if (type === 'order') {
        state.moves.push(command);

        return { state, message: `Dance Bot melakukan ${command}.` };
    }
    if (type === 'traffic') {
        if (command === 'RED_INPUT') state.light = 'red';
        if (command === 'READ_COLOR') state.processed = state.light === 'red';
        if (command === 'CAR_GO') { state.car = 'moving'; state.carPosition = 2; }
        if (command === 'CAR_STOP') { state.car = 'stopped'; state.carPosition = 0; }

        const message = command === 'CAR_GO'
            ? 'Lampu merah, tetapi mobil tetap jalan!'
            : command === 'CAR_STOP' ? 'Mobil berhenti dengan aman.' : `${command} dijalankan.`;
        return { state, message };
    }
    if (type === 'garden') {
        if (command === 'DETECT_DRY') state.detected = state.dry;
        if (command === 'TAKE_WATER') state.hasWater = true;
        if (command === 'DUMP_WATER' && state.hasWater) { state.hasWater = false; state.dumped = true; }
        if (command === 'WATER_PLANT' && state.hasWater) { state.hasWater = false; state.watered = true; state.dry = false; }

        const message = command === 'DUMP_WATER'
            ? 'Air dibuang ke lantai. Tanaman masih kering.'
            : command === 'WATER_PLANT' ? 'Tanaman disiram dan kembali segar!' : `${command} dijalankan.`;
        return { state, message };
    }
    if (type === 'door') {
        if (command === 'PERSON_APPROACHES') { state.personNear = true; state.personStage = 'near'; }
        if (command === 'OPEN_DOOR') {
            state.door = 'open';
            if (!state.changes.length) state.personStage = 'passing';
            state.changes.push('OPEN');
        }
        if (command === 'CLOSE_DOOR') {
            state.door = 'closed';
            state.personStage = 'passed';
            state.changes.push('CLOSE');
        }

        return { state, message: command === 'PERSON_APPROACHES' ? 'Seseorang mendekati pintu.' : `Pintu ${state.door === 'open' ? 'terbuka' : 'tertutup'}.` };
    }
    if (type === 'courier') {
        if (command === 'GO_WAREHOUSE') state.location = 'warehouse';
        if (command === 'TAKE_PACKAGE' && state.location === 'warehouse') state.package = true;
        if (command === 'GO_HOME') state.location = 'home';
        if (command === 'DELIVER_PACKAGE' && state.location === 'home' && state.package) { state.package = false; state.delivered = true; }

        const message = command === 'DELIVER_PACKAGE'
            ? (state.delivered ? 'Paket berhasil diantar!' : 'Ko-Ki tiba tanpa membawa paket.')
            : command === 'TAKE_PACKAGE' ? (state.package ? 'Paket berhasil diambil.' : 'Paket tidak ada di sini.') : `${command} dijalankan.`;
        return { state, message };
    }
    if (type === 'mapping') {
        const [input, output] = command.split('_TO_');
        state.mappings[input] = output;

        return { state, message: `${input} tersambung ke ${output}.` };
    }
    if (type === 'freezer') {
        if (command === 'TEMP_HIGH') state.temperature = 'hot';
        if (command === 'READ_TEMP') state.read = state.temperature === 'hot';
        if (command === 'COOLER_OFF') { state.cooler = 'off'; state.ice = 'melting'; }
        if (command === 'COOLER_ON' && state.read) { state.cooler = 'on'; state.ice = 'solid'; }
        return { state, message: command === 'COOLER_OFF' ? 'Pendingin mati. Es mulai mencair!' : command === 'COOLER_ON' ? 'Pendingin menyala. Es tetap beku.' : `${command} dijalankan.` };
    }
    if (type === 'cinema') {
        if (command === 'LIGHTS_OFF') state.lights = 'off';
        if (command === 'PLAY_MOVIE') { state.movie = 'playing'; state.watchedInDark = state.lights === 'off'; }
        if (command === 'LIGHTS_ON') { state.lights = 'on'; state.finished = state.movie === 'playing'; }
        return { state, message: command === 'PLAY_MOVIE' ? (state.watchedInDark ? 'Film dimulai setelah ruangan gelap.' : 'Film mulai saat lampu masih terang!') : `Lampu ${state.lights === 'off' ? 'dimatikan' : 'dinyalakan'}.` };
    }
    if (type === 'copier') {
        if (command === 'COPY') state.copies++;
        state.extra = state.copies > state.requested;
        return { state, message: state.extra ? `Kertas ke-${state.copies} tidak diminta!` : `Kertas ke-${state.copies} selesai dicetak.` };
    }
    if (type === 'drone') {
        if (command === 'FLY_TARGET') state.location = 'target';
        if (command === 'TAKE_PHOTO' && state.location === 'target') state.photo = true;
        if (command === 'RETURN_BASE') state.location = 'base';
        if (command === 'SEND_PHOTO' && state.location === 'base' && state.photo) state.sent = true;
        return { state, message: command === 'SEND_PHOTO' ? (state.sent ? 'Foto berhasil dikirim!' : 'Galeri kosong. Tidak ada foto untuk dikirim.') : command === 'TAKE_PHOTO' ? 'Klik! Foto berhasil diambil.' : `${command} dijalankan.` };
    }
    if (type === 'packing') {
        state.actions.push(command);
        if (command === 'PICK_ITEM') state.item = true;
        if (command === 'PACK_ITEM' && state.item && !state.shipped) state.packed = true;
        if (command === 'SCAN_LABEL' && state.item && !state.shipped) state.label = true;
        if (command === 'SHIP_PACKAGE') {
            state.shipped = true;
            state.damaged = !(state.item && state.packed && state.label);
        }
        return { state, message: command === 'SHIP_PACKAGE' ? (state.damaged ? 'Paket dikirim terlalu cepat dan isinya berantakan!' : 'Paket dikirim dengan aman!') : `${command} dijalankan.` };
    }
    if (type === 'delivery') {
        if (command === 'TURN_LEFT') state.direction = DIRECTIONS[(DIRECTIONS.indexOf(state.direction) + 3) % 4];
        if (command === 'TURN_RIGHT') state.direction = DIRECTIONS[(DIRECTIONS.indexOf(state.direction) + 1) % 4];
        if (command === 'MOVE_FORWARD') {
            const delta = { NORTH: [0, -1], EAST: [1, 0], SOUTH: [0, 1], WEST: [-1, 0] }[state.direction];
            const next = { x: state.x + delta[0], y: state.y + delta[1] };
            if (next.x < 0 || next.x >= 6 || next.y < 0 || next.y >= 4) state.bonk = true;
            else { state.x = next.x; state.y = next.y; }
        }
        if (command === 'TAKE_PACKAGE' && state.x === 1 && state.y === 1) state.package = true;
        if (command === 'DELIVERY' && state.package && state.x === 4 && state.y === 1) {
            state.package = false;
            state.delivered = true;
        }

        return { state, message: deliveryMessage(command, state) };
    }

    state.listened = command === 'SPACE_TO_JUMP' ? 'SPACE' : 'ARROW_UP';
    state.jumped = state.listened === state.pressed;

    return { state, message: state.jumped ? 'SPACE terbaca. Karakter melompat!' : 'SPACE ditekan, tetapi program menunggu ARROW UP.' };
}

function outcomeIsCorrect(type, state) {
    if (type === 'direction') return state.reached && !state.bonk;
    if (type === 'extra') return state.fills === 1 && !state.overflow;
    if (type === 'missing') return state.floor === state.target;
    if (type === 'order') return arraysEqual(state.moves, state.target);
    if (type === 'traffic') return state.light === 'red' && state.processed && state.car === 'stopped';
    if (type === 'garden') return state.watered && !state.dry;
    if (type === 'door') return state.personNear && state.door === 'closed' && arraysEqual(state.changes, ['OPEN', 'CLOSE']);
    if (type === 'courier') return state.delivered;
    if (type === 'mapping') return state.mappings.SPACE === 'JUMP' && state.mappings.LEFT === 'LEFT' && state.mappings.RIGHT === 'RIGHT';
    if (type === 'freezer') return state.read && state.cooler === 'on' && state.ice === 'solid';
    if (type === 'cinema') return state.watchedInDark && state.finished;
    if (type === 'copier') return state.copies === state.requested && !state.extra;
    if (type === 'drone') return state.sent;
    if (type === 'packing') return state.shipped && !state.damaged && arraysEqual(state.actions, ['PICK_ITEM', 'PACK_ITEM', 'SCAN_LABEL', 'SHIP_PACKAGE']);
    if (type === 'delivery') return state.delivered && state.x === 4 && state.y === 1;

    return state.jumped;
}

function arraysEqual(first, second) {
    return first.length === second.length && first.every((value, index) => value === second[index]);
}

function clone(value) {
    return JSON.parse(JSON.stringify(value));
}

function deliveryMessage(command, state) {
    if (state.bonk) return 'BONK! Ko-Ki mencoba keluar dari jalur pabrik.';
    if (command === 'TAKE_PACKAGE') return state.package ? 'Paket berhasil diambil.' : 'Tidak ada paket di kotak ini.';
    if (command === 'DELIVERY') return state.delivered ? 'Paket berhasil diserahkan ke Delivery!' : 'Delivery belum dapat dilakukan di sini.';

    return `${command}: Ko-Ki berada di kotak (${state.x + 1}, ${state.y + 1}).`;
}

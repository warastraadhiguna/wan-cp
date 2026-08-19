import { DIRECTIONS, RESCUE_COMMANDS as C } from './rescue-engine.js';

const F = C.MOVE_FORWARD;
const L = C.TURN_LEFT;
const R = C.TURN_RIGHT;
const TAKE = C.TAKE_WATER;
const WATER = C.WATER;

export const RESCUE_LEVELS = Object.freeze([
    {
        id: 1, name: 'Rescue One', icon: '🌱', minutes: 6,
        instruction: 'Ambil air, lalu selamatkan satu tanaman.',
        grid: { width: 5, height: 5 }, start: { x: 0, y: 4, direction: DIRECTIONS.NORTH },
        base: { x: 0, y: 4 }, waterStation: { x: 0, y: 2 }, waterSupply: 1,
        plants: [{ x: 2, y: 2 }], obstacles: [], returnToBase: false,
        commandLimit: 12, efficientCommands: 7,
        solution: [F, F, TAKE, R, F, F, WATER],
        tip: 'Ko-Ki harus berada di stasiun air saat menjalankan AMBIL AIR.',
    },
    {
        id: 2, name: 'Wrong Turn', icon: '↪️', minutes: 7,
        instruction: 'Belok pada tempat yang tepat dan hindari batu.',
        grid: { width: 6, height: 5 }, start: { x: 0, y: 4, direction: DIRECTIONS.NORTH },
        base: { x: 0, y: 4 }, waterStation: { x: 0, y: 3 }, waterSupply: 1,
        plants: [{ x: 4, y: 1 }],
        obstacles: [
            { x: 1, y: 0, type: 'rock' },
            { x: 1, y: 1, type: 'rock' },
            { x: 1, y: 3, type: 'rock' },
            { x: 2, y: 2, type: 'rock' },
            { x: 3, y: 4, type: 'rock' },
            { x: 4, y: 3, type: 'rock' },
        ],
        returnToBase: false, commandLimit: 23, efficientCommands: 19,
        solution: [F, TAKE, L, L, F, L, F, F, L, F, R, F, L, F, R, F, L, F, WATER],
        tip: 'Jalur atas tertutup. Cari celah dari bawah dan perhatikan arah di setiap belokan.',
    },
    {
        id: 3, name: 'Battery Challenge', icon: '🔋', minutes: 8,
        instruction: 'Baterai terbatas. Cari rute yang cukup singkat.',
        grid: { width: 7, height: 6 }, start: { x: 0, y: 5, direction: DIRECTIONS.NORTH },
        base: { x: 0, y: 5 }, waterStation: { x: 0, y: 4 }, waterSupply: 1,
        plants: [{ x: 6, y: 1 }],
        obstacles: [
            { x: 1, y: 0, type: 'rock' }, { x: 1, y: 1, type: 'rock' },
            { x: 1, y: 2, type: 'rock' }, { x: 1, y: 3, type: 'rock' }, { x: 1, y: 5, type: 'rock' },
            { x: 3, y: 0, type: 'rock' }, { x: 3, y: 2, type: 'rock' },
            { x: 3, y: 3, type: 'rock' }, { x: 3, y: 4, type: 'rock' }, { x: 3, y: 5, type: 'rock' },
            { x: 5, y: 0, type: 'rock' }, { x: 5, y: 1, type: 'rock' },
            { x: 5, y: 3, type: 'rock' }, { x: 5, y: 4, type: 'rock' }, { x: 5, y: 5, type: 'rock' },
        ],
        returnToBase: false, commandLimit: 21, efficientCommands: 20,
        solution: [F, TAKE, R, F, F, L, F, F, F, R, F, F, R, F, L, F, F, L, F, WATER],
        tip: 'Baterai hanya cukup untuk jalur celah bawah, lalu atas, lalu tengah.',
    },
    {
        id: 4, name: 'Double Rescue', icon: '🌿', minutes: 8,
        instruction: 'Siram dua tanaman dalam satu program.',
        grid: { width: 6, height: 6 }, start: { x: 0, y: 5, direction: DIRECTIONS.NORTH },
        base: { x: 0, y: 5 }, waterStation: { x: 0, y: 4 }, waterSupply: 2,
        plants: [{ x: 4, y: 4 }, { x: 4, y: 1 }],
        obstacles: [
            { x: 2, y: 2, type: 'rock' },
            { x: 3, y: 2, type: 'rock' },
            { x: 4, y: 3, type: 'rock' },
            { x: 5, y: 1, type: 'rock' },
        ],
        returnToBase: false, commandLimit: 20, efficientCommands: 17,
        solution: [F, TAKE, R, F, F, F, F, WATER, F, L, F, F, L, F, R, F, WATER],
        tip: 'Sekali mengambil air, tangki cukup untuk dua tanaman.',
    },
    {
        id: 5, name: 'Danger Zone', icon: '⚠️', minutes: 9,
        instruction: 'Lewati jalur aman di antara batu dan genangan.',
        grid: { width: 6, height: 6 }, start: { x: 0, y: 5, direction: DIRECTIONS.NORTH },
        base: { x: 0, y: 5 }, waterStation: { x: 0, y: 4 }, waterSupply: 1,
        plants: [{ x: 5, y: 0 }],
        obstacles: [
            { x: 0, y: 3, type: 'rock' }, { x: 2, y: 3, type: 'rock' }, { x: 3, y: 3, type: 'rock' }, { x: 4, y: 3, type: 'rock' },
            { x: 0, y: 1, type: 'puddle' }, { x: 1, y: 1, type: 'puddle' }, { x: 2, y: 1, type: 'puddle' }, { x: 4, y: 1, type: 'puddle' }, { x: 5, y: 1, type: 'puddle' },
        ],
        returnToBase: false, commandLimit: 20, efficientCommands: 17,
        solution: [F, TAKE, R, F, L, F, F, R, F, F, L, F, F, R, F, F, WATER],
        tip: 'BONK tidak menghapus programmu. Lihat command yang merah, lalu perbaiki.',
    },
    {
        id: 6, name: 'Emergency Garden', icon: '🚨', minutes: 12, final: true,
        instruction: 'Selamatkan tiga tanaman, lalu kembali ke base.',
        grid: { width: 7, height: 7 }, start: { x: 0, y: 6, direction: DIRECTIONS.NORTH },
        base: { x: 0, y: 6 }, waterStation: { x: 0, y: 4 }, waterSupply: 3,
        plants: [{ x: 2, y: 4 }, { x: 6, y: 2 }, { x: 3, y: 0 }],
        obstacles: [
            { x: 1, y: 5, type: 'rock' }, { x: 2, y: 5, type: 'rock' }, { x: 3, y: 5, type: 'rock' }, { x: 4, y: 5, type: 'rock' }, { x: 5, y: 5, type: 'rock' },
            { x: 2, y: 3, type: 'rock' }, { x: 3, y: 3, type: 'rock' }, { x: 4, y: 3, type: 'rock' },
            { x: 1, y: 1, type: 'puddle' }, { x: 2, y: 1, type: 'puddle' }, { x: 4, y: 1, type: 'puddle' }, { x: 5, y: 1, type: 'puddle' },
            { x: 5, y: 2, type: 'rock' }, { x: 6, y: 4, type: 'rock' },
        ],
        returnToBase: true, commandLimit: 42, efficientCommands: 34,
        solution: [F, F, TAKE, R, F, F, WATER, F, F, F, L, F, R, F, L, F, WATER, F, F, L, F, F, F, WATER, F, F, F, L, F, F, F, F, F, F],
        tip: 'Rencanakan urutan ketiga tanaman sebelum menekan RUN.',
    },
]);

export function getRescueLevel(id) {
    return RESCUE_LEVELS.find((level) => level.id === Number(id)) ?? null;
}

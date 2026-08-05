import { COMMANDS, DIRECTIONS } from './grid-engine.js';

export const LEVELS = Object.freeze([
    {
        id: 1,
        title: 'Misi 1 — Menuju Bunga',
        instruction: 'Bantu Ko-Ki mencapai bunga yang berjarak dua kotak.',
        grid: { width: 5, height: 5 },
        start: { x: 2, y: 4, direction: DIRECTIONS.NORTH },
        target: { x: 2, y: 2, emoji: '🌼', label: 'Bunga' },
        obstacles: [],
        starterProgram: [],
        minimumSolution: [COMMANDS.MOVE_FORWARD, COMMANDS.MOVE_FORWARD],
        successMessages: ['Ko-Ki Bot menjalankan perintahmu satu per satu.'],
        failureMessages: ['Ko-Ki belum sampai ke bunga. Periksa urutan command-mu.'],
    },
    {
        id: 2,
        title: 'Misi 2 — Belok ke Tanaman',
        instruction: 'Ko-Ki perlu bergerak, berputar ke kanan, lalu bergerak lagi menuju tanaman.',
        grid: { width: 5, height: 5 },
        start: { x: 1, y: 4, direction: DIRECTIONS.NORTH },
        target: { x: 2, y: 3, emoji: '🪴', label: 'Tanaman' },
        obstacles: [],
        starterProgram: [],
        minimumSolution: [COMMANDS.MOVE_FORWARD, COMMANDS.TURN_RIGHT, COMMANDS.MOVE_FORWARD],
        prediction: {
            prompt: 'Apakah Ko-Ki Bot akan sampai?',
            options: [
                { value: 'yes', label: 'Ya' },
                { value: 'no', label: 'Tidak' },
                { value: 'unsure', label: 'Belum yakin' },
            ],
        },
        successMessages: ['Ko-Ki sampai ke tanaman dengan mengikuti programmu.'],
        failureMessages: [
            'Ko-Ki Bot sudah mengikuti programmu.',
            'Perintah mana yang perlu diperiksa?',
        ],
    },
    {
        id: 3,
        title: 'Misi 3 — Detektif Bug',
        instruction: 'Jalankan starter program, amati hasilnya, lalu perbaiki command yang keliru.',
        grid: { width: 5, height: 5 },
        start: { x: 1, y: 4, direction: DIRECTIONS.NORTH },
        target: { x: 2, y: 3, emoji: '🔎', label: 'Target detektif' },
        obstacles: [],
        starterProgram: [COMMANDS.MOVE_FORWARD, COMMANDS.TURN_LEFT, COMMANDS.MOVE_FORWARD],
        minimumSolution: [COMMANDS.MOVE_FORWARD, COMMANDS.TURN_RIGHT, COMMANDS.MOVE_FORWARD],
        debugging: {
            observePrompt: 'Amati posisi Ko-Ki. Command mana yang membuat arah Ko-Ki keliru?',
        },
        successMessages: [
            'Kamu menemukan dan memperbaiki bug.',
            'Memperbaiki program disebut debugging.',
        ],
        failureMessages: [
            'Ko-Ki sudah menjalankan program yang kamu susun.',
            'Periksa kembali command yang mengubah arah Ko-Ki.',
        ],
    },
    {
        id: 4,
        title: 'Misi 4 — Dua Jalan',
        instruction: 'Pilih salah satu dari dua jalan untuk membawa Ko-Ki menuju target.',
        grid: { width: 5, height: 5 },
        start: { x: 0, y: 1, direction: DIRECTIONS.EAST },
        target: { x: 4, y: 1, emoji: '⭐', label: 'Target' },
        obstacles: [
            { x: 2, y: 1 },
            { x: 2, y: 2 },
            { x: 2, y: 3 },
        ],
        starterProgram: [],
        minimumSolution: [
            COMMANDS.TURN_LEFT,
            COMMANDS.MOVE_FORWARD,
            COMMANDS.TURN_RIGHT,
            COMMANDS.MOVE_FORWARD,
            COMMANDS.MOVE_FORWARD,
            COMMANDS.MOVE_FORWARD,
            COMMANDS.MOVE_FORWARD,
            COMMANDS.TURN_RIGHT,
            COMMANDS.MOVE_FORWARD,
        ],
        bonus: {
            grade: 5,
            maxCommands: 9,
            prompt: 'Bonus opsional: bisakah kamu mencapai target dengan 9 command atau lebih sedikit?',
        },
        successMessages: ['Ko-Ki berhasil mencapai target melalui jalan pilihanmu.'],
        failureMessages: ['Ko-Ki belum mencapai target. Kamu dapat mencoba jalan yang lain.'],
    },
]);

export function getLevel(levelId) {
    return LEVELS.find((level) => level.id === Number(levelId)) ?? null;
}

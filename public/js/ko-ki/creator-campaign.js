export const CREATOR_CAMPAIGN_LEVELS = Object.freeze([
    { id: 1, name: 'Pemanasan Kolektor', icon: '🌱', target: 5, description: 'Kumpulkan 5 item dan kenali arena gim-mu.', bonuses: {} },
    { id: 2, name: 'Lompatan Tepat', icon: '🪨', target: 8, description: 'Capai target sambil melewati rintangan.', bonuses: {} },
    { id: 3, name: 'Dua Harta', icon: '✨', target: 12, description: 'Dua collectible muncul bergantian di arena.', bonuses: { second_collectible: true } },
    { id: 4, name: 'Jalur Ganda', icon: '🚧', target: 15, description: 'Dua item dan dua rintangan mengisi arena.', bonuses: { second_collectible: true, second_obstacle: true } },
    { id: 5, name: 'Creator Rush', icon: '🏆', target: 15, description: 'Item spesial +3 dan timer 30 detik!', bonuses: { second_collectible: true, special_plus_three: true, second_obstacle: true, timer: true } },
    { id: 6, name: 'Obstacle Bergerak', icon: '🤖', target: 20, description: 'Rintangan berpatroli bolak-balik dengan pola tetap.', bonuses: { second_collectible: true, special_plus_three: true, second_obstacle: true, moving_obstacle: true } },
    { id: 7, name: 'Patroli Kembar', icon: '🚧', target: 25, description: 'Dua rintangan bergerak bersama dengan pola yang sama.', bonuses: { second_collectible: true, special_plus_three: true, second_obstacle: true, moving_obstacles_together: true } },
    { id: 8, name: 'Dunia Berjalan', icon: '🌍', target: 30, description: 'Stage, item, dan rintangan mengalir terus dari kanan ke kiri.', bonuses: { second_collectible: true, special_plus_three: true, second_obstacle: true, moving_world: true } },
    { id: 9, name: 'Jalur Atas-Bawah', icon: '↕️', target: 35, description: 'Hindari rintangan bawah dengan melompat dan rintangan atas dengan tetap di tanah.', bonuses: { second_collectible: true, special_plus_three: true, second_obstacle: true, moving_world: true, aerial_obstacles: true } },
    { id: 10, name: 'Dragon Rush', icon: '🐉', target: 40, description: 'Naga mengirim semakin banyak rintangan dan mempercepat arena setiap 10 poin.', bonuses: { second_collectible: true, special_plus_three: true, second_obstacle: true, moving_world: true, aerial_obstacles: true, dragon_boss: true } },
]);

export function normalizeCampaignProgress(input = {}) {
    const completed = [...new Set((input.completed || []).map(Number))]
        .filter((level) => level >= 1 && level <= CREATOR_CAMPAIGN_LEVELS.length)
        .sort((a, b) => a - b);
    const unlocked = Math.min(CREATOR_CAMPAIGN_LEVELS.length, Math.max(1, Number(input.unlocked || 1), completed.length + 1));
    return { unlocked, completed };
}

export function campaignLevel(id) {
    return CREATOR_CAMPAIGN_LEVELS.find((level) => level.id === Number(id)) || CREATOR_CAMPAIGN_LEVELS[0];
}

export function campaignGameConfig(base, id) {
    const level = campaignLevel(id);
    const controls = base.controls || {};
    const controlValues = Object.values(controls);
    const controlsValid = controlValues.length === 3
        && new Set(controlValues).size === 3
        && ['LEFT', 'RIGHT', 'SPACE'].every((key) => controlValues.includes(key));
    return {
        ...base,
        controls: controlsValid ? controls : { left: 'LEFT', right: 'RIGHT', jump: 'SPACE' },
        stage: 4,
        target_score: level.target,
        bonuses: {
            ...base.bonuses,
            second_collectible: false,
            special_plus_three: false,
            second_obstacle: false,
            timer: false,
            moving_obstacle: false,
            moving_obstacles_together: false,
            moving_world: false,
            aerial_obstacles: false,
            dragon_boss: false,
            ...level.bonuses,
        },
    };
}

export function completeCampaignLevel(progress, id) {
    const current = normalizeCampaignProgress(progress);
    const level = Number(id);
    if (level > current.unlocked) return current;
    return normalizeCampaignProgress({
        completed: [...current.completed, level],
        unlocked: Math.max(current.unlocked, Math.min(CREATOR_CAMPAIGN_LEVELS.length, level + 1)),
    });
}

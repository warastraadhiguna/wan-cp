import { RESCUE_LEVELS } from './rescue-levels.js';

const root = document.querySelector('[data-rescue-map]');
if (root) {
    const state = JSON.parse(root.dataset.state || '{}');
    const completed = (state.completed_levels || []).map(Number);
    const approved = (state.approved_levels || []).map(Number);
    const container = root.querySelector('[data-rescue-levels]');
    const baseUrl = root.dataset.missionUrl.replace(/\/1$/, '');
    root.querySelector('[data-total-stars]').textContent = Number(state.total_stars || 0);

    for (const level of RESCUE_LEVELS) {
        const stars = Number(state.level_progress?.[level.id]?.stars || 0);
        const prerequisiteDone = level.id === 1 || completed.includes(level.id - 1);
        const unlocked = prerequisiteDone && approved.includes(level.id);
        const lockedText = prerequisiteDone ? 'Menunggu izin admin' : 'Selesaikan level sebelumnya';
        const card = document.createElement('article');
        card.className = `rescue-map-card ${stars ? 'is-complete' : unlocked ? 'is-unlocked' : 'is-locked'}`;
        card.innerHTML = `
            <span class="rescue-map-number">${level.id}</span>
            <span class="rescue-map-icon" aria-hidden="true">${level.icon}</span>
            <div class="rescue-map-copy"><p>${level.final ? 'FINAL' : `LEVEL ${level.id}`} · ±${level.minutes} menit</p><h2>${level.name}</h2><p>${level.instruction}</p></div>
            <div class="rescue-map-score"><strong>${'★'.repeat(stars)}${'☆'.repeat(3 - stars)}</strong><small>${stars ? 'Bintang terbaik' : unlocked ? 'Siap dimainkan' : lockedText}</small></div>
            ${unlocked ? `<a class="btn ${stars ? 'btn-outline-success' : 'btn-primary koki-btn-primary'} rounded-pill fw-bold px-4" href="${baseUrl}/${level.id}">${stars ? 'Mainkan Lagi' : 'Mainkan'}</a>` : prerequisiteDone ? '<a class="btn btn-outline-primary rounded-pill fw-bold px-4" href="">Cek Izin Admin</a>' : ''}`;
        container.append(card);
    }
}

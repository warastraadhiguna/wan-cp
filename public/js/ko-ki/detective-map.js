import { DETECTIVE_CASES } from './detective-cases.js';
import { installDetectiveExitSync, loadDetectiveProgress, queueDetectiveSync, setSaveIndicator } from './detective-storage.js';

const root = document.querySelector('[data-detective-map]');
const navigationLifecycle = new AbortController();
window.addEventListener('koki:before-soft-navigation', () => navigationLifecycle.abort(), { once: true });

if (root) {
    const progress = loadDetectiveProgress(root.dataset.progressId, JSON.parse(root.dataset.state || '{}'));
    const completed = progress.completedCases;
    const approved = progress.approvedCases || [1];
    const baseUrl = root.dataset.caseUrl.replace(/\/1$/, '');
    const list = root.querySelector('[data-case-list]');
    const collection = root.querySelector('[data-collection]');
    const indicator = root.querySelector('[data-save-status]');
    root.querySelector('[data-total-stars]').textContent = `${Object.values(progress.cases).reduce((sum, item) => sum + Number(item.stars || 0), 0)}/48`;
    root.querySelector('[data-collection-count]').textContent = `${progress.bugCollection.length}/15`;
    root.querySelector('[data-detective-rank]').textContent = detectiveRank(completed);
    root.querySelector('[data-final-notebook]').hidden = !Array.from({ length: 16 }, (_, index) => index + 1).every((caseNumber) => completed.includes(caseNumber));
    setSaveIndicator(indicator, progress.pendingSync ? 'device' : 'saved');

    DETECTIVE_CASES.forEach((item) => {
        const solved = completed.includes(item.id);
        const prerequisiteDone = item.id === 1 || completed.includes(item.id - 1);
        const unlocked = prerequisiteDone && approved.includes(item.id);
        const stars = Number(progress.cases?.[item.id]?.stars || 0);
        const card = document.createElement('article');
        card.className = `detective-case-card ${solved ? 'is-solved' : unlocked ? 'is-open' : 'is-locked'}`;
        card.innerHTML = `<span class="detective-case-number">${item.final ? '🐞' : item.id}</span><span class="detective-case-icon">${item.icon}</span><div class="detective-case-copy"><p>${item.code} · ±${item.minutes} MENIT</p><h2>${item.title}</h2><p>${item.subtitle}</p></div><span class="detective-case-status">${solved ? `✅ SOLVED<br><span class="text-warning">${'★'.repeat(stars)}${'☆'.repeat(3 - stars)}</span>` : unlocked ? '📂 OPEN' : prerequisiteDone ? '⏳ IZIN ADMIN' : '🔒 LOCKED'}</span>${unlocked ? `<a class="btn ${solved ? 'btn-outline-success' : 'btn-primary koki-btn-primary'} rounded-pill fw-bold px-4" href="${baseUrl}/${item.id}">${solved ? 'Investigasi Lagi' : 'Selidiki'}</a>` : prerequisiteDone ? `<a class="btn btn-outline-primary rounded-pill fw-bold px-4" href="${window.location.href}">Cek Izin Admin</a>` : ''}`;
        list.append(card);

        if (item.id > 15) return;
        const bug = document.createElement('article');
        bug.className = `detective-bug-card ${solved ? 'is-unlocked' : ''}`;
        bug.innerHTML = `<span>${solved ? '🐞' : '❔'}</span><strong>${solved ? item.concept.replace(' Bug', '') : 'Belum ditemukan'}</strong>`;
        collection.append(bug);
    });

    const syncOptions = {
        progress,
        url: root.dataset.syncUrl,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
        onStatus: (status) => setSaveIndicator(indicator, status),
    };
    if (progress.pendingSync) queueDetectiveSync(syncOptions);
    const removeExitSync = installDetectiveExitSync(syncOptions);
    navigationLifecycle.signal.addEventListener('abort', removeExitSync, { once: true });
    window.addEventListener('online', () => { if (progress.pendingSync) queueDetectiveSync(syncOptions); }, { signal: navigationLifecycle.signal });
}

function detectiveRank(completed) {
    const hasThrough = (last) => Array.from({ length: last }, (_, index) => index + 1).every((caseNumber) => completed.includes(caseNumber));
    if (hasThrough(15)) return '🌟 Chief Bug Detective';
    if (hasThrough(10)) return '🏆 Master Detective';
    if (hasThrough(9)) return '🎖️ Senior Bug Detective';
    if (hasThrough(6)) return '🔎 Bug Investigator';
    if (hasThrough(3)) return '🕵️ Junior Detective';
    return '👣 Detective Trainee';
}

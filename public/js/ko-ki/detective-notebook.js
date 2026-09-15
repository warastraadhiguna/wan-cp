import {
    completeLocalNotebook, installDetectiveExitSync, loadDetectiveProgress,
    queueDetectiveSync, setSaveIndicator,
} from './detective-storage.js';

const root = document.querySelector('[data-detective-notebook]');
const navigationLifecycle = new AbortController();
window.addEventListener('koki:before-soft-navigation', () => navigationLifecycle.abort(), { once: true });

if (root) {
    const progress = loadDetectiveProgress(root.dataset.progressId, JSON.parse(root.dataset.state || '{}'));
    if (!Array.from({ length: 16 }, (_, index) => index + 1).every((caseNumber) => progress.completedCases.includes(caseNumber))) {
        window.location.replace(root.dataset.mapUrl);
    } else {
        const challenge = root.querySelector('[data-notebook-challenge]');
        const award = root.querySelector('[data-notebook-award]');
        const indicator = root.querySelector('[data-save-status]');
        const feedback = root.querySelector('[data-notebook-feedback]');
        const syncOptions = {
            progress,
            url: root.dataset.syncUrl,
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
            onStatus: (state) => setSaveIndicator(indicator, state),
        };

        showState();
        root.querySelector('[data-notebook-form]').addEventListener('submit', (event) => {
            event.preventDefault();
            const fix = new FormData(event.currentTarget).get('fix');
            if (fix !== 'TURN_RIGHT') {
                feedback.hidden = false;
                feedback.textContent = 'Ko-Ki masih berbelok ke arah yang salah. Amati lagi command nomor 2.';
                return;
            }
            completeLocalNotebook(progress);
            feedback.hidden = true;
            showState();
            setSaveIndicator(indicator, 'device');
            queueDetectiveSync(syncOptions);
        });

        if (progress.pendingSync) queueDetectiveSync(syncOptions);
        const removeExitSync = installDetectiveExitSync(syncOptions);
        navigationLifecycle.signal.addEventListener('abort', removeExitSync, { once: true });
        window.addEventListener('online', () => { if (progress.pendingSync) queueDetectiveSync(syncOptions); }, { signal: navigationLifecycle.signal });

        function showState() {
            challenge.hidden = Boolean(progress.notebook?.completed);
            award.hidden = !progress.notebook?.completed;
            root.querySelector('[data-notebook-stars]').textContent = Object.values(progress.cases).reduce((sum, item) => sum + Number(item.stars || 0), 0);
            setSaveIndicator(indicator, progress.pendingSync ? 'device' : 'saved');
        }
    }
}

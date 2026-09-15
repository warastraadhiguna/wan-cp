import { loadDetectiveProgress } from './detective-storage.js';

const link = document.querySelector('[data-detective-resume]');
if (link) {
    const progress = loadDetectiveProgress(link.dataset.progressId);
    if (Array.from({ length: 16 }, (_, index) => index + 1).every((caseNumber) => progress.completedCases.includes(caseNumber))) {
        link.href = link.dataset.notebookUrl;
        link.firstChild.textContent = progress.notebook?.completed ? 'Lihat Badge Pertemuan 4 ' : 'Buka Detective Notebook ';
    } else if (progress.completedCases.length || progress.cases?.[progress.currentCase]?.program) {
        link.href = link.dataset.caseUrl.replace(/\/1$/, `/${progress.currentCase}`);
        link.firstChild.textContent = 'Lanjutkan Pertemuan 4 ';
    }
}

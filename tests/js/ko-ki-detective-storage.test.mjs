import test from 'node:test';
import assert from 'node:assert/strict';
import {
    completeLocalCase, completeLocalNotebook, loadDetectiveProgress, resetLocalCase,
    saveCaseDraft, storageKey, syncDetectiveProgress, syncPayload,
} from '../../public/js/ko-ki/detective-storage.js';
import { getDetectiveCase } from '../../public/js/ko-ki/detective-cases.js';

function memoryStorage() {
    const values = new Map();
    return {
        getItem: (key) => values.get(key) ?? null,
        setItem: (key, value) => values.set(key, value),
        removeItem: (key) => values.delete(key),
    };
}

test('local draft restores current case, repaired program, attempts, and hint level', () => {
    const storage = memoryStorage();
    const progress = loadDetectiveProgress(41, {}, storage);
    saveCaseDraft(progress, 3, { program: ['UP', 'UP', 'UP'], testAttempts: 2, hintCount: 1, suspects: [2] }, storage);
    const restored = loadDetectiveProgress(41, {}, storage);
    assert.equal(restored.currentCase, 3);
    assert.deepEqual(restored.cases[3].program, ['UP', 'UP', 'UP']);
    assert.equal(restored.cases[3].testAttempts, 2);
    assert.equal(restored.cases[3].hintCount, 1);
});

test('case completion is immediate locally and builds collection without network', () => {
    const storage = memoryStorage();
    const progress = loadDetectiveProgress(42, {}, storage);
    completeLocalCase(progress, 1, {
        program: getDetectiveCase(1).solution, suspects: [2], hintCount: 0, testAttempts: 1,
    }, storage);
    assert.deepEqual(progress.completedCases, [1]);
    assert.deepEqual(progress.bugCollection, [1]);
    assert.deepEqual(progress.approvedCases, [1]);
    assert.equal(progress.cases[1].stars, 3);
    assert.equal(progress.pendingSync, true);
});

test('fresh server approval replaces the cached case permission', () => {
    const storage = memoryStorage();
    loadDetectiveProgress(49, { approved_cases: [1] }, storage);
    const restored = loadDetectiveProgress(49, { approved_cases: [1, 2] }, storage);
    assert.deepEqual(restored.approvedCases, [1, 2]);
});

test('restart restores initial program and clears transient case state', () => {
    const storage = memoryStorage();
    const progress = loadDetectiveProgress(43, {}, storage);
    saveCaseDraft(progress, 2, { program: ['FILL'], hintCount: 2, testAttempts: 3, suspects: [1] }, storage);
    resetLocalCase(progress, 2, getDetectiveCase(2).initialProgram, storage);
    assert.deepEqual(progress.cases[2].program, ['FILL', 'FILL', 'FILL']);
    assert.equal(progress.cases[2].hintCount, 0);
    assert.equal(progress.cases[2].testAttempts, 0);
    assert.deepEqual(progress.cases[2].suspects, []);
});

test('Final Case and notebook remain restorable from local storage', () => {
    const storage = memoryStorage();
    const progress = loadDetectiveProgress(44, {}, storage);
    for (let caseNumber = 1; caseNumber <= 16; caseNumber++) {
        completeLocalCase(progress, caseNumber, {
            program: getDetectiveCase(caseNumber).solution, suspects: [], hintCount: 1, testAttempts: 2, finalFixed: caseNumber === 16 ? 3 : 0,
        }, storage);
    }
    completeLocalNotebook(progress, storage);
    const restored = loadDetectiveProgress(44, {}, storage);
    assert.deepEqual(restored.completedCases, Array.from({ length: 16 }, (_, index) => index + 1));
    assert.equal(restored.cases[16].finalFixed, 3);
    assert.equal(restored.notebook.completed, true);
    assert.equal(syncPayload(restored).notebook_fix, 'TURN_RIGHT');
});

test('version one progress moves the old Final Case from six to sixteen without losing it', () => {
    const storage = memoryStorage();
    storage.setItem(storageKey(46), JSON.stringify({
        version: 1,
        progressId: 46,
        currentCase: 6,
        completedCases: [1, 2, 3, 4, 5, 6],
        cases: {
            6: { solved: true, program: getDetectiveCase(16).solution, stars: 3, finalFixed: 3 },
        },
        notebook: { completed: false },
    }));
    const restored = loadDetectiveProgress(46, {}, storage);
    assert.deepEqual(restored.completedCases, [1, 2, 3, 4, 5, 16]);
    assert.equal(restored.currentCase, 6);
    assert.equal(restored.cases[6].solved, false);
    assert.equal(restored.cases[16].finalFixed, 3);
});

test('version two progress moves Final Case eleven to sixteen and opens new case eleven', () => {
    const storage = memoryStorage();
    storage.setItem(storageKey(48), JSON.stringify({
        version: 2,
        progressId: 48,
        currentCase: 11,
        completedCases: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        cases: {
            11: { solved: true, program: getDetectiveCase(16).solution, stars: 3, finalFixed: 3 },
        },
        notebook: { completed: false },
    }));
    const restored = loadDetectiveProgress(48, {}, storage);
    assert.deepEqual(restored.completedCases, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 16]);
    assert.equal(restored.currentCase, 11);
    assert.equal(restored.cases[11].solved, false);
    assert.equal(restored.cases[16].finalFixed, 3);
});

test('refresh restores a new-case repair and controller test progress', () => {
    const storage = memoryStorage();
    const progress = loadDetectiveProgress(47, {}, storage);
    saveCaseDraft(progress, 10, {
        program: getDetectiveCase(10).solution,
        suspects: [0, 1, 2],
        controllerKeysTested: ['SPACE', 'LEFT'],
        hintCount: 1,
        testAttempts: 0,
    }, storage);
    const restored = loadDetectiveProgress(47, {}, storage);
    assert.equal(restored.currentCase, 10);
    assert.deepEqual(restored.cases[10].program, getDetectiveCase(10).solution);
    assert.deepEqual(restored.cases[10].controllerKeysTested, ['SPACE', 'LEFT']);
});

test('sync failure keeps local completion and returns control without throwing', async () => {
    const storage = memoryStorage();
    const progress = loadDetectiveProgress(45, {}, storage);
    completeLocalCase(progress, 1, {
        program: getDetectiveCase(1).solution, suspects: [2], hintCount: 0, testAttempts: 1,
    }, storage);
    const statuses = [];
    const result = await syncDetectiveProgress({
        progress, storage, url: '/sync', retryDelay: 60000,
        fetcher: async () => { throw new Error('offline'); },
        onStatus: (status) => statuses.push(status),
    });
    assert.equal(result.ok, false);
    assert.equal(progress.pendingSync, true);
    assert.equal(progress.cases[1].solved, true);
    assert.deepEqual(statuses, ['syncing', 'device']);
});

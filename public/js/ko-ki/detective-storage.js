const VERSION = 3;
const CASE_COUNT = 16;
const COLLECTION_COUNT = 15;
const PREFIX = 'wan-ko-ki.detective.progress.';
const retryTimers = new Map();

export function storageKey(progressId) {
    return `${PREFIX}${progressId}`;
}

export function loadDetectiveProgress(progressId, serverState = {}, storage = globalThis.localStorage) {
    const fallback = fromServer(progressId, serverState);
    let stored = null;
    try { stored = JSON.parse(storage?.getItem(storageKey(progressId)) || 'null'); } catch { stored = null; }
    if (stored?.version === 1 && Number(stored.progressId) === Number(progressId)) stored = migrateVersionOne(stored);
    if (stored?.version === 2 && Number(stored.progressId) === Number(progressId)) stored = migrateVersionTwo(stored);
    if (!stored || stored.version !== VERSION || Number(stored.progressId) !== Number(progressId)) {
        saveDetectiveProgress(fallback, storage);
        return fallback;
    }

    const serverCompleted = (serverState.completed_cases || []).map(Number);
    if (Array.isArray(serverState.approved_cases)) {
        stored.approvedCases = uniqueSorted(serverState.approved_cases);
    }
    serverCompleted.forEach((caseNumber) => {
        const serverCase = serverState.cases?.[caseNumber] || {};
        stored.cases[caseNumber] = {
            ...(stored.cases?.[caseNumber] || emptyCase()),
            solved: true,
            program: stored.cases?.[caseNumber]?.program || serverCase.program || null,
            stars: Math.max(Number(stored.cases?.[caseNumber]?.stars || 0), Number(serverCase.stars || 1)),
        };
    });
    stored.completedCases = uniqueSorted([...(stored.completedCases || []), ...serverCompleted]);
    stored.bugCollection = stored.completedCases.filter((item) => item <= COLLECTION_COUNT);
    stored.currentCase = Number(stored.currentCase) >= 1 && Number(stored.currentCase) <= CASE_COUNT
        ? Number(stored.currentCase)
        : firstIncomplete(stored.completedCases);
    if (serverState.notebook_completed) {
        stored.notebook = { completed: true, fix: 'TURN_RIGHT' };
        stored.badge = 'Bug Detective';
    }
    saveDetectiveProgress(stored, storage);

    return stored;
}

export function saveDetectiveProgress(progress, storage = globalThis.localStorage) {
    progress.updatedAt = Date.now();
    try { storage?.setItem(storageKey(progress.progressId), JSON.stringify(progress)); } catch { /* Storage penuh tidak boleh menghentikan game. */ }
    return progress;
}

export function saveCaseDraft(progress, caseNumber, draft, storage = globalThis.localStorage) {
    progress.cases[caseNumber] = { ...(progress.cases[caseNumber] || emptyCase()), ...draft };
    progress.currentCase = Number(caseNumber);
    progress.pendingSync = true;
    saveDetectiveProgress(progress, storage);
    return progress;
}

export function completeLocalCase(progress, caseNumber, data, storage = globalThis.localStorage) {
    const stars = 1 + (Number(data.hintCount || 0) <= 1 ? 1 : 0) + (Number(data.testAttempts || 0) <= 2 ? 1 : 0);
    progress.cases[caseNumber] = {
        ...(progress.cases[caseNumber] || emptyCase()),
        solved: true,
        program: [...data.program],
        suspects: [...new Set(data.suspects || [])],
        hintCount: Number(data.hintCount || 0),
        testAttempts: Math.max(1, Number(data.testAttempts || 1)),
        stars: Math.max(stars, Number(progress.cases[caseNumber]?.stars || 0)),
        finalFixed: Number(data.finalFixed || 0),
    };
    progress.completedCases = uniqueSorted([...(progress.completedCases || []), Number(caseNumber)]);
    progress.bugCollection = progress.completedCases.filter((item) => item <= COLLECTION_COUNT);
    progress.currentCase = firstIncomplete(progress.completedCases);
    progress.pendingSync = true;
    saveDetectiveProgress(progress, storage);
    return progress;
}

export function resetLocalCase(progress, caseNumber, initialProgram, storage = globalThis.localStorage) {
    const solved = Boolean(progress.cases[caseNumber]?.solved);
    const stars = Number(progress.cases[caseNumber]?.stars || 0);
    progress.cases[caseNumber] = { ...emptyCase(), solved, stars, program: [...initialProgram] };
    progress.currentCase = Number(caseNumber);
    saveDetectiveProgress(progress, storage);
    return progress;
}

export function completeLocalNotebook(progress, storage = globalThis.localStorage) {
    progress.notebook = { completed: true, fix: 'TURN_RIGHT' };
    progress.badge = 'Bug Detective';
    progress.pendingSync = true;
    saveDetectiveProgress(progress, storage);
    return progress;
}

export function syncPayload(progress) {
    return {
        cases: progress.completedCases
            .map((caseNumber) => ({ caseNumber, value: progress.cases[caseNumber] }))
            .filter(({ value }) => value?.solved && Array.isArray(value.program))
            .map(({ caseNumber, value }) => ({
                case: caseNumber,
                program: value.program,
                suspects: value.suspects || [],
                hint_count: Number(value.hintCount || 0),
                test_attempts: Math.max(1, Number(value.testAttempts || 1)),
            })),
        notebook_fix: progress.notebook?.completed ? 'TURN_RIGHT' : null,
    };
}

export async function syncDetectiveProgress(options) {
    const {
        progress, url, csrfToken = '', storage = globalThis.localStorage,
        fetcher = globalThis.fetch?.bind(globalThis), onStatus = () => {}, retryDelay = 15000,
    } = options;
    const payload = syncPayload(progress);
    if (!progress.pendingSync || payload.cases.length === 0 || !fetcher) {
        onStatus(progress.pendingSync ? 'device' : 'saved');
        return { ok: !progress.pendingSync, skipped: true };
    }

    onStatus('syncing');
    try {
        const response = await fetcher(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload),
        });
        if (!response.ok) throw new Error('Sinkronisasi belum berhasil.');
        const body = await response.json().catch(() => ({}));
        progress.pendingSync = false;
        progress.lastSyncedAt = Date.now();
        saveDetectiveProgress(progress, storage);
        onStatus('saved');
        clearRetry(progress.progressId);
        return { ok: true, body };
    } catch (error) {
        progress.pendingSync = true;
        saveDetectiveProgress(progress, storage);
        onStatus('device');
        scheduleRetry({ ...options, retryDelay });
        return { ok: false, error };
    }
}

export function queueDetectiveSync(options) {
    void syncDetectiveProgress(options);
}

export function installDetectiveExitSync({ progress, url, csrfToken = '' }) {
    if (!globalThis.addEventListener || !globalThis.navigator?.sendBeacon) return () => {};
    const handler = () => {
        if (!progress.pendingSync) return;
        const payload = syncPayload(progress);
        if (!payload.cases.length) return;
        const form = new FormData();
        form.append('_token', csrfToken);
        form.append('cases_json', JSON.stringify(payload.cases));
        if (payload.notebook_fix) form.append('notebook_fix', payload.notebook_fix);
        globalThis.navigator.sendBeacon(url, form);
    };
    globalThis.addEventListener('pagehide', handler);
    return () => globalThis.removeEventListener('pagehide', handler);
}

export function setSaveIndicator(element, status) {
    if (!element) return;
    element.dataset.saveState = status;
    element.textContent = status === 'syncing' ? '⟳ Menyinkronkan' : status === 'saved' ? '✓ Tersimpan' : '✓ Tersimpan di perangkat';
}

function fromServer(progressId, serverState) {
    const cases = {};
    for (let caseNumber = 1; caseNumber <= CASE_COUNT; caseNumber++) {
        const item = serverState.cases?.[caseNumber] || {};
        cases[caseNumber] = {
            ...emptyCase(),
            solved: Boolean(item.completed),
            program: item.program || null,
            hintCount: Number(item.hint_count || 0),
            testAttempts: Number(item.attempts || 0),
            stars: Number(item.stars || 0),
        };
    }
    const completedCases = (serverState.completed_cases || []).map(Number);
    return {
        version: VERSION,
        progressId: Number(progressId),
        currentCase: firstIncomplete(completedCases),
        completedCases,
        approvedCases: uniqueSorted(serverState.approved_cases || [1]),
        bugCollection: completedCases.filter((item) => item <= COLLECTION_COUNT),
        cases,
        notebook: { completed: Boolean(serverState.notebook_completed), fix: serverState.notebook_completed ? 'TURN_RIGHT' : null },
        badge: serverState.badge || null,
        pendingSync: false,
        updatedAt: Date.now(),
        lastSyncedAt: Date.now(),
    };
}

function emptyCase() {
    return { solved: false, program: null, suspects: [], hintCount: 0, testAttempts: 0, stars: 0, finalFixed: 0 };
}

function firstIncomplete(completed) {
    for (let caseNumber = 1; caseNumber <= CASE_COUNT; caseNumber++) if (!completed.includes(caseNumber)) return caseNumber;
    return CASE_COUNT;
}

function uniqueSorted(values) {
    return [...new Set(values.map(Number).filter((item) => item >= 1 && item <= CASE_COUNT))].sort((a, b) => a - b);
}

function migrateVersionOne(stored) {
    const cases = { ...(stored.cases || {}) };
    const legacyFinal = cases[6];
    const legacyProgram = legacyFinal?.program || [];
    const isLegacyFinal = (stored.completedCases || []).map(Number).includes(6)
        || legacyProgram.includes('TAKE_PACKAGE')
        || legacyProgram.includes('DELIVERY');
    let completedCases = (stored.completedCases || []).map(Number);

    if (isLegacyFinal) {
        cases[11] = { ...emptyCase(), ...legacyFinal };
        cases[6] = emptyCase();
        completedCases = completedCases.filter((caseNumber) => caseNumber !== 6);
        if (legacyFinal?.solved) completedCases.push(11);
    }
    for (let caseNumber = 1; caseNumber <= 11; caseNumber++) cases[caseNumber] ||= emptyCase();

    return {
        ...stored,
        version: 2,
        cases,
        completedCases: uniqueSorted(completedCases),
        bugCollection: uniqueSorted(completedCases).filter((item) => item <= COLLECTION_COUNT),
        currentCase: firstIncomplete(uniqueSorted(completedCases)),
        approvedCases: uniqueSorted(stored.approvedCases || [1]),
    };
}

function migrateVersionTwo(stored) {
    const cases = { ...(stored.cases || {}) };
    const oldFinal = cases[11];
    const oldProgram = oldFinal?.program || [];
    const isOldFinal = oldProgram.includes('TAKE_PACKAGE')
        || oldProgram.includes('DELIVERY')
        || Number(oldFinal?.finalFixed || 0) > 0;
    let completedCases = (stored.completedCases || []).map(Number);

    if (isOldFinal) {
        cases[16] = { ...emptyCase(), ...oldFinal };
        cases[11] = emptyCase();
        completedCases = completedCases.filter((caseNumber) => caseNumber !== 11);
        if (oldFinal?.solved) completedCases.push(16);
    }
    for (let caseNumber = 1; caseNumber <= CASE_COUNT; caseNumber++) cases[caseNumber] ||= emptyCase();
    completedCases = uniqueSorted(completedCases);

    return {
        ...stored,
        version: VERSION,
        cases,
        completedCases,
        bugCollection: completedCases.filter((item) => item <= COLLECTION_COUNT),
        currentCase: firstIncomplete(completedCases),
        approvedCases: uniqueSorted(stored.approvedCases || [1]),
    };
}

function scheduleRetry(options) {
    const key = Number(options.progress.progressId);
    if (retryTimers.has(key) || !globalThis.setTimeout) return;
    const timer = globalThis.setTimeout(() => {
        retryTimers.delete(key);
        void syncDetectiveProgress(options);
    }, options.retryDelay);
    timer?.unref?.();
    retryTimers.set(key, timer);
}

function clearRetry(progressId) {
    const timer = retryTimers.get(Number(progressId));
    if (timer && globalThis.clearTimeout) globalThis.clearTimeout(timer);
    retryTimers.delete(Number(progressId));
}

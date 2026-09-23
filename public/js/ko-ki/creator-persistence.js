export const FRIEND_FEEDBACK = ['Seru', 'Lucu', 'Menantang', 'Keren'];

export function creatorCheckpoint(previous, next) {
    if (!previous.playtested && next.playtested) return 'meeting';
    if (previous.stage < 4 && next.stage >= 4) return 'core';
    return null;
}

export function restoreCreatorDraft(serverState, stored) {
    const local = stored?.config;
    let config = local && Number(local.stage) >= Number(serverState.stage) ? local : serverState;
    const pending = Array.isArray(stored?.pending)
        ? stored.pending.filter((snapshot) => snapshot && Number(snapshot.stage) >= 4).slice(-1)
        : [];
    if (serverState.playtested && !config.playtested && !pending.length) {
        config = { ...config, playtested: true, playtest_checks: serverState.playtest_checks, bonuses: serverState.bonuses, target_score: serverState.target_score };
    }

    return { config, pending };
}

export function addFriendFeedback(history, label, at = Date.now()) {
    if (!FRIEND_FEEDBACK.includes(label)) return history;
    return [...history, { label, at }].slice(-20);
}

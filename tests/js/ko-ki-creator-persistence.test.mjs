import test from 'node:test';
import assert from 'node:assert/strict';
import { addFriendFeedback, creatorCheckpoint, restoreCreatorDraft } from '../../public/js/ko-ki/creator-persistence.js';

test('editing and playing stay local; only core and meeting completion are checkpoints', () => {
    const draft = { stage: 2, playtested: false };
    assert.equal(creatorCheckpoint({ stage: 1, playtested: false }, draft), null);
    assert.equal(creatorCheckpoint(draft, { ...draft, character: 'cat' }), null);
    assert.equal(creatorCheckpoint(draft, { stage: 4, playtested: false }), 'core');
    assert.equal(creatorCheckpoint({ stage: 4, playtested: false }, { stage: 4, playtested: true }), 'meeting');
    assert.equal(creatorCheckpoint({ stage: 4, playtested: true }, { stage: 4, playtested: true, bonuses: { timer: true } }), null);
});

test('refresh restores local work and drops old per-stage network requests', () => {
    const server = { stage: 0, character: 'koki', playtested: false };
    const stored = { config: { stage: 3, character: 'cat', theme: 'space' }, pending: [{ stage: 1 }, { stage: 2 }] };
    const restored = restoreCreatorDraft(server, stored);
    assert.equal(restored.config.character, 'cat');
    assert.equal(restored.config.theme, 'space');
    assert.deepEqual(restored.pending, []);
});

test('server completion is retained when a stale device draft has no unsent checkpoint', () => {
    const server = { stage: 4, playtested: true, playtest_checks: { win: true }, bonuses: { sound: true }, target_score: 5 };
    const stored = { config: { stage: 4, playtested: false }, pending: [] };
    assert.equal(restoreCreatorDraft(server, stored).config.playtested, true);
});

test('friend feedback is local, limited, and restricted to four simple choices', () => {
    let feedback = [];
    for (let index = 0; index < 25; index++) feedback = addFriendFeedback(feedback, 'Seru', index);
    assert.equal(feedback.length, 20);
    assert.equal(feedback[0].at, 5);
    assert.equal(addFriendFeedback(feedback, 'Leaderboard').length, 20);
});

import test from 'node:test';
import assert from 'node:assert/strict';
import { CREATOR_CAMPAIGN_LEVELS, campaignGameConfig, completeCampaignLevel, normalizeCampaignProgress } from '../../public/js/ko-ki/creator-campaign.js';
import { CreatorGameEngine } from '../../public/js/ko-ki/creator-engine.js';

test('creator campaign has ten increasingly demanding levels', () => {
    assert.equal(CREATOR_CAMPAIGN_LEVELS.length, 10);
    assert.deepEqual(CREATOR_CAMPAIGN_LEVELS.map((level) => level.target), [5, 8, 12, 15, 15, 20, 25, 30, 35, 40]);
    assert.equal(CREATOR_CAMPAIGN_LEVELS[2].bonuses.second_collectible, true);
    assert.equal(CREATOR_CAMPAIGN_LEVELS[3].bonuses.second_obstacle, true);
    assert.equal(CREATOR_CAMPAIGN_LEVELS[4].bonuses.timer, true);
    assert.equal(CREATOR_CAMPAIGN_LEVELS[5].bonuses.moving_obstacle, true);
    assert.equal(CREATOR_CAMPAIGN_LEVELS[6].bonuses.moving_obstacles_together, true);
    assert.equal(CREATOR_CAMPAIGN_LEVELS[7].bonuses.moving_world, true);
    assert.equal(CREATOR_CAMPAIGN_LEVELS[8].bonuses.aerial_obstacles, true);
    assert.equal(CREATOR_CAMPAIGN_LEVELS[9].bonuses.dragon_boss, true);
});

test('campaign uses the student creation without changing its saved config', () => {
    const base = { character: 'cat', theme: 'space', collectible: 'gem', target_score: 5, bonuses: { sound: true, timer: false } };
    const level = campaignGameConfig(base, 5);
    assert.equal(level.character, 'cat');
    assert.equal(level.theme, 'space');
    assert.equal(level.collectible, 'gem');
    assert.equal(level.target_score, 15);
    assert.equal(level.bonuses.sound, true);
    assert.equal(level.bonuses.timer, true);
    assert.equal(base.target_score, 5);
    assert.equal(base.bonuses.timer, false);
});

test('campaign repairs an old invalid control mapping so a level can always start', () => {
    const level = campaignGameConfig({
        character: 'koki', theme: 'green', controls: { left: 'LEFT', right: 'LEFT', jump: 'SPACE' }, bonuses: {},
    }, 8);
    assert.deepEqual(level.controls, { left: 'LEFT', right: 'RIGHT', jump: 'SPACE' });
    assert.equal(level.bonuses.moving_world, true);
});

test('levels unlock one by one and replay stays available', () => {
    let progress = normalizeCampaignProgress();
    assert.deepEqual(progress, { unlocked: 1, completed: [] });
    progress = completeCampaignLevel(progress, 2);
    assert.deepEqual(progress, { unlocked: 1, completed: [] });
    progress = completeCampaignLevel(progress, 1);
    assert.deepEqual(progress, { unlocked: 2, completed: [1] });
    progress = completeCampaignLevel(progress, 1);
    assert.deepEqual(progress, { unlocked: 2, completed: [1] });
    for (const level of [2, 3, 4, 5, 6, 7, 8, 9, 10]) progress = completeCampaignLevel(progress, level);
    assert.deepEqual(progress, { unlocked: 10, completed: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] });
});

test('every campaign level is playable with the finished student controls', () => {
    const base = {
        stage: 4, controls: { left: 'LEFT', right: 'RIGHT', jump: 'SPACE' },
        collect_rule: 'score_move', obstacle_rule: 'start', bonuses: {},
    };
    for (const level of CREATOR_CAMPAIGN_LEVELS) {
        let time = 0;
        const game = new CreatorGameEngine(campaignGameConfig(base, level.id), () => time);
        for (let step = 0; step < 500 && !game.won && !game.timedOut; step++) {
            time += 100;
            game.useKey('SPACE');
            const destinations = [game.itemX];
            if (game.config.bonuses.second_collectible) destinations.push(game.secondItemX);
            const target = destinations.sort((a, b) => Math.abs(game.x - a) - Math.abs(game.x - b))[0];
            game.useKey(game.x < target ? 'RIGHT' : 'LEFT');
        }
        assert.equal(game.won, true, `Level ${level.id} should be winnable`);
    }
});

import test from 'node:test';
import assert from 'node:assert/strict';
import { facingStation, ingredientCapacity, nextPosition, recipeResult } from '../../public/js/ko-ki/factory-rush-engine.js';

const grid = { width: 10, height: 7 };
const stations = [{ id: 'apple', x: 1, y: 1 }, { id: 'blender', x: 5, y: 1 }];

test('Ko-Ki moves one tile with arrow or WASD directions', () => {
    assert.deepEqual(nextPosition({ x: 4, y: 3 }, 'north', grid, stations), { x: 4, y: 2 });
    assert.deepEqual(nextPosition({ x: 4, y: 3 }, 'east', grid, stations), { x: 5, y: 3 });
});

test('Ko-Ki cannot leave the arena or walk through a station', () => {
    assert.deepEqual(nextPosition({ x: 0, y: 0 }, 'north', grid, stations), { x: 0, y: 0 });
    assert.deepEqual(nextPosition({ x: 1, y: 2 }, 'north', grid, stations), { x: 1, y: 2 });
});

test('SPACE targets the station in front of Ko-Ki', () => {
    assert.equal(facingStation({ x: 1, y: 2, direction: 'north' }, stations).id, 'apple');
    assert.equal(facingStation({ x: 2, y: 2, direction: 'north' }, stations), null);
});

test('recipes create juice and wrong mixtures create a mystery blob', () => {
    const recipes = {
        apple_juice: ['apple', 'water'],
        banana_juice: ['banana', 'water'],
        mixed_juice: ['apple', 'banana', 'water'],
    };

    assert.equal(recipeResult(['water', 'apple'], recipes), 'apple_juice');
    assert.equal(recipeResult(['apple', 'banana'], recipes), 'mystery');
});

test('Big Bag temporarily doubles ingredient capacity', () => {
    assert.equal(ingredientCapacity(false), 1);
    assert.equal(ingredientCapacity(true), 2);
});

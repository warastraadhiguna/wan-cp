export const directionVectors = {
    north: { x: 0, y: -1 },
    east: { x: 1, y: 0 },
    south: { x: 0, y: 1 },
    west: { x: -1, y: 0 },
};

export function nextPosition(player, direction, grid, blocked = []) {
    const vector = directionVectors[direction];
    const candidate = { x: player.x + vector.x, y: player.y + vector.y };
    const outside = candidate.x < 0 || candidate.x >= grid.width || candidate.y < 0 || candidate.y >= grid.height;
    const occupied = blocked.some((cell) => cell.x === candidate.x && cell.y === candidate.y);

    return outside || occupied ? { x: player.x, y: player.y } : candidate;
}

export function facingStation(player, stations) {
    const vector = directionVectors[player.direction];
    const x = player.x + vector.x;
    const y = player.y + vector.y;

    return stations.find((station) => station.x === x && station.y === y) ?? null;
}

export function recipeResult(ingredients, recipes) {
    const selected = [...ingredients].sort();

    for (const [product, recipe] of Object.entries(recipes)) {
        if (JSON.stringify(selected) === JSON.stringify([...recipe].sort())) return product;
    }

    return 'mystery';
}

export function ingredientCapacity(bigBagActive) {
    return bigBagActive ? 2 : 1;
}

<?php

namespace Tests\Unit;

use App\Services\KoKiAdventureService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KoKiAdventureServiceTest extends TestCase
{
    #[DataProvider('recipes')]
    public function test_factory_recipes_are_resolved(array $ingredients, string $output): void
    {
        $this->assertSame($output, (new KoKiAdventureService)->recipeResult($ingredients));
    }

    public static function recipes(): array
    {
        return [
            'apple juice' => [['apple', 'water'], 'apple_juice'],
            'banana juice' => [['water', 'banana'], 'banana_juice'],
            'mixed juice' => [['banana', 'apple', 'water'], 'mixed_juice'],
            'missing water' => [['apple', 'banana'], 'mystery'],
            'duplicate ingredient' => [['apple', 'apple', 'water'], 'mystery'],
        ];
    }

    public function test_progression_has_five_levels_and_about_forty_minutes(): void
    {
        $configuration = (new KoKiAdventureService)->configuration();

        $this->assertCount(5, $configuration['levels']);
        $this->assertGreaterThanOrEqual(40, $configuration['estimated_minutes']);
        $this->assertLessThanOrEqual(45, $configuration['estimated_minutes']);
        $this->assertSame(240, KoKiAdventureService::LEVELS[2]['timer']);
        $this->assertSame(8, KoKiAdventureService::LEVELS[5]['target']);
        $this->assertSame(900, KoKiAdventureService::LEVELS[4]['target_score']);
        $this->assertSame(3, KoKiAdventureService::LEVELS[4]['minimum_batches']);
        $this->assertSame(1800, KoKiAdventureService::LEVELS[5]['target_score']);
        $this->assertSame(4, KoKiAdventureService::LEVELS[5]['minimum_batches']);
        $this->assertContains('conveyor', KoKiAdventureService::LEVELS[3]['systems']);
        $this->assertContains('double_order', KoKiAdventureService::LEVELS[4]['systems']);
        $this->assertContains('powerups', KoKiAdventureService::LEVELS[5]['systems']);
    }

    public function test_factory_configuration_includes_a_two_slot_fridge_for_double_orders(): void
    {
        $configuration = (new KoKiAdventureService)->configuration();

        $fridge = collect($configuration['stations'])->firstWhere('id', 'fridge');
        $this->assertNotNull($fridge);
        $this->assertSame('fridge', $fridge['type']);
        $this->assertSame(2, $configuration['fridge_slots']);
    }
}

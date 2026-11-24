<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection($this->getConnection());
        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $schema = $connection->getSchemaBuilder();
        $engines = array_column($schema->getTables($connection->getDatabaseName()), 'engine', 'name');
        foreach (['quiz_meetings', 'quiz_banks', 'quiz_attempts'] as $table) {
            if (strcasecmp($engines[$connection->getTablePrefix().$table] ?? '', 'InnoDB') !== 0) {
                $name = $connection->getQueryGrammar()->wrapTable($table);
                $connection->statement("ALTER TABLE {$name} ENGINE=InnoDB");
            }
        }

        $references = [
            ['quiz_banks', 'quiz_meeting_id', 'quiz_meetings'],
            ['quiz_attempts', 'quiz_bank_id', 'quiz_banks'],
        ];
        foreach ($references as [$table, $column, $parent]) {
            $key = "{$table}_{$column}_foreign";
            if (! in_array($key, array_column($schema->getForeignKeys($table), 'name'), true)) {
                $schema->table($table, function (Blueprint $blueprint) use ($key, $column, $parent): void {
                    $blueprint->foreign($column, $key)->references('id')->on($parent)->cascadeOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        // Keep the constraints declared by the original quiz migration.
    }
};

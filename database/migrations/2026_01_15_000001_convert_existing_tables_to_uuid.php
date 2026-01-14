<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Tables to convert from bigint to UUID.
     */
    private array $tablesToConvert = [
        'users',
        'vehicle_types',
        'vehicles',
        'tax_periods',
        'vehicle_exemptions',
        'notifications',
        'settings',
        'activities',
        'personal_access_tokens',
    ];

    /**
     * Foreign key mappings: table => [column => referenced_table]
     */
    private array $foreignKeyMappings = [
        // Existing tables referencing other existing tables
        'vehicles' => [
            'vehicle_type_id' => 'vehicle_types',
        ],
        'tax_periods' => [
            'vehicle_id' => 'vehicles',
        ],
        'vehicle_exemptions' => [
            'vehicle_id' => 'vehicles',
        ],
        'notifications' => [
            'user_id' => 'users',
            'vehicle_id' => 'vehicles',
            'tax_period_id' => 'tax_periods',
        ],
        'activities' => [
            'vehicle_id' => 'vehicles',
        ],
        'personal_access_tokens' => [
            'tokenable_id' => 'users', // polymorphic but typically users
        ],
        // New tables referencing users
        'sites' => [
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'user_sites' => [
            'user_id' => 'users',
        ],
        'machine_types' => [
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'site_assignments' => [
            'vehicle_id' => 'vehicles',
            'assigned_by' => 'users',
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'checklist_categories' => [
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'checklist_items' => [
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'inspection_templates' => [
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'components' => [
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'readings' => [
            'vehicle_id' => 'vehicles',
            'recorded_by' => 'users',
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'services' => [
            'vehicle_id' => 'vehicles',
            'submitted_by' => 'users',
            'approved_by' => 'users',
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'parts_catalog' => [
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'service_parts' => [
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'job_cards' => [
            'vehicle_id' => 'vehicles',
            'submitted_by' => 'users',
            'approved_by' => 'users',
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'job_card_components' => [
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'job_card_parts' => [
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'inspections' => [
            'vehicle_id' => 'vehicles',
            'submitted_by' => 'users',
            'approved_by' => 'users',
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'inspection_results' => [
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'watch_list_items' => [
            'vehicle_id' => 'vehicles',
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'component_replacements' => [
            'vehicle_id' => 'vehicles',
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'oil_analyses' => [
            'vehicle_id' => 'vehicles',
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'interval_overrides' => [
            'vehicle_id' => 'vehicles',
            'changed_by' => 'users',
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
        'attachments' => [
            'uploaded_by' => 'users',
            'created_by' => 'users',
            'updated_by' => 'users',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Step 1: Add UUID columns to tables being converted and generate UUIDs
            foreach ($this->tablesToConvert as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                // Add new uuid column
                Schema::table($table, function (Blueprint $t) {
                    $t->uuid('new_uuid')->nullable()->after('id');
                });

                // Generate UUIDs for existing records
                $records = DB::table($table)->select('id')->get();
                foreach ($records as $record) {
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update(['new_uuid' => Str::uuid()->toString()]);
                }
            }

            // Step 2: Create UUID lookup maps for each converted table
            $uuidMaps = [];
            foreach ($this->tablesToConvert as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $uuidMaps[$table] = DB::table($table)
                    ->pluck('new_uuid', 'id')
                    ->toArray();
            }

            // Step 3: Update foreign keys in all tables
            foreach ($this->foreignKeyMappings as $table => $columns) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                foreach ($columns as $column => $referencedTable) {
                    if (!isset($uuidMaps[$referencedTable])) {
                        continue;
                    }

                    // Check if column exists
                    if (!Schema::hasColumn($table, $column)) {
                        continue;
                    }

                    // Get column info to check if it's already a UUID
                    $columnType = DB::selectOne("SHOW COLUMNS FROM `{$table}` WHERE Field = '{$column}'")->Type ?? '';
                    if (str_contains($columnType, 'char(36)')) {
                        continue; // Already a UUID
                    }

                    $newColumn = $column . '_new';

                    // Add new UUID column
                    Schema::table($table, function (Blueprint $t) use ($column, $newColumn) {
                        $t->uuid($newColumn)->nullable()->after($column);
                    });

                    // Copy UUID values based on the old ID relationships
                    foreach ($uuidMaps[$referencedTable] as $oldId => $newUuid) {
                        DB::table($table)
                            ->where($column, $oldId)
                            ->update([$newColumn => $newUuid]);
                    }
                }
            }

            // Step 4: Drop old foreign key constraints and columns, rename new columns
            foreach ($this->foreignKeyMappings as $table => $columns) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                foreach ($columns as $column => $referencedTable) {
                    if (!isset($uuidMaps[$referencedTable])) {
                        continue;
                    }

                    $newColumn = $column . '_new';
                    if (!Schema::hasColumn($table, $newColumn)) {
                        continue;
                    }

                    // Drop foreign key constraint if it exists
                    $this->dropForeignKeyIfExists($table, $column);

                    // Drop old column and rename new column
                    Schema::table($table, function (Blueprint $t) use ($column, $newColumn) {
                        $t->dropColumn($column);
                    });

                    Schema::table($table, function (Blueprint $t) use ($column, $newColumn) {
                        $t->renameColumn($newColumn, $column);
                    });
                }
            }

            // Step 5: Convert primary keys of tables being converted
            foreach ($this->tablesToConvert as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                // Drop auto-increment and primary key
                DB::statement("ALTER TABLE `{$table}` MODIFY `id` BIGINT UNSIGNED NOT NULL");
                DB::statement("ALTER TABLE `{$table}` DROP PRIMARY KEY");

                // Drop old id column
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('id');
                });

                // Rename new_uuid to id and make it primary
                Schema::table($table, function (Blueprint $t) {
                    $t->renameColumn('new_uuid', 'id');
                });

                DB::statement("ALTER TABLE `{$table}` MODIFY `id` CHAR(36) NOT NULL");
                DB::statement("ALTER TABLE `{$table}` ADD PRIMARY KEY (`id`)");
            }

            // Step 6: Fix composite primary keys on pivot tables
            $this->fixPivotTablePrimaryKeys();

            // Step 7: Re-add foreign key constraints
            $this->addForeignKeyConstraints();

        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible
        // A full rollback would require restoring the original bigint IDs
        throw new \RuntimeException('This migration cannot be reversed. Please restore from backup.');
    }

    /**
     * Fix composite primary keys on pivot tables after column conversion.
     */
    private function fixPivotTablePrimaryKeys(): void
    {
        // user_sites has composite primary key (user_id, site_id)
        if (Schema::hasTable('user_sites')) {
            // First drop all foreign keys on this table
            $this->dropAllForeignKeysOnTable('user_sites');

            // Then drop and recreate primary key
            $hasPK = $this->tableHasPrimaryKey('user_sites');
            if ($hasPK) {
                DB::statement('ALTER TABLE `user_sites` DROP PRIMARY KEY');
            }
            DB::statement('ALTER TABLE `user_sites` ADD PRIMARY KEY (`user_id`, `site_id`)');
        }

        // machine_type_checklist_items has composite primary key
        if (Schema::hasTable('machine_type_checklist_items')) {
            $this->dropAllForeignKeysOnTable('machine_type_checklist_items');
            $hasPK = $this->tableHasPrimaryKey('machine_type_checklist_items');
            if ($hasPK) {
                DB::statement('ALTER TABLE `machine_type_checklist_items` DROP PRIMARY KEY');
            }
            DB::statement('ALTER TABLE `machine_type_checklist_items` ADD PRIMARY KEY (`machine_type_id`, `checklist_item_id`)');
        }

        // inspection_template_items has composite primary key
        if (Schema::hasTable('inspection_template_items')) {
            $this->dropAllForeignKeysOnTable('inspection_template_items');
            $hasPK = $this->tableHasPrimaryKey('inspection_template_items');
            if ($hasPK) {
                DB::statement('ALTER TABLE `inspection_template_items` DROP PRIMARY KEY');
            }
            DB::statement('ALTER TABLE `inspection_template_items` ADD PRIMARY KEY (`template_id`, `checklist_item_id`)');
        }
    }

    /**
     * Drop all foreign key constraints on a table.
     */
    private function dropAllForeignKeysOnTable(string $table): void
    {
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '{$table}'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Exception $e) {
                // Ignore if already dropped
            }
        }
    }

    /**
     * Check if a table has a primary key.
     */
    private function tableHasPrimaryKey(string $table): bool
    {
        $result = DB::select("
            SELECT COUNT(*) as cnt
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '{$table}'
            AND CONSTRAINT_TYPE = 'PRIMARY KEY'
        ");
        return $result[0]->cnt > 0;
    }

    /**
     * Drop a foreign key constraint if it exists.
     */
    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '{$table}'
            AND COLUMN_NAME = '{$column}'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Exception $e) {
                // Constraint may already be dropped
            }
        }
    }

    /**
     * Add foreign key constraints after conversion.
     */
    private function addForeignKeyConstraints(): void
    {
        // Users foreign keys
        // (users has no foreign keys to other tables)

        // Vehicle types foreign keys
        // (vehicle_types has no foreign keys)

        // Vehicles foreign keys
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'vehicle_type_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->nullOnDelete();
            });
        }

        // Tax periods foreign keys
        if (Schema::hasTable('tax_periods') && Schema::hasColumn('tax_periods', 'vehicle_id')) {
            Schema::table('tax_periods', function (Blueprint $table) {
                $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            });
        }

        // Vehicle exemptions foreign keys
        if (Schema::hasTable('vehicle_exemptions') && Schema::hasColumn('vehicle_exemptions', 'vehicle_id')) {
            Schema::table('vehicle_exemptions', function (Blueprint $table) {
                $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            });
        }

        // Notifications foreign keys
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (Schema::hasColumn('notifications', 'user_id')) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                }
                if (Schema::hasColumn('notifications', 'vehicle_id')) {
                    $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
                }
                if (Schema::hasColumn('notifications', 'tax_period_id')) {
                    $table->foreign('tax_period_id')->references('id')->on('tax_periods')->nullOnDelete();
                }
            });
        }

        // Activities foreign keys
        if (Schema::hasTable('activities') && Schema::hasColumn('activities', 'vehicle_id')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
            });
        }

        // Personal access tokens (polymorphic, handled differently)
        if (Schema::hasTable('personal_access_tokens') && Schema::hasColumn('personal_access_tokens', 'tokenable_id')) {
            // Polymorphic relationship - no foreign key constraint needed
        }

        // New tables - user references
        $userRefTables = [
            'sites' => ['created_by', 'updated_by'],
            'machine_types' => ['created_by', 'updated_by'],
            'checklist_categories' => ['created_by', 'updated_by'],
            'checklist_items' => ['created_by', 'updated_by'],
            'inspection_templates' => ['created_by', 'updated_by'],
            'components' => ['created_by', 'updated_by'],
            'parts_catalog' => ['created_by', 'updated_by'],
            'service_parts' => ['created_by', 'updated_by'],
            'job_card_components' => ['created_by', 'updated_by'],
            'job_card_parts' => ['created_by', 'updated_by'],
            'inspection_results' => ['created_by', 'updated_by'],
            'attachments' => ['uploaded_by', 'created_by', 'updated_by'],
        ];

        foreach ($userRefTables as $tableName => $columns) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table->getTable(), $column)) {
                        $table->foreign($column)->references('id')->on('users')->nullOnDelete();
                    }
                }
            });
        }

        // Tables with both user and vehicle references
        $complexTables = [
            'site_assignments' => [
                'vehicle_id' => ['vehicles', 'cascadeOnDelete'],
                'assigned_by' => ['users', 'cascadeOnDelete'],
                'created_by' => ['users', 'nullOnDelete'],
                'updated_by' => ['users', 'nullOnDelete'],
            ],
            'readings' => [
                'vehicle_id' => ['vehicles', 'cascadeOnDelete'],
                'recorded_by' => ['users', 'cascadeOnDelete'],
                'created_by' => ['users', 'nullOnDelete'],
                'updated_by' => ['users', 'nullOnDelete'],
            ],
            'services' => [
                'vehicle_id' => ['vehicles', 'cascadeOnDelete'],
                'submitted_by' => ['users', 'nullOnDelete'],
                'approved_by' => ['users', 'nullOnDelete'],
                'created_by' => ['users', 'nullOnDelete'],
                'updated_by' => ['users', 'nullOnDelete'],
            ],
            'job_cards' => [
                'vehicle_id' => ['vehicles', 'cascadeOnDelete'],
                'submitted_by' => ['users', 'nullOnDelete'],
                'approved_by' => ['users', 'nullOnDelete'],
                'created_by' => ['users', 'nullOnDelete'],
                'updated_by' => ['users', 'nullOnDelete'],
            ],
            'inspections' => [
                'vehicle_id' => ['vehicles', 'cascadeOnDelete'],
                'submitted_by' => ['users', 'nullOnDelete'],
                'approved_by' => ['users', 'nullOnDelete'],
                'created_by' => ['users', 'nullOnDelete'],
                'updated_by' => ['users', 'nullOnDelete'],
            ],
            'watch_list_items' => [
                'vehicle_id' => ['vehicles', 'cascadeOnDelete'],
                'created_by' => ['users', 'nullOnDelete'],
                'updated_by' => ['users', 'nullOnDelete'],
            ],
            'component_replacements' => [
                'vehicle_id' => ['vehicles', 'cascadeOnDelete'],
                'created_by' => ['users', 'nullOnDelete'],
                'updated_by' => ['users', 'nullOnDelete'],
            ],
            'oil_analyses' => [
                'vehicle_id' => ['vehicles', 'cascadeOnDelete'],
                'created_by' => ['users', 'nullOnDelete'],
                'updated_by' => ['users', 'nullOnDelete'],
            ],
            'interval_overrides' => [
                'vehicle_id' => ['vehicles', 'cascadeOnDelete'],
                'changed_by' => ['users', 'cascadeOnDelete'],
                'created_by' => ['users', 'nullOnDelete'],
                'updated_by' => ['users', 'nullOnDelete'],
            ],
            'user_sites' => [
                'user_id' => ['users', 'cascadeOnDelete'],
            ],
        ];

        foreach ($complexTables as $tableName => $columns) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column => $config) {
                    if (!Schema::hasColumn($table->getTable(), $column)) {
                        continue;
                    }
                    [$refTable, $onDelete] = $config;
                    $fk = $table->foreign($column)->references('id')->on($refTable);
                    if ($onDelete === 'cascadeOnDelete') {
                        $fk->cascadeOnDelete();
                    } else {
                        $fk->nullOnDelete();
                    }
                }
            });
        }
    }
};

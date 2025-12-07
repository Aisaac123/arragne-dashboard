<?php

namespace Shreejan\CustomizeDashboardWidget\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class InstallCustomizableDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:customizable-dashboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the customizable dashboard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Step 1: Check if migration already exists before publishing
        $migrationName = 'create_user_widget_preferences_table';
        $migrationPath = database_path('migrations');
        $migrationFiles = glob($migrationPath . '/*_' . $migrationName . '.php');
        
        if (!empty($migrationFiles)) {
            $this->warn('⚠ Migration file already exists: ' . basename($migrationFiles[0]));
            $this->info('Skipping migration publishing...');
        } else {
            $this->info('Publishing migrations...');
            exec('php artisan vendor:publish --tag=customize-dashboard-widget-migrations --force', $output, $return_var);
            $this->info(implode("\n", $output));
            if ($return_var === 0) {
                $this->info('✓ Migrations published successfully');
                // Re-check for migration files after publishing
                $migrationFiles = glob($migrationPath . '/*_' . $migrationName . '.php');
            } else {
                $this->error('✗ Failed to publish migrations');
                $this->error(implode("\n", $output));
                return Command::FAILURE;
            }
        }

        // Step 2: Check if table already exists before running migrations
        $tableName = 'user_widget_preferences';
        
        if (Schema::hasTable($tableName)) {
            $this->warn("⚠ Table '{$tableName}' already exists in the database.");
            $this->info('Skipping migration execution...');
        } else {
            // Also check if migration has already run in migrations table
            $migrationRan = false;
            if (Schema::hasTable('migrations') && !empty($migrationFiles)) {
                $migrationFile = basename($migrationFiles[0], '.php');
                $migrationRan = DB::table('migrations')
                    ->where('migration', $migrationFile)
                    ->exists();
            }
            
            if ($migrationRan) {
                $this->warn('⚠ Migration has already been run (found in migrations table).');
                $this->info('Skipping migration execution...');
            } else {
                $this->info('Running migrations...');
                exec('php artisan migrate', $output, $return_var);
                $this->info(implode("\n", $output));
                if ($return_var === 0) {
                    $this->info('✓ Migrations ran successfully');
                } else {
                    $this->error('✗ Failed to run migrations');
                    $this->error(implode("\n", $output));
                    return Command::FAILURE;
                }
            }
        }

        // Step 3: Publish Dashboard stub
        $this->info('Publishing Dashboard stub...');
        exec('php artisan vendor:publish --tag=customize-dashboard-widget-dashboard --force', $output, $return_var);
        $this->info(implode("\n", $output));
        if ($return_var === 0) {
            $this->info('✓ Dashboard published successfully');
        } else {
            $this->error('✗ Failed to publish dashboard');
            $this->error(implode("\n", $output));
            return Command::FAILURE;
        }
    }
}

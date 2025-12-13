<?php

namespace Shreejan\DashArrange\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Install DashArrange Command.
 *
 * Publishes migrations, runs migrations, and publishes dashboard stub.
 */
class InstallDashArrange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dash-arrange:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install DashArrange package (migrations and dashboard stub)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing DashArrange...');

        // Step 1: Publish migrations if not already published
        if (! $this->publishMigrations()) {
            return Command::FAILURE;
        }

        // Step 2: Run migrations if needed
        if (! $this->runMigrations()) {
            return Command::FAILURE;
        }

        // Step 3: Publish Dashboard stub
        if (! $this->publishDashboard()) {
            return Command::FAILURE;
        }

        $this->info('✓ DashArrange installed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Publish migrations.
     */
    private function publishMigrations(): bool
    {
        $migrationName = 'create_user_widget_preferences_table';
        $migrationPath = database_path('migrations');
        $migrationFiles = glob($migrationPath.'/*_'.$migrationName.'.php');

        if (! empty($migrationFiles)) {
            $this->warn('⚠ Migration file already exists: '.basename($migrationFiles[0]));
            $this->info('Skipping migration publishing...');

            return true;
        }

        $this->info('Publishing migrations...');
        exec('php artisan vendor:publish --tag=dash-arrange-migrations --force', $output, $returnVar);

        if ($returnVar === 0) {
            $this->info('✓ Migrations published successfully');

            return true;
        }

        $this->error('✗ Failed to publish migrations');
        $this->error(implode("\n", $output));

        return false;
    }

    /**
     * Run migrations if needed.
     */
    private function runMigrations(): bool
    {
        $tableName = 'user_widget_preferences';

        if (Schema::hasTable($tableName)) {
            $this->warn("⚠ Table '{$tableName}' already exists in the database.");
            $this->info('Skipping migration execution...');

            return true;
        }

        // Check if migration has already run
        $migrationPath = database_path('migrations');
        $migrationFiles = glob($migrationPath.'/*_create_user_widget_preferences_table.php');

        if (! empty($migrationFiles) && Schema::hasTable('migrations')) {
            $migrationFile = basename($migrationFiles[0], '.php');
            $migrationRan = DB::table('migrations')
                ->where('migration', $migrationFile)
                ->exists();

            if ($migrationRan) {
                $this->warn('⚠ Migration has already been run (found in migrations table).');
                $this->info('Skipping migration execution...');

                return true;
            }
        }

        $this->info('Running migrations...');
        exec('php artisan migrate', $output, $returnVar);

        if ($returnVar === 0) {
            $this->info('✓ Migrations ran successfully');

            return true;
        }

        $this->error('✗ Failed to run migrations');
        $this->error(implode("\n", $output));

        return false;
    }

    /**
     * Publish dashboard stub.
     */
    private function publishDashboard(): bool
    {
        $this->info('Publishing Dashboard stub...');
        exec('php artisan vendor:publish --tag=dash-arrange-dashboard --force', $output, $returnVar);

        if ($returnVar === 0) {
            $this->info('✓ Dashboard published successfully');

            return true;
        }

        $this->error('✗ Failed to publish dashboard');
        $this->error(implode("\n", $output));

        return false;
    }
}



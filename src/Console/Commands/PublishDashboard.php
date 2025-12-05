<?php

namespace Shreejan\CustomizeDashboardWidget\Console\Commands;

use Illuminate\Console\Command;

class PublishDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:dashboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Step 1: Publish migrations first
        $this->info('Publishing migrations...');
        exec('php artisan vendor:publish --tag=customize-dashboard-widget-migrations --force', $output, $return_var);
        $this->info(implode("\n", $output));
        if ($return_var === 0) {
            $this->info('✓ Migrations published successfully');
        } else {
            $this->error('✗ Failed to publish migrations');
            $this->error(implode("\n", $output));
            return Command::FAILURE;
        }

        // Step 2: Run migrations
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

        // Step 4: Update AdminPanelProvider
        $this->info('Updating AdminPanelProvider...');
        $adminPanelPath = app_path('Providers/Filament/AdminPanelProvider.php');
        
        if (!file_exists($adminPanelPath)) {
            $this->error('✗ AdminPanelProvider.php not found at: ' . $adminPanelPath);
            return Command::FAILURE;
        }

        $content = file_get_contents($adminPanelPath);
        
        // Update the Dashboard import
        if (strpos($content, 'use App\Filament\Pages\Dashboard;') === false) {
            $content = str_replace('use Filament\Pages\Dashboard;', 'use App\Filament\Pages\Dashboard;
use Shreejan\CustomizeDashboardWidget\CustomizeDashboardWidgetPlugin;', $content);
            file_put_contents($adminPanelPath, $content);
            $this->info('✓ Dashboard import updated');
        }
    }
}

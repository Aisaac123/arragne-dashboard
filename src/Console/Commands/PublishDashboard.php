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
        exec('php artisan migrate', $output, $return_var);
        $this->info(implode("\n", $output));
        if ($return_var === 0) {
            $this->info('Migrations ran successfully');
        } else {
            $this->error('Failed to run migrations');
            $this->error(implode("\n", $output));
        }

        exec('php artisan vendor:publish --tag=customize-dashboard-widget-dashboard', $output, $return_var);
        $this->info(implode("\n", $output));
        if ($return_var === 0) {
            $this->info('Dashboard published successfully');
        } else {
            $this->error('Failed to publish dashboard');
            $this->error(implode("\n", $output));
        }



        $content = file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));
        $content = str_replace('use Filament\Pages\Dashboard;', 'use App\Filament\Pages\Dashboard;
use Shreejan\CustomizeDashboardWidget\CustomizeDashboardWidgetPlugin;', $content);
        file_put_contents(app_path('Providers/Filament/AdminPanelProvider.php'), $content);
        $this->info('AdminPanelProvider updated successfully');


        $pluginData= file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));
        if(strpos($pluginData, 'CustomizeDashboardWidgetPlugin::make()') === false) {
            $pluginData = str_replace('->plugins([', '->plugins([
                CustomizeDashboardWidgetPlugin::make(),', $pluginData);
            file_put_contents(app_path('Providers/Filament/AdminPanelProvider.php'), $pluginData);
        }
    }
}

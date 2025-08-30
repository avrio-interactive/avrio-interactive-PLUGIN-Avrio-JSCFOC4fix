<?php 

/**
 * ReCaptcha Testing Console Command
 * 
 * @package     Avrio\Jscfoc4fix\Console
 * @author      Avrio Interactive Sp. z o.o.
 * @copyright   2025 Avrio Interactive Sp. z o.o.
 * @license     MIT License
 * @link        https://avrio.pl
 * 
 * Console command for testing ReCaptcha loading and troubleshooting.
 */

namespace Avrio\Jscfoc4fix\Console;

use Illuminate\Console\Command;

class TestReCaptcha extends Command
{
    protected $signature = 'jscfoc4fix:test-recaptcha';
    protected $description = 'Test if ReCaptcha can be loaded properly';

    public function handle()
    {
        $this->info('Testing ReCaptcha loading...');
        
        // Test loading from various paths
        $paths = [
            base_path('plugins/janvince/smallcontactform/vendor/autoload.php'),
            base_path('plugins/janvince/smallcontactform/vendor/google/recaptcha/src/autoload.php'),
            base_path('plugins/janvince/smallcontactform/vendor.disabled/google/recaptcha/src/autoload.php')
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $this->line("Found: {$path}");
                try {
                    require_once $path;
                    if (class_exists('ReCaptcha\ReCaptcha')) {
                        $this->info("✓ Successfully loaded ReCaptcha from: {$path}");
                        $this->line("ReCaptcha class available: " . (class_exists('ReCaptcha\ReCaptcha') ? 'YES' : 'NO'));
                        return;
                    }
                } catch (\Exception $e) {
                    $this->error("✗ Failed to load from {$path}: " . $e->getMessage());
                }
            } else {
                $this->line("Not found: {$path}");
            }
        }
        
        $this->error('Could not load ReCaptcha from any path');
    }
}

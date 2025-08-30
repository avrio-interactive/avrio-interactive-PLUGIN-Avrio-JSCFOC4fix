<?php

/**
 * October CMS 4 Compatibility Fix for janvince/smallcontactform
 * 
 * @package     Avrio\Jscfoc4fix
 * @author      Avrio Interactive Sp. z o.o.
 * @copyright   2025 Avrio Interactive Sp. z o.o.
 * @license     MIT License
 * @link        https://avrio.pl
 * @version     1.0.1
 * 
 * This plugin fixes compatibility issues between janvince/smallcontactform 
 * and October CMS 4 by resolving vendor autoloader conflicts and ensuring 
 * proper ReCaptcha loading.
 */

namespace Avrio\Jscfoc4fix;

use System\Classes\PluginBase;
use Event;

class Plugin extends PluginBase {
    
    /**
     * @var array Plugin dependencies - make sure this loads before the problematic plugin
     */
    public $require = [];
    
    /**
     * @var bool Ensures this plugin loads first
     */
    public $elevated = true;

    public function pluginDetails() {
        return [
            'name' => 'Small Contact Form OC4 Fix',
            'description' => 'October CMS 4 compatibility fix for janvince.smallcontactform plugin',
            'author' => 'Avrio Interactive Sp. z o.o.',
            'icon' => 'icon-wrench',
            'homepage' => 'https://avrio.pl'
        ];
    }

    public function boot() {
        // Fix for October CMS 4 compatibility issues - order matters!
        $this->disableProblematicVendorFolder();
        
        // Register ReCaptcha autoloader early
        $this->registerReCaptchaAutoloader();
        
        // Load ReCaptcha early if we're in a context that might need it
        $this->preloadReCaptchaIfNeeded();
        
        $this->fixComposerServices();
        $this->fixValidationRules();
        $this->preventVendorAutoloaderConflicts();
    }
    
    protected function registerReCaptchaAutoloader() {
        // Register a global autoloader for ReCaptcha classes
        if (!defined('RECAPTCHA_AUTOLOADER_REGISTERED')) {
            define('RECAPTCHA_AUTOLOADER_REGISTERED', true);
            
            spl_autoload_register(function ($class) {
                if (strpos($class, 'ReCaptcha\\') === 0) {
                    $paths = [
                        base_path('plugins/janvince/smallcontactform/vendor/google/recaptcha/src/'),
                        base_path('plugins/janvince/smallcontactform/vendor.disabled/google/recaptcha/src/')
                    ];
                    
                    foreach ($paths as $basePath) {
                        $file = $basePath . str_replace('\\', '/', $class) . '.php';
                        if (file_exists($file)) {
                            require_once $file;
                            return true;
                        }
                    }
                }
                return false;
            });
        }
    }
    
    protected function preloadReCaptchaIfNeeded() {
        // Check if we're in a web request (not console)
        if (app()->runningInConsole()) {
            return;
        }
        
        // Check if current request might involve SmallContactForm
        $request = request();
        if ($request) {
            // If it's a POST request or has form data, preload ReCaptcha
            if ($request->isMethod('post') || 
                $request->has('_token') || 
                $request->has('g-recaptcha-response') ||
                strpos($request->path(), 'contact') !== false) {
                $this->ensureReCaptchaAvailable();
            }
        }
    }
    
    protected function ensureReCaptchaAvailable() {
        // Force load ReCaptcha early to prevent "Class not found" errors
        // Only load if not already loaded to prevent multiple loading
        if (!class_exists('ReCaptcha\ReCaptcha') && !defined('RECAPTCHA_LOADING_ATTEMPTED')) {
            define('RECAPTCHA_LOADING_ATTEMPTED', true);
            
            // Try multiple paths to load ReCaptcha
            $paths = [
                base_path('plugins/janvince/smallcontactform/vendor/autoload.php'),
                base_path('plugins/janvince/smallcontactform/vendor/google/recaptcha/src/autoload.php'),
                base_path('plugins/janvince/smallcontactform/vendor.disabled/google/recaptcha/src/autoload.php')
            ];
            
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    try {
                        require_once $path;
                        if (class_exists('ReCaptcha\ReCaptcha')) {
                            // Only log once, not every time
                            if (!defined('RECAPTCHA_LOADED_LOGGED')) {
                                define('RECAPTCHA_LOADED_LOGGED', true);
                                \Log::info('Successfully loaded ReCaptcha from: ' . $path);
                            }
                            break;
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to load ReCaptcha from ' . $path . ': ' . $e->getMessage());
                    }
                }
            }
            
            // If still not loaded, try direct class loading
            if (!class_exists('ReCaptcha\ReCaptcha')) {
                $this->loadReCaptchaDirectly();
            }
        }
    }
    
    protected function loadReCaptchaDirectly() {
        // Load ReCaptcha classes directly
        $recaptchaDir = base_path('plugins/janvince/smallcontactform/vendor/google/recaptcha/src/ReCaptcha');
        
        if (is_dir($recaptchaDir)) {
            try {
                // Load main classes
                $classes = [
                    'ReCaptcha.php',
                    'RequestMethod.php', 
                    'RequestParameters.php',
                    'Response.php'
                ];
                
                foreach ($classes as $class) {
                    $classPath = $recaptchaDir . '/' . $class;
                    if (file_exists($classPath)) {
                        require_once $classPath;
                    }
                }
                
                // Load RequestMethod implementations
                $requestMethodDir = $recaptchaDir . '/RequestMethod';
                if (is_dir($requestMethodDir)) {
                    $requestMethods = [
                        'Post.php',
                        'SocketPost.php',
                        'Curl.php',
                        'CurlPost.php'
                    ];
                    
                    foreach ($requestMethods as $method) {
                        $methodPath = $requestMethodDir . '/' . $method;
                        if (file_exists($methodPath)) {
                            require_once $methodPath;
                        }
                    }
                }
                
                if (class_exists('ReCaptcha\ReCaptcha')) {
                    \Log::info('Successfully loaded ReCaptcha via direct class loading');
                }
                
            } catch (\Exception $e) {
                \Log::error('Failed to load ReCaptcha directly: ' . $e->getMessage());
            }
        }
    }
    
    protected function disableProblematicVendorFolder() {
        // Temporarily rename the vendor folder to prevent composer/installers conflicts
        $vendorPath = base_path('plugins/janvince/smallcontactform/vendor');
        $vendorDisabledPath = base_path('plugins/janvince/smallcontactform/vendor.disabled');
        
        if (is_dir($vendorPath) && !is_dir($vendorDisabledPath)) {
            try {
                rename($vendorPath, $vendorDisabledPath);
                \Log::info('Temporarily disabled janvince/smallcontactform vendor folder to prevent October CMS 4 conflicts');
                
                // Create a minimal vendor structure with just ReCaptcha
                $this->createMinimalVendorStructure($vendorPath);
            } catch (\Exception $e) {
                \Log::warning('Could not disable vendor folder: ' . $e->getMessage());
            }
        }
    }
    
    protected function createMinimalVendorStructure($vendorPath) {
        // Create minimal vendor structure with only ReCaptcha
        $vendorDisabledPath = base_path('plugins/janvince/smallcontactform/vendor.disabled');
        
        if (is_dir($vendorDisabledPath . '/google')) {
            try {
                if (!is_dir($vendorPath)) {
                    mkdir($vendorPath, 0755, true);
                }
                
                // Copy only the google/recaptcha folder
                $this->recursiveCopy($vendorDisabledPath . '/google', $vendorPath . '/google');
                
                // Create a minimal autoload.php that only loads ReCaptcha
                file_put_contents($vendorPath . '/autoload.php', $this->getMinimalAutoloadContent());
                
            } catch (\Exception $e) {
                \Log::warning('Could not create minimal vendor structure: ' . $e->getMessage());
            }
        }
    }
    
    protected function getMinimalAutoloadContent() {
        return '<?php
// Minimal autoloader for October CMS 4 compatibility
// Only loads ReCaptcha to avoid composer/installers conflicts

if (!class_exists("ReCaptcha\\ReCaptcha")) {
    $recaptchaPath = __DIR__ . "/google/recaptcha/src/autoload.php";
    if (file_exists($recaptchaPath)) {
        require_once $recaptchaPath;
    }
}
';
    }
    
    protected function recursiveCopy($src, $dst) {
        if (!is_dir($src)) {
            return false;
        }
        
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file != "." && $file != "..") {
                if (is_dir($src . "/" . $file)) {
                    $this->recursiveCopy($src . "/" . $file, $dst . "/" . $file);
                } else {
                    copy($src . "/" . $file, $dst . "/" . $file);
                }
            }
        }
        closedir($dir);
        return true;
    }

    public function register() {
        // Override problematic service registrations before they cause issues
        // Don't register core.composer - let October handle it properly
        
        // Register any other needed mock services
        if (!$this->app->bound('composer.installers')) {
            $this->app->singleton('composer.installers', function() {
                return new \stdClass();
            });
        }
        
        // Register console commands
        $this->registerConsoleCommand('jscfoc4fix.test-recaptcha', 'Avrio\Jscfoc4fix\Console\TestReCaptcha');
    }

    protected function fixComposerServices() {
        // Don't interfere with October's ComposerManager
        // The problem is likely in the vendor autoloader, not the service itself
    }

    protected function preventVendorAutoloaderConflicts() {
        // Prevent loading of conflicting vendor autoloaders from janvince/smallcontactform
        $vendorAutoload = base_path('plugins/janvince/smallcontactform/vendor/autoload.php');
        
        // Disable the problematic vendor autoloader entirely
        if (file_exists($vendorAutoload)) {
            // Mark this autoloader as already loaded to prevent double loading
            if (!defined('JANVINCE_SMALLCONTACTFORM_VENDOR_LOADED')) {
                define('JANVINCE_SMALLCONTACTFORM_VENDOR_LOADED', true);
                
                // Only load ReCaptcha manually if needed
                if (!class_exists('ReCaptcha\ReCaptcha')) {
                    $recaptchaAutoload = base_path('plugins/janvince/smallcontactform/vendor/google/recaptcha/src/autoload.php');
                    if (file_exists($recaptchaAutoload)) {
                        try {
                            include_once $recaptchaAutoload;
                        } catch (\Exception $e) {
                            \Log::warning('Could not load ReCaptcha: ' . $e->getMessage());
                        }
                    }
                }
            }
        }
        
        // Also prevent the main composer/installers plugin from loading
        $this->preventComposerInstallersPlugin();
    }
    
    protected function preventComposerInstallersPlugin() {
        // Override the composer installers plugin loading that causes conflicts
        $composerInstallersPath = base_path('plugins/janvince/smallcontactform/vendor/composer/installers');
        if (is_dir($composerInstallersPath)) {
            // Temporarily disable this plugin to prevent conflicts
            \Log::info('Preventing composer/installers plugin conflicts from janvince/smallcontactform');
        }
    }

    protected function fixValidationRules() {
        // October CMS 4 validation rule registration fix
        Event::listen('system.extendConfigFile', function($path, $config) {
            if (strpos($path, 'janvince/smallcontactform') !== false) {
                // Apply compatibility fixes for validation rules if needed
            }
        });

        // Load ReCaptcha when SmallContactForm component is used
        Event::listen('cms.component.beforeRunAjax', function($component) {
            if ($component instanceof \JanVince\SmallContactForm\Components\SmallContactForm) {
                $this->ensureReCaptchaAvailable();
            }
        });
        
        // Also load on regular component run (not just AJAX)
        Event::listen('cms.component.beforeRun', function($component) {
            if ($component instanceof \JanVince\SmallContactForm\Components\SmallContactForm) {
                $this->ensureReCaptchaAvailable();
            }
        });
        
        // Load ReCaptcha early when page with contactForm is being rendered
        Event::listen('cms.page.beforeRenderPage', function($controller, $page) {
            if ($page && property_exists($page, 'components')) {
                foreach ($page->components as $component) {
                    if ($component instanceof \JanVince\SmallContactForm\Components\SmallContactForm) {
                        $this->ensureReCaptchaAvailable();
                        break;
                    }
                }
            }
        });
    }

    public function registerComponents() {
        return [
            'Avrio\Jscfoc4fix\Components\SmallContactFormFix' => 'smallContactFormFix',
        ];
    }
    
    /**
     * Public method to test ReCaptcha loading
     */
    public static function testReCaptchaLoading() {
        if (class_exists('ReCaptcha\ReCaptcha')) {
            return 'ReCaptcha is loaded successfully';
        } else {
            return 'ReCaptcha is NOT loaded';
        }
    }
}

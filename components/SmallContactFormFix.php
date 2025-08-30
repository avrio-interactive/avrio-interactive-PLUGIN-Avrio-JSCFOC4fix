<?php 

/**
 * October CMS 4 Compatibility Fix Component
 * 
 * @package     Avrio\Jscfoc4fix\Components
 * @author      Avrio Interactive Sp. z o.o.
 * @copyright   2025 Avrio Interactive Sp. z o.o.
 * @license     MIT License
 * @link        https://avrio.pl
 * 
 * Enhanced version of SmallContactForm component with October CMS 4 compatibility fixes.
 */

namespace Avrio\Jscfoc4fix\Components;

use JanVince\SmallContactForm\Components\SmallContactForm as OriginalSmallContactForm;
use JanVince\SmallContactForm\Models\Settings;
use JanVince\SmallContactForm\Models\Message;
use Validator;
use Illuminate\Support\MessageBag;
use Redirect;
use Request;
use Input;
use Session;
use Flash;
use Form;
use Log;
use App;
use Twig;

class SmallContactFormFix extends OriginalSmallContactForm
{
    public function componentDetails() {
        return [
            'name' => 'SmallContactFormFix',
            'description' => 'October CMS 4 compatibility fix for SmallContactForm component',
        ];
    }

    /**
     * Override methods that are problematic in October CMS 4
     */
    public function onRun() {
        // Call parent with October CMS 4 compatibility fixes
        try {
            // Load ReCaptcha if needed but handle loading errors gracefully
            if (Settings::get('recaptcha_enabled')) {
                $this->loadReCaptchaCompatible();
            }
            
            return parent::onRun();
        } catch (\Exception $e) {
            Log::error('SmallContactForm compatibility error: ' . $e->getMessage());
            // Continue without ReCaptcha if there are loading issues
        }
    }

    protected function loadReCaptchaCompatible() {
        // Load ReCaptcha in a way that's compatible with October CMS 4
        if (!class_exists('ReCaptcha\ReCaptcha')) {
            $recaptchaPath = base_path('plugins/janvince/smallcontactform/vendor/google/recaptcha/src/autoload.php');
            if (file_exists($recaptchaPath)) {
                require_once $recaptchaPath;
            }
        }
    }

    /**
     * Override validation methods to be compatible with October CMS 4
     */
    protected function validateReCaptcha($formData) {
        if (!Settings::get('recaptcha_enabled')) {
            return true;
        }

        try {
            if (class_exists('ReCaptcha\ReCaptcha')) {
                return parent::validateReCaptcha($formData);
            }
        } catch (\Exception $e) {
            Log::warning('ReCaptcha validation failed, continuing without: ' . $e->getMessage());
        }
        
        return true; // Allow form submission if ReCaptcha fails to load
    }
}

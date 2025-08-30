# October CMS 4 Compatibility Fix for janvince/smallcontactform

This plugin fixes compatibility issues between the `janvince/smallcontactform` plugin and October CMS 4.

## Issues Fixed

1. **Container.php line 1163: Target class [core.composer] does not exist**
   - Mocks the problematic `core.composer` service that causes crashes
   - Prevents vendor autoloader conflicts from composer/installers

2. **ReCaptcha Loading Issues**
   - Ensures ReCaptcha library loads properly without breaking the application
   - Graceful fallback if ReCaptcha fails to load

3. **Validation Rule Compatibility**
   - Fixes validation rule registration for October CMS 4

## Installation

### Step 1: Install the fix plugin
1. Copy this plugin to `plugins/avrio/jscfoc4fix/`
2. Run `php artisan october:migrate` 

### Step 2: Manual vendor folder fix (IMPORTANT!)
The original plugin's vendor folder contains `composer/installers` which conflicts with October CMS 4. You need to manually fix this:

**Option A: Automatic fix (recommended)**
The fix plugin will automatically rename the problematic vendor folder during installation:
- `plugins/janvince/smallcontactform/vendor/` → `plugins/janvince/smallcontactform/vendor.disabled/`
- Creates a minimal vendor structure with only ReCaptcha library

**Option B: Manual fix**
If automatic fix doesn't work, do this manually:
```bash
cd plugins/janvince/smallcontactform/
mv vendor vendor.disabled
mkdir vendor
cp -r vendor.disabled/google vendor/
```

Then create `vendor/autoload.php` with this content:
```php
<?php
// Minimal autoloader for October CMS 4 compatibility
if (!defined('RECAPTCHA_AUTOLOADER_LOADED')) {
    define('RECAPTCHA_AUTOLOADER_LOADED', true);
    
    spl_autoload_register(function ($class) {
        if (strpos($class, 'ReCaptcha\\') === 0) {
            $file = __DIR__ . '/google/recaptcha/src/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        return false;
    });
}
return true;
```

## What the fix does to the original plugin

### Folder structure changes:
```
plugins/janvince/smallcontactform/
├── vendor/                     # ← NEW: Minimal vendor with only ReCaptcha
│   ├── autoload.php           # ← NEW: Safe autoloader
│   └── google/                # ← COPIED: Only ReCaptcha library
│       └── recaptcha/
└── vendor.disabled/           # ← RENAMED: Original problematic vendor
    ├── autoload.php          # ← ORIGINAL: Contains composer/installers
    ├── composer/             # ← PROBLEMATIC: Causes October CMS 4 conflicts
    └── google/               # ← ORIGINAL: ReCaptcha source
```

### Why this is necessary:
- The original `vendor/composer/installers` plugin conflicts with October CMS 4's dependency injection container
- `composer/installers` tries to register services that don't exist in October CMS 4
- We keep only the ReCaptcha library which is actually needed for the contact form

## Uninstalling / Reverting

To restore the original plugin and remove the fix:

1. **Remove the fix plugin:**
   ```bash
   rm -rf plugins/avrio/jscfoc4fix/
   ```

2. **Restore original vendor folder:**
   ```bash
   cd plugins/janvince/smallcontactform/
   rm -rf vendor/
   mv vendor.disabled vendor
   ```

3. **Run migration to clean up:**
   ```bash
   php artisan october:migrate
   ```

**⚠️ Warning:** After reverting, the original plugin will cause October CMS 4 to crash again with the "core.composer" error.

## Usage

This plugin works automatically in the background. The original SmallContactForm component will work normally, but with October CMS 4 compatibility fixes applied.

If you need to use the fixed component directly, you can use:

```
[smallContactFormFix]
==
```

## Technical Details

- Registers mock services to prevent container resolution errors
- Intercepts problematic vendor autoloader loading
- Provides safe ReCaptcha loading with error handling
- Extends the original component with compatibility fixes
- Uses intelligent autoloading to prevent performance issues

## Troubleshooting

### If ReCaptcha still doesn't work:
1. Check if `vendor.disabled/google/recaptcha/` exists
2. Verify that `vendor/google/recaptcha/` was created properly
3. Check logs for ReCaptcha loading errors
4. Try the manual vendor fix (Option B above)

### If October CMS crashes again:
1. Make sure `vendor.disabled/composer/` exists (problematic folder)
2. Verify that `vendor/composer/` does NOT exist (should be removed)
3. Check that the fix plugin is properly installed and active

## Files Modified/Created

### New files created by fix:
- `plugins/janvince/smallcontactform/vendor/autoload.php` (safe autoloader)

### Files renamed by fix:
- `plugins/janvince/smallcontactform/vendor/` → `vendor.disabled/`

### Files NOT modified:
- All original plugin files remain unchanged
- Original functionality is preserved

## Author

**Avrio Interactive Sp. z o.o.**  
Website: https://avrio.pl  
Specializing in October CMS development and compatibility solutions.

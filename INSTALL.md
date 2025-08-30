# INSTALLATION GUIDE

**Plugin:** avrio/jscfoc4fix  
**Author:** Avrio Interactive Sp. z o.o. (https://avrio.pl)  
**October CMS 4 compatibility fix for janvince/smallcontactform**

## Quick Start

1. **Install the fix plugin:**
   ```bash
   # Copy to plugins directory
   cp -r avrio/jscfoc4fix /path/to/october/plugins/avrio/
   
   # Run migration
   php artisan october:migrate
   ```

2. **Verify the fix worked:**
   - Check that `plugins/janvince/smallcontactform/vendor.disabled/` exists
   - Check that `plugins/janvince/smallcontactform/vendor/` contains only ReCaptcha
   - Test the contact form

## What happens during installation:

### Before fix:
```
plugins/janvince/smallcontactform/
└── vendor/
    ├── autoload.php          # ← Contains composer/installers (PROBLEMATIC)
    ├── composer/             # ← Causes "core.composer" error
    │   └── installers/
    └── google/
        └── recaptcha/        # ← Only this is needed
```

### After fix:
```
plugins/janvince/smallcontactform/
├── vendor/                   # ← NEW: Safe minimal structure
│   ├── autoload.php         # ← NEW: Safe autoloader
│   └── google/              # ← COPIED: ReCaptcha only
│       └── recaptcha/
└── vendor.disabled/         # ← BACKUP: Original vendor folder
    ├── autoload.php         # ← OLD: Problematic autoloader
    ├── composer/            # ← OLD: Causes conflicts
    └── google/              # ← OLD: ReCaptcha source
```

## Manual Installation (if automatic fails)

If the automatic migration doesn't work, follow these steps:

1. **Backup original vendor:**
   ```bash
   cd plugins/janvince/smallcontactform/
   mv vendor vendor.disabled
   ```

2. **Create minimal vendor:**
   ```bash
   mkdir vendor
   cp -r vendor.disabled/google vendor/
   ```

3. **Create safe autoloader:**
   ```bash
   cat > vendor/autoload.php << 'EOF'
   <?php
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
   EOF
   ```

4. **Install fix plugin:**
   ```bash
   php artisan october:migrate
   ```

## Verification

Check that the fix worked:

```bash
# Should exist (backup)
ls plugins/janvince/smallcontactform/vendor.disabled/

# Should exist (minimal)
ls plugins/janvince/smallcontactform/vendor/

# Should NOT exist (problematic)
ls plugins/janvince/smallcontactform/vendor/composer/
```

## Troubleshooting

### If October CMS still crashes:
- Check that `vendor/composer/` doesn't exist
- Check that `vendor.disabled/composer/` exists
- Verify the fix plugin is active: `php artisan plugin:list`

### If ReCaptcha doesn't work:
- Check that `vendor/google/recaptcha/` exists
- Check logs for ReCaptcha loading errors
- Verify ReCaptcha is enabled in SmallContactForm settings

### If you need to revert:
```bash
cd plugins/janvince/smallcontactform/
rm -rf vendor/
mv vendor.disabled vendor
rm -rf /path/to/october/plugins/avrio/jscfoc4fix/
php artisan october:migrate
```

**⚠️ Warning:** Reverting will cause October CMS 4 to crash again!

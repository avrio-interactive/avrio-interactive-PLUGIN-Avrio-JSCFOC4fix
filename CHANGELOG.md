# CHANGELOG

**Plugin:** avrio/jscfoc4fix  
**Author:** Avrio Interactive Sp. z o.o. (https://avrio.pl)  
**Purpose:** October CMS 4 compatibility fix for janvince/smallcontactform

## v1.0.1 - 2025-08-30

### Added
- October CMS 4 compatibility fix for janvince/smallcontactform
- Automatic vendor folder restructuring to prevent composer/installers conflicts
- Safe ReCaptcha autoloader that doesn't break October CMS 4
- Intelligent ReCaptcha loading (only when needed)
- Event listeners for component lifecycle management
- Console command for ReCaptcha testing: `php artisan jscfoc4fix:test-recaptcha`
- Comprehensive documentation and installation guides

### Fixed
- **Fatal Error**: "Target class [core.composer] does not exist" in Container.php line 1163
- **Fatal Error**: "Class ReCaptcha\ReCaptcha not found" when submitting contact form
- Composer autoloader conflicts between plugin dependencies and October CMS 4
- Performance issues from excessive ReCaptcha loading

### Changed
- Original `vendor/` folder is safely backed up to `vendor.disabled/`
- Minimal `vendor/` structure created with only necessary ReCaptcha library
- Optimized autoloading to prevent unnecessary library loading in admin panel

### Technical Details
- Mock services registered to prevent DI container resolution errors
- PSR-4 compliant autoloader for ReCaptcha namespace
- Fallback loading mechanisms for different deployment scenarios
- Smart detection of form submission contexts

### Security
- No original plugin files are modified (preserves integrity)
- All changes are reversible through migration rollback
- Safe autoloader prevents arbitrary code execution

### Files Created/Modified
- `plugins/janvince/smallcontactform/vendor/autoload.php` (new safe autoloader)
- `plugins/janvince/smallcontactform/vendor.disabled/` (backup of original vendor)
- `plugins/avrio/jscfoc4fix/` (entire fix plugin structure)

### Migration Info
- Run `php artisan october:migrate` to apply fixes
- Run `php artisan migrate:rollback` to revert (will break October CMS 4 again)
- Manual installation instructions provided for edge cases

### Known Issues
- None currently identified

### Compatibility
- ✅ October CMS 4.x
- ✅ PHP 8.0+
- ✅ janvince/smallcontactform (all versions with vendor dependencies)
- ❌ October CMS 3.x (not needed, original plugin works fine)

### Performance Impact
- Minimal: ReCaptcha loads only when contact form is used
- Reduced: Eliminated redundant autoloader registrations
- Improved: No more autoloader conflicts slowing down requests

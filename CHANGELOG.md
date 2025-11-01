# Changelog

All notable changes to Securetor will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.0.1] - 2025-11-01

### Changed
- **Minimum WordPress version** bumped to 6.6 (from 5.0)
- **Minimum PHP version** bumped to 8.2 (from 7.4)
- Updated all documentation to reflect new requirements

### Technical
- Ensures compatibility with modern WordPress and PHP features
- Leverages PHP 8.2 improvements for better performance and security
- Aligns with WordPress 6.6+ features and APIs

---

## [2.0.0] - 2025-11-01

### 🎉 Major Release - Complete Transformation

Securetor v2.0.0 represents a complete transformation from Login/Admin Page Protector into a comprehensive, modular WordPress security suite.

### Added

#### Core Architecture
- **Modular plugin architecture** with independent security modules
- **Namespaced code** (`Securetor\`) following PSR-4 autoloading
- **Comprehensive admin interface** with dedicated pages for each module
- **Dashboard** with at-a-glance security status and statistics
- **Proper version checking** (WordPress 6.6+, PHP 8.2+)

#### Access Control Module
*Evolved from Login/Admin Page Protector v1.2.1*
- Multi-country support (previously Nigeria-only)
- **All features from v1.2.1 preserved and enhanced**

#### Anti-Spam Module ⭐ NEW
*Merged from Anti-spam v5.5, Reloaded v6.5, and Fortify v1.0*
- **Dual protection** - Year validation + honeypot trap
- **100% automated spam blocking**
- **Flexible JavaScript modes** (external or inline)
- **Email notifications** on spam detection
- **Detailed statistics** with breakdown by reason

### Changed
- **Plugin name**: Login/Admin Page Protector → Securetor
- **Architecture**: Monolithic → Modular
- **Standards**: WordPress Coding Standards throughout

### Credits
**Securetor v2.0+** developed by Krafty Sprouts Media, LLC

**Access Control** evolved from Login/Admin Page Protector

**Anti-Spam** merged from:
- Anti-spam v5.5 by webvitaly
- Anti-spam Reloaded v6.5 by kudlav
- Fortify v1.0 by webvitaly

---

## [1.2.1] - 2025-11-01 (Login/Admin Page Protector)

### Added
- Emergency bypass feature for locked-out administrators
- Bypass key generation with secure 30-minute session
- Bypass usage logging for security monitoring
- IP whitelist management system with AJAX support
- Support for CIDR range notation in whitelist
- Enhanced admin dashboard with security status overview
- Whitelist management interface with add/remove capabilities
- REST API authentication support for mobile apps
- Enhanced blocked attempts display with statistics
- One-click "Add to Whitelist" from blocked attempts log
- WP-CLI access support

### Changed
- Expanded Nigeria IP ranges with 2024 updates
- Improved IP detection with multiple fallback methods
- Enhanced geolocation caching with variable duration for unknown IPs
- Upgraded blocked access page with better UX and information
- Improved admin interface with security dashboard
- Enhanced logging with detailed server variables and referer information

### Fixed
- Mobile access issues for Nigerian users (added MTN range 102.90.0.0/16)
- Suspicious IP pattern detection
- CIDR range validation

### Security
- Added hash_equals() for constant-time bypass key comparison
- Improved IP validation and sanitization
- Enhanced suspicious IP pattern detection
- Session-based emergency bypass with time limits

## [1.2.0] - 2024

### Added
- Jetpack/WordPress.com IP whitelist support
- External API fallback for geolocation (optional)
- Caching system for IP geolocation data
- Comprehensive logging of blocked attempts
- Admin settings page
- Configurable cache duration
- Support for multiple external geolocation APIs

### Changed
- Improved performance with caching layer
- Enhanced Nigeria IP range detection

### Security
- Added validation for IP addresses
- Sanitized all user inputs
- Added nonce verification for admin actions

## [1.1.0] - 2024

### Added
- Country-based access control (Nigeria whitelist)
- Basic IP geolocation using local ranges
- Admin page blocking
- Login page blocking

### Changed
- Refactored code into class-based structure
- Improved code organization

## [1.0.0] - 2024

### Added
- Initial release
- Basic login page protection
- Simple IP blocking functionality

---

## Upgrade Notice

### 1.2.1
Important security update with emergency bypass feature. Recommended to generate an emergency bypass key immediately after updating to prevent lockouts.

### 1.2.0
Major update with improved performance through caching. Review settings after upgrade.

### 1.1.0
Added country-based access control. May affect existing users outside Nigeria.

---

## Developer Notes

### Breaking Changes
None in current versions. All updates maintain backward compatibility.

### Deprecated Features
None currently.

### Planned Features
- Multi-country whitelist support
- Automatic threat detection
- Integration with popular security plugins
- Custom blocked page templates
- Email notifications for administrators
- Rate limiting for repeated access attempts
- Two-factor authentication integration
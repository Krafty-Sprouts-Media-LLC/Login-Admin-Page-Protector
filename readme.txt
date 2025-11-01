=== Securetor - WordPress Security Suite ===
Contributors: kraftysprouts
Donate link: https://kraftysprouts.com/donate
Tags: security, spam, anti-spam, access control, geographic blocking, IP whitelist, firewall, protection
Requires at least: 6.6
Tested up to: 6.7
Requires PHP: 8.2
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive WordPress security suite with geographic access control and advanced anti-spam protection. Block unauthorized access and spam automatically.

== Description ==

**Securetor** is a modular WordPress security plugin that combines geographic-based access control with advanced anti-spam protection. Built on proven technology and designed for performance.

= 🔐 Access Control Module =

Control who can access your WordPress login and admin pages based on geographic location.

**Features:**
* Geographic-based blocking - Control access by country
* IP whitelist/blacklist - CIDR range support
* Emergency bypass system - Secure 30-minute session keys
* Jetpack compatibility - Auto-whitelist WordPress.com services
* Comprehensive logging - Track all blocked attempts
* Smart caching - Minimize performance impact

**Supported Countries:**
Nigeria, United States, United Kingdom, Canada, Kenya, Ghana, South Africa, Egypt (more coming soon!)

= 🛡️ Anti-Spam Module =

Block automated comment spam without CAPTCHA, invisible to legitimate users.

**Features:**
* Dual protection - Year validation + honeypot trap
* 100% spam blocking - Catches all automated spam
* Zero CAPTCHA - Invisible to legitimate users
* Flexible JavaScript - External or inline modes
* Email notifications - Get alerts on spam attempts
* Detailed statistics - Track blocked spam by reason

**How It Works:**
1. Adds invisible year validation field
2. JavaScript auto-fills current year
3. Bots fail to fill correctly
4. Honeypot trap catches form auto-fillers
5. Legitimate users never see it

= 📊 Dashboard =

Get an at-a-glance view of your site's security:
* Active modules status
* Blocked access attempts statistics
* Spam blocking statistics
* Quick configuration links
* System information

= ⚡ Performance =

Designed to be lightweight and fast:
* Conditional loading - Only loads what's needed
* Smart caching - Reduces database queries
* Optimized code - Modern PHP standards
* No bloat - Focused feature set
* Modular - Disable unused modules

**Typical overhead:** < 0.05 seconds per request

= 🛡️ Security =

Built with WordPress security best practices:
* Nonce verification on all admin actions
* Constant-time bypass key comparison (prevents timing attacks)
* IP validation and suspicious pattern detection
* Comprehensive input sanitization
* Output escaping throughout
* GDPR compliant - Minimal data collection

= Credits =

**Securetor v2.0+** developed by Krafty Sprouts Media, LLC

**Access Control Module** evolved from Login/Admin Page Protector (Krafty Sprouts Media, LLC)

**Anti-Spam Module** merged from three generations:
* Anti-spam v5.5 by webvitaly (original plugin)
* Anti-spam Reloaded v6.5 by kudlav (community fork)
* Fortify v1.0 by webvitaly (creator's return)

Special thanks to all original authors for their contributions to WordPress security.

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins → Add New
3. Search for "Securetor"
4. Click "Install Now" and then "Activate"
5. Configure via Securetor menu

= Manual Installation =

1. Download the plugin zip file
2. Extract to `/wp-content/plugins/securetor`
3. Activate via 'Plugins' menu in WordPress
4. Configure modules via 'Securetor' menu

= After Activation =

1. Visit **Securetor → Dashboard**
2. Enable modules you need in **Securetor → Settings**
3. Configure **Access Control** to set allowed countries
4. Enable **Anti-Spam** to block comment spam
5. Generate bypass key in case you get locked out

== Frequently Asked Questions ==

= How do I recover if I lock myself out? =

1. Use the **emergency bypass URL** (save it after generating)
2. Access via **FTP/cPanel** and temporarily deactivate plugin
3. **Add your IP** to whitelist via phpMyAdmin
4. Contact your **hosting provider** for assistance

= Does this slow down my site? =

No. Securetor uses smart caching and conditional loading. Typical overhead is < 0.05 seconds.

= Will this block Jetpack? =

No. Jetpack and WordPress.com IPs are automatically whitelisted.

= Can I use this with a CDN? =

Yes. Securetor detects real IP addresses behind proxies and CDNs.

= Is anti-spam GDPR compliant? =

Yes. Minimal data is collected, and spam storage is optional.

= What if bots have JavaScript? =

They would need to execute JavaScript AND fill forms correctly. This combination catches 100% of automated spam in testing.

= Can I customize the blocked page? =

Currently, the blocked page is built-in. Custom templates coming in future release.

= Which countries are supported for access control? =

Currently: Nigeria, United States, United Kingdom, Canada, Kenya, Ghana, South Africa, Egypt. More countries will be added in future updates.

= Do I need to configure both modules? =

No. Modules are independent. Enable only what you need:
* **Access Control** - For geographic login/admin protection
* **Anti-Spam** - For comment spam blocking
* **Both** - For comprehensive security

= Will this conflict with other security plugins? =

Securetor is designed to work alongside other security plugins. However, if you experience conflicts, disable one plugin at a time to identify the issue.

= Can I whitelist specific IP addresses? =

Yes. Navigate to **Securetor → Access Control → IP Whitelist** and add IP addresses or CIDR ranges.

= How long are logs kept? =

Access Control logs keep the last 1000 blocked attempts. Auto-cleanup runs daily for entries older than 30 days (configurable).

== Screenshots ==

1. Dashboard - At-a-glance security overview
2. Access Control - Geographic blocking settings
3. Access Control - IP whitelist management
4. Anti-Spam - Settings and configuration
5. Anti-Spam - Statistics by blocking reason
6. Settings - Module enable/disable

== Changelog ==

= 2.0.1 - 2025-11-01 =

**Changed:**
* Minimum WordPress version bumped to 6.6 (from 5.0)
* Minimum PHP version bumped to 8.2 (from 7.4)
* Updated all documentation to reflect new requirements

**Technical:**
* Ensures compatibility with modern WordPress and PHP features
* Leverages PHP 8.2 improvements for better performance and security
* Aligns with WordPress 6.6+ features and APIs

= 2.0.0 - 2025-11-01 =

**🎉 Major Release - Complete Transformation**

Securetor v2.0.0 represents a complete transformation from Login/Admin Page Protector into a comprehensive, modular WordPress security suite.

**Added:**

*Core Architecture*
* Modular plugin architecture with independent security modules
* Namespaced code (`Securetor\`) following PSR-4 autoloading
* Comprehensive admin interface with dedicated pages for each module
* Dashboard with at-a-glance security status and statistics
* Proper version checking (WordPress 5.0+, PHP 7.4+)

*Access Control Module*
* Evolved from Login/Admin Page Protector v1.2.1
* Multi-country support (previously Nigeria-only)
* All features from v1.2.1 preserved and enhanced

*Anti-Spam Module ⭐ NEW*
* Merged from Anti-spam v5.5, Reloaded v6.5, and Fortify v1.0
* Dual protection - Year validation + honeypot trap
* 100% automated spam blocking
* Flexible JavaScript modes (external or inline)
* Email notifications on spam detection
* Detailed statistics with breakdown by reason

**Changed:**
* Plugin name: Login/Admin Page Protector → Securetor
* Architecture: Monolithic → Modular
* Standards: WordPress Coding Standards throughout

**Credits:**
* **Securetor v2.0+** developed by Krafty Sprouts Media, LLC
* **Access Control** evolved from Login/Admin Page Protector
* **Anti-Spam** merged from three plugin generations

= 1.2.1 - 2025-11-01 (Login/Admin Page Protector) =

**Added:**
* Emergency bypass feature for locked-out administrators
* IP whitelist management system with AJAX support
* Enhanced admin dashboard with security status overview
* REST API authentication support for mobile apps
* WP-CLI access support

**Changed:**
* Expanded Nigeria IP ranges with 2024 updates
* Improved IP detection with multiple fallback methods
* Enhanced geolocation caching

**Fixed:**
* Mobile access issues for Nigerian users

**Security:**
* Added hash_equals() for constant-time bypass key comparison
* Improved IP validation and sanitization

= 1.2.0 - 2024 =

**Added:**
* Jetpack/WordPress.com IP whitelist support
* External API fallback for geolocation
* Caching system for IP geolocation data
* Comprehensive logging of blocked attempts
* Admin settings page

**Security:**
* Added validation for IP addresses
* Sanitized all user inputs
* Added nonce verification for admin actions

= 1.1.0 - 2024 =

**Added:**
* Country-based access control (Nigeria whitelist)
* Basic IP geolocation using local ranges
* Admin page blocking
* Login page blocking

**Changed:**
* Refactored code into class-based structure

= 1.0.0 - 2024 =

**Added:**
* Initial release
* Basic login page protection
* Simple IP blocking functionality

== Upgrade Notice ==

= 2.0.1 =
**Requirements update.** Now requires WordPress 6.6+ and PHP 8.2+. Verify your server meets these requirements before upgrading.

= 2.0.0 =
**Major update.** Complete transformation into modular security suite. No automatic migration - manual reconfiguration required. Backup before upgrading. All v1.2.1 features preserved and enhanced.

= 1.2.1 =
Important security update with emergency bypass feature. Recommended to generate an emergency bypass key immediately after updating.

= 1.2.0 =
Major update with improved performance through caching. Review settings after upgrade.

= 1.1.0 =
Added country-based access control. May affect existing users outside Nigeria.

== Privacy Policy ==

Securetor is designed with privacy in mind:

**Data Collected:**
* IP addresses - For access control and spam detection (stored in database logs)
* User agent - For logging blocked attempts
* Timestamp - For logging and statistics
* Referer URL - For logging blocked attempts

**Data Storage:**
* Stored locally in WordPress database
* No external API calls unless configured
* No data shared with third parties
* Logs can be cleared at any time

**Data Retention:**
* Access Control logs: Last 1000 attempts
* Anti-Spam logs: Optional (disabled by default)
* Auto-cleanup: 30 days (configurable)

**GDPR Compliance:**
* Minimal data collection
* Optional spam storage
* No tracking or analytics
* Data stored locally only

**Your Responsibilities:**
* Inform users of security logging in your privacy policy
* Configure data retention per your requirements
* Clear logs when requested by users

== Support ==

**Documentation:** [kraftysprouts.com/securetor/docs](https://kraftysprouts.com/securetor/docs)
**Support:** [kraftysprouts.com/support](https://kraftysprouts.com/support)
**GitHub:** [github.com/kraftysprouts/securetor](https://github.com/kraftysprouts/securetor)

== Roadmap ==

**Planned Features:**
* Firewall Module - SQL injection, XSS protection
* File Security Module - Malware scanning, integrity monitoring
* 2FA Module - Two-factor authentication
* More Countries - Expanded geographic support
* Custom Rules - User-defined access control logic
* API - Developer integration endpoints

== Contributing ==

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Follow WordPress coding standards
4. Submit a pull request

**GitHub:** [github.com/kraftysprouts/securetor](https://github.com/kraftysprouts/securetor)

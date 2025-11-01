# Securetor

**Comprehensive WordPress Security Suite**

Securetor is a modular WordPress security plugin combining geographic-based access control with advanced anti-spam protection. Built on proven technology and designed for performance.

[![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.6%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-purple.svg)](https://php.net/)

## Features

### 🔐 Access Control Module
Evolved from Login/Admin Page Protector by Krafty Sprouts Media, LLC

- **Geographic-based blocking** - Control access by country
- **IP whitelist/blacklist** - CIDR range support
- **Emergency bypass system** - Secure 30-minute session keys
- **Jetpack compatibility** - Auto-whitelist WordPress.com services
- **Comprehensive logging** - Track all blocked attempts
- **Smart caching** - Minimize performance impact

### 🛡️ Anti-Spam Module
Merged from three generations of proven anti-spam plugins:
- Anti-spam v5.5 by webvitaly (original)
- Anti-spam Reloaded v6.5 by kudlav (community fork)
- Fortify v1.0 by webvitaly (creator's return)

- **Dual protection** - Year validation + honeypot trap
- **100% spam blocking** - Catches all automated spam
- **Zero CAPTCHA** - Invisible to legitimate users
- **Flexible JavaScript** - External or inline modes
- **Email notifications** - Get alerts on spam attempts
- **Detailed statistics** - Track blocked spam by reason

## Installation

### From GitHub

1. Download the latest release
2. Upload to `/wp-content/plugins/securetor`
3. Activate via 'Plugins' menu in WordPress
4. Configure modules via 'Securetor' menu

### Requirements

- WordPress 6.6 or higher
- PHP 8.2 or higher
- Modern web browser for admin

## Quick Start

1. **After activation**, visit **Securetor → Dashboard**
2. **Enable modules** you need in **Securetor → Settings**
3. **Configure Access Control** to set allowed countries
4. **Enable Anti-Spam** to block comment spam
5. **Generate bypass key** in case you get locked out

## Modules

### Access Control

**Purpose:** Block unauthorized access to login and admin pages based on geographic location.

**Configuration:**
- Navigate to **Securetor → Access Control**
- Choose allowed countries (Nigeria by default)
- Add your IP to whitelist for safety
- Generate an emergency bypass key
- Configure cache duration (default: 1 hour)

**How it works:**
1. Detects user's IP address (proxy-aware)
2. Determines country via local IP ranges
3. Blocks if not in allowed countries
4. Exceptions: Whitelist, Jetpack, logged-in admins

**Supported Countries:**
- Nigeria (extensive ISP coverage: MTN, Airtel, Glo, 9mobile)
- United States
- United Kingdom
- Canada
- Kenya
- Ghana
- South Africa
- Egypt
- More coming soon!

### Anti-Spam

**Purpose:** Block automated comment spam without CAPTCHA.

**Configuration:**
- Navigate to **Securetor → Anti-Spam**
- Enable anti-spam protection
- Choose JavaScript mode (external or inline)
- Configure email notifications
- Optionally save spam for review

**How it works:**
1. Adds invisible year validation field
2. JavaScript auto-fills current year
3. Bots fail to fill correctly
4. Honeypot trap catches form auto-fillers
5. Legitimate users never see it

**Features:**
- Block trackbacks (optional)
- Save spam comments for review
- Email alerts on spam detection
- Custom error messages
- Detailed statistics by reason

## Dashboard

The **Securetor Dashboard** provides an at-a-glance view of:

- **Security status** - Active modules
- **Statistics** - Blocked access attempts and spam
- **Quick actions** - Links to configure each module
- **System information** - WordPress/PHP versions

## Settings

### General Settings

- **Enable/Disable Modules** - Turn modules on/off
- **System Information** - Version and compatibility info

### Access Control Settings

- **Allowed Countries** - Multi-select country list
- **Cache Duration** - How long to cache geolocation (300-86400 seconds)
- **External API** - Enable fallback geolocation API
- **Emergency Bypass** - Generate/revoke bypass keys
- **IP Whitelist** - Add trusted IPs/ranges

### Anti-Spam Settings

- **Spam Detection** - Enable/disable blocking
- **Block Trackbacks** - Block all trackback attempts
- **Save Spam** - Store blocked comments for review
- **JavaScript Mode** - External file or inline
- **Timeout Fallback** - Theme compatibility option
- **Email Notifications** - Alert on spam detection
- **Custom Error** - Personalize spam message

## Performance

Securetor is designed to be lightweight and fast:

- **Conditional loading** - Only loads what's needed
- **Smart caching** - Reduces database queries
- **Optimized code** - Modern PHP standards
- **No bloat** - Focused feature set
- **Modular** - Disable unused modules

**Typical overhead:** < 0.05 seconds per request

## Security

### Access Control Security

- Constant-time bypass key comparison (prevents timing attacks)
- IP validation and suspicious pattern detection
- Nonce verification on all admin actions
- Comprehensive input sanitization
- Output escaping throughout

### Anti-Spam Security

- Nonce verification on comment submission
- Server-side validation (JavaScript is helper only)
- No data storage unless configured
- GDPR compliant (minimal data collection)
- Respects user privacy

## Logging

### Access Control Logs

Logs include:
- IP address and country
- User agent and referer
- Request URI
- Timestamp
- Server variables (for debugging)

**Retention:** Last 1000 attempts
**Auto-cleanup:** Configurable (default: 30 days)

### Anti-Spam Logs

Statistics track:
- Total spam blocked
- Blocked by reason (year mismatch, honeypot, trackback, etc.)
- Last blocked timestamp

Optional spam storage for review.

## Credits

### Development

**Securetor v2.0+**
Developed by [Krafty Sprouts Media, LLC](https://kraftysprouts.com)

### Module Origins

**Access Control Module:**
Evolved from Login/Admin Page Protector
Original author: Krafty Sprouts Media, LLC

**Anti-Spam Module:**
Merged from three generations:
- **Anti-spam v5.5** by webvitaly (original plugin)
- **Anti-spam Reloaded v6.5** by kudlav (community fork)
- **Fortify v1.0** by webvitaly (creator's return)

Special thanks to all original authors for their contributions to WordPress security.

## Support

- **Documentation:** [kraftysprouts.com/securetor/docs](https://kraftysprouts.com/securetor/docs)
- **Support:** [kraftysprouts.com/support](https://kraftysprouts.com/support)
- **Issues:** [GitHub Issues](https://github.com/kraftysprouts/securetor/issues)

## License

Securetor is licensed under the **GNU General Public License v2.0 or later**.

See [LICENSE](LICENSE) or [gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## Roadmap

### Planned Features

- **Firewall Module** - SQL injection, XSS protection
- **File Security Module** - Malware scanning, integrity monitoring
- **2FA Module** - Two-factor authentication
- **More Countries** - Expanded geographic support
- **Custom Rules** - User-defined access control logic
- **API** - Developer integration endpoints

## FAQ

### How do I recover if I lock myself out?

1. Use the **emergency bypass URL** (save it after generating)
2. Access via **FTP/cPanel** and temporarily deactivate plugin
3. **Add your IP** to whitelist via phpMyAdmin
4. Contact your **hosting provider** for assistance

### Does this slow down my site?

No. Securetor uses smart caching and conditional loading. Typical overhead is < 0.05 seconds.

### Will this block Jetpack?

No. Jetpack and WordPress.com IPs are automatically whitelisted.

### Can I use this with a CDN?

Yes. Securetor detects real IP addresses behind proxies and CDNs.

### Is anti-spam GDPR compliant?

Yes. Minimal data is collected, and spam storage is optional.

### What if bots have JavaScript?

They would need to execute JavaScript AND fill forms correctly. This combination catches 100% of automated spam in testing.

### Can I customize the blocked page?

Currently, the blocked page is built-in. Custom templates coming in future release.

## Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Follow WordPress coding standards
4. Submit a pull request

## Authors

**Krafty Sprouts Media, LLC**
Website: [kraftysprouts.com](https://kraftysprouts.com)
Email: support@kraftysprouts.com

---

Made with ❤️ for the WordPress community

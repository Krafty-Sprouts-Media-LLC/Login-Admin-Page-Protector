# Login/Admin Page Protector

A WordPress security plugin that blocks access to WordPress login and admin pages with intelligent exceptions for Jetpack/WordPress.com services and Nigeria-based traffic. Includes comprehensive IP tracking, caching, and logging capabilities.

## Features

- **Geographic Access Control**: Automatically allows access from Nigeria while blocking other countries
- **Jetpack/WordPress.com Integration**: Whitelists official Jetpack and WordPress.com IP ranges
- **Intelligent IP Detection**: Comprehensive IP detection including proxy and CDN support
- **Performance Optimized**: Built-in caching system to minimize external API calls
- **Comprehensive Logging**: Tracks all blocked attempts with detailed information
- **Admin Dashboard**: Easy-to-use settings page with blocked attempts monitoring
- **Automated Cleanup**: Scheduled cleanup of old log entries
- **Privacy Focused**: Can operate without external APIs for enhanced privacy

## Installation

1. Download the plugin files
2. Upload the plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure settings under 'Settings' → 'Login Protector'

## Configuration

### Settings Page

Access the plugin settings via **Settings → Login Protector** in your WordPress admin.

#### Available Options:

- **Cache Duration**: How long to cache IP geolocation data (300-86400 seconds)
- **Log Cleanup**: Number of days to keep blocked attempt logs (1-365 days)
- **Use External API**: Enable/disable external IP geolocation API calls

### Recommended Settings

- **Cache Duration**: 3600 seconds (1 hour) - balances performance and accuracy
- **Log Cleanup**: 30 days - sufficient for monitoring without excessive storage
- **Use External API**: Disabled - for better privacy and performance

## How It Works

### Access Control Logic

The plugin follows this decision tree:

1. **Check if accessing login/admin pages** - If not, allow access
2. **Check if user is from Nigeria** - If yes, allow access
3. **Check if IP is from Jetpack/WordPress.com** - If yes, allow access
4. **Check if user is logged in with admin privileges** - If yes, allow access
5. **Otherwise** - Block access and log attempt

### IP Detection

The plugin uses a comprehensive IP detection system that checks for:
- `HTTP_CLIENT_IP`
- `HTTP_X_FORWARDED_FOR`
- `HTTP_X_FORWARDED`
- `HTTP_X_CLUSTER_CLIENT_IP`
- `HTTP_FORWARDED_FOR`
- `HTTP_FORWARDED`
- `REMOTE_ADDR`

### Geographic Detection

**Primary Method**: Local IP ranges for Nigeria (includes major ISPs like MTN, Airtel, and various IP blocks)

**Fallback Method**: External API (ipinfo.io) - only used when enabled in settings

### Jetpack/WordPress.com Integration

The plugin automatically whitelists official Jetpack and WordPress.com IP ranges:
- `192.0.64.0/18`
- `198.181.116.0/20`
- `66.155.8.0/21`
- `66.155.9.0/24`
- `66.155.11.0/24`
- `76.74.248.0/21`
- `76.74.254.0/24`
- `195.234.108.0/22`

## Performance Considerations

- **Caching**: IP geolocation results are cached to reduce processing time
- **Local Processing**: Primary geographic detection uses local IP ranges
- **Minimal External Calls**: External API usage is optional and limited
- **Efficient Storage**: Logs are automatically cleaned up to prevent database bloat

## Logging and Monitoring

### Blocked Attempts Log

The plugin logs all blocked attempts with:
- IP address
- Country code
- User agent
- Request URI
- Timestamp

### Log Management

- **Automatic Cleanup**: Old logs are automatically deleted based on settings
- **Size Limits**: Only keeps the last 500 entries to prevent excessive storage
- **Admin View**: Recent blocked attempts are displayed in the admin dashboard

## Security Features

- **403 Response**: Blocked users receive a proper HTTP 403 Forbidden response
- **No Information Leakage**: Blocked page provides minimal information
- **Cache Invalidation**: User cache is cleared on successful login
- **Secure Storage**: All data is stored using WordPress's secure options system

## Compatibility

- **WordPress**: 5.0 and higher
- **PHP**: 7.4 and higher
- **Jetpack**: Fully compatible with all Jetpack features
- **Caching Plugins**: Compatible with major caching plugins
- **CDN**: Works with CloudFlare, AWS CloudFront, and other CDNs

## Troubleshooting

### Common Issues

1. **Locked Out of Admin**
   - Access via FTP and temporarily deactivate the plugin
   - Or add your IP to the Nigeria IP ranges in the code

2. **Jetpack Not Working**
   - Verify Jetpack IP ranges are up to date
   - Check if external API is enabled for better detection

3. **False Positives**
   - Enable external API for more accurate geolocation
   - Check the blocked attempts log for patterns

4. **Performance Issues**
   - Increase cache duration
   - Disable external API if not needed

### Debug Information

Enable WordPress debug logging to see detailed information:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Technical Specifications

### Plugin Structure

```
ksm-lapp-login-admin-protector/
├── ksm-lapp-login-admin-protector.php (Main plugin file)
└── README.md (This file)
```

### Database Usage

- **Options Table**: Stores settings and blocked attempts log
- **Transients**: Caches IP geolocation data
- **No Custom Tables**: Uses WordPress's built-in storage systems

### Hooks and Filters

- `init` - Main plugin initialization
- `wp_login` - Clear user cache on login
- `admin_menu` - Add settings page
- `admin_init` - Initialize admin settings
- `ksm_lapp_cleanup_old_logs` - Scheduled cleanup

## Development

### Constants

- `KSM_LAPP_VERSION`: Plugin version
- `KSM_LAPP_PLUGIN_DIR`: Plugin directory path
- `KSM_LAPP_PLUGIN_URL`: Plugin URL

### Filters (for developers)

The plugin currently doesn't expose public filters, but can be extended by modifying the IP ranges or adding custom logic.

## Support

For support, feature requests, or bug reports:
- **Author**: Krafty Sprouts Media, LLC
- **Website**: http://kraftysporuts.com
- **Version**: 1.0.0
- **License**: GPL v2 or later

## Changelog

### Version 1.0.0
- Initial release
- Geographic access control for Nigeria
- Jetpack/WordPress.com integration
- Comprehensive logging system
- Performance optimized caching
- Admin dashboard with monitoring

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

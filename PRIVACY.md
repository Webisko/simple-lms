# Privacy & Data Retention - Simple LMS

## Overview

Simple LMS includes comprehensive privacy and data retention features to ensure GDPR compliance and give users control over their data.

---

## Features

### 1. **Data Retention Policy**

Located in: **Kursy → Settings → Privacy & Data Retention**

- **Analytics Data Retention**: Choose how long to keep analytics events
  - 90 days
  - 180 days
  - 1 year (365 days) - default
  - Unlimited (keep all data)

Older analytics events are automatically deleted daily via WordPress cron.

### 2. **Uninstall Data Preservation**

Located in: **Kursy → Settings → Privacy & Data Retention**

- **Keep Data on Uninstall**: Enable to preserve all plugin data when uninstalling
  - If enabled: Courses, lessons, user progress, and settings remain after plugin deletion
  - If disabled (default): All plugin data is removed during uninstall

This setting is checked by `uninstall.php` when the plugin is deleted.

### 3. **GDPR Privacy Tools**

Simple LMS integrates with WordPress Privacy Tools (**Settings → Privacy**):

#### Personal Data Export

Users can request their data via **Settings → Privacy → Export Personal Data**. Simple LMS exports:

- **Course Progress**: Courses, lessons, completion status, start/completion dates
- **Analytics Events**: Event types, timestamps, course/lesson associations

Exported data is included in the user's privacy export ZIP file.

#### Personal Data Erasure

Users can request data deletion via **Settings → Privacy → Erase Personal Data**. Simple LMS erases:

- **Course Progress**: All progress records for the user
- **Analytics Events**: All analytics events for the user
- **User Meta**: Course access metadata and expiration dates

Data is permanently deleted from the database.

---

## Technical Details

### Automated Cleanup (Cron)

**Cron Job**: `simple_lms_cleanup_old_analytics`  
**Schedule**: Daily  
**Action**: Deletes analytics events older than the retention period

The cron job is automatically:
- **Scheduled**: On plugin load (via `wp` action)
- **Unscheduled**: On plugin deactivation

### Database Tables Affected

1. **`wp_simple_lms_analytics`**: Analytics events (subject to retention)
2. **`wp_simple_lms_progress`**: User progress (deleted on GDPR erasure)

### Uninstall Process

When the plugin is uninstalled (deleted):

1. Check `simple_lms_keep_data_on_uninstall` option
2. If **enabled**: Exit without deleting data
3. If **disabled** (default): Delete:
   - All courses, modules, lessons (custom post types)
   - All plugin options (`simple_lms_*`)
   - All user meta (`simple_lms_*`)
   - All transients (`simple_lms_*`)
   - Custom tables (`wp_simple_lms_progress`, `wp_simple_lms_analytics`)
   - Clear object cache

---

## Compliance

### GDPR (General Data Protection Regulation)

- **Right to Access (Art. 15)**: ✅ Personal data export via WordPress Privacy Tools
- **Right to Erasure (Art. 17)**: ✅ Personal data deletion via WordPress Privacy Tools
- **Data Minimization (Art. 5.1.c)**: ✅ Automatic deletion of old analytics (retention policy)
- **Storage Limitation (Art. 5.1.e)**: ✅ Configurable retention periods

### Best Practices

1. **Set Retention Period**: Review analytics needs and set appropriate retention (default: 365 days)
2. **Honor Erasure Requests**: Use WordPress Privacy Tools to handle user requests
3. **Document Policies**: Include data retention info in your Privacy Policy
4. **Backup Before Uninstall**: If preserving data, export before plugin deletion
5. **Test Erasure**: Verify GDPR erasure tools work correctly in your environment

---

## Usage Examples

### Testing Retention (WP-CLI)

```bash
# Trigger manual cleanup (WP-CLI)
wp cron event run simple_lms_cleanup_old_analytics

# Check next scheduled cleanup
wp cron event list --fields=hook,next_run_relative | grep simple_lms
```

### Testing GDPR Export

1. Go to **Settings → Privacy → Export Personal Data**
2. Enter user email
3. Send Request
4. Download ZIP and verify `simple-lms-progress` and `simple-lms-analytics` groups

### Testing GDPR Erasure

1. Go to **Settings → Privacy → Erase Personal Data**
2. Enter user email
3. Send Request
4. Confirm erasure and verify database records deleted

### Checking Retention Status (PHP)

```php
use SimpleLMS\Analytics_Retention;

$status = Analytics_Retention::get_retention_status();
// Returns: enabled, retention_days, total_events, old_events, message
```

---

## Troubleshooting

### Cron Not Running

**Symptom**: Old analytics not being deleted

**Solutions**:
1. Check if WP Cron is disabled: `define('DISABLE_WP_CRON', true)` in `wp-config.php`
2. If disabled, set up system cron: `*/15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron`
3. Manually trigger: `wp cron event run simple_lms_cleanup_old_analytics`

### Uninstall Not Deleting Data

**Symptom**: Data remains after plugin deletion

**Cause**: `simple_lms_keep_data_on_uninstall` option is enabled

**Solution**:
1. Go to **Kursy → Settings → Privacy & Data Retention**
2. Uncheck **Keep Data on Uninstall**
3. Save Settings
4. Uninstall plugin

### GDPR Export Empty

**Symptom**: No Simple LMS data in export

**Causes**:
1. User has no progress or analytics events
2. Tables don't exist (fresh install)
3. User ID mismatch

**Check**:
```sql
-- Check progress table
SELECT COUNT(*) FROM wp_simple_lms_progress WHERE user_id = [USER_ID];

-- Check analytics table
SELECT COUNT(*) FROM wp_simple_lms_analytics WHERE user_id = [USER_ID];
```

---

## Developer Reference

### Filters

```php
// Modify retention period programmatically
add_filter('option_simple_lms_analytics_retention_days', function($value) {
    return 180; // Override to 180 days
});

// Prevent uninstall data deletion
add_filter('option_simple_lms_keep_data_on_uninstall', '__return_true');
```

### Actions

```php
// Hook before analytics cleanup
add_action('simple_lms_cleanup_old_analytics', 'my_pre_cleanup_callback', 5);

// Hook after uninstall
add_action('simple_lms_uninstalled', 'my_uninstall_callback');
```

### Classes

- **`Analytics_Retention`**: Handles cron and cleanup logic
- **`Privacy_Handlers`**: GDPR export/erasure callbacks

---

## Summary

Simple LMS provides enterprise-grade privacy and data retention features:

✅ Configurable analytics retention (90/180/365 days or unlimited)  
✅ Automated daily cleanup via WordPress cron  
✅ Optional data preservation on uninstall  
✅ Full GDPR compliance with WordPress Privacy Tools  
✅ Personal data export (progress + analytics)  
✅ Personal data erasure (all user records)  

For questions or issues, consult your WordPress admin or developer.

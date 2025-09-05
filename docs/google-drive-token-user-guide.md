# Google Drive Token Auto-Renewal System - User Guide

## Overview

This guide helps users understand the Google Drive connection status, token information, and notification system in the file upload platform.

## Understanding Connection Status

### Dashboard Status Indicators

The admin dashboard displays real-time Google Drive connection status with clear visual indicators:

#### 🟢 Connected (Healthy)
- **What it means**: Your Google Drive connection is working properly
- **What you see**: Green indicator with "Connected" status
- **File uploads**: Will work normally
- **Action needed**: None - system is operating correctly

#### 🟡 Connection Issues (Warning)
- **What it means**: Temporary connection problems detected
- **What you see**: Yellow indicator with "Connection Issues - Retrying" message
- **File uploads**: May experience delays but will retry automatically
- **Action needed**: Monitor status - usually resolves automatically

#### 🔴 Authentication Required (Critical)
- **What it means**: Your Google Drive connection needs to be renewed
- **What you see**: Red indicator with "Reconnect Required" message
- **File uploads**: Will fail until reconnected
- **Action needed**: Click "Reconnect to Google Drive" button immediately

#### ⚪ Not Connected
- **What it means**: Google Drive has not been set up yet
- **What you see**: Gray indicator with "Not Connected" status
- **File uploads**: Will fail
- **Action needed**: Set up Google Drive connection through admin settings

### Token Status Information

The enhanced dashboard shows detailed token information:

#### Token Lifecycle Display
- **Issued Date**: When the current token was created
  - Example: "Issued 3 days ago on March 15, 2025"
- **Expiration Date**: When the token will expire
  - Example: "Expires in 2 hours 15 minutes on March 18, 2025 at 3:30 PM"
- **Auto-Renewal Schedule**: When the system will automatically refresh the token
  - Example: "Auto-renewal scheduled for March 18, 2025 at 3:15 PM"

#### Token Health Indicators
- **Green**: Token is healthy and valid
- **Yellow**: Token is expiring soon but will be renewed automatically
- **Red**: Token has expired or failed to renew

#### Last Refresh Status
- **Success**: "Last refreshed successfully 2 hours ago"
- **Failure**: "Last refresh failed 30 minutes ago - retrying automatically"
- **Never**: "Token has not been refreshed yet"

## Automatic Token Renewal

### How It Works

The system automatically manages your Google Drive tokens without requiring manual intervention:

1. **Proactive Renewal**: Tokens are refreshed 15 minutes before they expire
2. **Automatic Retry**: If renewal fails, the system retries with increasing delays
3. **Background Processing**: All renewals happen in the background without interrupting uploads
4. **Failure Recovery**: If automatic renewal fails, you'll be notified to reconnect

### What You'll Experience

#### Normal Operation
- Tokens refresh automatically every ~1 hour
- No interruption to file uploads
- Dashboard shows "Connected" status consistently
- No action required from you

#### Temporary Issues
- Brief "Connection Issues" status during network problems
- Automatic retries resolve most issues within minutes
- File uploads may be delayed but will complete successfully
- System recovers automatically in most cases

#### Manual Intervention Required
- "Authentication Required" status appears
- Email notification sent immediately
- File uploads will fail until reconnection
- One-click reconnection available in dashboard

## Email Notifications

### Types of Notifications

#### 1. Token Expired Notification
**When sent**: When your Google Drive token expires and cannot be renewed automatically

**Subject**: "Google Drive Connection Expired - Action Required"

**What it contains**:
- Clear explanation that your Google Drive connection has expired
- Step-by-step instructions to reconnect
- Direct link to the reconnection page
- Timeline for when action is needed

**What to do**:
1. Click the "Reconnect to Google Drive" link in the email
2. Follow the Google authentication process
3. Verify connection is restored in the dashboard

#### 2. Refresh Failure Notification
**When sent**: When automatic token renewal fails multiple times

**Subject**: "Google Drive Connection Issue - Automatic Renewal Failed"

**What it contains**:
- Explanation of what went wrong
- Whether the system is still trying to fix the issue automatically
- When manual action might be required
- Expected timeline for resolution

**What to do**:
1. Check if the issue resolves automatically within the specified timeframe
2. If problem persists, use the reconnection link provided
3. Contact support if reconnection fails

#### 3. Connection Restored Notification
**When sent**: When connection issues are resolved and service is restored

**Subject**: "Google Drive Connection Restored"

**What it contains**:
- Confirmation that connection issues have been resolved
- Summary of any pending uploads that will now process
- Verification that normal service has resumed

**What to do**:
- No action required - this is a confirmation message
- Verify that file uploads are working normally
- Check dashboard shows "Connected" status

### Notification Frequency

#### Smart Throttling
- Maximum of 1 email per issue type per 24 hours
- Prevents spam while ensuring you're informed of critical issues
- Escalation to admin if employee notifications fail

#### Immediate vs. Delayed Notifications
- **Immediate**: Issues requiring your action (expired tokens, authentication failures)
- **Delayed**: Temporary issues that may resolve automatically (network problems, service outages)

## Troubleshooting Common Issues

### "I'm not receiving notifications"

**Possible causes**:
- Email address not configured correctly
- Notifications being filtered to spam folder
- Email delivery issues

**Solutions**:
1. Check your spam/junk folder
2. Add the system email address to your contacts
3. Verify your email address in user settings
4. Contact admin to check notification settings

### "Dashboard shows 'Connected' but uploads are failing"

**Possible causes**:
- Cached status information
- Temporary Google API issues
- Token refresh in progress

**Solutions**:
1. Click "Test Connection" button to force status refresh
2. Wait 2-3 minutes and try uploading again
3. Check if status changes to "Connection Issues"
4. If problem persists, try manual reconnection

### "I keep getting reconnection notifications"

**Possible causes**:
- Google account security settings blocking refresh tokens
- OAuth consent screen issues
- Repeated authentication failures

**Solutions**:
1. Check Google account security settings
2. Ensure you're using the correct Google account
3. Try disconnecting and reconnecting completely
4. Contact admin if problem continues

### "Uploads were working, now they're not"

**Possible causes**:
- Token expired during upload process
- Network connectivity issues
- Google Drive storage quota exceeded

**Solutions**:
1. Check dashboard connection status
2. Verify Google Drive has available storage space
3. Try reconnecting to Google Drive
4. Check if files appear in Google Drive after a few minutes

## Best Practices

### For Administrators

#### Regular Monitoring
- Check dashboard status daily
- Review token expiration dates weekly
- Monitor notification delivery
- Keep Google account credentials secure

#### Proactive Maintenance
- Respond to notifications promptly
- Test connection after any Google account changes
- Keep backup authentication methods available
- Document any recurring issues

#### Security Considerations
- Use strong passwords for Google account
- Enable two-factor authentication on Google account
- Regularly review Google account security settings
- Don't share authentication credentials

### For End Users (Clients)

#### Upload Best Practices
- Upload files during business hours when possible
- Check for confirmation emails after large uploads
- Keep local copies of important files until confirmed uploaded
- Report any upload failures immediately

#### Understanding Status
- Green status = uploads will work
- Yellow status = uploads may be delayed
- Red status = uploads will fail
- Contact admin if status is red for more than 30 minutes

## FAQ

### Q: How often do tokens need to be renewed?
**A**: Tokens are automatically renewed every hour. You don't need to do anything - the system handles this automatically.

### Q: What happens if I'm uploading files when a token expires?
**A**: The system will automatically refresh the token and retry the upload. You may see a brief delay, but uploads will complete successfully.

### Q: Can I manually refresh my token?
**A**: Yes, you can click the "Test Connection" button in the dashboard to force a connection test and token refresh if needed.

### Q: Why do I sometimes see "Connection Issues" status?
**A**: This usually indicates temporary network problems or Google API issues. The system automatically retries and usually resolves within a few minutes.

### Q: What should I do if I get multiple notification emails?
**A**: The system limits notifications to prevent spam, but if you receive multiple emails, it may indicate a persistent issue. Try reconnecting to Google Drive and contact support if the problem continues.

### Q: How do I know if my files uploaded successfully?
**A**: Check your Google Drive account directly, or look for confirmation emails if configured. The dashboard also shows recent upload activity.

### Q: What happens to pending uploads if connection is lost?
**A**: Pending uploads are automatically retried when connection is restored. You don't need to re-upload files - the system will complete them automatically.

### Q: Can I use multiple Google accounts?
**A**: Currently, the system supports one Google Drive connection per user account. Contact your administrator if you need to change which Google account is connected.

### Q: How secure is my Google Drive connection?
**A**: The system uses OAuth 2.0 security standards and never stores your Google password. Tokens are encrypted and automatically rotated for security.

### Q: What if Google Drive is down?
**A**: The system will detect Google service outages and automatically retry uploads when service is restored. You'll see "Connection Issues" status during outages.

## Getting Help

### When to Contact Support

Contact your administrator or support team if:
- Connection status shows "Authentication Required" for more than 1 hour
- You receive multiple failure notifications in a short period
- Uploads consistently fail despite "Connected" status
- You cannot complete the reconnection process
- You suspect security issues with your Google account

### Information to Provide

When contacting support, include:
- Current dashboard status (Connected, Connection Issues, etc.)
- Any error messages you've received
- Recent notification emails
- Approximate time when issues started
- Whether the problem affects all uploads or specific files

### Self-Service Options

Before contacting support, try:
1. Clicking "Test Connection" to refresh status
2. Waiting 5-10 minutes for automatic recovery
3. Trying the reconnection process
4. Checking Google Drive directly for uploaded files
5. Reviewing this user guide for similar issues

## System Maintenance

### Scheduled Maintenance

The system performs automatic maintenance:
- **Token health checks**: Every 15 minutes
- **Connection validation**: Every hour
- **Cleanup tasks**: Daily during off-peak hours

During maintenance:
- Brief connection status updates may occur
- No impact on file uploads
- Notifications may be delayed by a few minutes

### Planned Upgrades

You'll be notified in advance of:
- System upgrades that may affect Google Drive connectivity
- Changes to the authentication process
- New features or improvements

During upgrades:
- Temporary service interruptions may occur
- Reconnection may be required after major updates
- Enhanced features will be documented and explained

This user guide ensures you understand how to monitor your Google Drive connection, interpret status indicators, and respond appropriately to notifications from the token auto-renewal system.
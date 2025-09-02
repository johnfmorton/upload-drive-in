# Google Drive Connection Issues Troubleshooting Guide

## Quick Diagnosis

Use this flowchart to quickly identify and resolve Google Drive connection issues:

```
Connection Issue?
├── Status: "Authentication Required" → Go to Section A
├── Status: "Connection Issues" → Go to Section B  
├── Status: "Not Connected" → Go to Section C
└── Status shows "Healthy" but uploads fail → Go to Section D
```

## Section A: Authentication Required

### Symptoms
- Red status indicator in dashboard
- Message: "Please reconnect your account"
- "Test Connection" button fails with authentication error
- File uploads fail with OAuth errors

### Root Causes
1. **Refresh Token Expired**: Google refresh tokens can expire after 6 months of inactivity
2. **Token Revoked**: User manually revoked access in Google Account settings
3. **App Credentials Changed**: Google Cloud Console credentials were updated
4. **Scope Changes**: Required permissions were modified

### Resolution Steps

#### Step 1: Reconnect Account
1. Navigate to Admin Dashboard → Cloud Storage
2. Click the "Reconnect" button next to Google Drive
3. Complete the OAuth flow in the popup window
4. Ensure all requested permissions are granted
5. Verify status changes to "Healthy"

#### Step 2: Verify Permissions
If reconnection fails, check Google Account permissions:
1. Go to [Google Account Permissions](https://myaccount.google.com/permissions)
2. Look for your application in the list
3. If present, remove the app and try reconnecting
4. If not present, proceed with reconnection

#### Step 3: Check Google Cloud Console
If issues persist, verify API credentials:
1. Open [Google Cloud Console](https://console.cloud.google.com)
2. Navigate to APIs & Services → Credentials
3. Verify OAuth 2.0 Client ID is active
4. Check redirect URIs include your application URL
5. Ensure Google Drive API is enabled

### Prevention
- Monitor token refresh logs for early warning signs
- Set up alerts for authentication failures
- Document OAuth app settings for team reference

## Section B: Connection Issues

### Symptoms
- Yellow status indicator in dashboard
- Message: "Experiencing connectivity problems"
- "Test Connection" succeeds sometimes, fails other times
- File uploads are slow or intermittent

### Root Causes
1. **API Quota Exceeded**: Too many requests to Google Drive API
2. **Network Issues**: Connectivity problems between server and Google
3. **Google Service Outage**: Temporary issues with Google Drive service
4. **Rate Limiting**: Application is being throttled by Google

### Resolution Steps

#### Step 1: Check API Quota
1. Open [Google Cloud Console](https://console.cloud.google.com)
2. Navigate to APIs & Services → Google Drive API
3. Click on "Quotas" tab
4. Check current usage against limits
5. If near limits, wait for quota reset or request increase

#### Step 2: Verify Network Connectivity
```bash
# Test from server
ddev ssh
curl -I https://www.googleapis.com/drive/v3/about
ping googleapis.com
```

#### Step 3: Check Google Service Status
1. Visit [Google Workspace Status](https://www.google.com/appsstatus)
2. Look for Google Drive service issues
3. If outage is reported, wait for resolution

#### Step 4: Review Application Logs
```bash
# Check for specific error patterns
ddev artisan pail --filter="google-drive"

# Look for quota or rate limit errors
grep -i "quota\|rate limit" storage/logs/cloud-storage-*.log
```

### Temporary Workarounds
- Reduce upload frequency during quota issues
- Implement exponential backoff (already built-in)
- Use manual retry for failed uploads

## Section C: Not Connected

### Symptoms
- Gray status indicator in dashboard
- Message: "Account not connected"
- No "Test Connection" button available
- Upload attempts show setup required

### Root Causes
1. **Initial Setup Not Completed**: Google Drive integration never configured
2. **Database Issues**: Token records were deleted or corrupted
3. **Configuration Missing**: Environment variables not set

### Resolution Steps

#### Step 1: Complete Initial Setup
1. Navigate to Admin Dashboard → Cloud Storage
2. Click "Connect Google Drive" button
3. Follow the OAuth setup wizard
4. Grant all requested permissions
5. Verify connection is established

#### Step 2: Check Environment Configuration
```bash
# Verify required environment variables
ddev artisan tinker
>>> config('cloud-storage.providers.google-drive')
```

Required variables:
- `GOOGLE_DRIVE_CLIENT_ID`
- `GOOGLE_DRIVE_CLIENT_SECRET`

#### Step 3: Verify Database State
```bash
# Check for existing tokens
ddev artisan tinker
>>> App\Models\GoogleDriveToken::where('user_id', 1)->first()
```

If tokens exist but status shows "Not Connected", run:
```bash
ddev artisan cloud-storage:fix-status
```

## Section D: Status Healthy But Uploads Fail

### Symptoms
- Green status indicator shows "Healthy"
- "Test Connection" succeeds
- File uploads fail or get stuck in queue
- Queue jobs show errors

### Root Causes
1. **Folder Permissions**: Insufficient permissions on target folder
2. **File Size Limits**: Files exceed Google Drive limits
3. **Queue Issues**: Background job processing problems
4. **Storage Space**: Google Drive storage quota full

### Resolution Steps

#### Step 1: Check Queue Status
```bash
# View failed jobs
ddev artisan queue:failed

# Check queue worker status
ddev artisan queue:work --once --verbose
```

#### Step 2: Verify Folder Permissions
1. Check configured root folder in dashboard
2. Ensure folder exists and is accessible
3. Test with a different folder if needed
4. Verify folder sharing settings

#### Step 3: Check File Size and Storage
```bash
# Check file sizes in upload queue
ddev artisan tinker
>>> App\Models\FileUpload::where('cloud_storage_status', 'pending')->get(['original_filename', 'file_size'])
```

Google Drive limits:
- Individual file: 5TB
- Account storage: Varies by plan

#### Step 4: Test Manual Upload
```bash
# Test upload with diagnostic command
ddev artisan cloud-storage:diagnose --test-upload
```

## Advanced Troubleshooting

### Debug Mode
Enable detailed logging for complex issues:

```bash
# Enable debug logging
ddev artisan tinker
>>> config(['logging.channels.cloud-storage.level' => 'debug'])

# Monitor detailed logs
ddev artisan pail --filter="cloud-storage"
```

### Token Refresh Testing
Test token refresh mechanism manually:

```bash
# Force token refresh
ddev artisan cloud-storage:sync-tokens --force

# Check refresh results
ddev artisan cloud-storage:diagnose --verbose
```

### Cache Issues
Clear caches if status seems stuck:

```bash
# Clear all caches
ddev artisan optimize:clear

# Clear specific cloud storage cache
ddev artisan cloud-storage:cache --clear
```

## Common Error Messages

### "invalid_grant" Error
**Meaning**: Refresh token is invalid or expired
**Solution**: Reconnect account (Section A)

### "insufficient_permissions" Error
**Meaning**: App doesn't have required Google Drive permissions
**Solution**: Check OAuth scopes and reconnect

### "quotaExceeded" Error
**Meaning**: API quota limit reached
**Solution**: Wait for quota reset or increase limits

### "rateLimitExceeded" Error
**Meaning**: Too many requests in short time
**Solution**: Wait and retry (automatic backoff implemented)

### "storageQuotaExceeded" Error
**Meaning**: Google Drive storage is full
**Solution**: Free up space or upgrade Google storage plan

## Prevention and Monitoring

### Proactive Monitoring
1. Set up alerts for authentication failures
2. Monitor API quota usage regularly
3. Track upload success rates
4. Review error logs weekly

### Best Practices
1. Test connections regularly using dashboard
2. Keep Google Cloud Console credentials secure
3. Monitor Google service status during issues
4. Maintain backup upload methods if needed

### Automated Health Checks
The system includes automated health checks that run every 5 minutes:
- Token validity verification
- API connectivity testing
- Status cache updates
- Error rate monitoring

## Getting Additional Help

### Log Analysis
When reporting issues, include:
1. Current status from dashboard
2. Recent error logs from `storage/logs/cloud-storage-*.log`
3. Output from `ddev artisan cloud-storage:diagnose`
4. Timeline of when issues started

### Escalation Path
1. Check this troubleshooting guide
2. Review application logs
3. Run diagnostic commands
4. Contact development team with detailed information

### Useful Commands Summary
```bash
# Quick status check
ddev artisan cloud-storage:diagnose

# Clear caches
ddev artisan cloud-storage:cache --clear

# Fix status inconsistencies  
ddev artisan cloud-storage:fix-status

# Test token refresh
ddev artisan cloud-storage:sync-tokens

# Monitor real-time logs
ddev artisan pail --filter="cloud-storage"

# Check failed queue jobs
ddev artisan queue:failed
```
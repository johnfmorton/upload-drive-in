# Dashboard Token Status Widget - Visual Examples

## Overview

This document provides visual examples and mockups of the enhanced dashboard token status widget, showing how token information is displayed to users in different states.

## Widget Layout Examples

### Healthy Token Status

```
┌─────────────────────────────────────────────────────────────┐
│ 🟢 Google Drive Connection                                   │
│                                                             │
│ Status: Connected                                           │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Token Information                                       │ │
│ │                                                         │ │
│ │ • Issued: 3 days ago on March 15, 2025                │ │
│ │ • Expires: in 2 hours 15 minutes                      │ │
│ │   (March 18, 2025 at 3:30 PM)                        │ │
│ │ • Auto-renewal: Scheduled for March 18, 2025 at 3:15 PM│ │
│ │ • Last refresh: Successful 2 hours ago                 │ │
│ │                                                         │ │
│ │ [Test Connection] [Manual Refresh]                     │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Token Expiring Soon (Warning)

```
┌─────────────────────────────────────────────────────────────┐
│ 🟡 Google Drive Connection                                   │
│                                                             │
│ Status: Token Expiring Soon - Auto-renewal in progress     │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Token Information                                       │ │
│ │                                                         │ │
│ │ • Issued: 1 hour ago on March 18, 2025                │ │
│ │ • Expires: ⚠️ in 8 minutes                             │ │
│ │   (March 18, 2025 at 3:30 PM)                        │ │
│ │ • Auto-renewal: 🔄 In progress...                      │ │
│ │ • Last refresh: Attempting now (retry 2 of 5)         │ │
│ │                                                         │ │
│ │ ℹ️ System is automatically renewing your token.        │ │
│ │   No action required.                                   │ │
│ │                                                         │ │
│ │ [Test Connection] [View Logs]                          │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Authentication Required (Critical)

```
┌─────────────────────────────────────────────────────────────┐
│ 🔴 Google Drive Connection                                   │
│                                                             │
│ Status: Authentication Required                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Token Information                                       │ │
│ │                                                         │ │
│ │ • Issued: 2 days ago on March 16, 2025                │ │
│ │ • Expired: ❌ 30 minutes ago                           │ │
│ │   (March 18, 2025 at 3:30 PM)                        │ │
│ │ • Auto-renewal: ❌ Failed after 5 attempts            │ │
│ │ • Last refresh: Failed 25 minutes ago                 │ │
│ │   Error: Invalid refresh token                         │ │
│ │                                                         │ │
│ │ ⚠️ Your Google Drive connection has expired and        │ │
│ │   cannot be renewed automatically. Please reconnect.   │ │
│ │                                                         │ │
│ │ [🔗 Reconnect to Google Drive] [View Error Details]   │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Connection Issues (Temporary)

```
┌─────────────────────────────────────────────────────────────┐
│ 🟡 Google Drive Connection                                   │
│                                                             │
│ Status: Connection Issues - Retrying                        │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Token Information                                       │ │
│ │                                                         │ │
│ │ • Issued: 5 hours ago on March 18, 2025               │ │
│ │ • Expires: in 55 minutes                              │ │
│ │   (March 18, 2025 at 3:30 PM)                        │ │
│ │ • Auto-renewal: ⏳ Scheduled for 3:15 PM              │ │
│ │ • Last refresh: ⚠️ Network timeout (retry 3 of 5)     │ │
│ │                                                         │ │
│ │ ℹ️ Temporary network issues detected. System is        │ │
│ │   automatically retrying. Uploads may be delayed.      │ │
│ │                                                         │ │
│ │ [Test Connection] [Force Retry]                        │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Not Connected

```
┌─────────────────────────────────────────────────────────────┐
│ ⚪ Google Drive Connection                                   │
│                                                             │
│ Status: Not Connected                                       │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Token Information                                       │ │
│ │                                                         │ │
│ │ • No Google Drive connection configured                │ │
│ │                                                         │ │
│ │ ℹ️ Connect your Google Drive account to enable         │ │
│ │   automatic file uploads and storage.                   │ │
│ │                                                         │ │
│ │ Benefits of connecting:                                 │ │
│ │ • Automatic file backup                                │ │
│ │ • Seamless client file sharing                         │ │
│ │ • Organized folder structure                           │ │
│ │                                                         │ │
│ │ [🔗 Connect Google Drive] [Learn More]                │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Interactive Elements

### Real-time Countdown Timer

The widget includes a live countdown timer that updates every minute:

```javascript
// Example countdown display
"Expires in 2 hours 15 minutes"  // Updates to:
"Expires in 2 hours 14 minutes"  // Then:
"Expires in 2 hours 13 minutes"  // And so on...

// When approaching expiration:
"Expires in 15 minutes" // Yellow warning
"Expires in 5 minutes"  // Orange alert
"Expires in 1 minute"   // Red critical
"Expired 2 minutes ago" // Red expired
```

### Status Indicators with Icons

```
🟢 Healthy (Green)
   - Token valid and working
   - Auto-renewal scheduled
   - No issues detected

🟡 Warning (Yellow)
   - Token expiring soon
   - Temporary connection issues
   - Auto-renewal in progress

🔴 Critical (Red)
   - Token expired
   - Authentication failed
   - Manual intervention required

⚪ Not Connected (Gray)
   - No token configured
   - Initial setup needed
   - Service not activated
```

### Button States

#### Test Connection Button
```
[Test Connection]           // Normal state
[🔄 Testing...]            // Loading state
[✅ Connection OK]         // Success state (3 seconds)
[❌ Connection Failed]     // Error state (persistent)
```

#### Manual Refresh Button
```
[Manual Refresh]           // Normal state
[🔄 Refreshing...]        // Loading state
[✅ Refresh Complete]     // Success state (3 seconds)
[❌ Refresh Failed]       // Error state (persistent)
```

#### Reconnect Button
```
[🔗 Reconnect to Google Drive]     // Normal state
[🔄 Connecting...]                 // Loading state
[✅ Connected Successfully]        // Success state (3 seconds)
```

## Mobile Responsive Design

### Mobile Layout (< 768px)

```
┌─────────────────────────────┐
│ 🟢 Google Drive             │
│                             │
│ Status: Connected           │
│                             │
│ Token Info:                 │
│ • Issued: 3 days ago       │
│ • Expires: in 2h 15m       │
│ • Auto-renewal: 3:15 PM    │
│ • Last refresh: 2h ago ✅  │
│                             │
│ [Test Connection]           │
│ [Manual Refresh]            │
└─────────────────────────────┘
```

### Tablet Layout (768px - 1024px)

```
┌───────────────────────────────────────────┐
│ 🟢 Google Drive Connection                 │
│                                           │
│ Status: Connected                         │
│                                           │
│ Token Information:                        │
│ • Issued: 3 days ago on March 15, 2025  │
│ • Expires: in 2 hours 15 minutes        │
│ • Auto-renewal: Scheduled for 3:15 PM   │
│ • Last refresh: Successful 2 hours ago   │
│                                           │
│ [Test Connection] [Manual Refresh]       │
└───────────────────────────────────────────┘
```

## Color Scheme and Styling

### Status Colors
```css
/* Healthy Status */
.status-healthy {
    border-left: 4px solid #10b981; /* Green */
    background-color: #f0fdf4;
}

/* Warning Status */
.status-warning {
    border-left: 4px solid #f59e0b; /* Yellow */
    background-color: #fffbeb;
}

/* Critical Status */
.status-critical {
    border-left: 4px solid #ef4444; /* Red */
    background-color: #fef2f2;
}

/* Not Connected */
.status-not-connected {
    border-left: 4px solid #6b7280; /* Gray */
    background-color: #f9fafb;
}
```

### Typography
```css
.token-widget {
    font-family: 'Inter', sans-serif;
}

.status-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
}

.token-info {
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
}

.countdown-timer {
    font-weight: 500;
    color: #374151;
}

.error-message {
    color: #dc2626;
    font-weight: 500;
}
```

## Accessibility Features

### Screen Reader Support
```html
<!-- Status announcement -->
<div role="status" aria-live="polite" aria-label="Google Drive connection status">
    <span class="sr-only">Google Drive connection is healthy</span>
</div>

<!-- Token information -->
<dl aria-label="Token information">
    <dt>Token issued</dt>
    <dd>3 days ago on March 15, 2025</dd>
    
    <dt>Token expires</dt>
    <dd>in 2 hours 15 minutes on March 18, 2025 at 3:30 PM</dd>
</dl>

<!-- Action buttons -->
<button 
    type="button" 
    aria-describedby="test-connection-help"
    class="btn btn-primary">
    Test Connection
</button>
<div id="test-connection-help" class="sr-only">
    Performs a live test of your Google Drive connection
</div>
```

### Keyboard Navigation
- Tab order: Status → Token info → Action buttons
- Enter/Space activates buttons
- Escape closes any modal dialogs
- Arrow keys navigate between related elements

### High Contrast Mode
```css
@media (prefers-contrast: high) {
    .token-widget {
        border: 2px solid #000;
        background-color: #fff;
    }
    
    .status-healthy { border-left-color: #006400; }
    .status-warning { border-left-color: #ff8c00; }
    .status-critical { border-left-color: #dc143c; }
}
```

## Animation and Transitions

### Loading States
```css
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.loading-spinner {
    animation: spin 1s linear infinite;
}

.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
```

### Status Transitions
```css
.status-indicator {
    transition: all 0.3s ease-in-out;
}

.countdown-timer {
    transition: color 0.5s ease-in-out;
}

/* Pulse animation for critical status */
.status-critical .status-indicator {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
```

## Implementation Notes

### Real-time Updates
- Widget updates every 30 seconds via AJAX
- Countdown timer updates every minute via JavaScript
- Status changes trigger immediate updates
- WebSocket connection for real-time notifications (optional)

### Performance Considerations
- Lazy loading for non-critical information
- Debounced API calls for manual actions
- Cached status with appropriate TTL
- Minimal DOM manipulation for updates

### Error Handling
- Graceful degradation when API unavailable
- Fallback to cached status information
- Clear error messages for user actions
- Retry mechanisms for failed operations

This visual guide ensures consistent implementation of the enhanced dashboard token status widget across all user interfaces.
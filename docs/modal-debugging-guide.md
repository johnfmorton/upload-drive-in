# Modal Z-Index Debugging Guide

This guide explains how to use the modal debugging utilities to troubleshoot z-index layering issues.

## Enabling Debug Mode

There are several ways to enable modal debugging:

### 1. URL Parameter
Add `?modal-debug=true` to any URL:
```
https://upload-drive-in.ddev.site/client/file-upload?modal-debug=true
```

### 2. Browser Console
```javascript
localStorage.setItem('modal-debug', 'true');
// Refresh the page to activate debugging
```

### 3. Debug Control Panel
Once enabled, a debug control panel will appear in the top-left corner of the page.

## Debug Features

### Visual Indicators
When debugging is enabled, modal elements will have colored outlines:
- **Red outline**: Modal container (z-index: 9999)
- **Green outline**: Modal backdrop (z-index: 9998)  
- **Blue outline**: Modal content (z-index: 10000)

### Console Logging
The debugger automatically logs modal state changes:
```javascript
🔍 Modal Debug: Opening - upload-success
  Modal Name: upload-success
  Show State: true
  Container Z-Index: 9999
  Timestamp: 2025-01-27T10:30:45.123Z
```

### Debug Control Panel
The control panel provides these buttons:
- **Toggle Debug Mode**: Enable/disable debugging
- **Log Z-Index Hierarchy**: Print all z-index values to console
- **Highlight Modals**: Add visual highlights to modal elements
- **Clear Highlights**: Remove visual highlights

## Data Attributes

Modal elements include debugging data attributes:
```html
<div data-modal-name="upload-success" 
     data-z-index="9999" 
     data-modal-type="container">
```

Available attributes:
- `data-modal-name`: The modal's name identifier
- `data-z-index`: The element's z-index value
- `data-modal-type`: Element type (container, backdrop, content)

## CSS Debug Classes

The following CSS classes are available for manual debugging:

### Z-Index Visualization
- `.z-debug-low`: Red background/border for low z-index elements
- `.z-debug-medium`: Yellow background/border for medium z-index elements
- `.z-debug-high`: Green background/border for high z-index elements
- `.z-debug-highest`: Blue background/border for highest z-index elements

### Stacking Context
- `.stacking-context-debug`: Adds "Stacking Context" label to elements

## Console Commands

When debugging is enabled, these commands are available in the browser console:

```javascript
// Toggle debugging on/off
modalDebugger.toggleDebugging();

// Log z-index hierarchy
modalDebugger.logZIndexHierarchy();

// Highlight all modal elements
modalDebugger.highlightModals();

// Clear all highlights
modalDebugger.clearHighlights();
```

## Troubleshooting Common Issues

### Gray Overlay Appearing
1. Enable debugging: `?modal-debug=true`
2. Open the problematic modal
3. Check console logs for z-index conflicts
4. Use `modalDebugger.logZIndexHierarchy()` to see all z-index values
5. Look for elements with z-index values between 9998-10000

### Modal Not Visible
1. Check if modal container has `display: none`
2. Verify z-index values are applied correctly
3. Look for stacking context issues (elements with `position: relative/absolute/fixed`)

### Multiple Overlays
1. Check for duplicate modal instances
2. Verify Alpine.js state management
3. Look for timing conflicts in modal transitions

## Disabling Debug Mode

To disable debugging:

### Temporary (current session)
```javascript
modalDebugger.toggleDebugging();
```

### Permanent
```javascript
localStorage.removeItem('modal-debug');
// Refresh the page
```

## Development vs Production

- Debug utilities are automatically included in development mode
- In production, debugging must be explicitly enabled via URL parameter
- Debug CSS classes are always available but only activated when debugging is enabled

## Performance Impact

The debugging utilities have minimal performance impact:
- Console logging only occurs when debugging is enabled
- Visual indicators use CSS classes (no JavaScript animations)
- MutationObserver only runs when debugging is active
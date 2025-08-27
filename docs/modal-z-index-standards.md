# Modal Z-Index Standards

## Overview

This document establishes the z-index standards for modal components to prevent overlay issues and ensure consistent layering behavior across the application.

## Z-Index Hierarchy

### Standard Modal Z-Index Values

| Component | Z-Index | Purpose |
|-----------|---------|---------|
| Modal Container | `z-[9999]` | Highest level container, ensures modal appears above all page content |
| Modal Content | `z-[10000]` | Content layer, positioned above backdrop within modal container |
| Modal Backdrop | `z-[9998]` | Background overlay, positioned below content but above page content |

### Z-Index Range Allocation

- **0-999**: Page content, navigation, headers
- **1000-8999**: Tooltips, dropdowns, overlays
- **9000-9997**: Reserved for future use
- **9998**: Modal backdrop
- **9999**: Modal container
- **10000**: Modal content
- **10001+**: Reserved for critical system overlays

## Implementation Guidelines

### 1. Modal Component Structure

```blade
<!-- Modal Container: z-[9999] -->
<div class="fixed inset-0 z-[9999] modal-container">
    <!-- Modal Backdrop: z-[9998] -->
    <div class="fixed inset-0 bg-gray-500 opacity-75 z-[9998] modal-backdrop"></div>
    
    <!-- Modal Content: z-[10000] -->
    <div class="relative z-[10000] modal-content">
        <!-- Modal content here -->
    </div>
</div>
```

### 2. Stacking Context Rules

- Modal container creates a new stacking context
- All child elements inherit this context
- Backdrop and content compete within the same stacking context
- Content wins with higher z-index (10000 > 9998)

### 3. Development Best Practices

#### DO:
- Use explicit z-index values from the standard hierarchy
- Test modal layering with debug mode (`?modal-debug=true`)
- Maintain the container > content > backdrop hierarchy
- Add data attributes for testing (`data-z-index`, `data-modal-type`)
- Document any deviations from standard z-index values

#### DON'T:
- Use z-index values above 10000 for modal content
- Rely on relative positioning for modal layering
- Create new stacking contexts within modal content unnecessarily
- Use `z-50` or other low z-index values for modals
- Modify z-index values without updating documentation

## Debugging Modal Z-Index Issues

### Debug Mode

Enable modal debugging by adding `?modal-debug=true` to the URL or setting `localStorage.setItem('modal-debug', 'true')`.

Debug mode provides:
- Console logging of modal state changes
- Visual z-index indicators
- Stacking context visualization
- Data attributes for automated testing

### Common Issues and Solutions

#### Issue: Gray overlay appears on top of modal content
**Cause**: Backdrop z-index higher than or equal to content z-index
**Solution**: Ensure content z-index (10000) > backdrop z-index (9998)

#### Issue: Modal appears behind page content
**Cause**: Modal container z-index too low
**Solution**: Use `z-[9999]` for modal container

#### Issue: Multiple modals interfere with each other
**Cause**: Inconsistent z-index hierarchy
**Solution**: All modals should use the same z-index standards

### Testing Checklist

- [ ] Modal displays without gray overlay on content
- [ ] Modal appears above all page content
- [ ] Backdrop click-to-close works correctly
- [ ] Focus trap remains functional
- [ ] Multiple modals don't interfere with each other
- [ ] Debug mode provides useful information

## CSS Classes and Utilities

### Standard Classes

```css
.modal-container {
    z-index: 9999;
}

.modal-backdrop {
    z-index: 9998;
}

.modal-content {
    z-index: 10000;
}
```

### Debug Classes

```css
.z-debug-highest {
    outline: 3px solid red !important;
}

.z-debug-high {
    outline: 2px solid orange !important;
}

.stacking-context-debug {
    position: relative;
    background: rgba(255, 0, 0, 0.1);
}
```

## Migration Guide

### Updating Existing Modals

1. Replace `z-50` with `z-[9999]` for modal containers
2. Add `z-[10000]` to modal content elements
3. Set `z-[9998]` for modal backdrops
4. Add data attributes for testing
5. Test with debug mode enabled

### Example Migration

```blade
<!-- Before -->
<div class="fixed inset-0 z-50">
    <div class="fixed inset-0 bg-gray-500 opacity-75"></div>
    <div class="relative bg-white">Content</div>
</div>

<!-- After -->
<div class="fixed inset-0 z-[9999] modal-container" data-modal-type="container">
    <div class="fixed inset-0 bg-gray-500 opacity-75 z-[9998] modal-backdrop" data-modal-type="backdrop"></div>
    <div class="relative bg-white z-[10000] modal-content" data-modal-type="content">Content</div>
</div>
```

## Requirements Compliance

This documentation addresses the following requirements:

- **Requirement 3.1**: Z-index values are properly defined and documented
- **Requirement 3.2**: Developer notes about proper modal layering are provided
- **Requirement 3.3**: CSS debugging utilities are documented
- **Requirement 3.4**: Testing guidelines for modal z-index hierarchy are established

## Maintenance

This document should be updated when:
- New modal types are added to the application
- Z-index hierarchy changes are required
- New debugging tools are implemented
- Issues with modal layering are discovered and resolved

Last updated: August 27, 2025
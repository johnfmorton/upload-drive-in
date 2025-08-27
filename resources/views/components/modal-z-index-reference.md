# Modal Component Z-Index Reference

## Quick Reference

This file provides a quick reference for developers working with the modal component located at `resources/views/components/modal.blade.php`.

## Z-Index Values

| Element | Class | Z-Index | Purpose |
|---------|-------|---------|---------|
| Container | `.modal-container` | `z-[9999]` | Main modal container |
| Content | `.modal-content` | `z-[10000]` | Modal content area |
| Backdrop | `.modal-backdrop` | `z-[9998]` | Background overlay |

## Data Attributes

The modal component includes data attributes for testing and debugging:

- `data-modal-name`: The name of the modal instance
- `data-z-index`: The z-index value of the element
- `data-modal-type`: The type of modal element (container, content, backdrop)

## Debug Mode

Enable debug mode by adding `?modal-debug=true` to the URL or running:
```javascript
localStorage.setItem('modal-debug', 'true');
```

Debug mode provides:
- Console logging of modal state changes
- Visual outlines for z-index debugging
- Stacking context visualization

## Common Patterns

### Opening a Modal
```javascript
window.dispatchEvent(new CustomEvent("open-modal", { detail: "modal-name" }));
```

### Closing a Modal
```javascript
window.dispatchEvent(new CustomEvent("close-modal", { detail: "modal-name" }));
```

### Modal Usage in Blade
```blade
<x-modal name="example-modal" :show="$errors->any()">
    <div class="p-6">
        <h2 class="text-lg font-medium">Modal Title</h2>
        <p>Modal content goes here.</p>
    </div>
</x-modal>
```

## Troubleshooting

### Gray Overlay Issue
If you see a gray overlay on top of modal content:
1. Check that content z-index (10000) > backdrop z-index (9998)
2. Verify no other elements are creating stacking contexts
3. Enable debug mode to visualize z-index hierarchy

### Modal Behind Page Content
If modal appears behind page content:
1. Ensure modal container uses `z-[9999]`
2. Check for competing high z-index values on page elements
3. Verify modal container has `fixed` positioning

## Testing

The modal component includes automated tests:
- `tests/Feature/ModalZIndexVerificationTest.php`
- `tests/Feature/ModalZIndexConsistencyTest.php`
- `tests/js/modal-overlay-behavior.test.js`

Run tests with:
```bash
ddev artisan test --filter=Modal
ddev npm test -- modal
```

## Related Documentation

- [Modal Z-Index Standards](../../docs/modal-z-index-standards.md)
- [Modal Debugging Guide](../../docs/modal-debugging-guide.md)

Last updated: August 27, 2025
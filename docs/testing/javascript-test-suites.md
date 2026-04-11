# JavaScript Test Suites

This document describes the Jest test suites for Upload Drive-In's frontend JavaScript. All tests run in a jsdom environment.

```bash
npm test              # Run all suites
npm run test:watch    # Watch mode
```

---

## 1. Button State Integration

**File:** `tests/js/button-state-integration.test.js`
**Tests:** 21

Tests the coordination logic that prevents conflicting async operations from running simultaneously. Covers the "Refresh All" and "Test Queue Worker" buttons on the setup status page.

- **Debouncing** — repeated clicks within a short window are ignored
- **Mutual exclusion** — starting a refresh disables the queue test button and vice versa
- **Button enable/disable** — buttons reflect the current operation state
- **Timeout management** — pending timeouts are tracked and can be cleared
- **Concurrency** — rapid successive calls and state transitions are handled correctly

---

## 2. Progressive Queue Status

**File:** `tests/js/progressive-queue-status.test.js`
**Tests:** 4

Tests the helper functions that format queue worker status information for display.

- **Status constants** — predefined status labels exist (checking, success, error, timeout)
- **Processing time formatting** — durations are converted to human-readable strings
- **Elapsed time calculation** — time since a status check started is computed correctly
- **Status messages** — appropriate user-facing messages are generated for each status

---

## 3. Dual User Creation — Frontend (Admin)

**File:** `tests/js/dual-user-creation-frontend.test.js`
**Tests:** 36

Tests the admin-facing form for creating client users, which offers two actions: "Create User" and "Create & Invite."

- **Button clicks** — each button sets the correct action parameter before submission
- **Action parameters** — invalid, empty, and null action values are rejected
- **Validation** — email format and name length are checked before submission
- **Double-submit prevention** — buttons disable and show a loading spinner during submission
- **Tooltips and help text** — each button displays its purpose; error messages appear and clear correctly
- **Responsive design** — layout adapts across mobile (320px), tablet (768px), desktop (1024px), and large desktop (1920px)
- **Accessibility** — ARIA attributes, keyboard navigation, proper labels, and screen reader error announcements
- **Cross-browser** — form submission and email validation work across different browser APIs

---

## 4. Dual User Creation — Employee Frontend

**File:** `tests/js/dual-user-creation-employee-frontend.test.js`
**Tests:** 25

Tests the employee-facing variant of the user creation form. Verifies it behaves consistently with the admin version while respecting employee-specific routing and permissions.

- **Employee routing** — form submits to the correct employee-scoped route with the username in the URL
- **Interface consistency** — validation rules, action parameters, button styling, tooltips, and loading states match the admin interface
- **Client terminology** — labels and messages use "client" language appropriate to the employee context
- **Responsive design** — layout adapts across breakpoints, same as the admin form
- **Permissions** — form structure enforces employee-scoped access
- **Error handling** — validation errors display and clear consistently with the admin form
- **Accessibility** — ARIA attributes and keyboard navigation are maintained
- **Cross-interface compatibility** — same JavaScript patterns and button behaviors as the admin form

---

## 5. Dual User Creation — Responsive Design

**File:** `tests/js/dual-user-creation-responsive.test.js`
**Tests:** 23

Dedicated responsive design tests for the dual-action user creation form across all breakpoints.

- **Mobile (320px)** — buttons stack vertically, use full width, touch-friendly heights (44px+), appropriate font sizes
- **Tablet (768px)** — buttons display in a row with appropriate widths and standard heights
- **Desktop (1024px+)** — buttons in a row with optimal sizing; handles large screens
- **Breakpoint transitions** — smooth transitions between mobile and tablet layouts; edge cases at exact breakpoints
- **Form fields** — proper widths on mobile vs desktop
- **Touch targets** — adequate size (44px minimum) and spacing between interactive elements on mobile
- **Accessibility** — keyboard navigation and focus indicators work at all screen sizes
- **Performance** — no layout thrashing during resize; handles rapid size changes
- **CSS compatibility** — works with different CSS implementations and viewport scenarios

---

## 6. Modal Overlay Timing

**File:** `tests/js/modal-overlay-timing.test.js`
**Tests:** 19

Tests that file-preview modals display immediately and maintain correct z-index layering.

- **Immediate display** — modal appears without artificial delay when triggered
- **Visibility persistence** — modal remains visible after 1, 2, and 3 second delays (no disappearing bug)
- **Z-index hierarchy** — container has highest z-index, content is above backdrop, hierarchy is maintained during transitions and across multiple show/hide cycles
- **Multiple modals** — z-index hierarchy is correct when multiple modals exist in the DOM
- **DOM manipulation** — z-index values persist through DOM changes
- **Event handling** — `open-modal` custom event triggers immediate display
- **User interaction** — timing does not interfere with clicks; backdrop click dismissal works through timing cycles
- **Debug mode** — adds CSS classes, detects debug mode, and provides state information for troubleshooting

---

## 7. Google Drive Copy Feedback

**File:** `tests/js/google-drive-copy-feedback.test.js`
**Tests:** 31

Tests the "Copy Link" button on the Google Drive upload confirmation, built as an Alpine.js component.

- **Initial state** — component initializes with default values; button shows default label; screen reader area is configured
- **Successful copy** — state updates correctly; visual feedback shows immediately; multiple copies work independently
- **Auto-reset** — state reverts to default after 2 seconds; visual feedback clears; rapid clicking does not break the timeout
- **Error handling** — clipboard failures show an error message that clears after 3 seconds; button remains functional after errors; subsequent operations still work
- **Keyboard accessibility** — Enter and Space keys trigger copy; other keys are ignored; default behavior is prevented
- **ARIA attributes** — button and URL display have correct attributes; attributes update during state changes; spans use `aria-hidden`
- **Screen reader** — announcements fire on copy and error events
- **Integration** — works with different upload URLs; maintains state independence across multiple instances; handles missing refs; works in the dashboard context; styling matches the design system

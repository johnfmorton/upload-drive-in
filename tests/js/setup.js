/**
 * Jest setup file for JavaScript tests
 */

// Mock Shoelace components
global.customElements = {
    define: jest.fn()
};

// Mock CSS imports
jest.mock('@shoelace-style/shoelace/dist/components/alert/alert.js', () => ({}));
jest.mock('@shoelace-style/shoelace/dist/components/icon/icon.js', () => ({}));
jest.mock('@shoelace-style/shoelace/dist/components/button/button.js', () => ({}));

// Mock module exports check
global.module = { exports: {} };

// Mock DOMContentLoaded event
const mockAddEventListener = jest.fn();
global.document = {
    ...global.document,
    addEventListener: mockAddEventListener
};

global.window = {
    ...global.window,
    addEventListener: jest.fn()
};
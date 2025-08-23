/**
 * Jest setup file for JavaScript tests
 */

// Mock global objects that are available in browsers but not in Node.js
global.navigator = {
  clipboard: {
    writeText: jest.fn(() => Promise.resolve())
  }
};

global.AbortController = class AbortController {
  constructor() {
    this.signal = {
      aborted: false,
      addEventListener: jest.fn(),
      removeEventListener: jest.fn()
    };
  }
  
  abort() {
    this.signal.aborted = true;
  }
};

global.AbortSignal = class AbortSignal {
  constructor() {
    this.aborted = false;
  }
};

// Mock localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn()
};
global.localStorage = localStorageMock;

// Mock sessionStorage
const sessionStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn()
};
global.sessionStorage = sessionStorageMock;

// Suppress console warnings in tests unless explicitly testing them
const originalConsoleWarn = console.warn;
console.warn = (...args) => {
  if (args[0] && args[0].includes && args[0].includes('CSRF token not found')) {
    // Allow this specific warning for testing
    originalConsoleWarn(...args);
  }
  // Suppress other warnings
};

// Setup common test utilities
global.createMockElement = (tagName = 'div', properties = {}) => {
  const element = {
    tagName: tagName.toUpperCase(),
    classList: {
      add: jest.fn(),
      remove: jest.fn(),
      toggle: jest.fn(),
      contains: jest.fn()
    },
    style: {},
    innerHTML: '',
    textContent: '',
    setAttribute: jest.fn(),
    getAttribute: jest.fn(),
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    appendChild: jest.fn(),
    removeChild: jest.fn(),
    insertAdjacentElement: jest.fn(),
    querySelector: jest.fn(),
    querySelectorAll: jest.fn(() => []),
    remove: jest.fn(),
    ...properties
  };
  
  return element;
};

global.createMockResponse = (data, ok = true, status = 200) => {
  return {
    ok,
    status,
    statusText: ok ? 'OK' : 'Error',
    json: jest.fn(() => Promise.resolve(data)),
    text: jest.fn(() => Promise.resolve(JSON.stringify(data)))
  };
};
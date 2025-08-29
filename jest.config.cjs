module.exports = {
  testEnvironment: 'jsdom',
  roots: ['<rootDir>/tests/js'],
  testMatch: [
    '**/__tests__/**/*.js',
    '**/?(*.)+(spec|test).js'
  ],
  collectCoverageFrom: [
    'resources/js/**/*.js',
    '!resources/js/app.js', // Exclude main app file
    '!resources/js/bootstrap.js',
    '!**/node_modules/**',
    '!**/vendor/**'
  ],
  coverageDirectory: 'coverage',
  coverageReporters: [
    'text',
    'lcov',
    'html'
  ],
  setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js'],
  transform: {
    '^.+\\.js$': 'babel-jest'
  },
  moduleFileExtensions: ['js', 'json'],
  verbose: true,
  testTimeout: 10000
};
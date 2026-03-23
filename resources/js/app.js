// Import bootstrap first (this sets up Echo)
import './bootstrap';

// Now import the tracker functions
import { initializeMapTracker, cleanupTracker } from './map-tracker';

// Make functions available globally
window.initializeMapTracker = initializeMapTracker;
window.cleanupTracker = cleanupTracker;

console.log('App.js loaded - functions available:', {
    initializeMapTracker: !!window.initializeMapTracker,
    cleanupTracker: !!window.cleanupTracker
});
// Import bootstrap first (this sets up Echo)
import './bootstrap';
import MapLibreGlDirections from '@maplibre/maplibre-gl-directions';

// Now import the tracker functions
import { initializeMapTracker, cleanupTracker } from './map-tracker';

// Make functions available globally
window.initializeMapTracker = initializeMapTracker;
window.cleanupTracker = cleanupTracker;
window.MapLibreGlDirections = MapLibreGlDirections;

console.log('App.js loaded - functions available:', {
    initializeMapTracker: !!window.initializeMapTracker,
    cleanupTracker: !!window.cleanupTracker,
    MapLibreGlDirections: !!window.MapLibreGlDirections
});
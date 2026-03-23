// Import MapLibre
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

// Function to initialize the tracker
export function initializeMapTracker(mapContainerId, trackingId) {
    console.log('Initializing map tracker for:', trackingId);

    // Initialize the map
    const map = new maplibregl.Map({
        container: mapContainerId,
        style: 'https://demotiles.maplibre.org/style.json',
        center: [0, 0],
        zoom: 2
    });

    // Store position history
    const positions = [];

    map.on('load', () => {
        console.log('Map loaded');

        // Add source for current position
        map.addSource('current-position', {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: []
            }
        });

        map.addLayer({
            id: 'current-marker',
            type: 'circle',
            source: 'current-position',
            paint: {
                'circle-radius': 10,
                'circle-color': '#FF4444',
                'circle-stroke-width': 2,
                'circle-stroke-color': '#FFFFFF'
            }
        });

        map.addSource('path-history', {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: []
            }
        });

        map.addLayer({
            id: 'path-line',
            type: 'line',
            source: 'path-history',
            paint: {
                'line-color': '#007cbf',
                'line-width': 3,
                'line-opacity': 0.7
            }
        });
    });

    // Listen for real-time updates
    if (window.Echo) {
        window.Echo.channel(`tracking.${trackingId}`)
            .listen('.location.updated', (e) => {
                console.log('📍 Location update received:', e);

                const coordinates = e.coordinates;
                positions.push(coordinates);

                if (positions.length > 100) {
                    positions.shift();
                }

                const positionSource = map.getSource('current-position');
                if (positionSource) {
                    positionSource.setData({
                        type: 'FeatureCollection',
                        features: [{
                            type: 'Feature',
                            geometry: {
                                type: 'Point',
                                coordinates: coordinates
                            },
                            properties: {
                                timestamp: e.timestamp,
                                id: trackingId
                            }
                        }]
                    });
                }

                const pathSource = map.getSource('path-history');
                if (pathSource && positions.length > 1) {
                    pathSource.setData({
                        type: 'FeatureCollection',
                        features: [{
                            type: 'Feature',
                            geometry: {
                                type: 'LineString',
                                coordinates: positions
                            },
                            properties: {}
                        }]
                    });
                }

                map.flyTo({
                    center: coordinates,
                    zoom: 15,
                    duration: 2000
                });
            });
    } else {
        console.error('Echo not initialized!');
    }

    return map;
}

// Function to clean up Echo listeners
export function cleanupTracker(trackingId) {
    if (window.Echo) {
        window.Echo.leaveChannel(`tracking.${trackingId}`);
        console.log('Cleaned up channel:', trackingId);
    }
}
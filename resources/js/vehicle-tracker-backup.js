import maplibregl from 'maplibre-gl';

const activeTrackers = new Map();

export function startBrowserTracking(vehicleId) {
    if (!navigator.geolocation) {
        console.error('Geolocation not supported by this browser');
        alert('Geolocation is not supported by your browser');
        return null;
    }

    console.log(`Starting browser tracking for vehicle: ${vehicleId}`);

    updateTrackingStatus(vehicleId, 'Requesting location permission...', 'loading');

    const watchId = navigator.geolocation.watchPosition(
        async (position) => {
            const { latitude, longitude, speed, accuracy } = position.coords;

            console.log(`📍 New location for ${vehicleId}:`, { latitude, longitude, speed, accuracy });

            // Update UI with current location
            updateVehicleMarker(vehicleId, { latitude, longitude, speed, accuracy });

            // Update status
            updateTrackingStatus(vehicleId, `Tracking active - ${speed ? speed.toFixed(1) + ' km/h' : 'speed unknown'}`, 'active');


            const watchId = navigator.geolocation.watchPosition(
                async (position) => {
                    const { latitude, longitude, speed, accuracy } = position.coords;

                    console.log(`📍 New location for ${vehicleId}:`, { latitude, longitude, speed, accuracy });

                    // Update UI with current location
                    updateVehicleMarker(vehicleId, { latitude, longitude, speed, accuracy });

                    // Update status
                    updateTrackingStatus(vehicleId, `Tracking active - ${speed ? speed.toFixed(1) + ' km/h' : 'speed unknown'}`, 'active');

                    // In your geolocate.on handler, before the fetch
                    const requestData = {
                        vehicle_id: currentVehicleId,
                        latitude: latitude,
                        longitude: longitude,
                        speed: 0,
                        accuracy: accuracy || 0
                    };

                    console.log('Sending data:', requestData); // Debug log

                    fetch(`/track/${currentVehicleId}/update`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken.content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(requestData)
                    })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => {
                                    console.error('Validation errors:', err);
                                    throw new Error(JSON.stringify(err));
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Success:', data);
                        })
                        .catch(error => console.error('Error:', error));
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    let errorMessage = '';
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Permission denied. Please allow location access.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location request timed out.';
                            break;
                        default:
                            errorMessage = 'An unknown error occurred.';
                    }
                    updateTrackingStatus(vehicleId, errorMessage, 'error');
                },
                {
                    enableHighAccuracy: true,      // Use GPS if available
                    timeout: 10000,                // 10 second timeout
                    maximumAge: 0,                // Don't use cached positions
                    distanceFilter: 10            // Only update if moved > 10 meters (if supported)
                }
            );

            // Store the watchId for cleanup
            activeTrackers.set(vehicleId, watchId);

            // Return function to stop tracking
            return () => stopBrowserTracking(vehicleId);
        }
    )
}

export function stopBrowserTracking(vehicleId) {
    const watchId = activeTrackers.get(vehicleId);
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
        activeTrackers.delete(vehicleId);
        updateTrackingStatus(vehicleId, 'Tracking stopped', 'stopped');
        console.log(`Stopped tracking for vehicle: ${vehicleId}`);
    }
}

function updateVehicleMarker(vehicleId, location) {
    // This function should be implemented to update the map marker
    // You can either emit an event or update the marker directly
    const event = new CustomEvent('vehicleLocationUpdate', {
        detail: {
            vehicleId,
            location: {
                latitude: location.latitude,
                longitude: location.longitude,
                speed: location.speed,
                accuracy: location.accuracy,
                timestamp: new Date().toISOString()
            }
        }
    });
    window.dispatchEvent(event);

    // Also update any UI elements showing this vehicle's info
    const vehicleInfoElement = document.getElementById(`vehicle-${vehicleId}-info`);
    if (vehicleInfoElement) {
        vehicleInfoElement.innerHTML = `
            <strong>Vehicle: ${vehicleId}</strong><br>
            Location: ${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}<br>
            Speed: ${location.speed ? location.speed.toFixed(1) + ' km/h' : 'N/A'}<br>
            Accuracy: ${location.accuracy ? location.accuracy.toFixed(1) + 'm' : 'N/A'}<br>
            Last update: ${new Date().toLocaleTimeString()}
        `;
    }
}

function updateTrackingStatus(vehicleId, status, type) {
    const statusElement = document.getElementById(`tracking-status-${vehicleId}`);
    if (statusElement) {
        statusElement.textContent = status;
        statusElement.className = `tracking-status ${type}`;
    }

    // Also log to console
    console.log(`[${vehicleId}] ${status}`);
}

// Export for use in other files
export function isTracking(vehicleId) {
    return activeTrackers.has(vehicleId);
}

export function getAllActiveTrackers() {
    return Array.from(activeTrackers.keys());
}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <script src="{{ mix('js/app.js') }}" defer></script>
    <title>Real-Time Map Tracking</title>
    <style>
        #map {
            width: 100%;
            height: 600px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .tracking-info {
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
            margin-top: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            background: #4CAF50;
            color: white;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <h1>Real-Time Vehicle Tracking</h1>
        <div class="status-badge" id="status">🟢 Connected</div>

        <!-- Map Container -->
        <div id="map"></div>

        <!-- Tracking Info -->
        <div class="tracking-info">
            <h3>Tracking: Vehicle #{{ $trackingId }}</h3>
            <p>Waiting for location updates...</p>
            <div id="last-update">Last update: Never</div>
        </div>
    </div>

    <script>
        // Initialize the tracker when page loads
        document.addEventListener('DOMContentLoaded', function () {
            const trackingId = '{{ $trackingId }}'; // Pass from controller
            const map = initializeMapTracker('map', trackingId);

            // Update last update time display
            window.Echo.channel(`tracking.${trackingId}`)
                .listen('.location.updated', (e) => {
                    document.getElementById('last-update').innerHTML =
                        `Last update: ${new Date().toLocaleTimeString()}`;
                });
        });

        // Clean up when leaving page
        window.addEventListener('beforeunload', function () {
            cleanupTracker('{{ $trackingId }}');
        });
    </script>
</body>

</html>
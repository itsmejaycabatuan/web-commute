<!DOCTYPE html>

<head>
    <title>Pusher Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>

<body>
    <h1>Pusher Test</h1>
    <p>
        Try publishing an event to channel <code>my-channel</code>
        with event name <code>my-event</code>.
    </p>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Add button to trigger events manually
            const button = document.createElement('button');
            button.textContent = 'Send Test Event';
            button.style.margin = '20px';
            button.style.padding = '10px 20px';
            button.style.backgroundColor = '#4CAF50';
            button.style.color = 'white';
            button.style.border = 'none';
            button.style.borderRadius = '5px';
            button.style.cursor = 'pointer';

            button.onclick = function () {
                // Get CSRF token from meta tag
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                axios.post('/fire-event', {}, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => {
                        console.log('✅ Event triggered:', response.data);
                        alert('Event sent successfully!');
                    })
                    .catch(error => {
                        console.error('❌ Error:', error.response?.data || error.message);
                        alert('Error: ' + (error.response?.data?.message || error.message));
                    });
            };

            document.body.appendChild(button);
        });
    </script>
</body>
window._ = require('lodash');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

window.Pusher = require('pusher-js');
Pusher.logToConsole = true;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// var channel = window.Echo.channel('pusher-channel');
// channel.listen('.pusher-event', function (data) {
//     alert(JSON.stringify(data));
// });

// Add a counter to see how many events you're actually receiving
let eventCounter = 0;

// channel.listen('my-event', function (data) {
//     eventCounter++;
//     console.log(`9. 🎉 EVENT #${eventCounter} RECEIVED!`, data);
//     console.log('10. Message content:', data.message);
//     alert(`Event #${eventCounter}: ${JSON.stringify(data)}`);
// });

// document.addEventListener('DOMContentLoaded', function () {
//     console.log('1. DOM Content Loaded');
//     console.log('2. Window.Echo exists:', !!window.Echo);

//     if (window.Echo) {
//         console.log('3. Echo is available, attempting channel subscription');

//         // Log connection state
//         if (window.Echo.connector && window.Echo.connector.pusher) {
//             console.log('4. Pusher connection state:', window.Echo.connector.pusher.connection.state);

//             // Listen for connection events
//             window.Echo.connector.pusher.connection.bind('connected', function () {
//                 console.log('5. Pusher connected successfully!');
//             });

//             window.Echo.connector.pusher.connection.bind('error', function (err) {
//                 console.error('6. Pusher connection error:', err);
//             });
//         }

//         // Subscribe to channel
//         var channel = window.Echo.channel('my-channel');
//         console.log('7. Channel subscription attempted:', channel);

//         // Listen for subscription success
//         channel.subscription.bind('pusher:subscription_succeeded', function () {
//             console.log('8. Successfully subscribed to pusher-channel!');
//         });

//         // Listen for the event
//         channel.listen('my-event', function (data) {
//             console.log('9. 🎉 EVENT RECEIVED!', data);
//             console.log('10. Message content:', data.message);
//             alert(JSON.stringify(data));
//         });

//         console.log('11. Listener attached, waiting for events...');
//     } else {
//         console.error('Echo not found! Check if bootstrap.js loaded correctly');
//     }
// });


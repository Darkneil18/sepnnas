// OneSignal Service Worker v16
// This service worker is required for OneSignal to work properly
// It handles push notifications and background sync

// Import OneSignal SDK
importScripts("https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.sw.js");

// Handle service worker installation
self.addEventListener('install', function(event) {
    console.log('OneSignal Service Worker installing...');
    self.skipWaiting();
});

// Handle service worker activation
self.addEventListener('activate', function(event) {
    console.log('OneSignal Service Worker activating...');
    event.waitUntil(self.clients.claim());
});

// Handle push events
self.addEventListener('push', function(event) {
    console.log('Push event received:', event);
    // OneSignal SDK will handle the push event
});

// Handle notification clicks
self.addEventListener('notificationclick', function(event) {
    console.log('Notification clicked:', event);
    // OneSignal SDK will handle the notification click
});

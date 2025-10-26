// =============================================
// OneSignal Web Push Initialization (v16+ SDK) - Vercel Optimized
// =============================================

// ✅ OneSignal App ID from environment variables
window.ONESIGNAL_APP_ID = "bbdac752-319a-4245-9f1b-7ef78cf88bbb";

let oneSignalInitialized = false;
let oneSignalInitPromise = null;

// Prevent multiple initializations
if (window.oneSignalConfigLoaded) {
    console.log("⚠️ OneSignal config already loaded, skipping...");
} else {
    window.oneSignalConfigLoaded = true;
    
    // Start initialization on DOM ready
    document.addEventListener("DOMContentLoaded", function () {
        console.log("🚀 DOM loaded - starting OneSignal setup...");
        startOneSignalInitialization();
    });
}

// ===============================
// 🔹 Start SDK Initialization
// ===============================
function startOneSignalInitialization() {
    // Check if already initialized
    if (oneSignalInitialized) {
        console.log("✅ OneSignal already initialized");
        return;
    }

    if (typeof window.OneSignalDeferred !== "undefined") {
        initializeOneSignal();
    } else {
        console.log("⏳ Waiting for OneSignal SDK to load...");
        let attempts = 0;
        const maxAttempts = 30;

        const waitForSDK = setInterval(() => {
            attempts++;
            if (typeof window.OneSignalDeferred !== "undefined") {
                clearInterval(waitForSDK);
                initializeOneSignal();
            } else if (attempts >= maxAttempts) {
                clearInterval(waitForSDK);
                console.error("❌ OneSignal SDK failed to load.");
                showNotificationError("OneSignal SDK not loaded. Check script include.");
            }
        }, 100);
    }
}

// ===============================
// 🔧 Initialize OneSignal
// ===============================
function initializeOneSignal() {
    if (!window.ONESIGNAL_APP_ID) {
        console.error("❌ OneSignal App ID not set");
        showNotificationError("OneSignal App ID missing in configuration.");
        return;
    }

    if (oneSignalInitialized) {
        console.log("✅ OneSignal already initialized, skipping...");
        return;
    }

    // Prevent multiple initialization attempts
    if (oneSignalInitPromise) {
        return oneSignalInitPromise;
    }

    console.log("⚙️ Initializing OneSignal v16 with App ID:", window.ONESIGNAL_APP_ID);

    oneSignalInitPromise = new Promise((resolve, reject) => {
        window.OneSignalDeferred.push(async function (OneSignal) {
            try {
                // Check if already initialized
                if (oneSignalInitialized) {
                    resolve();
                    return;
                }

                await OneSignal.init({
                    appId: window.ONESIGNAL_APP_ID,
                    allowLocalhostAsSecureOrigin: true,
                    autoRegister: false,
                    serviceWorkerParam: {
                        scope: '/'
                    },
                    serviceWorkerPath: '/sw.js',
                    promptOptions: {
                        slidedown: {
                            enabled: false
                        }
                    },
                    notifyButton: {
                        enable: false
                    },
                    welcomeNotification: {
                        disable: true
                    }
                });

                oneSignalInitialized = true;

                // Set up event listeners
                OneSignal.Notifications.addEventListener('permissionChange', function (e) {
                    console.log("🔔 permissionChange:", e);
                    checkSubscriptionStatus();
                    checkNotificationPermission();
                });

                OneSignal.User.PushSubscription.addEventListener('change', function (event) {
                    console.log("🔁 PushSubscription change:", event);
                    updateNotificationStatus(!!(event.current && event.current.optedIn));
                    
                    // Save subscription to Supabase
                    saveSubscriptionToSupabase(event.current);
                });

                console.log("✅ OneSignal initialized successfully");
                checkSubscriptionStatus();
                checkNotificationPermission();
                resolve();
            } catch (error) {
                console.error("❌ OneSignal initialization failed:", error);
                
                // Try fallback initialization without service worker
                if (error.message.includes('ServiceWorker') || error.message.includes('redirect')) {
                    console.log("🔄 Trying fallback initialization without service worker...");
                    try {
                        await OneSignal.init({
                            appId: window.ONESIGNAL_APP_ID,
                            allowLocalhostAsSecureOrigin: true,
                            autoRegister: false,
                            serviceWorkerParam: null,
                            serviceWorkerPath: null,
                            promptOptions: {
                                slidedown: {
                                    enabled: false
                                }
                            },
                            notifyButton: {
                                enable: false
                            },
                            welcomeNotification: {
                                disable: true
                            }
                        });
                        
                        oneSignalInitialized = true;
                        console.log("✅ OneSignal initialized successfully (fallback mode)");
                        
                        // Set up event listeners
                        OneSignal.Notifications.addEventListener('permissionChange', function (e) {
                            console.log("🔔 permissionChange:", e);
                            checkSubscriptionStatus();
                            checkNotificationPermission();
                        });

                        OneSignal.User.PushSubscription.addEventListener('change', function (event) {
                            console.log("🔁 PushSubscription change:", event);
                            updateNotificationStatus(!!(event.current && event.current.optedIn));
                            
                            // Save subscription to Supabase
                            saveSubscriptionToSupabase(event.current);
                        });

                        checkSubscriptionStatus();
                        checkNotificationPermission();
                        resolve();
                        return;
                        
                    } catch (fallbackError) {
                        console.error("❌ Fallback initialization also failed:", fallbackError);
                        showNotificationError("OneSignal initialization failed: " + fallbackError.message);
                        reject(fallbackError);
                        return;
                    }
                }
                
                showNotificationError("OneSignal initialization failed: " + error.message);
                reject(error);
            }
        });
    });

    return oneSignalInitPromise;
}

// ===============================
// 🔍 Check subscription status
// ===============================
function checkSubscriptionStatus() {
    if (typeof window.OneSignalDeferred === "undefined") return;

    window.OneSignalDeferred.push(async function (OneSignal) {
        try {
            const userId = await OneSignal.User.PushSubscription.id;
            const optedIn = OneSignal.User.PushSubscription.optedIn;
            console.log("👤 OneSignal Push Subscription ID:", userId);
            console.log("👤 OneSignal Push Subscription Opted In:", optedIn);
            updateNotificationStatus(!!optedIn);
        } catch (error) {
            console.error("⚠️ Error reading subscription status:", error);
            updateNotificationStatus(false);
        }
    });
}

// ===============================
// 🔔 Check notification permission
// ===============================
function checkNotificationPermission() {
    if (typeof window.OneSignalDeferred === "undefined") return;

    window.OneSignalDeferred.push(async function (OneSignal) {
        try {
            const permission = await OneSignal.Notifications.permission;
            console.log("🔔 Notification permission:", permission);
        } catch (error) {
            console.error("⚠️ Error getting notification permission:", error);
        }
    });
}

// ===============================
// 🟢 Subscribe / Unsubscribe
// ===============================
function subscribeToNotifications() {
    if (typeof window.OneSignalDeferred === "undefined") {
        showNotificationError("OneSignal SDK not loaded.");
        return;
    }

    window.OneSignalDeferred.push(async function (OneSignal) {
        try {
            await OneSignal.Slidedown.promptPush();
            console.log("✅ Subscription prompt shown");
        } catch (e) {
            console.error("⚠️ Error showing subscription prompt:", e);
        }
    });
}

function unsubscribeFromNotifications() {
    if (typeof window.OneSignalDeferred === "undefined") {
        showNotificationError("OneSignal SDK not loaded.");
        return;
    }

    window.OneSignalDeferred.push(async function (OneSignal) {
        try {
            await OneSignal.User.PushSubscription.optOut();
            console.log("🔕 Unsubscribed from notifications");
        } catch (e) {
            console.error("⚠️ Error unsubscribing:", e);
        }
    });
}

// ===============================
// 💾 Save subscription to Supabase
// ===============================
async function saveSubscriptionToSupabase(subscription) {
    if (!subscription || !window.supabaseClient) return;
    
    try {
        const { data: { user } } = await window.supabaseClient.auth.getUser();
        if (!user) return;
        
        const subscriptionData = {
            user_id: user.id,
            onesignal_user_id: subscription.id,
            device_type: subscription.deviceType || 'web',
            is_active: subscription.optedIn
        };
        
        const { error } = await window.supabaseClient
            .from('onesignal_subscriptions')
            .upsert(subscriptionData, { 
                onConflict: 'user_id,onesignal_user_id',
                ignoreDuplicates: false 
            });
        
        if (error) {
            console.error("❌ Error saving subscription to Supabase:", error);
        } else {
            console.log("✅ Subscription saved to Supabase");
        }
    } catch (error) {
        console.error("❌ Error saving subscription:", error);
    }
}

// ===============================
// 🧩 UI Feedback Functions
// ===============================
function updateNotificationStatus(isSubscribed) {
    const el = document.getElementById("notification-status");
    if (!el) return;

    el.innerHTML = isSubscribed
        ? `<i class="fas fa-check-circle text-success me-1"></i> Notifications Enabled`
        : `<i class="fas fa-times-circle text-danger me-1"></i> Notifications Disabled`;

    el.className = isSubscribed ? "text-success" : "text-danger";
}

function showNotificationError(message) {
    const div = document.createElement("div");
    div.className = "alert alert-danger alert-dismissible fade show";
    div.innerHTML = `
        <i class="fas fa-exclamation-circle me-2"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.insertBefore(div, document.body.firstChild);
}

// ===============================
// 🌍 Expose Functions Globally
// ===============================
window.subscribeToNotifications = subscribeToNotifications;
window.unsubscribeFromNotifications = unsubscribeFromNotifications;
window.checkNotificationPermission = checkNotificationPermission;
window.checkSubscriptionStatus = checkSubscriptionStatus;
window.initializeOneSignal = initializeOneSignal;
window.updateNotificationStatus = updateNotificationStatus;

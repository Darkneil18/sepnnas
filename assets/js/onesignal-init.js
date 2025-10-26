// =========================================
// ✅ OneSignal Initialization v16+
// =========================================

// Replace these with your actual IDs
window.ONESIGNAL_APP_ID = "bbdac752-319a-4245-9f1b-7ef78cf88bbb";
window.ONESIGNAL_SAFARI_WEB_ID = ""; // optional for Safari push

let oneSignalInitialized = false;

// Start OneSignal initialization when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    console.log("🚀 DOM loaded - starting OneSignal initialization...");
    startOneSignalInitialization();
});

// ==========================
// 🔹 Initialize SDK
// ==========================
function startOneSignalInitialization() {
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

// ==========================
// 🔧 OneSignal Initialization
// ==========================
function initializeOneSignal() {
    if (!window.ONESIGNAL_APP_ID) {
        console.error("❌ OneSignal App ID not configured.");
        showNotificationError("OneSignal App ID missing.");
        return;
    }

    if (oneSignalInitialized) {
        return;
    }

    console.log("⚙️ Initializing OneSignal with App ID:", window.ONESIGNAL_APP_ID);

    window.OneSignalDeferred.push(async function (OneSignal) {
        try {
            await OneSignal.init({
                appId: window.ONESIGNAL_APP_ID,
                allowLocalhostAsSecureOrigin: true,
            });

            oneSignalInitialized = true;

            OneSignal.Notifications.addEventListener('permissionChange', function (e) {
                console.log("🔔 permissionChange:", e);
                checkSubscriptionStatus();
                checkNotificationPermission();
            });

            OneSignal.User.PushSubscription.addEventListener('change', function (event) {
                console.log("🔁 PushSubscription change:", event);
                updateNotificationStatus(!!(event.current && event.current.optedIn));
            });

            console.log("✅ OneSignal initialized successfully");
            checkSubscriptionStatus();
            checkNotificationPermission();
        } catch (error) {
            console.error("❌ OneSignal initialization failed:", error);
            showNotificationError("OneSignal initialization failed: " + error.message);
        }
    });
}

// ==========================
// 🔍 Subscription Status
// ==========================
function checkSubscriptionStatus() {
    if (typeof window.OneSignalDeferred === "undefined") return;

    window.OneSignalDeferred.push(function (OneSignal) {
        try {
            const subId = OneSignal.User?.PushSubscription?.id || null;
            const optedIn = OneSignal.User?.PushSubscription?.optedIn === true;
            console.log("👤 OneSignal Push Subscription ID:", subId);
            updateNotificationStatus(!!optedIn);
        } catch (error) {
            console.error("⚠️ Error reading subscription status:", error);
            updateNotificationStatus(false);
        }
    });
}

// ==========================
// 🔔 Notification Permission
// ==========================
function checkNotificationPermission() {
    if (typeof window.OneSignalDeferred === "undefined") return;

    window.OneSignalDeferred.push(function (OneSignal) {
        try {
            const permission = OneSignal.Notifications?.permission;
            console.log("🔔 Notification permission:", permission);
        } catch (error) {
            console.error("⚠️ Error checking notification permission:", error);
        }
    });
}

// ==========================
// 🔔 Manual Subscribe / Unsubscribe
// ==========================
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

    window.OneSignalDeferred.push(function (OneSignal) {
        try {
            OneSignal.User.PushSubscription.optOut();
            console.log("🔕 Unsubscribed from notifications");
        } catch (e) {
            console.error("⚠️ Error unsubscribing:", e);
        }
    });
}

// ==========================
// 🧩 UI Feedback Functions
// ==========================
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

// ==========================
// 🌍 Expose Functions Globally
// ==========================
window.subscribeToNotifications = subscribeToNotifications;
window.unsubscribeFromNotifications = unsubscribeFromNotifications;
window.checkNotificationPermission = checkNotificationPermission;
window.checkSubscriptionStatus = checkSubscriptionStatus;
window.initializeOneSignal = initializeOneSignal;
window.updateNotificationStatus = updateNotificationStatus;

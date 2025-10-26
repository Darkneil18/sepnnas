// =============================================
// Supabase Configuration
// =============================================

// Supabase configuration
const SUPABASE_URL = 'https://tkkekykihajshznppiqp.supabase.co'; // Replace with your Supabase URL
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRra2VreWtpaGFqc2h6bnBwaXFwIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjE0MjU3MTMsImV4cCI6MjA3NzAwMTcxM30.hnhki0isO-hVMirA9E2gBZ7sWyyV2Y2oJy6iGjuhfAo'; // Replace with your Supabase anon key

// Initialize Supabase client
const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

// =============================================
// Database Functions
// =============================================

// Get all events
async function getEvents() {
    try {
        const { data, error } = await supabase
            .from('events')
            .select('*')
            .order('event_date', { ascending: true });
        
        if (error) throw error;
        return data;
    } catch (error) {
        console.error('Error fetching events:', error);
        return [];
    }
}

// Get event by ID
async function getEventById(id) {
    try {
        const { data, error } = await supabase
            .from('events')
            .select('*')
            .eq('id', id)
            .single();
        
        if (error) throw error;
        return data;
    } catch (error) {
        console.error('Error fetching event:', error);
        return null;
    }
}

// Create new event
async function createEvent(eventData) {
    try {
        const { data, error } = await supabase
            .from('events')
            .insert([eventData])
            .select();
        
        if (error) throw error;
        return data[0];
    } catch (error) {
        console.error('Error creating event:', error);
        throw error;
    }
}

// Update event
async function updateEvent(id, eventData) {
    try {
        const { data, error } = await supabase
            .from('events')
            .update(eventData)
            .eq('id', id)
            .select();
        
        if (error) throw error;
        return data[0];
    } catch (error) {
        console.error('Error updating event:', error);
        throw error;
    }
}

// Delete event
async function deleteEvent(id) {
    try {
        const { error } = await supabase
            .from('events')
            .delete()
            .eq('id', id);
        
        if (error) throw error;
        return true;
    } catch (error) {
        console.error('Error deleting event:', error);
        throw error;
    }
}

// Get user notifications
async function getUserNotifications(userId) {
    try {
        const { data, error } = await supabase
            .from('notifications')
            .select('*')
            .eq('user_id', userId)
            .order('created_at', { ascending: false });
        
        if (error) throw error;
        return data;
    } catch (error) {
        console.error('Error fetching notifications:', error);
        return [];
    }
}

// Mark notification as read
async function markNotificationAsRead(notificationId) {
    try {
        const { error } = await supabase
            .from('notifications')
            .update({ is_read: true })
            .eq('id', notificationId);
        
        if (error) throw error;
        return true;
    } catch (error) {
        console.error('Error marking notification as read:', error);
        throw error;
    }
}

// =============================================
// Real-time Subscriptions
// =============================================

// Subscribe to events changes
function subscribeToEvents(callback) {
    return supabase
        .channel('events')
        .on('postgres_changes', 
            { event: '*', schema: 'public', table: 'events' }, 
            callback
        )
        .subscribe();
}

// Subscribe to notifications for a user
function subscribeToUserNotifications(userId, callback) {
    return supabase
        .channel('user_notifications')
        .on('postgres_changes', 
            { 
                event: 'INSERT', 
                schema: 'public', 
                table: 'notifications',
                filter: `user_id=eq.${userId}`
            }, 
            callback
        )
        .subscribe();
}

// =============================================
// Authentication Functions
// =============================================

// Sign up user
async function signUp(email, password, userData = {}) {
    try {
        const { data, error } = await supabase.auth.signUp({
            email,
            password,
            options: {
                data: userData
            }
        });
        
        if (error) throw error;
        return data;
    } catch (error) {
        console.error('Error signing up:', error);
        throw error;
    }
}

// Sign in user
async function signIn(email, password) {
    try {
        const { data, error } = await supabase.auth.signInWithPassword({
            email,
            password
        });
        
        if (error) throw error;
        return data;
    } catch (error) {
        console.error('Error signing in:', error);
        throw error;
    }
}

// Sign out user
async function signOut() {
    try {
        const { error } = await supabase.auth.signOut();
        if (error) throw error;
        return true;
    } catch (error) {
        console.error('Error signing out:', error);
        throw error;
    }
}

// Get current user
function getCurrentUser() {
    return supabase.auth.getUser();
}

// Listen to auth changes
function onAuthStateChange(callback) {
    return supabase.auth.onAuthStateChange(callback);
}

// =============================================
// Export functions for global use
// =============================================
window.supabaseClient = supabase;
window.getEvents = getEvents;
window.getEventById = getEventById;
window.createEvent = createEvent;
window.updateEvent = updateEvent;
window.deleteEvent = deleteEvent;
window.getUserNotifications = getUserNotifications;
window.markNotificationAsRead = markNotificationAsRead;
window.subscribeToEvents = subscribeToEvents;
window.subscribeToUserNotifications = subscribeToUserNotifications;
window.signUp = signUp;
window.signIn = signIn;
window.signOut = signOut;
window.getCurrentUser = getCurrentUser;
window.onAuthStateChange = onAuthStateChange;

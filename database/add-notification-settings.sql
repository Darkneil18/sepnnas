-- Add notification_settings column to users table
-- This column will store user notification preferences as JSON

ALTER TABLE users ADD COLUMN notification_settings TEXT NULL;

-- Add index for better performance
CREATE INDEX idx_users_notification_settings ON users(notification_settings(255));

-- Update existing users with default notification settings
UPDATE users SET notification_settings = '{
    "event_reminders": 1,
    "event_updates": 1,
    "event_cancellations": 1,
    "new_events": 1,
    "feedback_requests": 1,
    "system_announcements": 1,
    "email_notifications": 1,
    "push_notifications": 1,
    "reminder_timing": "24",
    "notification_frequency": "immediate"
}' WHERE notification_settings IS NULL;

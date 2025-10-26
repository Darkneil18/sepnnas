-- =============================================
-- SEPNAS Event Management System Database Schema
-- =============================================

-- Enable necessary extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =============================================
-- Users Table (extends Supabase auth.users)
-- =============================================
CREATE TABLE public.profiles (
    id UUID REFERENCES auth.users(id) ON DELETE CASCADE PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    full_name TEXT,
    role TEXT DEFAULT 'user' CHECK (role IN ('user', 'admin', 'organizer')),
    phone TEXT,
    department TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- =============================================
-- Event Categories
-- =============================================
CREATE TABLE public.event_categories (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    description TEXT,
    color TEXT DEFAULT '#007bff',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- =============================================
-- Venues
-- =============================================
CREATE TABLE public.venues (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    name TEXT NOT NULL,
    address TEXT,
    capacity INTEGER,
    facilities TEXT[],
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- =============================================
-- Events
-- =============================================
CREATE TABLE public.events (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT,
    event_date TIMESTAMP WITH TIME ZONE NOT NULL,
    end_date TIMESTAMP WITH TIME ZONE,
    venue_id UUID REFERENCES public.venues(id),
    category_id UUID REFERENCES public.event_categories(id),
    organizer_id UUID REFERENCES public.profiles(id),
    max_attendees INTEGER,
    registration_deadline TIMESTAMP WITH TIME ZONE,
    status TEXT DEFAULT 'active' CHECK (status IN ('draft', 'active', 'cancelled', 'completed')),
    is_public BOOLEAN DEFAULT true,
    registration_fee DECIMAL(10,2) DEFAULT 0,
    requirements TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- =============================================
-- Event Registrations
-- =============================================
CREATE TABLE public.event_registrations (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    event_id UUID REFERENCES public.events(id) ON DELETE CASCADE,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE,
    registration_date TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    status TEXT DEFAULT 'registered' CHECK (status IN ('registered', 'cancelled', 'attended', 'no_show')),
    payment_status TEXT DEFAULT 'pending' CHECK (payment_status IN ('pending', 'paid', 'refunded')),
    special_requirements TEXT,
    UNIQUE(event_id, user_id)
);

-- =============================================
-- Notifications
-- =============================================
CREATE TABLE public.notifications (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    type TEXT DEFAULT 'info' CHECK (type IN ('info', 'warning', 'success', 'error')),
    is_read BOOLEAN DEFAULT false,
    event_id UUID REFERENCES public.events(id) ON DELETE SET NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- =============================================
-- Feedback
-- =============================================
CREATE TABLE public.feedback (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    event_id UUID REFERENCES public.events(id) ON DELETE CASCADE,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE,
    rating INTEGER CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(event_id, user_id)
);

-- =============================================
-- OneSignal Subscriptions
-- =============================================
CREATE TABLE public.onesignal_subscriptions (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE,
    onesignal_user_id TEXT NOT NULL,
    device_type TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(user_id, onesignal_user_id)
);

-- =============================================
-- Indexes for better performance
-- =============================================
CREATE INDEX idx_events_date ON public.events(event_date);
CREATE INDEX idx_events_status ON public.events(status);
CREATE INDEX idx_events_organizer ON public.events(organizer_id);
CREATE INDEX idx_registrations_event ON public.event_registrations(event_id);
CREATE INDEX idx_registrations_user ON public.event_registrations(user_id);
CREATE INDEX idx_notifications_user ON public.notifications(user_id);
CREATE INDEX idx_notifications_read ON public.notifications(is_read);
CREATE INDEX idx_feedback_event ON public.feedback(event_id);
CREATE INDEX idx_onesignal_user ON public.onesignal_subscriptions(user_id);

-- =============================================
-- Row Level Security (RLS) Policies
-- =============================================

-- Enable RLS on all tables
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.event_registrations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.feedback ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.onesignal_subscriptions ENABLE ROW LEVEL SECURITY;

-- Profiles policies
CREATE POLICY "Users can view their own profile" ON public.profiles
    FOR SELECT USING (auth.uid() = id);

CREATE POLICY "Users can update their own profile" ON public.profiles
    FOR UPDATE USING (auth.uid() = id);

CREATE POLICY "Users can insert their own profile" ON public.profiles
    FOR INSERT WITH CHECK (auth.uid() = id);

-- Events policies
CREATE POLICY "Anyone can view public events" ON public.events
    FOR SELECT USING (is_public = true);

CREATE POLICY "Organizers can view their own events" ON public.events
    FOR SELECT USING (auth.uid() = organizer_id);

CREATE POLICY "Admins can view all events" ON public.events
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM public.profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

CREATE POLICY "Organizers can manage their events" ON public.events
    FOR ALL USING (auth.uid() = organizer_id);

CREATE POLICY "Admins can manage all events" ON public.events
    FOR ALL USING (
        EXISTS (
            SELECT 1 FROM public.profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

-- Event registrations policies
CREATE POLICY "Users can view their own registrations" ON public.event_registrations
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can register for events" ON public.event_registrations
    FOR INSERT WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update their own registrations" ON public.event_registrations
    FOR UPDATE USING (auth.uid() = user_id);

-- Notifications policies
CREATE POLICY "Users can view their own notifications" ON public.notifications
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can update their own notifications" ON public.notifications
    FOR UPDATE USING (auth.uid() = user_id);

-- Feedback policies
CREATE POLICY "Users can view feedback for events they attended" ON public.feedback
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM public.event_registrations 
            WHERE event_id = feedback.event_id 
            AND user_id = auth.uid() 
            AND status = 'attended'
        )
    );

CREATE POLICY "Users can create feedback for events they attended" ON public.feedback
    FOR INSERT WITH CHECK (
        auth.uid() = user_id AND
        EXISTS (
            SELECT 1 FROM public.event_registrations 
            WHERE event_id = feedback.event_id 
            AND user_id = auth.uid() 
            AND status = 'attended'
        )
    );

-- OneSignal subscriptions policies
CREATE POLICY "Users can manage their own OneSignal subscriptions" ON public.onesignal_subscriptions
    FOR ALL USING (auth.uid() = user_id);

-- =============================================
-- Functions and Triggers
-- =============================================

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers for updated_at
CREATE TRIGGER update_profiles_updated_at BEFORE UPDATE ON public.profiles
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_events_updated_at BEFORE UPDATE ON public.events
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_onesignal_subscriptions_updated_at BEFORE UPDATE ON public.onesignal_subscriptions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to create profile on user signup
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO public.profiles (id, email, full_name)
    VALUES (NEW.id, NEW.email, NEW.raw_user_meta_data->>'full_name');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Trigger to create profile on user signup
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();

-- =============================================
-- Sample Data
-- =============================================

-- Insert sample event categories
INSERT INTO public.event_categories (name, description, color) VALUES
('Workshop', 'Educational workshops and training sessions', '#007bff'),
('Conference', 'Professional conferences and seminars', '#28a745'),
('Social', 'Social events and networking', '#ffc107'),
('Sports', 'Sports and recreational activities', '#dc3545'),
('Cultural', 'Cultural and artistic events', '#6f42c1');

-- Insert sample venues
INSERT INTO public.venues (name, address, capacity, facilities) VALUES
('Main Auditorium', 'Building A, Ground Floor', 200, ARRAY['Projector', 'Sound System', 'Air Conditioning']),
('Conference Room 1', 'Building B, 2nd Floor', 50, ARRAY['Projector', 'Whiteboard', 'Air Conditioning']),
('Sports Complex', 'Building C, Ground Floor', 100, ARRAY['Sports Equipment', 'Changing Rooms']),
('Garden Area', 'Outdoor Space', 150, ARRAY['Tents', 'Sound System']);

-- =============================================
-- Views for easier querying
-- =============================================

-- View for events with related data
CREATE VIEW public.events_with_details AS
SELECT 
    e.*,
    v.name as venue_name,
    v.address as venue_address,
    ec.name as category_name,
    ec.color as category_color,
    p.full_name as organizer_name,
    COUNT(er.id) as registered_count
FROM public.events e
LEFT JOIN public.venues v ON e.venue_id = v.id
LEFT JOIN public.event_categories ec ON e.category_id = ec.id
LEFT JOIN public.profiles p ON e.organizer_id = p.id
LEFT JOIN public.event_registrations er ON e.id = er.event_id AND er.status = 'registered'
GROUP BY e.id, v.name, v.address, ec.name, ec.color, p.full_name;

-- View for user registrations with event details
CREATE VIEW public.user_registrations AS
SELECT 
    er.*,
    e.title as event_title,
    e.event_date,
    e.venue_id,
    v.name as venue_name,
    ec.name as category_name
FROM public.event_registrations er
JOIN public.events e ON er.event_id = e.id
LEFT JOIN public.venues v ON e.venue_id = v.id
LEFT JOIN public.event_categories ec ON e.category_id = ec.id;

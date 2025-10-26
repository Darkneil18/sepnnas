# SEPNAS Event Management System - Vercel + Supabase Deployment Guide

## 🚀 Architecture Overview

```
Browser / User
     ↓ HTTPS
Frontend on Vercel (HTML + JS + OneSignal)
     ↓ Supabase client SDK / REST API
Supabase (PostgreSQL + Auth + Realtime)
     ↑ returns data / real-time updates
Frontend → Browser
```

## 📋 Prerequisites

1. **Vercel Account** - [Sign up at vercel.com](https://vercel.com)
2. **Supabase Account** - [Sign up at supabase.com](https://supabase.com)
3. **OneSignal Account** - [Sign up at onesignal.com](https://onesignal.com)
4. **GitHub Account** - For version control and deployment

## 🗄️ Step 1: Set up Supabase

### 1.1 Create Supabase Project
1. Go to [supabase.com](https://supabase.com) and create a new project
2. Choose a region close to your users
3. Set a strong database password
4. Wait for the project to be created

### 1.2 Set up Database Schema
1. Go to the SQL Editor in your Supabase dashboard
2. Copy and paste the contents of `supabase/schema.sql`
3. Run the SQL to create all tables, policies, and functions

### 1.3 Configure Authentication
1. Go to Authentication > Settings in Supabase dashboard
2. Configure your site URL (you'll get this from Vercel)
3. Add your domain to allowed origins
4. Enable email authentication

### 1.4 Get Supabase Credentials
1. Go to Settings > API in Supabase dashboard
2. Copy your:
   - Project URL
   - Anon public key

## 🔔 Step 2: Set up OneSignal

### 2.1 Create OneSignal App
1. Go to [onesignal.com](https://onesignal.com) and create a new app
2. Choose "Web Push" as the platform
3. Enter your website URL (you'll get this from Vercel)
4. Configure your app settings

### 2.2 Get OneSignal Credentials
1. Go to Settings > Keys & IDs in OneSignal dashboard
2. Copy your:
   - App ID
   - REST API Key

## 🚀 Step 3: Deploy to Vercel

### 3.1 Prepare Your Code
1. Make sure all files are in the `public/` directory
2. Update the Supabase configuration in `public/assets/js/supabase-config.js`:
   ```javascript
   const SUPABASE_URL = 'YOUR_SUPABASE_URL';
   const SUPABASE_ANON_KEY = 'YOUR_SUPABASE_ANON_KEY';
   ```

### 3.2 Deploy to Vercel
1. Push your code to GitHub
2. Go to [vercel.com](https://vercel.com) and import your repository
3. Vercel will automatically detect it's a static site
4. Deploy!

### 3.3 Configure Environment Variables
In your Vercel dashboard, go to Settings > Environment Variables and add:

```
NEXT_PUBLIC_SUPABASE_URL=your_supabase_url
NEXT_PUBLIC_SUPABASE_ANON_KEY=your_supabase_anon_key
ONESIGNAL_APP_ID=your_onesignal_app_id
ONESIGNAL_REST_API_KEY=your_onesignal_rest_api_key
```

## 🔧 Step 4: Configure Domains

### 4.1 Update Supabase Settings
1. Go to Authentication > Settings in Supabase
2. Add your Vercel domain to:
   - Site URL
   - Additional Redirect URLs

### 4.2 Update OneSignal Settings
1. Go to Settings > Web Push in OneSignal
2. Update your website URL to your Vercel domain
3. Add your Vercel domain to allowed origins

## 🧪 Step 5: Test Your Deployment

### 5.1 Test OneSignal
1. Visit your Vercel URL
2. Open browser developer tools
3. Check console for OneSignal initialization messages
4. Try requesting notification permission

### 5.2 Test Supabase
1. Try creating an account
2. Check if data is being saved to Supabase
3. Test real-time features

## 📱 Step 6: Mobile Optimization

### 6.1 Add PWA Support (Optional)
1. Create a `manifest.json` file
2. Add meta tags for mobile optimization
3. Configure service worker for offline support

### 6.2 Test on Mobile
1. Test on different mobile devices
2. Ensure OneSignal works on mobile browsers
3. Test responsive design

## 🔒 Security Considerations

### 6.1 Row Level Security (RLS)
- All tables have RLS enabled
- Users can only access their own data
- Admins have elevated permissions

### 6.2 API Security
- Supabase handles authentication
- OneSignal uses secure HTTPS
- Vercel provides SSL certificates

## 🚨 Troubleshooting

### Common Issues:

1. **OneSignal not initializing**
   - Check if service worker is accessible
   - Verify App ID is correct
   - Check browser console for errors

2. **Supabase connection issues**
   - Verify URL and API key
   - Check RLS policies
   - Ensure CORS is configured

3. **Vercel deployment issues**
   - Check build logs
   - Verify file structure
   - Check environment variables

## 📊 Monitoring

### 6.1 Vercel Analytics
- Enable Vercel Analytics for performance monitoring
- Monitor page views and user behavior

### 6.2 Supabase Monitoring
- Use Supabase dashboard to monitor database performance
- Check authentication logs

### 6.3 OneSignal Analytics
- Monitor notification delivery rates
- Track user engagement

## 🔄 Updates and Maintenance

### 6.1 Regular Updates
- Keep dependencies updated
- Monitor security advisories
- Update OneSignal SDK when new versions are released

### 6.2 Backup Strategy
- Supabase provides automatic backups
- Export data regularly
- Keep code in version control

## 📞 Support

- **Vercel**: [vercel.com/docs](https://vercel.com/docs)
- **Supabase**: [supabase.com/docs](https://supabase.com/docs)
- **OneSignal**: [documentation.onesignal.com](https://documentation.onesignal.com)

## 🎉 You're Done!

Your SEPNAS Event Management System is now deployed on Vercel with Supabase backend and OneSignal notifications. The system should work without the redirect issues you experienced with free hosting providers.

### Next Steps:
1. Customize the UI to match your organization's branding
2. Add more features like event management dashboard
3. Set up automated notifications for events
4. Configure user roles and permissions
5. Add payment integration if needed

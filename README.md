# SEPNAS Event Management System

A comprehensive event and resource management system with automated notifications for SEPNAS High School.

## Features

### 🎯 Core Modules

1. **Event Management Module**
   - Create, edit, and delete events
   - Assign organizers and manage event details
   - Upload event information and manage categories
   - Venue management and scheduling

2. **User Management Module**
   - Role-based access control (Admin, Teacher, Student)
   - Personalized dashboards for each user type
   - Secure authentication and session management

3. **Automated Notification System**
   - Real-time notifications via OneSignal
   - Event reminders and updates
   - Custom notification targeting

4. **Event Calendar and Scheduling**
   - Interactive calendar view with FullCalendar.js
   - Event timeline tracking
   - Conflict detection and prevention

5. **Attendance and Participation Tracking**
   - Digital attendance recording
   - Participation rate monitoring
   - Attendance statistics and reports

6. **Feedback and Evaluation Forms**
   - Post-event feedback collection
   - Rating system (1-5 stars)
   - Suggestions and improvement tracking

7. **Reports and Analytics**
   - Comprehensive dashboard with statistics
   - Event performance metrics
   - User engagement analytics
   - Exportable reports

### 🚀 Technical Features

- **Responsive Design**: Mobile-first approach with Bootstrap 5
- **Real-time Updates**: AJAX-powered live data refresh
- **Modern UI**: Clean, intuitive interface with custom CSS
- **Database**: MySQL with optimized schema
- **Security**: Role-based access control and input validation
- **Notifications**: OneSignal integration for push notifications

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- OneSignal account for notifications

### Setup Instructions

1. **Download and Extract**
   ```bash
   # Extract the files to your web server directory
   # For InfinityFree: Upload to your public_html folder
   ```

2. **Database Configuration**
   - Create a MySQL database on InfinityFree
   - Import the schema from `database/schema.sql`
   - Update database credentials in `config/database.php`

3. **Configuration**
   - Update `config/config.php` with your settings:
     - Set your domain URL
     - Configure OneSignal App ID and REST API Key
     - Adjust timezone settings

4. **OneSignal Setup**
   - Create a OneSignal account
   - Create a new web app
   - Get your App ID and REST API Key
   - Update the configuration files

5. **File Permissions**
   ```bash
   chmod 755 -R .
   chmod 644 config/*.php
   ```

## Configuration

### Database Settings
Update `config/database.php` with your InfinityFree MySQL credentials:

```php
private $host = 'sql201.infinityfree.com';
private $db_name = 'your_database_name';
private $username = 'your_username';
private $password = 'your_password';
```

### OneSignal Configuration
Update `config/config.php`:

```php
define('ONESIGNAL_APP_ID', 'your-onesignal-app-id');
define('ONESIGNAL_REST_API_KEY', 'your-onesignal-rest-api-key');
```

### Application Settings
```php
define('BASE_URL', 'https://your-domain.infinityfreeapp.com/');
date_default_timezone_set('Asia/Manila');
```

## Usage

### Initial Setup

1. **Access the System**
   - Navigate to your domain
   - You'll be redirected to the login page

2. **Create Admin Account**
   - Manually insert an admin user in the database:
   ```sql
   INSERT INTO users (username, email, password, first_name, last_name, role, is_active)
   VALUES ('admin', 'admin@sepnas.edu', '$2y$10$hash', 'Admin', 'User', 'admin', 1);
   ```

3. **Login and Configure**
   - Login with admin credentials
   - Set up event categories and venues
   - Create user accounts for teachers and students

### User Roles

- **Admin**: Full system access, user management, all features
- **Teacher**: Event management, attendance tracking, reports
- **Student**: Event registration, feedback submission, personal dashboard

### Key Features Usage

#### Event Management
1. Navigate to "Manage Events"
2. Click "Create Event"
3. Fill in event details, select venue and category
4. Set registration deadline and participant limits
5. Publish the event

#### Attendance Tracking
1. Go to "Attendance" section
2. Select an event
3. Mark attendance for participants
4. Add notes if needed
5. View attendance statistics

#### Feedback Collection
1. After an event, participants can submit feedback
2. Access "Feedback" section to view responses
3. Analyze ratings and comments
4. Generate improvement suggestions

#### Notifications
1. System automatically sends event reminders
2. Manual notifications can be sent via "Notifications"
3. Target specific user groups or all users
4. Schedule notifications for future delivery

## File Structure

```
sepnas-event-system/
├── api/                    # API endpoints
│   ├── dashboard.php
│   ├── events.php
│   ├── notifications.php
│   └── attendance.php
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   ├── ajax.js
│   │   └── onesignal.js
│   └── images/
├── auth/                   # Authentication
│   ├── login.php
│   └── logout.php
├── classes/                # PHP classes
│   ├── User.php
│   ├── Event.php
│   ├── Attendance.php
│   ├── Feedback.php
│   └── Notification.php
├── config/                 # Configuration
│   ├── config.php
│   └── database.php
├── dashboard/              # Main application
│   ├── index.php
│   ├── events.php
│   ├── calendar.php
│   ├── attendance.php
│   ├── feedback.php
│   └── reports.php
├── database/               # Database files
│   └── schema.sql
├── includes/               # Shared includes
│   ├── auth_check.php
│   └── role_check.php
├── index.php              # Entry point
└── README.md
```

## API Endpoints

### Dashboard API
- `GET /api/dashboard.php?action=stats` - Get dashboard statistics
- `GET /api/dashboard.php?action=recent_events` - Get recent events

### Events API
- `GET /api/events.php?action=list` - List events
- `GET /api/events.php?action=upcoming` - Get upcoming events
- `POST /api/events.php?action=create` - Create event
- `POST /api/events.php?action=update` - Update event

### Notifications API
- `POST /api/notifications.php?action=send` - Send notification
- `POST /api/notifications.php?action=send_reminder` - Send event reminder

## Security Features

- **Input Validation**: All user inputs are validated and sanitized
- **SQL Injection Protection**: Prepared statements used throughout
- **XSS Prevention**: Output escaping and content security
- **Session Security**: Secure session management
- **Role-based Access**: Granular permission system
- **CSRF Protection**: Cross-site request forgery prevention

## Performance Optimization

- **Database Indexing**: Optimized database queries
- **Caching**: Strategic caching for frequently accessed data
- **AJAX**: Asynchronous loading for better user experience
- **Image Optimization**: Compressed and optimized assets
- **CDN Ready**: Compatible with content delivery networks

## Browser Support

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify database credentials in `config/database.php`
   - Check if MySQL service is running
   - Ensure database exists and user has proper permissions

2. **OneSignal Notifications Not Working**
   - Verify App ID and REST API Key
   - Check browser console for JavaScript errors
   - Ensure HTTPS is enabled (required for notifications)

3. **File Upload Issues**
   - Check file permissions (755 for directories, 644 for files)
   - Verify PHP upload limits in php.ini
   - Ensure sufficient disk space

4. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies and cache

### Debug Mode

Enable debug mode in `config/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation

## Changelog

### Version 1.0.0
- Initial release
- Core event management features
- User authentication and roles
- Automated notifications
- Attendance tracking
- Feedback system
- Reports and analytics
- Responsive design
- AJAX functionality

---

**SEPNAS Event Management System** - Streamlining school events with technology.

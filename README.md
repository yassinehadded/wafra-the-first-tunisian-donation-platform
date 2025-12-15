# WAFRA - Complete Integration Platform

**WAFRA** is a comprehensive web-based platform for managing donations, events, associations, and community engagement. This is the final integrated MVC structure combining all WAFRA modules into a unified system.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Architecture](#architecture)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Routing](#routing)
- [API Endpoints](#api-endpoints)
- [Admin Features](#admin-features)
- [Security](#security)
- [Development Notes](#development-notes)

## 🎯 Overview

WAFRA is a full-featured platform that enables:

- **User Management**: Registration, authentication (including OAuth), profile management
- **Event Management**: Create, manage, and reserve spots for events
- **Donation System**: Post donations, request items, manage fulfillment
- **Forum**: Social interaction with posts, comments, likes, and saves
- **Associations**: Join and manage associations
- **Cotisations**: Membership fee management
- **Messaging**: Real-time messaging between users
- **Notifications**: Real-time notification system
- **Reclamations**: Complaint/claim management system
- **Admin Dashboard**: Comprehensive admin panel with analytics

## ✨ Features

### Authentication & User Management

- ✅ User registration with email verification
- ✅ Login with reCAPTCHA v3 protection
- ✅ OAuth integration (Google & GitHub)
- ✅ Password reset functionality
- ✅ Profile management with picture upload
- ✅ Session management and tracking
- ✅ Role-based access control (Admin/User)

### Events & Reservations

- ✅ Event creation and management
- ✅ Event filtering and search
- ✅ Reservation system linked to user accounts
- ✅ QR code generation for events
- ✅ Location-based event management (latitude/longitude)
- ✅ Event types: festival, conference, seminar, workshop, social

### Donation System

- ✅ Donation posting (Food, Clothing, Books, Electronics, etc.)
- ✅ Donation requests
- ✅ Request approval/denial workflow
- ✅ Donation fulfillment tracking
- ✅ Category-based organization
- ✅ Image upload for donations
- ✅ Email notifications for donations

### Forum & Social Features

- ✅ Post creation, editing, and deletion
- ✅ Comments on posts
- ✅ Like/unlike posts
- ✅ Save posts for later
- ✅ Post reporting system
- ✅ Comment reporting system
- ✅ Media upload support
- ✅ Search and filter functionality
- ✅ User-specific post management

### Associations

- ✅ Association listing and details
- ✅ Join associations
- ✅ Association management (admin)

### Cotisations (Membership Fees)

- ✅ Cotisation listing
- ✅ Payment history
- ✅ Payment processing
- ✅ Association-linked cotisations

### Messaging System

- ✅ Real-time messaging between users
- ✅ Conversation management
- ✅ Message rate limiting (5 messages per day for new users)
- ✅ Conversation blocking
- ✅ Related entity linking (donations, posts, requests)
- ✅ Unread message tracking

### Notifications

- ✅ Real-time notification system
- ✅ Notification types: post_liked, post_commented, new_message
- ✅ Unread notification count
- ✅ Mark as read functionality
- ✅ Admin notifications

### Reclamations (Complaints/Claims)

- ✅ Submit reclamations
- ✅ View reclamation history
- ✅ Admin review and response
- ✅ Status tracking (pending, in_progress, resolved, closed)
- ✅ Admin notes and responses

### Admin Dashboard

- ✅ User statistics and charts
- ✅ Event management
- ✅ Donation management
- ✅ Reclamation management
- ✅ Post/comment report review
- ✅ Association management
- ✅ Cotisation management
- ✅ Message monitoring
- ✅ Analytics and reporting

### Additional Features

- ✅ AI Chatbot integration (Hugging Face)
- ✅ Email service (PHPMailer with SMTP)
- ✅ Static pages (About, Mission, Help, Contact, Privacy, Terms, etc.)
- ✅ Responsive design
- ✅ File upload management

## 🏗️ Architecture

### MVC Structure

```
wafra-integration/
├── config/              # Configuration files
│   ├── config.php       # Main configuration
│   ├── Database.php     # Database connection singleton
│   ├── env_loader.php   # Environment variable loader
│   ├── oauth-config.php # OAuth configuration
│   └── autoload.php     # Class autoloader
├── controllers/         # Controllers (MVC)
│   ├── AuthController.php
│   ├── OAuthController.php
│   ├── EventController.php
│   ├── ReservationController.php
│   ├── PostController.php
│   ├── PostReportController.php
│   ├── DonationController.php
│   ├── AssociationController.php
│   ├── CotisationController.php
│   ├── ReclamationController.php
│   ├── NotificationController.php
│   ├── ChatbotController.php
│   ├── AdminController.php
│   ├── AdminDonationController.php
│   ├── AdminReclamationController.php
│   ├── AdminAssociationController.php
│   ├── AdminCotisationController.php
│   ├── AdminMessageController.php
│   ├── AdminNotificationController.php
│   └── api/            # API endpoints
│       ├── post_comment.php
│       ├── post_like.php
│       ├── post_report.php
│       ├── post_save.php
│       ├── comment_report.php
│       ├── message.php
│       ├── user_stats.php
│       ├── user_registrations_chart.php
│       ├── login_activity_chart.php
│       ├── user_profile.php
│       ├── search_events.php
│       └── search_reservations.php
├── models/              # Models (MVC)
│   ├── User.php
│   ├── Event.php
│   ├── Reservation.php
│   ├── Post.php
│   ├── PostReport.php
│   ├── CommentReport.php
│   ├── Donation.php
│   ├── Association.php
│   ├── Cotisation.php
│   ├── Reclamation.php
│   ├── Notification.php
│   ├── LoginSession.php
│   ├── Settings.php
│   └── EmailUtility.php
├── services/            # Services (Business Logic)
│   ├── AuthService.php
│   ├── EmailService.php
│   ├── DonationEmailService.php
│   ├── PostCommentService.php
│   ├── PostLikeService.php
│   ├── PostReportService.php
│   ├── PostSaveService.php
│   ├── CommentReportService.php
│   ├── MessageService.php
│   ├── ConversationService.php
│   └── NotificationService.php
├── view/
│   ├── frontoffice/     # User-facing views
│   │   ├── homepage.php
│   │   ├── login.php
│   │   ├── signup.php
│   │   ├── profile.php
│   │   ├── index.php
│   │   ├── posts.php
│   │   ├── forum.php
│   │   ├── messages.php
│   │   ├── donations/
│   │   ├── associations/
│   │   ├── cotisations/
│   │   ├── reclamations/
│   │   └── assets/
│   ├── backoffice/      # Admin views
│   │   ├── dashboard.php
│   │   ├── donations/
│   │   ├── reclamations/
│   │   ├── associations/
│   │   ├── cotisations/
│   │   └── assets/
│   ├── components/      # Reusable components
│   │   └── chatbot.php
│   └── pages/          # Static pages
│       ├── about.php
│       ├── mission.php
│       ├── how-it-works.php
│       ├── help.php
│       ├── contact.php
│       ├── privacy.php
│       ├── terms.php
│       ├── transparency.php
│       └── report.php
├── sql/                # Database schemas
│   ├── wafra.sql
│   ├── donation_tables.sql
│   ├── messaging_tables.sql
│   ├── notifications_table.sql
│   ├── reclamation_tables.sql
│   └── comment_report_table.sql
├── uploads/            # User uploads
│   ├── posts/
│   ├── donations/
│   └── profile_pictures/
├── vendor/             # Dependencies
│   └── PHPMailer/
└── index.php           # Main router
```

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB 10.4 or higher
- Apache/Nginx web server
- Composer (for dependencies, if needed)

### Step 1: Clone/Download Project

Place the `wafra-integration` folder in your web server directory (e.g., `htdocs` for XAMPP).

### Step 2: Database Setup

1. Create a MySQL database named `wafra` (or your preferred name)
2. Import the database schema:

```bash
mysql -u root -p wafra < sql/wafra.sql
```

Or use phpMyAdmin to import `sql/wafra.sql`.

**Required Tables:**
- `users` - User accounts
- `evenements` - Events
- `reservations` - Event reservations
- `post`, `post_comment`, `post_like`, `post_save`, `post_report` - Forum tables
- `donor_offers`, `donor_requests` - Donation tables
- `association` - Associations
- `dons` - Donations
- `conversations`, `messages`, `message_rate_limits` - Messaging tables
- `notifications` - Notifications
- `reclamations` - Reclamations
- `loginsession` - Session tracking
- `settings` - Application settings
- `comment_report` - Comment reports

### Step 3: Environment Configuration

Create a `.env` file in the `wafra-integration/` root directory:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=wafra
DB_USER=root
DB_PASS=

# Application
APP_URL=http://localhost/wafra-integration

# Email (SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@wafra.com
MAIL_FROM_NAME=Wafra

# reCAPTCHA v3
RECAPTCHA_SITE_KEY=your_site_key
RECAPTCHA_SECRET_KEY=your_secret_key

# OAuth - Google
GOOGLE_CLIENT_ID=your_google_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost/wafra-integration/index.php?action=google_callback

# OAuth - GitHub
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret
GITHUB_REDIRECT_URI=http://localhost/wafra-integration/index.php?action=github_callback

# Hugging Face (for chatbot)
HUGGINGFACE_API_KEY=your_api_key

# Admin Email
ADMIN_EMAIL=admin@wafra.com
```

### Step 4: File Permissions

Ensure the `uploads/` directory and subdirectories are writable:

```bash
chmod -R 755 uploads/
```

### Step 5: Web Server Configuration

**For Apache (XAMPP):**

Ensure `mod_rewrite` is enabled. The project should work without URL rewriting as it uses query parameters.

**For Nginx:**

Configure your server block to point to the `wafra-integration` directory.

### Step 6: Access the Application

Open your browser and navigate to:

```
http://localhost/wafra-integration/
```

## ⚙️ Configuration

### OAuth Setup

#### Google OAuth

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URI: `http://localhost/wafra-integration/index.php?action=google_callback`
6. Copy Client ID and Client Secret to `.env`

#### GitHub OAuth

1. Go to GitHub Settings > Developer settings > OAuth Apps
2. Create a new OAuth App
3. Set Authorization callback URL: `http://localhost/wafra-integration/index.php?action=github_callback`
4. Copy Client ID and Client Secret to `.env`

### reCAPTCHA Setup

1. Go to [Google reCAPTCHA](https://www.google.com/recaptcha/admin)
2. Register a new site (reCAPTCHA v3)
3. Copy Site Key and Secret Key to `.env`

### Email Configuration

For Gmail:
1. Enable 2-Step Verification
2. Generate an App Password
3. Use the App Password in `SMTP_PASSWORD`

## 🗄️ Database Setup

### Key Tables Overview

**Users & Authentication:**
- `users` - User accounts with CIN as primary key
- `loginsession` - Active login sessions

**Events:**
- `evenements` - Event information
- `reservations` - Event reservations linked to users

**Forum:**
- `post` - Forum posts
- `post_comment` - Comments on posts
- `post_like` - Post likes
- `post_save` - Saved posts
- `post_report` - Post reports
- `comment_report` - Comment reports

**Donations:**
- `donor_offers` - Donation offers
- `donor_requests` - Donation requests

**Messaging:**
- `conversations` - User conversations
- `messages` - Individual messages
- `message_rate_limits` - Rate limiting for new users

**Notifications:**
- `notifications` - User notifications

**Other:**
- `association` - Associations
- `dons` - Donations
- `reclamations` - Complaints/claims
- `settings` - Application settings

### Important Notes

- The `reservations` table should have a `user_id` column linking to `users.cin`
- Forum tables (`post_comment`, `post_like`) are created automatically by services
- All foreign keys use CASCADE for deletion

## 🛣️ Routing

All routes go through `index.php?action=...`:

### Authentication Routes

- `?action=login` - User login
- `?action=signup` - User registration
- `?action=logout` - Logout
- `?action=update_profile` - Update user profile
- `?action=verify_email&token=...` - Email verification
- `?action=google_login` - Google OAuth login
- `?action=github_login` - GitHub OAuth login
- `?action=google_callback` - Google OAuth callback
- `?action=github_callback` - GitHub OAuth callback

### Event Routes

- `?action=events` - List user events
- `?action=event_show&id=X` - Show event details
- `?action=reservation_create` - Create reservation
- `?action=reservation_update` - Update reservation
- `?action=reservation_delete&id=X` - Delete reservation

### Forum Routes

- `?action=forum_list` - List all forum posts
- `?action=forum_show&id=X` - Show specific post
- `?action=forum_create` - Create new post
- `?action=forum_update` - Update post
- `?action=forum_delete&id=X` - Delete post
- `?action=forum_search` - Search posts

### Donation Routes

- `?action=donations` - List donations
- `?action=donation_show&id=X` - Show donation details
- `?action=my_donations` - User's donations
- `?action=donation_submit` - Submit donation
- `?action=donation_request` - Request donation (AJAX)
- `?action=donation_update_request_status` - Update request status
- `?action=donation_mark_fulfilled&id=X` - Mark donation as fulfilled
- `?action=donation_delete&id=X` - Delete donation

### Association Routes

- `?action=associations` - List associations
- `?action=association_show&id=X` - Show association details
- `?action=association_join&id=X` - Join association

### Cotisation Routes

- `?action=cotisations` - List cotisations
- `?action=cotisation_history` - Payment history
- `?action=cotisation_pay` - Process payment

### Reclamation Routes

- `?action=reclamations` - List reclamations
- `?action=reclamation_submit` - Submit reclamation
- `?action=reclamation_view&id=X` - View reclamation
- `?action=reclamation_update` - Update reclamation
- `?action=reclamation_delete&id=X` - Delete reclamation
- `?action=reclamation_get_details` - Get details (AJAX)

### Messaging Routes

- `?action=messages` - Messages page
- `?action=api_message` - Message API endpoint

### Admin Routes

- `?action=dashboard` - Admin dashboard
- `?action=admin_donation_delete&id=X` - Delete donation (admin)
- `?action=admin_donation_update_status` - Update donation status
- `?action=admin_donation_update_quantity` - Update donation quantity
- `?action=admin_donation_request_status` - Update request status
- `?action=admin_reclamation_update_status` - Update reclamation status
- `?action=admin_reclamation_add_response` - Add response to reclamation
- `?action=admin_reclamation_delete&id=X` - Delete reclamation
- `?action=admin_reclamation_details&id=X` - Get reclamation details
- `?action=admin_messages` - Admin message management

### Static Pages

- `?action=page&name=about` - About page
- `?action=page&name=mission` - Mission page
- `?action=page&name=how-it-works` - How it works
- `?action=page&name=help` - Help page
- `?action=page&name=contact` - Contact page
- `?action=page&name=privacy` - Privacy policy
- `?action=page&name=terms` - Terms of service
- `?action=page&name=transparency` - Transparency page
- `?action=page&name=report` - Report page

### Other Routes

- `?action=homepage` - Public homepage
- `?action=chatbot` - Chatbot API endpoint

## 🔌 API Endpoints

All API endpoints return JSON responses:

### Forum APIs

- `?action=api_post_comment` - Add comment to post
- `?action=api_post_like` - Like/unlike post
- `?action=api_post_save` - Save/unsave post
- `?action=api_post_report` - Report post
- `?action=api_comment_report` - Report comment

### User APIs

- `?action=api_user_stats` - Get user statistics
- `?action=api_user_registrations_chart` - Registration chart data
- `?action=api_login_activity_chart` - Login activity chart data
- `?action=api_user_profile` - Get user profile data

### Search APIs

- `?action=api_search_events` - Search events
- `?action=api_search_reservations` - Search reservations

### Notification APIs

- `?action=api_notifications&subaction=getUnread` - Get unread notifications
- `?action=api_notifications&subaction=getAll` - Get all notifications
- `?action=api_notifications&subaction=markAsRead` - Mark notification as read
- `?action=api_notifications&subaction=markAllAsRead` - Mark all as read
- `?action=api_notifications&subaction=getCount` - Get unread count

### Admin APIs

- `?action=api_admin_notifications&subaction=getUnread` - Admin unread notifications
- `?action=api_admin_notifications&subaction=markAsRead` - Mark admin notification as read
- `?action=api_admin_notifications&subaction=markAllAsRead` - Mark all admin notifications as read
- `?action=api_admin_notifications&subaction=getCount` - Admin notification count
- `?action=api_admin_reclamations` - Get reclamations (AJAX)

## 👨‍💼 Admin Features

### Dashboard

- User statistics and analytics
- Registration and login activity charts
- Quick access to all management sections

### User Management

- View all users
- User statistics
- Registration charts
- Login activity tracking

### Event Management

- Create, edit, delete events
- View all reservations
- Search and filter events

### Donation Management

- View all donations
- Update donation status
- Update quantities
- Manage donation requests
- Delete donations

### Reclamation Management

- View all reclamations
- Update status (pending, in_progress, resolved, closed)
- Add admin responses
- Delete reclamations

### Post/Comment Moderation

- Review reported posts
- Review reported comments
- Take action on reports

### Association Management

- Create, edit, delete associations
- Manage association details

### Cotisation Management

- View all cotisations
- Manage payments

### Message Monitoring

- View all conversations
- Monitor message activity
- Block conversations if needed
- View reported conversations

## 🔒 Security

### Implemented Security Features

- ✅ Password hashing (bcrypt)
- ✅ reCAPTCHA v3 for login protection
- ✅ CSRF protection for OAuth (state tokens)
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (input sanitization)
- ✅ Session management
- ✅ Email verification
- ✅ Password reset tokens with expiration
- ✅ File upload validation
- ✅ Rate limiting for messaging (new users: 5 messages/day)
- ✅ Role-based access control
- ✅ Input validation and sanitization

### Best Practices

- All database queries use prepared statements
- Passwords are hashed using `password_hash()` with bcrypt
- Session IDs are regenerated on login
- File uploads are validated by type and size
- OAuth state tokens prevent CSRF attacks
- Admin routes require authentication and admin role

## 📝 Development Notes

### Code Standards

- All code follows PSR-12 style guide
- MVC pattern strictly enforced
- No duplicate code
- Clean separation of concerns
- Service layer for business logic

### Session Variables

After login, session contains:

- `userID` - User CIN (primary key)
- `sessionID` - Login session ID
- `role` - 'user' or 'admin'
- `firstname`, `lastname`, `email`, `username`

### Redirects

- User login → `view/frontoffice/profile.php` or `view/frontoffice/index.php`
- Admin login → `index.php?action=dashboard`
- Not logged in → `view/frontoffice/login.php` or homepage

### File Uploads

- Profile pictures: `uploads/profile_pictures/`
- Post media: `uploads/posts/`
- Donation images: `uploads/donations/{category}/`

### Email Templates

Email notifications are sent for:
- Welcome emails (signup)
- Email verification
- Password reset
- Donation requests
- Donation approvals

### Chatbot

- Integrated on all pages via `view/components/chatbot.php`
- Different prompts for admin vs user
- Uses Hugging Face API
- Requires `HUGGINGFACE_API_KEY` in `.env`

### Database Connection

- Uses singleton pattern (`Database::connect()`)
- Connection is reused across requests
- Automatic error handling

### Error Handling

- Errors are logged to `debug.log`
- AJAX requests return JSON error responses
- User-friendly error messages displayed to users

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check `.env` file database credentials
   - Ensure MySQL service is running
   - Verify database exists

2. **OAuth Not Working**
   - Verify OAuth credentials in `.env`
   - Check redirect URIs match exactly
   - Ensure OAuth apps are configured correctly

3. **Email Not Sending**
   - Verify SMTP credentials
   - Check firewall settings
   - For Gmail, use App Password, not regular password

4. **File Upload Errors**
   - Check `uploads/` directory permissions
   - Verify PHP `upload_max_filesize` and `post_max_size`
   - Check directory exists

5. **reCAPTCHA Errors**
   - Verify site key and secret key in `.env`
   - Check domain is registered in reCAPTCHA console

## 📄 License

This project is proprietary software. All rights reserved.

## 👥 Support

For issues or questions, please contact the development team.

---

**Last Updated:** December 2025  
**Version:** 1.0.0 (Final Integration)

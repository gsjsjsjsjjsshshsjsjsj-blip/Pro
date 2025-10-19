# Medical Appointment Booking System

A comprehensive, secure, and user-friendly medical appointment booking system built with PHP, MySQL, and Bootstrap. This system follows modern web development best practices and implements robust security measures.

## 🌟 Features

### For Patients
- **Easy Registration & Login** - Secure account creation with email verification
- **Doctor Search & Profiles** - Browse available doctors by specialization
- **Appointment Booking** - Real-time availability checking and booking
- **Appointment Management** - View, reschedule, or cancel appointments
- **Personal Dashboard** - Overview of upcoming and past appointments

### For Doctors
- **Professional Profiles** - Detailed doctor profiles with specializations
- **Schedule Management** - View and manage daily/weekly schedules
- **Availability Control** - Set working hours and available days
- **Patient Management** - Access to patient appointment history
- **Appointment Status Updates** - Confirm, complete, or reschedule appointments

### For Administrators
- **User Management** - Manage patients, doctors, and admin accounts
- **System Overview** - Comprehensive dashboard with statistics
- **Appointment Oversight** - View and manage all system appointments
- **Doctor Approval** - Approve new doctor registrations
- **System Reports** - Generate usage and performance reports

## 🔒 Security Features

- **Password Security** - Bcrypt hashing with salt
- **SQL Injection Prevention** - Prepared statements throughout
- **XSS Protection** - Input sanitization and output encoding
- **CSRF Protection** - Token-based request validation
- **Session Security** - Secure session management with regeneration
- **Role-Based Access Control** - Granular permission system
- **Input Validation** - Server-side and client-side validation
- **Error Handling** - Secure error logging without information disclosure

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3 for responsive design
- **Icons**: Font Awesome 6.0
- **Security**: Bcrypt, CSRF tokens, prepared statements

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.3+)
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)
- PHP extensions: PDO, PDO_MySQL, mbstring, openssl

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/medical-appointment-system.git
cd medical-appointment-system
```

### 2. Database Setup
1. Create a MySQL database:
```sql
CREATE DATABASE medical_appointment_system;
```

2. Update database configuration in `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'medical_appointment_system';
private $username = 'your_username';
private $password = 'your_password';
```

3. Run the initialization script:
```bash
php init.php
```

### 3. Web Server Configuration

#### Apache (.htaccess included)
- Ensure mod_rewrite is enabled
- Point document root to the project directory

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/medical-appointment-system;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. File Permissions
```bash
chmod 755 logs/
chmod 755 uploads/
chmod 644 config/*.php
```

## 🎯 Default Accounts

After installation, you can use these demo accounts:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@medical.local | admin123 |
| Doctor | dr.smith@medical.local | admin123 |
| Doctor | dr.johnson@medical.local | admin123 |

**⚠️ Important**: Change these passwords in production!

## 📁 Project Structure

```
medical-appointment-system/
├── admin/                  # Admin panel pages
├── auth/                   # Authentication pages
├── classes/                # PHP classes
│   ├── Appointment.php     # Appointment management
│   ├── Doctor.php          # Doctor operations
│   ├── RoleManager.php     # Role-based access control
│   └── User.php            # User management
├── config/                 # Configuration files
│   ├── database.php        # Database connection
│   └── database_setup.sql  # Database schema
├── doctor/                 # Doctor panel pages
├── includes/               # Shared components
│   ├── footer.php          # Footer template
│   ├── header.php          # Header template
│   ├── helpers.php         # Utility functions
│   └── session.php         # Session management
├── logs/                   # Application logs
├── patient/                # Patient panel pages
├── uploads/                # File uploads
├── .htaccess              # Apache configuration
├── index.php              # Landing page
├── init.php               # System initialization
└── README.md              # This file
```

## 🔧 Configuration

### Database Configuration
Edit `config/database.php` to match your database settings.

### Security Settings
- Update CSRF token settings in `includes/helpers.php`
- Configure session settings in `includes/session.php`
- Review security headers in `.htaccess`

### Email Configuration
For production use, configure email settings for:
- Appointment confirmations
- Password reset functionality
- System notifications

## 🎨 Customization

### Styling
- Main styles are in `includes/header.php`
- Bootstrap variables can be customized
- Additional CSS can be added to individual pages

### Functionality
- Extend classes in the `classes/` directory
- Add new pages following the existing structure
- Implement additional features using the established patterns

## 🧪 Testing

### Manual Testing
1. Test user registration and login
2. Verify appointment booking workflow
3. Check role-based access controls
4. Test form validations
5. Verify security measures

### Security Testing
- Test for SQL injection vulnerabilities
- Verify XSS protection
- Check CSRF token validation
- Test session security
- Verify input sanitization

## 📊 Performance Optimization

- Database indexes are included in the schema
- Prepared statements prevent SQL injection and improve performance
- Session management is optimized
- CSS and JavaScript are minified in production

## 🔍 Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Permission Denied Errors**
   - Check file permissions on logs/ and uploads/ directories
   - Ensure web server has write access

3. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies

4. **Bootstrap/CSS Not Loading**
   - Check internet connection for CDN resources
   - Verify file paths in header.php

## 🚀 Deployment

### Production Checklist
- [ ] Change default passwords
- [ ] Update database credentials
- [ ] Enable HTTPS/SSL
- [ ] Configure proper error logging
- [ ] Set up automated backups
- [ ] Configure email settings
- [ ] Review security headers
- [ ] Test all functionality
- [ ] Set up monitoring

### Environment Variables
For production, consider using environment variables for sensitive configuration:
```php
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? 'medical_appointment_system';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, please:
1. Check the troubleshooting section
2. Review the documentation
3. Open an issue on GitHub
4. Contact the development team

## 🔄 Updates

Keep your system updated by:
- Regularly checking for security updates
- Monitoring PHP and MySQL versions
- Updating dependencies
- Reviewing security best practices

---

**Built with ❤️ for healthcare professionals and patients**
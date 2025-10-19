<?php
/**
 * System Initialization Script
 * Run this script to set up the medical appointment booking system
 */

require_once 'config/database.php';

echo "Medical Appointment Booking System - Initialization\n";
echo "==================================================\n\n";

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database. Please check your database configuration.");
    }
    echo "   ✓ Database connection successful\n\n";

    // Check if database exists and create if not
    echo "2. Setting up database schema...\n";
    
    // Read and execute SQL setup file
    $sqlFile = 'config/database_setup.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Database setup file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $db->exec($statement);
            } catch (PDOException $e) {
                // Ignore errors for statements that might already exist
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "   Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    echo "   ✓ Database schema setup complete\n\n";

    // Create necessary directories
    echo "3. Creating necessary directories...\n";
    $directories = [
        'logs',
        'uploads',
        'auth',
        'patient',
        'doctor',
        'admin',
        'api'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "   ✓ Created directory: $dir\n";
        } else {
            echo "   ✓ Directory exists: $dir\n";
        }
    }
    echo "\n";

    // Set up .htaccess for security
    echo "4. Setting up security configurations...\n";
    
    $htaccessContent = "# Security Headers\n";
    $htaccessContent .= "Header always set X-Content-Type-Options nosniff\n";
    $htaccessContent .= "Header always set X-Frame-Options DENY\n";
    $htaccessContent .= "Header always set X-XSS-Protection \"1; mode=block\"\n";
    $htaccessContent .= "Header always set Referrer-Policy \"strict-origin-when-cross-origin\"\n\n";
    
    $htaccessContent .= "# Protect sensitive files\n";
    $htaccessContent .= "<Files \"*.log\">\n";
    $htaccessContent .= "    Order allow,deny\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n\n";
    
    $htaccessContent .= "<Files \"config/*\">\n";
    $htaccessContent .= "    Order allow,deny\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n\n";
    
    $htaccessContent .= "# Enable error reporting (disable in production)\n";
    $htaccessContent .= "php_flag display_errors On\n";
    $htaccessContent .= "php_value error_reporting E_ALL\n";
    
    file_put_contents('.htaccess', $htaccessContent);
    echo "   ✓ Security configurations applied\n\n";

    // Verify system components
    echo "5. Verifying system components...\n";
    
    $requiredFiles = [
        'config/database.php',
        'includes/helpers.php',
        'includes/session.php',
        'classes/User.php',
        'classes/Doctor.php',
        'classes/Appointment.php',
        'classes/RoleManager.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "   ✓ $file\n";
        } else {
            echo "   ✗ $file (missing)\n";
        }
    }
    echo "\n";

    // Test class loading
    echo "6. Testing class loading...\n";
    try {
        require_once 'includes/helpers.php';
        require_once 'classes/User.php';
        require_once 'classes/Doctor.php';
        require_once 'classes/Appointment.php';
        require_once 'classes/RoleManager.php';
        
        $user = new User($db);
        $doctor = new Doctor($db);
        $appointment = new Appointment($db);
        $roleManager = new RoleManager($db);
        
        echo "   ✓ All classes loaded successfully\n\n";
    } catch (Exception $e) {
        echo "   ✗ Error loading classes: " . $e->getMessage() . "\n\n";
    }

    echo "System initialization completed successfully!\n\n";
    echo "Next steps:\n";
    echo "1. Update database credentials in config/database.php if needed\n";
    echo "2. Create your frontend pages (login, registration, dashboards)\n";
    echo "3. Test the system with the default admin account:\n";
    echo "   Email: admin@medical.local\n";
    echo "   Password: admin123\n\n";
    echo "Default doctor accounts:\n";
    echo "   Dr. Smith - dr.smith@medical.local (password: admin123)\n";
    echo "   Dr. Johnson - dr.johnson@medical.local (password: admin123)\n\n";
    echo "Remember to:\n";
    echo "- Change default passwords in production\n";
    echo "- Configure SSL/HTTPS\n";
    echo "- Set up proper error logging\n";
    echo "- Review and adjust security settings\n";

} catch (Exception $e) {
    echo "✗ Initialization failed: " . $e->getMessage() . "\n";
    echo "Please check your configuration and try again.\n";
}
?>
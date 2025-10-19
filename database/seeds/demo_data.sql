-- Demo Data for Medical Appointment System
-- Insert sample data for testing and demonstration

-- Insert demo users
INSERT INTO users (first_name, last_name, email, password, role, phone, date_of_birth, gender, address, is_active, created_at) VALUES
-- Admin user
('System', 'Administrator', 'admin@medical.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+1-555-0001', '1980-01-01', 'other', '123 Admin St, Medical City, MC 12345', 1, NOW()),

-- Doctor users
('John', 'Smith', 'dr.smith@medical.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '+1-555-0101', '1975-05-15', 'male', '456 Doctor Ave, Medical City, MC 12345', 1, NOW()),
('Sarah', 'Johnson', 'dr.johnson@medical.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '+1-555-0102', '1978-08-22', 'female', '789 Physician Blvd, Medical City, MC 12345', 1, NOW()),
('Michael', 'Brown', 'dr.brown@medical.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '+1-555-0103', '1972-12-10', 'male', '321 Medical Plaza, Medical City, MC 12345', 1, NOW()),

-- Patient users
('Alice', 'Wilson', 'alice.wilson@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '+1-555-0201', '1990-03-15', 'female', '123 Patient St, City, ST 12345', 1, NOW()),
('Bob', 'Davis', 'bob.davis@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '+1-555-0202', '1985-07-20', 'male', '456 Health Ave, City, ST 12345', 1, NOW()),
('Carol', 'Miller', 'carol.miller@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '+1-555-0203', '1992-11-08', 'female', '789 Wellness Blvd, City, ST 12345', 1, NOW());

-- Insert doctor profiles
INSERT INTO doctor_profiles (user_id, specialization, license_number, experience_years, education, consultation_fee, working_hours, is_available, bio, created_at) VALUES
(2, 'Cardiology', 'MD123456', 15, 'MD from Harvard Medical School, Cardiology Fellowship from Johns Hopkins', 150.00, 'Mon-Fri: 9:00 AM - 5:00 PM', 1, 'Dr. Smith is a board-certified cardiologist with over 15 years of experience in treating heart conditions.', NOW()),
(3, 'Pediatrics', 'MD234567', 12, 'MD from Stanford Medical School, Pediatrics Residency at UCSF', 120.00, 'Mon-Sat: 8:00 AM - 6:00 PM', 1, 'Dr. Johnson specializes in pediatric care and has been treating children for over a decade.', NOW()),
(4, 'Orthopedics', 'MD345678', 18, 'MD from Mayo Medical School, Orthopedic Surgery Fellowship', 180.00, 'Tue-Fri: 10:00 AM - 4:00 PM', 1, 'Dr. Brown is an experienced orthopedic surgeon specializing in sports medicine and joint replacement.', NOW());

-- Insert sample appointments
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, notes, created_at) VALUES
-- Upcoming appointments
(5, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'Regular checkup and chest pain evaluation', 'scheduled', NULL, NOW()),
(6, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:30:00', 'Child vaccination and growth assessment', 'confirmed', NULL, NOW()),
(7, 4, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '11:15:00', 'Knee pain and mobility issues', 'scheduled', NULL, NOW()),
(5, 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00:00', 'Follow-up consultation', 'scheduled', NULL, NOW()),

-- Past appointments
(5, 2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '15:00:00', 'Annual physical examination', 'completed', 'Patient is in good health. Recommended annual follow-up.', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(6, 4, DATE_SUB(CURDATE(), INTERVAL 14 DAY), '13:00:00', 'Sports injury assessment', 'completed', 'Minor sprain. Prescribed rest and physical therapy.', DATE_SUB(NOW(), INTERVAL 14 DAY)),
(7, 2, DATE_SUB(CURDATE(), INTERVAL 21 DAY), '16:30:00', 'Hypertension monitoring', 'completed', 'Blood pressure stable. Continue current medication.', DATE_SUB(NOW(), INTERVAL 21 DAY)),

-- Cancelled appointment
(6, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '10:30:00', 'Routine checkup', 'cancelled', 'Patient requested cancellation due to scheduling conflict.', DATE_SUB(NOW(), INTERVAL 4 DAY));

-- Insert system settings (if you have a settings table)
-- INSERT INTO system_settings (setting_key, setting_value, description) VALUES
-- ('site_name', 'Medical Appointment System', 'Name of the medical facility'),
-- ('site_email', 'contact@medical.local', 'Contact email address'),
-- ('site_phone', '+1-555-MEDICAL', 'Contact phone number'),
-- ('appointment_duration', '30', 'Default appointment duration in minutes'),
-- ('working_hours_start', '08:00', 'Daily working hours start time'),
-- ('working_hours_end', '18:00', 'Daily working hours end time'),
-- ('max_appointments_per_day', '20', 'Maximum appointments per doctor per day'),
-- ('booking_advance_days', '30', 'How many days in advance appointments can be booked'),
-- ('cancellation_hours', '24', 'Minimum hours before appointment for cancellation');

-- Note: Password for all demo accounts is 'admin123'
-- In production, these should be changed immediately
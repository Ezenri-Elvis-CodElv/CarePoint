-- =====================================================================
--  CarePoint – Clinic Appointment Booking System
--  Database: carepoint_db
--  Compatible with MySQL 5.7+ / MariaDB 10.3+ (XAMPP)
--
--  Import via phpMyAdmin (Import tab) or the command line:
--     mysql -u root -p < database/carepoint_db.sql
--
--  Demo accounts
--     Admin   : username "admin"                 password "Admin@123"
--     Patient : email "patient@carepoint.com"     password "Patient@123"
-- =====================================================================

DROP DATABASE IF EXISTS carepoint_db;
CREATE DATABASE carepoint_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carepoint_db;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------
-- Table: admin
-- ---------------------------------------------------------------------
CREATE TABLE admin (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    last_login    DATETIME     NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: users  (registered patients)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(30)  NOT NULL,
    age           TINYINT UNSIGNED NULL,
    gender        ENUM('Male','Female','Other') NULL,
    date_of_birth DATE         NULL,
    blood_group   ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL,
    emergency_name     VARCHAR(100) NULL,
    emergency_relation VARCHAR(50)  NULL,
    emergency_phone    VARCHAR(30)  NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: departments  (medical services)
-- ---------------------------------------------------------------------
CREATE TABLE departments (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(100) NOT NULL UNIQUE,
    slug              VARCHAR(100) NOT NULL UNIQUE,
    icon              VARCHAR(60)  NOT NULL DEFAULT 'fa-stethoscope',
    short_description VARCHAR(255) NOT NULL,
    description       TEXT         NOT NULL,
    features          VARCHAR(500) NOT NULL DEFAULT '',
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: doctors
-- ---------------------------------------------------------------------
CREATE TABLE doctors (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id    INT UNSIGNED NOT NULL,
    full_name        VARCHAR(100) NOT NULL,
    specialisation   VARCHAR(120) NOT NULL,
    qualification    VARCHAR(150) NOT NULL DEFAULT '',
    experience_years TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bio              TEXT         NOT NULL,
    photo            VARCHAR(255) NULL,
    email            VARCHAR(150) NULL,
    phone            VARCHAR(30)  NULL,
    available_days   VARCHAR(40)  NOT NULL DEFAULT 'Mon,Tue,Wed,Thu,Fri',
    start_time       TIME         NOT NULL DEFAULT '09:00:00',
    end_time         TIME         NOT NULL DEFAULT '17:00:00',
    slot_duration    SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    consultation_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_featured      TINYINT(1)   NOT NULL DEFAULT 0,
    status           ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor_department FOREIGN KEY (department_id)
        REFERENCES departments(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: appointments
--   * user_id   -> users.id   (NULL for guest bookings; linked automatically
--                               when the patient registers/logs in with the
--                               same email address)
--   * doctor_id -> doctors.id (NULL only if the doctor was later deleted;
--                               doctor_name keeps a historical snapshot)
--   * slot_lock is 1 while the appointment holds its time slot and NULL once
--     it is Cancelled/Rejected. The UNIQUE key below therefore makes it
--     impossible (even under concurrent requests) to double-book a doctor,
--     while still allowing a freed slot to be booked again.
-- ---------------------------------------------------------------------
CREATE TABLE appointments (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference        VARCHAR(20)  NOT NULL UNIQUE,
    user_id          INT UNSIGNED NULL,
    doctor_id        INT UNSIGNED NULL,
    department_id    INT UNSIGNED NOT NULL,
    doctor_name      VARCHAR(100) NOT NULL,
    patient_name     VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL,
    phone            VARCHAR(30)  NOT NULL,
    age              TINYINT UNSIGNED NOT NULL,
    gender           ENUM('Male','Female','Other') NOT NULL,
    appointment_date DATE         NOT NULL,
    appointment_time TIME         NOT NULL,
    reason           TEXT         NOT NULL,
    status           ENUM('Pending','Confirmed','Completed','Cancelled','Rejected') NOT NULL DEFAULT 'Pending',
    admin_note       VARCHAR(255) NULL,
    slot_lock        TINYINT(1)   NULL DEFAULT 1,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_doctor_slot (doctor_id, appointment_date, appointment_time, slot_lock),
    KEY idx_status (status),
    KEY idx_email (email),
    KEY idx_date (appointment_date),
    CONSTRAINT fk_appt_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_appt_doctor FOREIGN KEY (doctor_id)
        REFERENCES doctors(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_appt_department FOREIGN KEY (department_id)
        REFERENCES departments(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: contact_messages  (submissions from the Contact page)
-- ---------------------------------------------------------------------
CREATE TABLE contact_messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    phone      VARCHAR(30)  NULL,
    subject    VARCHAR(150) NOT NULL,
    message    TEXT         NOT NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
--  DEMO DATA
-- =====================================================================

-- Admin: admin / Admin@123
INSERT INTO admin (username, full_name, email, password_hash) VALUES
('admin', 'Clinic Administrator', 'admin@carepointclinic.com',
 '$2y$10$LS2ZlqfJhhQg9N/ww2DnYuxwBhjENKPTgr/yFrjKQOKevx6FERUge');

-- Patients (all demo patients use password: Patient@123)
INSERT INTO users (full_name, email, phone, age, gender, password_hash) VALUES
('John Adebayo',   'patient@carepoint.com',      '+1 (555) 301-2290', 34, 'Male',
 '$2y$10$RlXrlNT2lC1fvtetRachMeNA5ilwDWtE8PcaL4TA79DJliORfpXlK'),
('Maria Gonzalez', 'maria.gonzalez@example.com', '+1 (555) 418-7720', 29, 'Female',
 '$2y$10$RlXrlNT2lC1fvtetRachMeNA5ilwDWtE8PcaL4TA79DJliORfpXlK'),
('Liam O''Connor',  'liam.oconnor@example.com',   '+1 (555) 562-0914', 47, 'Male',
 '$2y$10$RlXrlNT2lC1fvtetRachMeNA5ilwDWtE8PcaL4TA79DJliORfpXlK');

-- Sample profile details for the main demo patient (shown on profile.php)
UPDATE users SET date_of_birth = '1992-03-14', blood_group = 'O+',
    emergency_name = 'Grace Adebayo', emergency_relation = 'Spouse', emergency_phone = '+1 (555) 301-7784'
WHERE email = 'patient@carepoint.com';

-- Departments / Medical services
INSERT INTO departments (id, name, slug, icon, short_description, description, features) VALUES
(1, 'General Consultation', 'general-consultation', 'fa-user-doctor',
 'Comprehensive check-ups, diagnosis and treatment for everyday health concerns.',
 'Our family physicians are your first point of contact for any health concern. From routine check-ups and chronic disease management to acute illnesses and preventive screening, we provide thorough, unhurried consultations and coordinate specialist referrals when you need them.',
 'Annual health check-ups|Chronic disease management|Vaccinations & travel health|Specialist referrals'),
(2, 'Dental Care', 'dental-care', 'fa-tooth',
 'Preventive, restorative and cosmetic dentistry for healthy, confident smiles.',
 'Our dental team offers gentle, modern dental care for the whole family. We focus on prevention first, with professional cleaning and early detection, and provide fillings, root canal therapy, crowns, whitening and orthodontic assessment in a calm, comfortable environment.',
 'Scaling & polishing|Fillings & root canals|Teeth whitening|Orthodontic assessment'),
(3, 'Cardiology', 'cardiology', 'fa-heart-pulse',
 'Expert diagnosis and management of heart and blood vessel conditions.',
 'Our cardiologists assess and treat conditions such as high blood pressure, chest pain, palpitations, heart failure and high cholesterol. On-site ECG, echocardiography and stress testing allow fast, accurate diagnosis and a personalised plan to protect your heart.',
 'ECG & echocardiography|Hypertension clinic|Cardiac risk assessment|Stress testing'),
(4, 'Paediatrics', 'paediatrics', 'fa-baby',
 'Caring, child-friendly healthcare from newborns through adolescence.',
 'Our paediatricians look after children from birth to 18 years. We provide well-baby checks, growth and developmental monitoring, childhood immunisations and treatment of childhood illnesses, all in a warm setting designed to put young patients and parents at ease.',
 'Newborn & well-baby checks|Childhood immunisations|Growth & development tracking|Allergy & asthma care'),
(5, 'Dermatology', 'dermatology', 'fa-hand-dots',
 'Medical and cosmetic care for skin, hair and nail conditions.',
 'Our dermatology service diagnoses and treats acne, eczema, psoriasis, fungal infections, hair loss and pigmentation disorders. We also offer mole mapping and skin cancer screening to catch problems early, plus evidence-based cosmetic skin treatments.',
 'Acne & eczema treatment|Mole & skin cancer screening|Hair & scalp disorders|Cosmetic dermatology'),
(6, 'Eye Care', 'eye-care', 'fa-eye',
 'Complete eye examinations, vision correction and eye disease management.',
 'From routine eye tests and prescription glasses to the management of glaucoma, cataracts and diabetic eye disease, our eye care specialists use modern diagnostic equipment to protect and preserve your vision at every stage of life.',
 'Comprehensive eye exams|Glasses & contact lens fitting|Glaucoma & cataract screening|Diabetic eye checks'),
(7, 'Laboratory Services', 'laboratory-services', 'fa-flask-vial',
 'Accurate, fast diagnostic testing with results you can trust.',
 'Our accredited in-house laboratory performs a wide range of blood, urine and specialised tests with quick turnaround times. Book a sample collection slot, and your results are reviewed by our pathologist and shared securely with your doctor.',
 'Full blood count & chemistry|Lipid & diabetes panels|Hormone & thyroid tests|Rapid turnaround results');

-- Doctors
INSERT INTO doctors (id, department_id, full_name, specialisation, qualification, experience_years, bio, photo, email, phone, available_days, start_time, end_time, slot_duration, consultation_fee, is_featured) VALUES
(1, 1, 'Dr. Sarah Mitchell', 'Family Physician', 'MBBS, MRCGP', 14,
 'Dr. Mitchell has over 14 years of experience in family medicine with a special interest in women''s health and chronic disease management. She is known for her calm, attentive approach and for taking the time to explain every treatment option.',
 'sarah-mitchell.jpg', 'sarah.mitchell@carepointclinic.com', '+1 (555) 240-7701', 'Mon,Tue,Wed,Thu,Fri', '08:30:00', '16:30:00', 30, 50.00, 1),
(2, 3, 'Dr. Arjun Mehta', 'Consultant Cardiologist', 'MD, DM (Cardiology), FACC', 12,
 'Dr. Mehta specialises in preventive cardiology, hypertension and heart rhythm disorders. He trained at leading cardiac centres and is passionate about helping patients reduce their long-term cardiovascular risk through lifestyle and evidence-based treatment.',
 'arjun-mehta.jpg', 'arjun.mehta@carepointclinic.com', '+1 (555) 240-7702', 'Mon,Wed,Fri', '09:00:00', '15:00:00', 30, 90.00, 1),
(3, 5, 'Dr. Elena Rossi', 'Consultant Dermatologist', 'MD, FAAD', 10,
 'Dr. Rossi treats a wide range of skin, hair and nail conditions and leads our skin cancer screening programme. She combines medical dermatology with gentle cosmetic treatments to help patients feel comfortable in their skin.',
 'elena-rossi.jpg', 'elena.rossi@carepointclinic.com', '+1 (555) 240-7703', 'Tue,Thu,Sat', '09:00:00', '14:00:00', 30, 70.00, 1),
(4, 4, 'Dr. Daniel Okafor', 'Paediatrician', 'MBBS, FMCPaed', 9,
 'Dr. Okafor has a special interest in childhood asthma, allergies and developmental milestones. Children love his friendly, playful manner, and parents appreciate his clear, practical advice.',
 'daniel-okafor.jpg', 'daniel.okafor@carepointclinic.com', '+1 (555) 240-7704', 'Mon,Tue,Wed,Thu,Fri', '09:00:00', '17:00:00', 30, 60.00, 1),
(5, 3, 'Dr. Amara Nwosu', 'Interventional Cardiologist', 'MBBS, MRCP, CCT (Cardiology)', 15,
 'Dr. Nwosu is an experienced cardiologist focusing on coronary artery disease, heart failure and cardiac imaging. She leads our echocardiography service and is dedicated to compassionate, patient-centred heart care.',
 'amara-nwosu.jpg', 'amara.nwosu@carepointclinic.com', '+1 (555) 240-7705', 'Tue,Thu', '10:00:00', '16:00:00', 30, 95.00, 0),
(6, 7, 'Dr. Omar Haddad', 'Clinical Pathologist', 'MBBS, FRCPath', 11,
 'Dr. Haddad oversees our in-house laboratory, ensuring every test meets strict quality standards. He reviews complex results and works closely with our doctors so that patients get accurate diagnoses quickly.',
 'omar-haddad.jpg', 'omar.haddad@carepointclinic.com', '+1 (555) 240-7706', 'Mon,Tue,Wed,Thu,Fri,Sat', '08:00:00', '13:00:00', 20, 25.00, 0),
(7, 2, 'Dr. Grace Chen', 'Dental Surgeon', 'BDS, MFDS RCS', 8,
 'Dr. Chen provides comprehensive dental care with a focus on preventive dentistry and pain-free restorative treatment. She is especially skilled at helping anxious patients feel relaxed in the dental chair.',
 'grace-chen.jpg', 'grace.chen@carepointclinic.com', '+1 (555) 240-7707', 'Mon,Wed,Thu,Fri,Sat', '09:00:00', '17:00:00', 30, 55.00, 1),
(8, 6, 'Dr. James Whitaker', 'Ophthalmologist', 'MD, FRCOphth', 18,
 'Dr. Whitaker has nearly two decades of experience in medical and surgical eye care, including cataract assessment, glaucoma management and diabetic retinopathy screening.',
 NULL, 'james.whitaker@carepointclinic.com', '+1 (555) 240-7708', 'Mon,Tue,Thu,Fri', '09:00:00', '16:00:00', 30, 75.00, 0),
(9, 1, 'Dr. Michael Adeyemi', 'General Practitioner', 'MBBS, MPH', 7,
 'Dr. Adeyemi provides friendly, thorough general consultations with a focus on preventive health, men''s health and lifestyle medicine. He also runs our travel health and vaccination clinic.',
 NULL, 'michael.adeyemi@carepointclinic.com', '+1 (555) 240-7709', 'Tue,Wed,Thu,Fri,Sat', '10:00:00', '18:00:00', 30, 50.00, 0);

-- Appointments (dates are relative to the day you import the file)
INSERT INTO appointments (reference, user_id, doctor_id, department_id, doctor_name, patient_name, email, phone, age, gender, appointment_date, appointment_time, reason, status, admin_note, slot_lock) VALUES
('CP-7K2M9Q', 1, 1, 1, 'Dr. Sarah Mitchell', 'John Adebayo', 'patient@carepoint.com', '+1 (555) 301-2290', 34, 'Male',
 CURDATE() - INTERVAL 21 DAY, '10:00:00', 'Annual health check-up and blood pressure review.', 'Completed', 'Routine check completed. Follow-up in 12 months.', 1),
('CP-4H8TXR', 1, 2, 3, 'Dr. Arjun Mehta', 'John Adebayo', 'patient@carepoint.com', '+1 (555) 301-2290', 34, 'Male',
 CURDATE() + INTERVAL 3 DAY, '11:00:00', 'Occasional chest tightness after exercise; referred by GP for cardiac assessment.', 'Confirmed', 'Please arrive 15 minutes early for an ECG.', 1),
('CP-9PL3VD', 1, 7, 2, 'Dr. Grace Chen', 'John Adebayo', 'patient@carepoint.com', '+1 (555) 301-2290', 34, 'Male',
 CURDATE() + INTERVAL 8 DAY, '14:30:00', 'Sensitivity in lower left molar when drinking cold drinks.', 'Pending', NULL, 1),
('CP-2WQ6NB', 2, 3, 5, 'Dr. Elena Rossi', 'Maria Gonzalez', 'maria.gonzalez@example.com', '+1 (555) 418-7720', 29, 'Female',
 CURDATE() + INTERVAL 2 DAY, '09:30:00', 'Persistent eczema on both hands that is not improving with over-the-counter creams.', 'Pending', NULL, 1),
('CP-6RJ8EZ', 2, 4, 4, 'Dr. Daniel Okafor', 'Sofia Gonzalez', 'maria.gonzalez@example.com', '+1 (555) 418-7720', 4, 'Female',
 CURDATE() + INTERVAL 5 DAY, '10:30:00', 'Four-year check-up and pre-school immunisations for my daughter.', 'Confirmed', NULL, 1),
('CP-3TN5KC', 3, 5, 3, 'Dr. Amara Nwosu', 'Liam O''Connor', 'liam.oconnor@example.com', '+1 (555) 562-0914', 47, 'Male',
 CURDATE() + INTERVAL 6 DAY, '13:00:00', 'Follow-up on echocardiogram results and review of cholesterol medication.', 'Pending', NULL, 1),
('CP-8BV4LS', 3, 6, 7, 'Dr. Omar Haddad', 'Liam O''Connor', 'liam.oconnor@example.com', '+1 (555) 562-0914', 47, 'Male',
 CURDATE() - INTERVAL 4 DAY, '08:20:00', 'Fasting lipid profile and HbA1c blood tests.', 'Completed', 'Results sent to Dr. Nwosu.', 1),
('CP-5YC7HU', NULL, 8, 6, 'Dr. James Whitaker', 'Aisha Bello', 'aisha.bello@example.com', '+1 (555) 733-4108', 61, 'Female',
 CURDATE() + INTERVAL 4 DAY, '11:30:00', 'Blurred vision when reading; diabetic eye screening due.', 'Pending', NULL, 1),
('CP-1MD9GA', NULL, 9, 1, 'Dr. Michael Adeyemi', 'Ethan Brooks', 'ethan.brooks@example.com', '+1 (555) 284-9963', 23, 'Male',
 CURDATE() + INTERVAL 1 DAY, '15:00:00', 'Travel vaccinations before a trip to East Africa next month.', 'Confirmed', NULL, 1),
('CP-0XE2PW', NULL, 1, 1, 'Dr. Sarah Mitchell', 'Hannah Lee', 'hannah.lee@example.com', '+1 (555) 610-2275', 38, 'Female',
 CURDATE() + INTERVAL 2 DAY, '09:00:00', 'Recurring migraines over the last two months.', 'Cancelled', 'Cancelled by patient.', NULL),
('CP-6FA3QM', NULL, 2, 3, 'Dr. Arjun Mehta', 'Robert King', 'robert.king@example.com', '+1 (555) 845-3301', 66, 'Male',
 CURDATE() + INTERVAL 7 DAY, '09:30:00', 'Second opinion on pacemaker recommendation.', 'Rejected', 'Please book with our pacemaker clinic at the partner hospital.', NULL);

-- Contact messages
INSERT INTO contact_messages (name, email, phone, subject, message, is_read) VALUES
('Peter Novak', 'peter.novak@example.com', '+1 (555) 902-1184', 'Insurance question',
 'Hello, do you accept HealthFirst insurance plans for specialist consultations? Thank you.', 0),
('Grace Thompson', 'grace.t@example.com', NULL, 'Wheelchair access',
 'Is the clinic wheelchair accessible and is there disabled parking available near the entrance?', 1);

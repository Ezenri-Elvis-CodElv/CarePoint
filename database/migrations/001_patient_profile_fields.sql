-- Adds the patient profile fields used by profile.php.
-- Only needed for databases imported before these columns existed;
-- a fresh import of carepoint_db.sql already includes them.
USE carepoint_db;

ALTER TABLE users
    ADD COLUMN date_of_birth      DATE NULL AFTER gender,
    ADD COLUMN blood_group        ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL AFTER date_of_birth,
    ADD COLUMN emergency_name     VARCHAR(100) NULL AFTER blood_group,
    ADD COLUMN emergency_relation VARCHAR(50)  NULL AFTER emergency_name,
    ADD COLUMN emergency_phone    VARCHAR(30)  NULL AFTER emergency_relation;

UPDATE users SET date_of_birth = '1992-03-14', blood_group = 'O+',
    emergency_name = 'Grace Adebayo', emergency_relation = 'Spouse', emergency_phone = '+1 (555) 301-7784'
WHERE email = 'patient@carepoint.com';

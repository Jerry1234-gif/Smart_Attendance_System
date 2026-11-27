-- Smart Attendance System upgrade: enforce Limkokwing student ID prefix 90500.
-- Run this ONCE on an already-installed database, using phpMyAdmin > SQL.
-- First check for existing student IDs that do not match the new rule:
-- SELECT id, full_name, student_number
-- FROM users
-- WHERE role = 'student'
--   AND (student_number IS NULL OR student_number NOT REGEXP '^90500[0-9]+$');
--
-- Correct any invalid records using the real student IDs supplied by the university
-- before running the ALTER TABLE statement below. Do not guess or invent IDs.

ALTER TABLE users
    ADD CONSTRAINT chk_users_student_number_prefix
    CHECK (student_number IS NULL OR student_number REGEXP '^90500[0-9]+$');

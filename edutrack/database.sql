-- ============================================
--  EduTrack Database Schema
--  Run this in phpMyAdmin or MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS edutrack_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE edutrack_db;

-- ── Students ──────────────────────────────
CREATE TABLE IF NOT EXISTS students (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  VARCHAR(20)  NOT NULL UNIQUE,
    firstname   VARCHAR(80)  NOT NULL,
    lastname    VARCHAR(80)  NOT NULL,
    course      VARCHAR(100) NOT NULL,
    year_level  VARCHAR(20)  NOT NULL,
    email       VARCHAR(120),
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── Subjects ──────────────────────────────
CREATE TABLE IF NOT EXISTS subjects (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20)  NOT NULL UNIQUE,
    subject_name VARCHAR(120) NOT NULL
);

-- ── Grades ────────────────────────────────
CREATE TABLE IF NOT EXISTS grades (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT            NOT NULL,
    subject_id INT            NOT NULL,
    grade      DECIMAL(5, 2)  NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_grade (student_id, subject_id)   -- one grade per subject per student
);

-- ── Seed: Default Subjects ─────────────────
INSERT IGNORE INTO subjects (subject_code, subject_name) VALUES
    ('MATH101', 'Mathematics'),
    ('ENG101',  'English'),
    ('SCI101',  'Science'),
    ('FIL101',  'Filipino'),
    ('PE101',   'Physical Education'),
    ('ICT101',  'Information Technology'),
    ('SOC101',  'Social Studies'),
    ('VAL101',  'Values Education');

-- ── Seed: Sample Students ──────────────────
INSERT IGNORE INTO students (student_id, firstname, lastname, course, year_level, email) VALUES
    ('2024-0001', 'Maria',   'Santos',    'BS Computer Science', '2nd Year', 'maria.santos@edu.ph'),
    ('2024-0002', 'Juan',    'dela Cruz', 'BS Information Technology', '1st Year', 'juan.delacruz@edu.ph'),
    ('2024-0003', 'Ana',     'Reyes',     'BS Computer Science', '3rd Year', 'ana.reyes@edu.ph'),
    ('2024-0004', 'Carlos',  'Garcia',    'BS Information Systems', '2nd Year', 'carlos.garcia@edu.ph'),
    ('2024-0005', 'Liza',    'Mendoza',   'BS Computer Science', '1st Year', 'liza.mendoza@edu.ph');

-- ── Seed: Sample Grades ────────────────────
INSERT IGNORE INTO grades (student_id, subject_id, grade) VALUES
    (1, 1, 88.50), (1, 2, 92.00), (1, 3, 85.75), (1, 4, 90.00),
    (2, 1, 76.00), (2, 2, 80.50), (2, 3, 78.25), (2, 4, 82.00),
    (3, 1, 95.00), (3, 2, 97.50), (3, 3, 93.00), (3, 4, 96.00),
    (4, 1, 70.00), (4, 2, 72.50), (4, 3, 68.00), (4, 4, 74.00),
    (5, 1, 84.00), (5, 2, 88.00), (5, 3, 81.50), (5, 4, 86.00);

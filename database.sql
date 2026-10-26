-- ==========================================================
-- 1. CREATE AND SELECT DATABASE
-- ==========================================================
DROP DATABASE IF EXISTS cuyotech_ssis_db;
CREATE DATABASE IF NOT EXISTS cuyotech_ssis_db;
USE cuyotech_ssis_db;

-- ==========================================================
-- 2. CREATE TABLES
-- ==========================================================

CREATE TABLE IF NOT EXISTS accounts (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(254) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    account_role VARCHAR(10) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_code VARCHAR(20) NOT NULL UNIQUE,
    department_name VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS academic_terms (
    term_id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(9) NOT NULL,
    term_code VARCHAR(20) NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    UNIQUE (academic_year, term_code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL UNIQUE,
    department_id INT NOT NULL,
    student_number VARCHAR(30) NOT NULL UNIQUE,
    first_name VARCHAR(200) NOT NULL,
    last_name VARCHAR(200) NOT NULL,
    academic_status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
    FOREIGN KEY (account_id) REFERENCES accounts(account_id),
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS staff_users (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL UNIQUE,
    department_id INT NULL,
    first_name VARCHAR(200) NOT NULL,
    last_name VARCHAR(200) NOT NULL,
    staff_role VARCHAR(20) NOT NULL,
    access_scope VARCHAR(20) NOT NULL DEFAULT 'UNIVERSITY',
    FOREIGN KEY (account_id) REFERENCES accounts(account_id),
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    course_code VARCHAR(30) NOT NULL UNIQUE,
    course_title VARCHAR(180) NOT NULL,
    units DECIMAL(6,2) NULL,
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(30) NOT NULL UNIQUE,
    subject_title VARCHAR(180) NOT NULL,
    units DECIMAL(4,2) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    term_id INT NOT NULL,
    enrollment_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    processed_by_staff_id INT NULL,
    enrolled_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (student_id, term_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (term_id) REFERENCES academic_terms(term_id),
    FOREIGN KEY (processed_by_staff_id) REFERENCES staff_users(staff_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enrollment_subjects (
    enrollment_subject_id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    subject_id INT NOT NULL,
    subject_status VARCHAR(20) NOT NULL DEFAULT 'ENROLLED',
    UNIQUE (enrollment_id, subject_id),
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(enrollment_id),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grades (
    enrollment_subject_id INT PRIMARY KEY,
    grade_value VARCHAR(12) NOT NULL,
    remarks VARCHAR(500) NULL,
    encoded_by_staff_id INT NOT NULL,
    encoded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_staff_id INT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_subject_id) REFERENCES enrollment_subjects(enrollment_subject_id),
    FOREIGN KEY (encoded_by_staff_id) REFERENCES staff_users(staff_id),
    FOREIGN KEY (updated_by_staff_id) REFERENCES staff_users(staff_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS document_types (
    document_type_id INT AUTO_INCREMENT PRIMARY KEY,
    document_name VARCHAR(120) NOT NULL UNIQUE,
    fee_amount DECIMAL(10,2) NOT NULL,
    clearance_required BOOLEAN NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clearance_records (
    clearance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    department_id INT NOT NULL,
    term_id INT NOT NULL,
    clearance_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    verified_by_staff_id INT NULL,
    verified_at DATETIME NULL,
    remarks VARCHAR(500) NULL,
    UNIQUE (student_id, department_id, term_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    FOREIGN KEY (term_id) REFERENCES academic_terms(term_id),
    FOREIGN KEY (verified_by_staff_id) REFERENCES staff_users(staff_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS document_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    document_type_id INT NOT NULL,
    term_id INT NOT NULL,
    clearance_department_id INT NULL,
    assessed_fee DECIMAL(10,2) NOT NULL,
    purpose VARCHAR(1000) NOT NULL,
    request_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    eligibility_status VARCHAR(20) NOT NULL DEFAULT 'UNCHECKED',
    reviewed_by_staff_id INT NULL,
    reviewed_at DATETIME NULL,
    rejection_reason VARCHAR(1000) NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ready_at DATETIME NULL,
    released_at DATETIME NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (document_type_id) REFERENCES document_types(document_type_id),
    FOREIGN KEY (term_id) REFERENCES academic_terms(term_id),
    FOREIGN KEY (clearance_department_id) REFERENCES departments(department_id),
    FOREIGN KEY (reviewed_by_staff_id) REFERENCES staff_users(staff_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_ref VARCHAR(100) NULL UNIQUE,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_by_staff_id INT NULL,
    verified_at DATETIME NULL,
    remarks VARCHAR(1000) NULL,
    FOREIGN KEY (request_id) REFERENCES document_requests(request_id),
    FOREIGN KEY (verified_by_staff_id) REFERENCES staff_users(staff_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS receipts (
    receipt_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL UNIQUE,
    receipt_number VARCHAR(60) NOT NULL UNIQUE,
    issued_by_staff_id INT NOT NULL,
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id),
    FOREIGN KEY (issued_by_staff_id) REFERENCES staff_users(staff_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS request_status_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    changed_by_staff_id INT NULL,
    old_status VARCHAR(20) NULL,
    new_status VARCHAR(20) NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    remarks VARCHAR(1000) NULL,
    FOREIGN KEY (request_id) REFERENCES document_requests(request_id),
    FOREIGN KEY (changed_by_staff_id) REFERENCES staff_users(staff_id)
) ENGINE=InnoDB;

-- ==========================================================
-- 3. SAMPLE DATA
-- ==========================================================

-- Departments, accounts, and academic terms

INSERT INTO departments (department_id, department_code, department_name)
VALUES (1, 'CCS', 'College of Computing Studies');

INSERT INTO accounts (account_id, email, password_hash, account_role, is_active)
VALUES (1, 'admin@cuyotech.edu', '$2y$10$JmxMjXj5AdjTBSkXlv868umxYjK4iajbFGOGNa6nxuqS9cOUn4Oia', 'STAFF', TRUE);

INSERT INTO accounts (account_id, email, password_hash, account_role, is_active)
VALUES (2, 'registrar@cuyotech.edu', '$2y$10$hx9XaYXZJoMb7kBelHvqZek7htsv9fAghfP.02Cr5O6J9YdVKr5GS', 'STAFF', TRUE);

INSERT INTO accounts (account_id, email, password_hash, account_role, is_active)
VALUES (3, 'cashier@cuyotech.edu', '$2y$10$omDQsafac2WlYbl3cHeXeug3oE54hv3ecLDP.rLCVcFkkfFyow9Wm', 'STAFF', TRUE);

INSERT INTO accounts (account_id, email, password_hash, account_role, is_active)
VALUES (4, 'department@cuyotech.edu', '$2y$10$Rfrm4qFtO83jd4PQOC8YZe7805VTOLHvyBDhPFIQF0fNl1DcidVrC', 'STAFF', TRUE);

INSERT INTO accounts (account_id, email, password_hash, account_role, is_active)
VALUES (5, 'student@cuyotech.edu', '$2y$10$tBGNxGIaEWEcnLcWTkBbzuz0kPfxrVc64JENX6Tt7HekeWohaZZFe', 'STUDENT', TRUE);

INSERT INTO accounts (account_id, email, password_hash, account_role, is_active)
VALUES (6, 'student2@cuyotech.edu', '$2y$10$tBGNxGIaEWEcnLcWTkBbzuz0kPfxrVc64JENX6Tt7HekeWohaZZFe', 'STUDENT', TRUE);

INSERT INTO academic_terms (term_id, academic_year, term_code, starts_on, ends_on)
VALUES (1, '2026-2027', '1S', '2026-08-01', '2026-12-31');

-- Students and staff users

INSERT INTO students (student_id, account_id, department_id, student_number, first_name, last_name)
VALUES (1, 5, 1, '2026-0001', 'Andrea', 'Villanueva');

INSERT INTO students (student_id, account_id, department_id, student_number, first_name, last_name)
VALUES (2, 6, 1, '2026-0002', 'Miguel', 'Navarro');

INSERT INTO staff_users (staff_id, account_id, department_id, first_name, last_name, staff_role, access_scope)
VALUES (1, 1, 1, 'Mariana', 'Reyes', 'ADMIN', 'UNIVERSITY');

INSERT INTO staff_users (staff_id, account_id, department_id, first_name, last_name, staff_role, access_scope)
VALUES (2, 2, 1, 'Gabriel', 'Santos', 'REGISTRAR', 'UNIVERSITY');

INSERT INTO staff_users (staff_id, account_id, department_id, first_name, last_name, staff_role, access_scope)
VALUES (3, 3, 1, 'Isabel', 'Cruz', 'CASHIER', 'UNIVERSITY');

INSERT INTO staff_users (staff_id, account_id, department_id, first_name, last_name, staff_role, access_scope)
VALUES (4, 4, 1, 'Rafael', 'Mendoza', 'DEPARTMENT', 'DEPARTMENT');

-- Courses and subjects

INSERT INTO courses (course_id, department_id, course_code, course_title, units)
VALUES (1, 1, 'BSCS', 'Bachelor of Science in Computer Science', NULL);

INSERT INTO subjects (subject_id, subject_code, subject_title, units)
VALUES (1, 'CCS109', 'Systems Analysis and Design', 3.00);

INSERT INTO subjects (subject_id, subject_code, subject_title, units)
VALUES (2, 'DB101', 'Database Management', 3.00);

INSERT INTO subjects (subject_id, subject_code, subject_title, units)
VALUES (3, 'ITP107', 'Mobile Application Development 1', 3.00);

-- Enrollments and enrolled subjects

INSERT INTO enrollments (enrollment_id, student_id, course_id, term_id, enrollment_status, processed_by_staff_id)
VALUES (1, 1, 1, 1, 'ENROLLED', 2);

INSERT INTO enrollments (enrollment_id, student_id, course_id, term_id, enrollment_status, processed_by_staff_id)
VALUES (2, 2, 1, 1, 'PENDING', NULL);

INSERT INTO enrollment_subjects (enrollment_subject_id, enrollment_id, subject_id)
VALUES (1, 1, 1);

INSERT INTO enrollment_subjects (enrollment_subject_id, enrollment_id, subject_id)
VALUES (2, 1, 2);

INSERT INTO enrollment_subjects (enrollment_subject_id, enrollment_id, subject_id)
VALUES (3, 1, 3);

-- Grades (two encoded grades; third subject remains ungraded)

INSERT INTO grades (enrollment_subject_id, grade_value, remarks, encoded_by_staff_id, updated_by_staff_id)
VALUES (1, '1.75', 'Recorded from approved grade sheet', 2, 2);

INSERT INTO grades (enrollment_subject_id, grade_value, remarks, encoded_by_staff_id, updated_by_staff_id)
VALUES (2, '1.75', 'Recorded from approved grade sheet', 2, 2);

-- Clearance records

INSERT INTO clearance_records
    (clearance_id, student_id, department_id, term_id, clearance_status, verified_by_staff_id, verified_at, remarks)
VALUES
    (1, 1, 1, 1, 'CLEARED', 4, UTC_TIMESTAMP(), 'Department requirements complete');

INSERT INTO clearance_records (clearance_id, student_id, department_id, term_id)
VALUES (2, 2, 1, 1);

-- Document types and document requests

INSERT INTO document_types (document_type_id, document_name, fee_amount, clearance_required)
VALUES (1, 'Transcript of Records (TOR)', 150.00, TRUE);

INSERT INTO document_types (document_type_id, document_name, fee_amount, clearance_required)
VALUES (2, 'Certificate of Registration (COR)', 0.00, FALSE);

INSERT INTO document_types (document_type_id, document_name, fee_amount, clearance_required)
VALUES (3, 'Certification', 50.00, TRUE);

INSERT INTO document_requests
    (request_id, student_id, document_type_id, term_id, clearance_department_id,
     assessed_fee, purpose, request_status, eligibility_status)
VALUES
    (1, 2, 1, 1, 1, 150.00, 'Scholarship application', 'PENDING', 'UNCHECKED');

INSERT INTO document_requests
    (request_id, student_id, document_type_id, term_id, clearance_department_id,
     assessed_fee, purpose, request_status, eligibility_status, reviewed_by_staff_id, reviewed_at)
VALUES
    (2, 1, 1, 1, 1, 150.00, 'Graduate school application', 'AWAITING_PAYMENT',
     'ELIGIBLE', 2, UTC_TIMESTAMP());

INSERT INTO document_requests
    (request_id, student_id, document_type_id, term_id, clearance_department_id,
     assessed_fee, purpose, request_status, eligibility_status, reviewed_by_staff_id,
     reviewed_at, ready_at, released_at)
VALUES
    (3, 1, 3, 1, 1, 50.00, 'Employment requirements', 'RELEASED',
     'ELIGIBLE', 2, UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP());

-- Payments and receipt

INSERT INTO payments
    (payment_id, request_id, amount, transaction_ref, payment_status, remarks)
VALUES
    (1, 2, 150.00, 'CTU-2026-0001', 'PENDING', 'Payment reference recorded');

INSERT INTO payments
    (payment_id, request_id, amount, transaction_ref, payment_status,
     verified_by_staff_id, verified_at, remarks)
VALUES
    (2, 3, 50.00, 'CTU-2026-0002', 'VERIFIED', 3,
     UTC_TIMESTAMP(), 'Payment verified against cashier records');

INSERT INTO receipts (receipt_id, payment_id, receipt_number, issued_by_staff_id)
VALUES (1, 2, 'CTU-OR-2026-0001', 3);

-- Request-status history

INSERT INTO request_status_events
    (event_id, request_id, changed_by_staff_id, old_status, new_status, remarks)
VALUES
    (1, 1, NULL, NULL, 'PENDING', 'Request status recorded at initial import');

INSERT INTO request_status_events
    (event_id, request_id, changed_by_staff_id, old_status, new_status, remarks)
VALUES
    (2, 2, 2, NULL, 'AWAITING_PAYMENT', 'Request status recorded at initial import');

INSERT INTO request_status_events
    (event_id, request_id, changed_by_staff_id, old_status, new_status, remarks)
VALUES
    (3, 3, 2, NULL, 'RELEASED', 'Request status recorded at initial import');

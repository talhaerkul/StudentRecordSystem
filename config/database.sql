-- Create database
CREATE DATABASE IF NOT EXISTS student_record_system;
USE student_record_system;
ALTER DATABASE `student_record_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create roles table for user roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255)
);

-- Create faculties table
CREATE TABLE IF NOT EXISTS faculties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    dean VARCHAR(100),
    established_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
);

-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    faculty VARCHAR(100) NOT NULL,
    FOREIGN KEY (faculty_id) REFERENCES faculties(id)
);

-- Create scholarships table
CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL
);

-- Create users table (unified model)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    surname VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    department_id INT,
    student_id VARCHAR(20) UNIQUE,
    phone VARCHAR(20),
    title VARCHAR(100),
    specialization TEXT,
    birthdate DATE,
    address TEXT,
    advisor_id INT,
    scholarship_id INT,
    entry_year INT,
    office_number VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended', 'graduated') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    
    -- Role flags
    is_teacher BOOLEAN DEFAULT FALSE,
    is_student BOOLEAN DEFAULT FALSE,
    
    -- Foreign key constraints
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (advisor_id) REFERENCES users(id),
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id)
);

-- Create terms table
CREATE TABLE IF NOT EXISTS terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE
);

-- Create marks table
CREATE TABLE IF NOT EXISTS marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    point DECIMAL(5,2) NOT NULL
);

-- Create courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    credit INT NOT NULL,
    department_id INT,
    teacher_id INT,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (teacher_id) REFERENCES users(id)
);

-- Create student_courses table with term reference
CREATE TABLE IF NOT EXISTS student_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    term_id INT NOT NULL,
    mark_id INT,
    grade DECIMAL(5,2),
    status ENUM('enrolled', 'completed', 'dropped', 'failed') DEFAULT 'enrolled',
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
    FOREIGN KEY (mark_id) REFERENCES marks(id) ON DELETE SET NULL
);

-- Create teacher_courses table
CREATE TABLE IF NOT EXISTS teacher_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    course_id INT NOT NULL,
    term_id INT NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE
);

-- Insert sample data for roles
INSERT INTO roles (name, description) VALUES
('admin', 'System Administrator'),
('student_affairs', 'Student Affairs Personnel'),
('teacher', 'Academic Staff'),
('student', 'Student');

-- Insert sample data for faculties
INSERT INTO faculties (name, code, dean, established_date) VALUES
('Engineering Faculty', 'ENG', 'Prof. Dr. Ahmet Yılmaz', '1990-09-01'),
('Faculty of Arts and Sciences', 'FAS', 'Prof. Dr. Mehmet Demir', '1992-09-01'),
('Faculty of Economics and Administrative Sciences', 'FEAS', 'Prof. Dr. Ayşe Kaya', '1995-09-01'),
('Faculty of Medicine', 'MED', 'Prof. Dr. Ali Öztürk', '2000-09-01');

-- Insert sample data for departments
INSERT INTO departments (faculty_id, name, code, faculty) VALUES
(1, 'Computer Engineering', 'CENG', 'Engineering Faculty'),
(1, 'Electrical Engineering', 'EE', 'Engineering Faculty'),
(3, 'Business Administration', 'BA', 'Faculty of Economics and Administrative Sciences'),
(2, 'Psychology', 'PSY', 'Faculty of Arts and Sciences');

-- Insert sample data for scholarships
INSERT INTO scholarships (name, amount) VALUES
('Full Scholarship', 10000.00),
('Half Scholarship', 5000.00),
('Quarter Scholarship', 2500.00),
('No Scholarship', 0.00);

-- Insert sample data for terms
INSERT INTO terms (name, start_date, end_date, is_current) VALUES
('Fall 2023', '2023-09-01', '2024-01-15', FALSE),
('Spring 2024', '2024-02-01', '2024-06-15', TRUE);

-- Insert sample data for marks
INSERT INTO marks (name, point) VALUES
('AA', 4.00),
('BA', 3.50),
('BB', 3.00),
('CB', 2.50),
('CC', 2.00),
('DC', 1.50),
('DD', 1.00),
('FF', 0.00);

-- Insert sample admin user (password: admin123)
INSERT INTO users (name, surname, email, password, role_id, status) VALUES
('Admin', 'User', 'admin@okan.edu.tr', '$2y$10$8MNE.3Z6hU6oCT1JgZJHIeKB8S9Uc0yQKZ/7r4hLHDiNFGIi9EVlK', 1, 'active');

-- Insert sample teacher user (password: teacher123)
INSERT INTO users (name, surname, email, password, role_id, department_id, title, office_number, phone, is_teacher, status) VALUES
('Ahmet', 'Yılmaz', 'teacher@okan.edu.tr', '$2y$10$LQvfU0nrc7KWdMvPp/1zFe2KYD0mSJl7J8UrONRMF7GafHqg4QwFG', 3, 1, 'Doç. Dr.', 'B-203', '555-123-4567', TRUE, 'active');

-- Insert sample student user (password: student123)
INSERT INTO users (name, surname, email, password, role_id, department_id, student_id, scholarship_id, entry_year, is_student, status) VALUES
('Mehmet', 'Demir', 'student@stu.okan.edu.tr', '$2y$10$Iy.uShLfeoxRiLJrLmcFZ.VWB3.LyUALo0zHlVzLYKRm5lhLzh0EK', 4, 1, '2023001', 2, 2023, TRUE, 'active');

-- Insert sample courses
INSERT INTO courses (name, code, credit, department_id, teacher_id) VALUES
('Introduction to Programming', 'CENG101', 4, 1, 2),
('Data Structures', 'CENG201', 4, 1, 2),
('Database Management Systems', 'CENG301', 3, 1, 2);

-- Insert sample teacher course assignments
INSERT INTO teacher_courses (teacher_id, course_id, term_id) VALUES
(2, 1, 1),
(2, 2, 2),
(2, 3, 2);

-- Insert sample student course registrations
INSERT INTO student_courses (student_id, course_id, term_id, status) VALUES
(3, 1, 1, 'completed'),
(3, 2, 2, 'enrolled');

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    user_id INT NOT NULL,
    role_id INT,
    department_id INT,
    course_id INT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    
    -- Index for faster queries
    INDEX (start_date, end_date),
    INDEX (status),
    INDEX (role_id),
    INDEX (department_id),
    INDEX (course_id)
);
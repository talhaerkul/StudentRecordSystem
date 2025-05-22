-- Drop tables in reverse order to avoid foreign key constraints
DROP TABLE IF EXISTS student_courses;
DROP TABLE IF EXISTS student_course_requests;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS course_schedule;
DROP TABLE IF EXISTS course_grade_scales;
DROP TABLE IF EXISTS teacher_courses;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS terms;
DROP TABLE IF EXISTS scholarships;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS roles;

-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create scholarships table
CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00",
    updated_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" on update now()
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    surname VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
    role_id INT NOT NULL,
    department_id INT,
    student_id VARCHAR(20) UNIQUE,
    phone VARCHAR(20),
    advisor_id INT NULL,
    scholarship_id INT NULL,
    title VARCHAR(100) NULL,
    specialization VARCHAR(100) NULL,
    birthdate DATE NULL,
    address TEXT NULL,
    entry_year INT NULL,
    is_teacher BOOLEAN DEFAULT FALSE,
    is_student BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00",
    updated_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" on update now(),
    last_login DATETIME DEFAULT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add advisor_id foreign key after users table is created
ALTER TABLE users
ADD FOREIGN KEY (advisor_id) REFERENCES users(id) ON DELETE SET NULL;

-- Create terms table
CREATE TABLE IF NOT EXISTS terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00",
    updated_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" on update now(),
    course_selection_start DATETIME NULL,
    course_selection_end DATETIME NULL,
    is_course_selection_active TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    code VARCHAR(20) NOT NULL UNIQUE,
    credit INT NOT NULL,
    department_id INT,
    year TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=1. year, 2=2. year, etc.',
    hours_per_week INT NOT NULL DEFAULT 2,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00",
    updated_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" on update now(),
    FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create teacher_courses table
CREATE TABLE IF NOT EXISTS teacher_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    course_id INT NOT NULL,
    term_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00",
    updated_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" on update now(),
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_course_term (teacher_id, course_id, term_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create student_courses table
CREATE TABLE IF NOT EXISTS student_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    term_id INT NOT NULL,
    grade DECIMAL(5,2) DEFAULT NULL,
    status ENUM('enrolled', 'completed', 'dropped', 'failed') DEFAULT 'enrolled',
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_course_term (student_id, course_id, term_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create course_grade_scales table
CREATE TABLE IF NOT EXISTS course_grade_scales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    term_id INT NOT NULL,
    letter VARCHAR(2) NOT NULL,
    min_grade DECIMAL(5,2) NOT NULL,
    max_grade DECIMAL(5,2) NOT NULL,
    grade_point DECIMAL(3,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00",
    updated_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" on update now(),
    UNIQUE KEY course_teacher_term_letter (course_id, teacher_id, term_id, letter),
    KEY course_id (course_id),
    KEY teacher_id (teacher_id),
    KEY term_id (term_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create course_schedule table
CREATE TABLE IF NOT EXISTS course_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    teacher_id INT NOT NULL,
    term_id INT NOT NULL,
    day_of_week TINYINT(1) NOT NULL COMMENT '1=Monday, 2=Tuesday, etc.',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    classroom VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00",
    updated_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" on update now(),
    KEY fk_schedule_course_idx (course_id),
    KEY fk_schedule_teacher_idx (teacher_id),
    KEY fk_schedule_term_idx (term_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create announcements table
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
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00",
    updated_at TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" on update now(),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create password_reset_tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
    UNIQUE KEY user_id (user_id),
    UNIQUE KEY token (token),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create student_course_requests table
CREATE TABLE IF NOT EXISTS student_course_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    term_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    processed_by INT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_student_course_term_request (student_id, course_id, term_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 

-- Insert default roles
INSERT INTO roles (name, description) VALUES
('admin', 'Sistem Yöneticisi'),
('teacher', 'Akademik Personel'),
('student', 'Öğrenci');

-- Insert sample departments
INSERT IGNORE INTO departments (name, code) VALUES
('Bilgisayar Mühendisliği', 'CENG'),
('Elektrik Mühendisliği', 'EE'),
('Makine Mühendisliği', 'ME'),
('Endüstri Mühendisliği', 'IE'),
('İşletme', 'BA');

-- Insert sample scholarships
INSERT INTO scholarships (name, description, amount) VALUES
('Başarı Bursu', 'Yüksek akademik başarı gösteren öğrencilere verilen burs', 1000.00),
('İhtiyaç Bursu', 'Maddi ihtiyacı olan öğrencilere verilen burs', 800.00),
('Spor Bursu', 'Spor alanında başarılı olan öğrencilere verilen burs', 500.00),
('Sanat Bursu', 'Sanat alanında başarılı olan öğrencilere verilen burs', 500.00),
('Burs Yok', 'Burs almayan öğrenciler için seçenek', 0.00);

-- Insert sample terms
INSERT INTO terms (name, start_date, end_date, is_current) VALUES
('Güz 2022', '2022-09-01', '2023-01-15', 0),
('Bahar 2023', '2023-02-01', '2023-06-15', 0),
('Güz 2023', '2023-09-01', '2024-01-15', 0),
('Bahar 2024', '2024-02-01', '2024-06-15', 1);

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO users (name, surname, email, password, role_id, department_id, status, is_teacher, is_student) VALUES
('Admin', 'Kullanıcı', 'admin@uni.edu.tr', 'admin123', 1, 1, 'active', 0, 0);

-- Insert sample teachers (password: teacher123)
INSERT INTO users (name, surname, email, password, role_id, department_id, status, title, specialization, is_teacher, is_student) VALUES
('Ahmet', 'Yılmaz', 'ahmet.yilmaz@uni.edu.tr', 'teacher123', 3, 1, 'active', 'Doç. Dr.', 'Yapay Zeka', 1, 0),
('Mehmet', 'Kaya', 'mehmet.kaya@uni.edu.tr', 'teacher123', 3, 1, 'active', 'Prof. Dr.', 'Veritabanı Sistemleri', 1, 0),
('Ayşe', 'Çelik', 'ayse.celik@uni.edu.tr', 'teacher123', 3, 2, 'active', 'Dr. Öğr. Üyesi', 'Elektronik Devreler', 1, 0),
('Fatma', 'Demir', 'fatma.demir@uni.edu.tr', 'teacher123', 3, 3, 'active', 'Prof. Dr.', 'Termodinamik', 1, 0),
('Ali', 'Şahin', 'ali.sahin@uni.edu.tr', 'teacher123', 3, 4, 'active', 'Dr. Öğr. Üyesi', 'Optimizasyon', 1, 0),
('Zeynep', 'Yıldız', 'zeynep.yildiz@uni.edu.tr', 'teacher123', 3, 5, 'active', 'Doç. Dr.', 'Finansal Yönetim', 1, 0);

-- Insert sample students (password: student123)
INSERT INTO users (name, surname, email, password, role_id, department_id, status, student_id, is_teacher, is_student, entry_year, advisor_id, scholarship_id) VALUES
('Cem', 'Demir', 'cem.demir@stu.uni.edu.tr', 'student123', 4, 1, 'active', '2022001', 0, 1, 2022, 3, 1),
('Deniz', 'Yılmaz', 'deniz.yilmaz@stu.uni.edu.tr', 'student123', 4, 1, 'active', '2022002', 0, 1, 2022, 3, 5),
('Elif', 'Kara', 'elif.kara@stu.uni.edu.tr', 'student123', 4, 1, 'active', '2023001', 0, 1, 2023, 4, 1),
('Burak', 'Öztürk', 'burak.ozturk@stu.uni.edu.tr', 'student123', 4, 2, 'active', '2023002', 0, 1, 2023, 5, 5),
('Gizem', 'Aydın', 'gizem.aydin@stu.uni.edu.tr', 'student123', 4, 3, 'active', '2022003', 0, 1, 2022, 6, 2),
('Hakan', 'Yıldırım', 'hakan.yildirim@stu.uni.edu.tr', 'student123', 4, 4, 'active', '2023003', 0, 1, 2023, 7, 3),
('İrem', 'Koç', 'irem.koc@stu.uni.edu.tr', 'student123', 4, 5, 'active', '2022004', 0, 1, 2022, 8, 5);

-- Insert sample courses
INSERT INTO courses (name, code, description, credit, department_id, year, hours_per_week) VALUES
('Programlama Temelleri', 'CENG101', 'Temel programlama kavramları ve algoritma geliştirme', 3, 1, 1, 4),
('Veri Yapıları', 'CENG201', 'Temel veri yapıları ve algoritmaları', 3, 1, 2, 4),
('Veritabanı Sistemleri', 'CENG301', 'İlişkisel veritabanları ve SQL', 4, 1, 3, 4),
('Web Programlama', 'CENG302', 'Web uygulamaları geliştirme', 3, 1, 3, 3),
('Yapay Zeka', 'CENG401', 'Yapay zeka ve makine öğrenmesi temelleri', 4, 1, 4, 3),
('Devre Teorisi', 'EE101', 'Elektrik devreleri ve analizi', 4, 2, 1, 4),
('Elektronik', 'EE201', 'Analog ve dijital elektronik', 3, 2, 2, 4),
('Sinyal İşleme', 'EE301', 'Analog ve dijital sinyal işleme', 4, 2, 3, 4),
('Termodinamik', 'ME101', 'Termodinamiğin temelleri', 4, 3, 1, 4),
('Mukavemet', 'ME201', 'Malzeme mukavemeti ve gerilme analizi', 3, 3, 2, 4),
('Üretim Sistemleri', 'IE101', 'Üretim sistemleri ve süreçleri', 3, 4, 1, 3),
('Optimizasyon', 'IE201', 'Optimizasyon teorisi ve modelleri', 4, 4, 2, 4),
('İşletme Yönetimi', 'BA101', 'İşletme yönetimi temelleri', 3, 5, 1, 3),
('Finans', 'BA201', 'Finansal analiz ve yönetim', 3, 5, 2, 3);

-- Assign courses to teachers
INSERT INTO teacher_courses (teacher_id, course_id, term_id) VALUES
(2, 1, 1), -- Ahmet Yılmaz - Programlama Temelleri - Güz 2022
(2, 2, 2), -- Ahmet Yılmaz - Veri Yapıları - Bahar 2023
(2, 5, 3), -- Ahmet Yılmaz - Yapay Zeka - Güz 2023
(2, 5, 4), -- Ahmet Yılmaz - Yapay Zeka - Bahar 2024
(3, 3, 1), -- Mehmet Kaya - Veritabanı Sistemleri - Güz 2022
(3, 3, 3), -- Mehmet Kaya - Veritabanı Sistemleri - Güz 2023
(3, 4, 2), -- Mehmet Kaya - Web Programlama - Bahar 2023
(3, 4, 4), -- Mehmet Kaya - Web Programlama - Bahar 2024
(4, 6, 1), -- Ayşe Çelik - Devre Teorisi - Güz 2022
(4, 7, 2), -- Ayşe Çelik - Elektronik - Bahar 2023
(4, 8, 3), -- Ayşe Çelik - Sinyal İşleme - Güz 2023
(4, 8, 4), -- Ayşe Çelik - Sinyal İşleme - Bahar 2024
(5, 9, 1), -- Fatma Demir - Termodinamik - Güz 2022
(5, 10, 2), -- Fatma Demir - Mukavemet - Bahar 2023
(5, 9, 3), -- Fatma Demir - Termodinamik - Güz 2023
(5, 10, 4), -- Fatma Demir - Mukavemet - Bahar 2024
(6, 11, 1), -- Ali Şahin - Üretim Sistemleri - Güz 2022
(6, 12, 2), -- Ali Şahin - Optimizasyon - Bahar 2023
(6, 11, 3), -- Ali Şahin - Üretim Sistemleri - Güz 2023
(6, 12, 4), -- Ali Şahin - Optimizasyon - Bahar 2024
(7, 13, 1), -- Zeynep Yıldız - İşletme Yönetimi - Güz 2022
(7, 14, 2), -- Zeynep Yıldız - Finans - Bahar 2023
(7, 13, 3), -- Zeynep Yıldız - İşletme Yönetimi - Güz 2023
(7, 14, 4); -- Zeynep Yıldız - Finans - Bahar 2024

-- Enroll students in courses and assign grades
-- Student 1: Cem Demir (Bilgisayar Mühendisliği)
INSERT INTO student_courses (student_id, course_id, term_id, grade, status) VALUES
(8, 1, 1, 85, 'completed'), -- CENG101 Güz 2022 - BA
(8, 2, 2, 92, 'completed'), -- CENG201 Bahar 2023 - AA
(8, 3, 3, 78, 'completed'), -- CENG301 Güz 2023 - CB
(8, 4, 4, 88, 'enrolled'); -- CENG302 Bahar 2024 - Currently enrolled

-- Student 2: Deniz Yılmaz (Bilgisayar Mühendisliği)
INSERT INTO student_courses (student_id, course_id, term_id, grade, status) VALUES
(9, 1, 1, 75, 'completed'), -- CENG101 Güz 2022 - CB
(9, 2, 2, 68, 'completed'), -- CENG201 Bahar 2023 - DC
(9, 3, 3, 82, 'completed'), -- CENG301 Güz 2023 - BB
(9, 4, 4, NULL, 'enrolled'); -- CENG302 Bahar 2024 - Currently enrolled

-- Student 3: Elif Kara (Bilgisayar Mühendisliği - Started 2023)
INSERT INTO student_courses (student_id, course_id, term_id, grade, status) VALUES
(10, 1, 3, 95, 'completed'), -- CENG101 Güz 2023 - AA
(10, 2, 4, NULL, 'enrolled'); -- CENG201 Bahar 2024 - Currently enrolled

-- Student 4: Burak Öztürk (Elektrik Mühendisliği - Started 2023)
INSERT INTO student_courses (student_id, course_id, term_id, grade, status) VALUES
(11, 6, 3, 72, 'completed'), -- EE101 Güz 2023 - CC
(11, 7, 4, NULL, 'enrolled'); -- EE201 Bahar 2024 - Currently enrolled

-- Student 5: Gizem Aydın (Makine Mühendisliği)
INSERT INTO student_courses (student_id, course_id, term_id, grade, status) VALUES
(12, 9, 1, 82, 'completed'), -- ME101 Güz 2022 - BB
(12, 10, 2, 88, 'completed'), -- ME201 Bahar 2023 - BB
(12, 9, 3, 55, 'failed'), -- ME101 Güz 2023 - FF (Failed and will retake)
(12, 10, 4, NULL, 'enrolled'); -- ME201 Bahar 2024 - Currently enrolled

-- Student 6: Hakan Yıldırım (Endüstri Mühendisliği - Started 2023)
INSERT INTO student_courses (student_id, course_id, term_id, grade, status) VALUES
(13, 11, 3, 78, 'completed'), -- IE101 Güz 2023 - CB
(13, 12, 4, NULL, 'enrolled'); -- IE201 Bahar 2024 - Currently enrolled

-- Student 7: İrem Koç (İşletme)
INSERT INTO student_courses (student_id, course_id, term_id, grade, status) VALUES
(14, 13, 1, 95, 'completed'), -- BA101 Güz 2022 - AA
(14, 14, 2, 90, 'completed'), -- BA201 Bahar 2023 - AA
(14, 13, 3, 92, 'completed'), -- BA101 Güz 2023 (retaking for higher grade) - AA
(14, 14, 4, NULL, 'enrolled'); -- BA201 Bahar 2024 - Currently enrolled

-- Insert default grade scales for all course-teacher-term combinations
INSERT INTO course_grade_scales (course_id, teacher_id, term_id, letter, min_grade, max_grade, grade_point) VALUES
-- For each teacher_courses entry, insert default grade scales
-- Ahmet Yılmaz - Programlama Temelleri - Güz 2022
(1, 3, 1, 'AA', 90, 100, 4.0),
(1, 3, 1, 'BA', 85, 89.99, 3.5),
(1, 3, 1, 'BB', 80, 84.99, 3.0),
(1, 3, 1, 'CB', 75, 79.99, 2.5),
(1, 3, 1, 'CC', 70, 74.99, 2.0),
(1, 3, 1, 'DC', 65, 69.99, 1.5),
(1, 3, 1, 'DD', 60, 64.99, 1.0),
(1, 3, 1, 'FF', 0, 59.99, 0.0);

-- Insert sample course schedule
INSERT INTO course_schedule (course_id, teacher_id, term_id, day_of_week, start_time, end_time, classroom) VALUES
(1, 3, 4, 1, '09:00:00', '11:00:00', 'A101'), -- Programlama Temelleri, Ahmet Yılmaz, Bahar 2024, Monday
(1, 3, 4, 3, '13:00:00', '15:00:00', 'A101'), -- Programlama Temelleri, Ahmet Yılmaz, Bahar 2024, Wednesday
(4, 4, 4, 2, '10:00:00', '12:00:00', 'B203'), -- Web Programlama, Mehmet Kaya, Bahar 2024, Tuesday
(4, 4, 4, 4, '14:00:00', '15:00:00', 'B203'); -- Web Programlama, Mehmet Kaya, Bahar 2024, Thursday

-- Insert sample announcements
INSERT INTO announcements (title, content, user_id, role_id, department_id, course_id, start_date, end_date, status) VALUES
('Bahar 2024 Dönemine Hoşgeldiniz', 'Bahar 2024 dönemine hoşgeldiniz. Lütfen ders programlarınızı kontrol ediniz.', 1, NULL, NULL, NULL, '2024-02-01 00:00:00', '2024-06-15 23:59:59', 'active'),
('Bilgisayar Mühendisliği Bölümü Duyurusu', 'Bilgisayar Mühendisliği öğrencileri için ders kaydı hakkında önemli bilgilendirme.', 1, 3, 1, NULL, '2024-02-01 00:00:00', '2024-06-15 23:59:59', 'active'),
('Sistem Bakımı', 'Sistem 15 Şubat 2024 tarihinde 22:00 - 23:00 saatleri arasında bakımda olacaktır.', 1, 1, NULL, NULL, '2024-02-14 00:00:00', '2024-02-15 23:59:59', 'active'),
('Yönetici Duyurusu', 'Tüm yöneticiler için sistem güncellemeleri hakkında önemli duyuru.', 1, 1, NULL, NULL, '2024-02-01 00:00:00', '2024-12-31 23:59:59', 'active'),
('Genel Duyuru', 'Bu bir test duyurusudur, tüm kullanıcılar görebilir.', 1, NULL, NULL, NULL, '2024-02-01 00:00:00', '2024-12-31 23:59:59', 'active');
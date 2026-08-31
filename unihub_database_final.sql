-- =========================================================
-- UniHub - FINAL DATABASE SCHEMA
-- MySQL 8.x
-- SRS-aligned hybrid schema
-- =========================================================

CREATE DATABASE IF NOT EXISTS unihub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE unihub;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS
    quiz_answers,
    quiz_attempts,
    quiz_options,
    quiz_questions,
    quizzes,
    submissions,
    assignments,
    learning_progress,
    learning_resources,
    learning_modules,
    materials,
    attendance,
    class_sessions,
    grades,
    announcements,
    enrollments,
    course_lecturers,
    courses,
    student_profiles,
    students,
    lecturers,
    programs,
    departments,
    users;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- 1. USERS
-- =========================================================

CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    profile_image VARCHAR(255) NULL,
    role ENUM('student', 'lecturer', 'admin') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- =========================================================
-- 2. DEPARTMENTS
-- =========================================================

CREATE TABLE departments (
    department_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT NULL,
    head_of_department INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_department_head
        FOREIGN KEY (head_of_department)
        REFERENCES users(user_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 3. PROGRAMS
-- =========================================================

CREATE TABLE programs (
    program_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    program_name VARCHAR(150) NOT NULL,
    duration_years DECIMAL(3,1) NULL,
    degree_type VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_program_department
        FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    UNIQUE KEY uq_program_department_name
        (department_id, program_name)
) ENGINE=InnoDB;

-- =========================================================
-- 4. STUDENTS
-- =========================================================

CREATE TABLE students (
    student_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    program_id INT UNSIGNED NOT NULL,
    semester INT UNSIGNED NULL,
    year_level INT UNSIGNED NULL,

    CONSTRAINT fk_student_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_student_program
        FOREIGN KEY (program_id)
        REFERENCES programs(program_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 5. STUDENT PROFILES
-- =========================================================

CREATE TABLE student_profiles (
    profile_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL UNIQUE,
    date_of_birth DATE NULL,
    address VARCHAR(255) NULL,
    emergency_contact VARCHAR(100) NULL,

    CONSTRAINT fk_student_profile_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 6. LECTURERS
-- =========================================================

CREATE TABLE lecturers (
    lecturer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    employee_number VARCHAR(50) NOT NULL UNIQUE,
    department_id INT UNSIGNED NOT NULL,
    specialization VARCHAR(150) NULL,

    CONSTRAINT fk_lecturer_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_lecturer_department
        FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 7. COURSES
-- =========================================================

CREATE TABLE courses (
    course_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    course_code VARCHAR(30) NOT NULL UNIQUE,
    course_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    credit_hours TINYINT UNSIGNED NOT NULL DEFAULT 3,
    semester INT UNSIGNED NULL,
    academic_year VARCHAR(20) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_course_department
        FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    INDEX idx_courses_department (department_id)
) ENGINE=InnoDB;

-- =========================================================
-- 8. COURSE LECTURERS
-- =========================================================

CREATE TABLE course_lecturers (
    course_id INT UNSIGNED NOT NULL,
    lecturer_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (course_id, lecturer_id),

    CONSTRAINT fk_course_lecturers_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_course_lecturers_lecturer
        FOREIGN KEY (lecturer_id)
        REFERENCES lecturers(lecturer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 9. ENROLLMENTS
-- =========================================================

CREATE TABLE enrollments (
    enrollment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    enrollment_date DATE NOT NULL,
    semester INT UNSIGNED NULL,
    academic_year VARCHAR(20) NULL,
    status ENUM('enrolled', 'completed', 'withdrawn') NOT NULL DEFAULT 'enrolled',

    CONSTRAINT fk_enrollment_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_enrollment_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_student_course_year
        (student_id, course_id, academic_year),

    INDEX idx_enrollments_course (course_id)
) ENGINE=InnoDB;

-- =========================================================
-- 10. CLASS SESSIONS / TIMETABLE
-- =========================================================

CREATE TABLE class_sessions (
    session_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    lecturer_id INT UNSIGNED NOT NULL,
    day_of_week ENUM(
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday'
    ) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(100) NULL,

    CONSTRAINT fk_class_session_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_class_session_lecturer
        FOREIGN KEY (lecturer_id)
        REFERENCES lecturers(lecturer_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    INDEX idx_class_sessions_course (course_id),
    INDEX idx_class_sessions_lecturer (lecturer_id)
) ENGINE=InnoDB;

-- =========================================================
-- 11. ATTENDANCE
-- =========================================================

CREATE TABLE attendance (
    attendance_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    session_id INT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL,

    CONSTRAINT fk_attendance_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_attendance_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_attendance_session
        FOREIGN KEY (session_id)
        REFERENCES class_sessions(session_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_attendance_student_session_date
        (student_id, session_id, attendance_date),

    INDEX idx_attendance_course (course_id),
    INDEX idx_attendance_date (attendance_date)
) ENGINE=InnoDB;

-- =========================================================
-- 12. GRADES / RESULTS
-- =========================================================

CREATE TABLE grades (
    grade_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    assessment_name VARCHAR(150) NOT NULL,
    marks DECIMAL(6,2) NOT NULL,
    total_marks DECIMAL(6,2) NOT NULL,
    grade VARCHAR(5) NULL,
    semester INT UNSIGNED NULL,
    academic_year VARCHAR(20) NULL,

    CONSTRAINT fk_grade_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_grade_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_grades_student (student_id),
    INDEX idx_grades_course (course_id)
) ENGINE=InnoDB;

-- =========================================================
-- 13. LEARNING MODULES
-- =========================================================

CREATE TABLE learning_modules (
    module_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    order_number INT UNSIGNED NOT NULL DEFAULT 1,

    CONSTRAINT fk_module_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_modules_course (course_id)
) ENGINE=InnoDB;

-- =========================================================
-- 14. LEARNING RESOURCES
-- =========================================================

CREATE TABLE learning_resources (
    resource_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    resource_type ENUM(
        'pdf',
        'document',
        'presentation',
        'video',
        'image',
        'link',
        'other'
    ) NOT NULL DEFAULT 'other',
    file_name VARCHAR(255) NULL,
    file_path VARCHAR(500) NULL,
    external_url VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_resource_module
        FOREIGN KEY (module_id)
        REFERENCES learning_modules(module_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_resource_uploader
        FOREIGN KEY (uploaded_by)
        REFERENCES lecturers(lecturer_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    INDEX idx_resources_module (module_id),
    INDEX idx_resources_uploader (uploaded_by)
) ENGINE=InnoDB;

-- =========================================================
-- 15. LEARNING PROGRESS
-- =========================================================

CREATE TABLE learning_progress (
    progress_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    completion_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
    completed_modules INT UNSIGNED NOT NULL DEFAULT 0,
    completed_resources INT UNSIGNED NOT NULL DEFAULT 0,
    last_accessed DATETIME NULL,

    CONSTRAINT fk_progress_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_progress_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_student_course_progress
        (student_id, course_id)
) ENGINE=InnoDB;

-- =========================================================
-- 16. ASSIGNMENTS
-- =========================================================

CREATE TABLE assignments (
    assignment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    lecturer_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    due_date DATETIME NOT NULL,
    total_marks DECIMAL(6,2) NOT NULL DEFAULT 100,
    attachment_url VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_assignment_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_assignment_lecturer
        FOREIGN KEY (lecturer_id)
        REFERENCES lecturers(lecturer_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    INDEX idx_assignments_course (course_id),
    INDEX idx_assignments_due_date (due_date)
) ENGINE=InnoDB;

-- =========================================================
-- 17. SUBMISSIONS
-- =========================================================

CREATE TABLE submissions (
    submission_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    marks DECIMAL(6,2) NULL,
    feedback TEXT NULL,
    status ENUM('submitted', 'graded', 'late') NOT NULL DEFAULT 'submitted',

    CONSTRAINT fk_submission_assignment
        FOREIGN KEY (assignment_id)
        REFERENCES assignments(assignment_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_submission_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_assignment_student
        (assignment_id, student_id),

    INDEX idx_submissions_student (student_id),
    INDEX idx_submissions_assignment (assignment_id)
) ENGINE=InnoDB;

-- =========================================================
-- 18. QUIZZES
-- =========================================================

CREATE TABLE quizzes (
    quiz_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    lecturer_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    duration_minutes SMALLINT UNSIGNED NULL,
    total_marks DECIMAL(6,2) NOT NULL DEFAULT 0,
    start_date DATETIME NULL,
    end_date DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_quiz_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_quiz_lecturer
        FOREIGN KEY (lecturer_id)
        REFERENCES lecturers(lecturer_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    INDEX idx_quizzes_course (course_id)
) ENGINE=InnoDB;

-- =========================================================
-- 19. QUIZ QUESTIONS
-- =========================================================

CREATE TABLE quiz_questions (
    question_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM(
        'multiple_choice',
        'true_false',
        'short_answer'
    ) NOT NULL DEFAULT 'multiple_choice',
    marks DECIMAL(6,2) NOT NULL DEFAULT 1,
    question_order INT UNSIGNED NOT NULL DEFAULT 1,
    correct_answer TEXT NULL,

    CONSTRAINT fk_quiz_question_quiz
        FOREIGN KEY (quiz_id)
        REFERENCES quizzes(quiz_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_quiz_questions_quiz (quiz_id)
) ENGINE=InnoDB;

-- =========================================================
-- 20. QUIZ OPTIONS
-- =========================================================

CREATE TABLE quiz_options (
    option_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,

    CONSTRAINT fk_quiz_option_question
        FOREIGN KEY (question_id)
        REFERENCES quiz_questions(question_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_quiz_options_question (question_id)
) ENGINE=InnoDB;

-- =========================================================
-- 21. QUIZ ATTEMPTS
-- =========================================================

CREATE TABLE quiz_attempts (
    attempt_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    start_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME NULL,
    score DECIMAL(6,2) NULL,
    status ENUM('in_progress', 'submitted', 'graded') NOT NULL DEFAULT 'in_progress',

    CONSTRAINT fk_quiz_attempt_quiz
        FOREIGN KEY (quiz_id)
        REFERENCES quizzes(quiz_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_quiz_attempt_student
        FOREIGN KEY (student_id)
        REFERENCES students(student_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_quiz_attempts_quiz (quiz_id),
    INDEX idx_quiz_attempts_student (student_id)
) ENGINE=InnoDB;

-- =========================================================
-- 22. QUIZ ANSWERS
-- =========================================================

CREATE TABLE quiz_answers (
    answer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    selected_option_id INT UNSIGNED NULL,
    answer_text TEXT NULL,
    is_correct BOOLEAN NULL,

    CONSTRAINT fk_quiz_answer_attempt
        FOREIGN KEY (attempt_id)
        REFERENCES quiz_attempts(attempt_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_quiz_answer_question
        FOREIGN KEY (question_id)
        REFERENCES quiz_questions(question_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_quiz_answer_option
        FOREIGN KEY (selected_option_id)
        REFERENCES quiz_options(option_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    UNIQUE KEY uq_attempt_question
        (attempt_id, question_id),

    INDEX idx_quiz_answers_question (question_id)
) ENGINE=InnoDB;

-- =========================================================
-- 23. ANNOUNCEMENTS
-- =========================================================

CREATE TABLE announcements (
    announcement_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_by INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    target_audience ENUM(
        'all',
        'students',
        'lecturers',
        'course',
        'department'
    ) NOT NULL DEFAULT 'all',
    course_id INT UNSIGNED NULL,
    department_id INT UNSIGNED NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    priority TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_announcement_creator
        FOREIGN KEY (created_by)
        REFERENCES users(user_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_announcement_course
        FOREIGN KEY (course_id)
        REFERENCES courses(course_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_announcement_department
        FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_announcements_course (course_id),
    INDEX idx_announcements_department (department_id),
    INDEX idx_announcements_created (created_at)
) ENGINE=InnoDB;

-- =========================================================
-- END OF FINAL UNIHUB SCHEMA
-- =========================================================

-- =========================================================
-- UniHub - SEED DATA
-- Departments, programs, and the bootstrap admin account.
--
-- Run this AFTER importing unihub_database_final.sql:
--     mysql -u root -p unihub < seed.sql
--
-- Safe to run more than once: every statement upserts on its
-- primary key, so re-running will not raise duplicate-key errors.
--
-- Requires MySQL 8.0.19+ (uses the row-alias upsert syntax).
-- =========================================================

USE unihub;

-- ---------------------------------------------------------
-- Departments (5)
-- ---------------------------------------------------------
INSERT INTO departments (department_id, department_name, code) VALUES
  (1, 'School of Science',       'SOS'),
  (2, 'School of Engineering',   'SOE'),
  (3, 'School of Management',    'SOM'),
  (4, 'School of Pharmacy',      'SOP'),
  (5, 'School of Physiotherapy', 'SPT')
AS new
ON DUPLICATE KEY UPDATE
  department_name = new.department_name,
  code            = new.code;

-- ---------------------------------------------------------
-- Programs (34)
-- ---------------------------------------------------------

-- School of Engineering (SOE)
INSERT INTO programs (program_id, department_id, program_name, duration_years, degree_type) VALUES
  ( 1, 2, 'Computer Science and Engineering',           4,   'B.Tech'),
  ( 2, 2, 'Computer Science and Engineering (AI & ML)', 4,   'B.Tech'),
  ( 3, 2, 'Artificial Intelligence and Data Science',   4,   'B.Tech'),
  ( 4, 2, 'Civil Engineering',                          4,   'B.Tech'),
  ( 5, 2, 'Electrical Engineering',                     4,   'B.Tech'),
  ( 6, 2, 'Information Technology',                     4,   'B.Tech'),
  ( 7, 2, 'Mechanical Engineering',                     4,   'B.Tech'),
  ( 8, 2, 'Bachelor of Computer Application',           3,   'BCA'),
  ( 9, 2, 'Computer Engineering',                       2,   'M.Tech'),
  (10, 2, 'Construction Technology',                    2,   'M.Tech'),
  (11, 2, 'Electrical Power System',                    2,   'M.Tech'),
  (12, 2, 'Machine Design',                             2,   'M.Tech'),
  (13, 2, 'Structural Engineering',                     2,   'M.Tech'),
  (14, 2, 'Thermal Sciences',                           2,   'M.Tech'),
  (15, 2, 'Master of Computer Application',             2,   'MCA')
AS new
ON DUPLICATE KEY UPDATE
  department_id  = new.department_id,
  program_name   = new.program_name,
  duration_years = new.duration_years,
  degree_type    = new.degree_type;

-- School of Management (SOM)
INSERT INTO programs (program_id, department_id, program_name, duration_years, degree_type) VALUES
  (21, 3, 'BBA',                                     3, 'BBA'),
  (22, 3, 'BBA (Applied Management)',                3, 'BBA'),
  (23, 3, 'BBA (Entrepreneurship and Family Business)', 3, 'BBA'),
  (24, 3, 'B.Voc',                                   3, 'B.Voc'),
  (25, 3, 'MBA',                                     2, 'MBA'),
  (26, 3, 'MBA (Banking & Finance)',                 2, 'MBA'),
  (27, 3, 'MBA (Entrepreneurship & Family Business)', 2, 'MBA')
AS new
ON DUPLICATE KEY UPDATE
  department_id  = new.department_id,
  program_name   = new.program_name,
  duration_years = new.duration_years,
  degree_type    = new.degree_type;

-- School of Pharmacy (SOP)
INSERT INTO programs (program_id, department_id, program_name, duration_years, degree_type) VALUES
  (16, 4, 'Bachelor of Pharmacy', 4, 'B.Pharm'),
  (17, 4, 'Pharmaceutics',        2, 'M.Pharm'),
  (18, 4, 'Pharmaceutical QA',    2, 'M.Pharm'),
  (19, 4, 'Pharmacology',         2, 'M.Pharm'),
  (20, 4, 'Doctor of Pharmacy',   6, 'PharmD')
AS new
ON DUPLICATE KEY UPDATE
  department_id  = new.department_id,
  program_name   = new.program_name,
  duration_years = new.duration_years,
  degree_type    = new.degree_type;

-- School of Science (SOS)
INSERT INTO programs (program_id, department_id, program_name, duration_years, degree_type) VALUES
  (28, 1, 'B.Sc. Chemistry',    3, 'B.Sc.'),
  (29, 1, 'B.Sc. Microbiology', 3, 'B.Sc.'),
  (30, 1, 'M.Sc. Chemistry',    2, 'M.Sc.'),
  (31, 1, 'M.Sc. Microbiology', 2, 'M.Sc.')
AS new
ON DUPLICATE KEY UPDATE
  department_id  = new.department_id,
  program_name   = new.program_name,
  duration_years = new.duration_years,
  degree_type    = new.degree_type;

-- School of Physiotherapy (SPT)
INSERT INTO programs (program_id, department_id, program_name, duration_years, degree_type) VALUES
  (32, 5, 'Bachelor of Physiotherapy', 4.5, 'BPT'),
  (33, 5, 'Master of Physiotherapy',   2,   'MPT'),
  (34, 5, 'B.Sc. MRIT',                3,   'B.Sc.')
AS new
ON DUPLICATE KEY UPDATE
  department_id  = new.department_id,
  program_name   = new.program_name,
  duration_years = new.duration_years,
  degree_type    = new.degree_type;

-- ---------------------------------------------------------
-- Bootstrap admin account
--
--   email:    admin@uni.edu
--   password: admin123   <-- CHANGE THIS before any real use.
--
-- There is no self-service password change for admins in the
-- UI, so update it directly if you rotate it:
--     UPDATE users SET password_hash = '<hash>' WHERE email = 'admin@uni.edu';
-- ---------------------------------------------------------
INSERT INTO users (user_id, full_name, email, password_hash, role, status) VALUES
  (1, 'System Administrator', 'admin@uni.edu',
   '$2y$12$oJGZAY4YqF/tHqBpbqc9COyVv1id7JIBNu4UKVLoSi..uuWcsMjTG',
   'admin', 'active')
AS new
ON DUPLICATE KEY UPDATE
  full_name     = new.full_name,
  password_hash = new.password_hash,
  role          = new.role,
  status        = new.status;

-- Online Examination System (Auto-Grading)
-- Import via phpMyAdmin or: mysql -u root -P 3306 < schema.sql
-- Creates database, schema, and demo seed data.

CREATE DATABASE IF NOT EXISTS online_examination_system
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE online_examination_system;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS attempt_answers;
DROP TABLE IF EXISTS exam_attempts;
DROP TABLE IF EXISTS student_subjects;
DROP TABLE IF EXISTS question_options;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS exams;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Schema
-- ============================================================

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    role          ENUM('super_admin','examiner','student') NOT NULL,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE subjects (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    description   TEXT NULL,
    created_by    INT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE exams (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    subject_id        INT NOT NULL,
    examiner_id       INT NOT NULL,
    title             VARCHAR(150) NOT NULL,
    description       TEXT NULL,
    duration_minutes  INT NOT NULL,
    negative_marking  DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    shuffle_questions TINYINT(1) NOT NULL DEFAULT 0,
    status            ENUM('draft','published') NOT NULL DEFAULT 'draft',
    start_at          DATETIME NOT NULL,
    end_at            DATETIME NOT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (examiner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE questions (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    exam_id               INT NOT NULL,
    question_text         TEXT NOT NULL,
    type                  ENUM('single','multi','true_false','fill_blank') NOT NULL,
    marks                 DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    order_no              INT NOT NULL DEFAULT 0,
    correct_answer_text   VARCHAR(255) NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE question_options (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    question_id   INT NOT NULL,
    option_text   VARCHAR(500) NOT NULL,
    is_correct    TINYINT(1) NOT NULL DEFAULT 0,
    order_no      INT NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE student_subjects (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    student_id    INT NOT NULL,
    subject_id    INT NOT NULL,
    enrolled_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_student_subject (student_id, subject_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE exam_attempts (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    exam_id           INT NOT NULL,
    student_id        INT NOT NULL,
    started_at        DATETIME NOT NULL,
    submitted_at      DATETIME NULL,
    status            ENUM('in_progress','submitted','auto_submitted','disqualified') NOT NULL DEFAULT 'in_progress',
    score             DECIMAL(6,2) NULL,
    total_marks       DECIMAL(6,2) NULL,
    violation_count   INT NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_exam_student (exam_id, student_id),
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE attempt_answers (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id            INT NOT NULL,
    question_id           INT NOT NULL,
    selected_option_ids   VARCHAR(255) NULL,
    answer_text           VARCHAR(255) NULL,
    is_correct            TINYINT(1) NULL,
    marks_awarded         DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    UNIQUE KEY uniq_attempt_question (attempt_id, question_id),
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Seed data
-- Demo passwords (documented in README.md):
--   Super Admin : Admin@123
--   Examiner    : Examiner@123
--   Student     : Student@123
-- ============================================================

INSERT INTO users (name, email, password, role, status) VALUES
('System Administrator', 'superadmin@exam.test', '$2y$12$MR82QFveMU.KuZLdNBs5GO4Izi7dI7AQ/kyiwNEeELoXecmsFdayq', 'super_admin', 'active'),
('Dr. Ananya Rao',       'examiner1@exam.test',  '$2y$12$U6LXyku7jC6FkXHNMgAIEeHlVExUZnCmZa2GElCFG9v7.lIjgpqcy', 'examiner', 'active'),
('Prof. Vikram Sinha',   'examiner2@exam.test',  '$2y$12$U6LXyku7jC6FkXHNMgAIEeHlVExUZnCmZa2GElCFG9v7.lIjgpqcy', 'examiner', 'active'),
('Dr. Leela Menon',      'examiner3@exam.test',  '$2y$12$U6LXyku7jC6FkXHNMgAIEeHlVExUZnCmZa2GElCFG9v7.lIjgpqcy', 'examiner', 'active'),
('Aarav Sharma',   'student1@exam.test',  '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Diya Patel',     'student2@exam.test',  '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Kabir Singh',    'student3@exam.test',  '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Ananya Iyer',    'student4@exam.test',  '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Rohan Gupta',    'student5@exam.test',  '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Ishita Reddy',   'student6@exam.test',  '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Vivaan Nair',    'student7@exam.test',  '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Sara Khan',      'student8@exam.test',  '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Arjun Mehta',    'student9@exam.test',  '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Priya Verma',    'student10@exam.test', '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Aditya Joshi',   'student11@exam.test', '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Neha Kulkarni',  'student12@exam.test', '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Karan Malhotra', 'student13@exam.test', '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Pooja Desai',    'student14@exam.test', '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Rahul Bose',     'student15@exam.test', '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Simran Kaur',    'student16@exam.test', '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Yash Agarwal',   'student17@exam.test', '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Meera Pillai',   'student18@exam.test', '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Aryan Chatterjee','student19@exam.test','$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'active'),
('Tanvi Rao',      'student20@exam.test', '$2y$12$gz7RuFfNna4XTrUt65Ou..rpB7imR3KgOXHfkhEgUZVavjL/sU6ju', 'student', 'inactive');

INSERT INTO subjects (name, description, created_by) VALUES
('Data Structures & Algorithms', 'Core DSA concepts: arrays, trees, sorting, complexity analysis.', 1),
('Database Management Systems',  'Relational modelling, normalization, SQL, transactions.', 1),
('Computer Networks',            'OSI/TCP-IP model, routing, protocols.', 1),
('Operating Systems',            'Process/thread management, scheduling, memory, deadlocks.', 1),
('Web Technologies',             'HTML, CSS, JavaScript, HTTP, server-side scripting basics.', 1);

-- Exam windows are seeded RELATIVE TO IMPORT TIME, not as fixed dates. Hardcoded dates
-- go stale: re-importing this file months after it was written left every window in the
-- past, so nothing could be started and the timed-attempt engine — the centrepiece of the
-- demo — had nothing to demonstrate. Relative windows keep the seed evergreen: exams 1-2
-- are always closed-and-graded, 3-4 always open now, 5 always upcoming.
-- id 1                                                               id 2               examiner
INSERT INTO exams (subject_id, examiner_id, title, description, duration_minutes, negative_marking, shuffle_questions, status, start_at, end_at) VALUES
(1, 2, 'DSA Mid-Term Quiz',        'Covers stacks, queues, searching, sorting and trees.', 30, 0.25, 1, 'published', DATE_SUB(NOW(), INTERVAL 32 DAY), DATE_SUB(NOW(), INTERVAL 28 DAY)),
(2, 3, 'DBMS Fundamentals Test',   'Normalization, SQL basics, ACID properties.',          40, 0.50, 0, 'published', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 26 DAY)),
(3, 4, 'Computer Networks Basics', 'OSI layers, DNS, transport-layer protocols.',          25, 0.00, 1, 'published', DATE_SUB(NOW(), INTERVAL 1 DAY),  DATE_ADD(NOW(), INTERVAL 6 DAY)),
(4, 2, 'Operating Systems Concepts','Scheduling, deadlocks, paging, threads.',             35, 0.25, 0, 'published', DATE_SUB(NOW(), INTERVAL 2 DAY),  DATE_ADD(NOW(), INTERVAL 8 DAY)),
(5, 3, 'Web Tech Practicals Quiz', 'HTML/CSS/JS basics and HTTP fundamentals.',            20, 0.00, 1, 'published', DATE_ADD(NOW(), INTERVAL 3 DAY),  DATE_ADD(NOW(), INTERVAL 10 DAY)),
(1, 2, 'DSA Advanced Draft',       'Graph algorithms and complexity — still being authored.', 45, 0.25, 0, 'draft', DATE_ADD(NOW(), INTERVAL 20 DAY), DATE_ADD(NOW(), INTERVAL 25 DAY));

-- ---- Exam 1 (DSA Mid-Term Quiz) — questions 1-6 ----
INSERT INTO questions (exam_id, question_text, type, marks, order_no, correct_answer_text) VALUES
(1, 'Which data structure uses LIFO (Last-In-First-Out) order?', 'single', 1.00, 1, NULL),
(1, 'What is the time complexity of binary search on a sorted array of n elements?', 'single', 1.00, 2, NULL),
(1, 'Which of the following are linear data structures? (select all that apply)', 'multi', 2.00, 3, NULL),
(1, 'A queue follows First-In-First-Out (FIFO) order.', 'true_false', 1.00, 4, NULL),
(1, 'Quick sort has a worst-case time complexity of O(n log n).', 'true_false', 1.00, 5, NULL),
(1, 'The process of visiting all nodes of a tree in a specific order is called tree ____.', 'fill_blank', 2.00, 6, 'traversal');

INSERT INTO question_options (question_id, option_text, is_correct, order_no) VALUES
(1, 'Stack', 1, 1), (1, 'Queue', 0, 2), (1, 'Array', 0, 3), (1, 'Linked List', 0, 4),
(2, 'O(n)', 0, 1), (2, 'O(log n)', 1, 2), (2, 'O(n^2)', 0, 3), (2, 'O(1)', 0, 4),
(3, 'Array', 1, 1), (3, 'Linked List', 1, 2), (3, 'Graph', 0, 3), (3, 'Tree', 0, 4),
(4, 'True', 1, 1), (4, 'False', 0, 2),
(5, 'True', 0, 1), (5, 'False', 1, 2);

-- ---- Exam 2 (DBMS Fundamentals Test) — questions 7-12 ----
INSERT INTO questions (exam_id, question_text, type, marks, order_no, correct_answer_text) VALUES
(2, 'Which normal form eliminates transitive dependency?', 'single', 1.00, 1, NULL),
(2, 'Which SQL keyword is used to remove duplicate rows from a result set?', 'single', 1.00, 2, NULL),
(2, 'Which of these are types of SQL JOIN? (select all that apply)', 'multi', 2.00, 3, NULL),
(2, 'A primary key column can contain NULL values.', 'true_false', 1.00, 4, NULL),
(2, 'ACID stands for Atomicity, Consistency, Isolation, Durability.', 'true_false', 1.00, 5, NULL),
(2, 'The SQL sub-language used for CREATE and ALTER statements is called ____.', 'fill_blank', 2.00, 6, 'DDL');

INSERT INTO question_options (question_id, option_text, is_correct, order_no) VALUES
(7, '1NF', 0, 1), (7, '2NF', 0, 2), (7, '3NF', 1, 3), (7, 'BCNF', 0, 4),
(8, 'UNIQUE', 0, 1), (8, 'DISTINCT', 1, 2), (8, 'REMOVE', 0, 3), (8, 'FILTER', 0, 4),
(9, 'INNER JOIN', 1, 1), (9, 'LEFT JOIN', 1, 2), (9, 'FULL JOIN', 1, 3), (9, 'STACK JOIN', 0, 4),
(10, 'True', 0, 1), (10, 'False', 1, 2),
(11, 'True', 1, 1), (11, 'False', 0, 2);

-- ---- Exam 3 (Computer Networks Basics) — questions 13-18 ----
INSERT INTO questions (exam_id, question_text, type, marks, order_no, correct_answer_text) VALUES
(3, 'Which layer of the OSI model is responsible for routing?', 'single', 1.00, 1, NULL),
(3, 'What does DNS stand for?', 'single', 1.00, 2, NULL),
(3, 'Which of these are transport layer protocols? (select all that apply)', 'multi', 2.00, 3, NULL),
(3, 'IPv6 addresses are 128 bits long.', 'true_false', 1.00, 4, NULL),
(3, 'HTTP is a connectionless protocol running directly at the transport layer.', 'true_false', 1.00, 5, NULL),
(3, 'Converting a domain name into an IP address is called ____ resolution.', 'fill_blank', 2.00, 6, 'DNS');

INSERT INTO question_options (question_id, option_text, is_correct, order_no) VALUES
(13, 'Data Link', 0, 1), (13, 'Network', 1, 2), (13, 'Transport', 0, 3), (13, 'Session', 0, 4),
(14, 'Domain Name System', 1, 1), (14, 'Data Network Service', 0, 2), (14, 'Digital Naming System', 0, 3), (14, 'Domain Network Server', 0, 4),
(15, 'TCP', 1, 1), (15, 'UDP', 1, 2), (15, 'HTTP', 0, 3), (15, 'FTP', 0, 4),
(16, 'True', 1, 1), (16, 'False', 0, 2),
(17, 'True', 0, 1), (17, 'False', 1, 2);

-- ---- Exam 4 (Operating Systems Concepts) — questions 19-24 ----
INSERT INTO questions (exam_id, question_text, type, marks, order_no, correct_answer_text) VALUES
(4, 'Which scheduling algorithm can cause starvation of low-priority processes?', 'single', 1.00, 1, NULL),
(4, 'What is a deadlock?', 'single', 1.00, 2, NULL),
(4, 'Which of these are necessary conditions for deadlock (Coffman conditions)? (select all that apply)', 'multi', 2.00, 3, NULL),
(4, 'Paging eliminates external fragmentation.', 'true_false', 1.00, 4, NULL),
(4, 'Threads within the same process share the same address space.', 'true_false', 1.00, 5, NULL),
(4, 'Temporarily moving an entire process out to disk to free up memory is called ____.', 'fill_blank', 2.00, 6, 'swapping');

INSERT INTO question_options (question_id, option_text, is_correct, order_no) VALUES
(19, 'Round Robin', 0, 1), (19, 'Priority Scheduling', 1, 2), (19, 'FCFS', 0, 3), (19, 'SJF', 0, 4),
(20, 'A process using too much CPU', 0, 1), (20, 'A set of processes waiting indefinitely for each other', 1, 2), (20, 'A memory leak', 0, 3), (20, 'A page fault', 0, 4),
(21, 'Mutual Exclusion', 1, 1), (21, 'Hold and Wait', 1, 2), (21, 'Preemption', 0, 3), (21, 'Circular Wait', 1, 4),
(22, 'True', 1, 1), (22, 'False', 0, 2),
(23, 'True', 1, 1), (23, 'False', 0, 2);

-- ---- Exam 5 (Web Tech Practicals Quiz) — questions 25-30 ----
INSERT INTO questions (exam_id, question_text, type, marks, order_no, correct_answer_text) VALUES
(5, 'Which HTTP method is safe and idempotent, used to retrieve data?', 'single', 1.00, 1, NULL),
(5, 'Which CSS property controls text size?', 'single', 1.00, 2, NULL),
(5, 'Which of these are client-side technologies? (select all that apply)', 'multi', 2.00, 3, NULL),
(5, 'JavaScript is a statically typed language.', 'true_false', 1.00, 4, NULL),
(5, 'HTTPS uses SSL/TLS to encrypt data between client and server.', 'true_false', 1.00, 5, NULL),
(5, 'The PHP superglobal array used to access form data submitted via POST is called $_____.', 'fill_blank', 2.00, 6, 'POST');

INSERT INTO question_options (question_id, option_text, is_correct, order_no) VALUES
(25, 'POST', 0, 1), (25, 'GET', 1, 2), (25, 'PUT', 0, 3), (25, 'DELETE', 0, 4),
(26, 'font-style', 0, 1), (26, 'font-size', 1, 2), (26, 'text-decoration', 0, 3), (26, 'font-weight', 0, 4),
(27, 'HTML', 1, 1), (27, 'CSS', 1, 2), (27, 'JavaScript', 1, 3), (27, 'PHP', 0, 4),
(28, 'True', 0, 1), (28, 'False', 1, 2),
(29, 'True', 1, 1), (29, 'False', 0, 2);

-- ---- Exam 6 (DSA Advanced Draft) — questions 31-34 ----
INSERT INTO questions (exam_id, question_text, type, marks, order_no, correct_answer_text) VALUES
(6, 'Which algorithm finds the shortest path in a weighted graph with non-negative edges?', 'single', 1.00, 1, NULL),
(6, 'What is the space complexity of merge sort?', 'single', 1.00, 2, NULL),
(6, 'A binary search tree guarantees O(log n) search time in all cases.', 'true_false', 1.00, 3, NULL),
(6, 'A tree in which every node has at most two children is called a ____ tree.', 'fill_blank', 2.00, 4, 'binary');

INSERT INTO question_options (question_id, option_text, is_correct, order_no) VALUES
(31, 'DFS', 0, 1), (31, 'BFS', 0, 2), (31, 'Dijkstra''s Algorithm', 1, 3), (31, 'Bubble Sort', 0, 4),
(32, 'O(1)', 0, 1), (32, 'O(log n)', 0, 2), (32, 'O(n)', 1, 3), (32, 'O(n^2)', 0, 4),
(33, 'True', 0, 1), (33, 'False', 1, 2);

-- ---- Enrollments: every active student enrolled in every subject ----
INSERT INTO student_subjects (student_id, subject_id)
SELECT u.id, s.id FROM users u CROSS JOIN subjects s WHERE u.role = 'student';

-- ---- Demo exam attempts (closed exams, graded) ----
-- Timestamps are relative to import time for the same reason the exam windows are.
-- Exam 1 (DSA Mid-Term) attempts: student1-4 (user ids 5-8)
INSERT INTO exam_attempts (exam_id, student_id, started_at, submitted_at, status, score, total_marks, violation_count) VALUES
(1, 5, DATE_SUB(NOW(), INTERVAL 31 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY), 'submitted', 8.00, 8.00, 0),
(1, 6, DATE_SUB(NOW(), INTERVAL 31 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY), 'submitted', 5.50, 8.00, 1),
(1, 7, DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY), 'submitted', 2.25, 8.00, 0),
(1, 8, DATE_SUB(NOW(), INTERVAL 29 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY), 'auto_submitted', 4.25, 8.00, 2);

-- Exam 2 (DBMS Fundamentals) attempts: student1-4 + student5-6 (user ids 5-8, 9-10)
INSERT INTO exam_attempts (exam_id, student_id, started_at, submitted_at, status, score, total_marks, violation_count) VALUES
(2, 5,  DATE_SUB(NOW(), INTERVAL 29 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY), 'submitted', 8.00, 8.00, 0),
(2, 6,  DATE_SUB(NOW(), INTERVAL 29 DAY), DATE_SUB(NOW(), INTERVAL 29 DAY), 'submitted', 5.50, 8.00, 0),
(2, 7,  DATE_SUB(NOW(), INTERVAL 28 DAY), DATE_SUB(NOW(), INTERVAL 28 DAY), 'submitted', 4.75, 8.00, 0),
(2, 8,  DATE_SUB(NOW(), INTERVAL 28 DAY), DATE_SUB(NOW(), INTERVAL 28 DAY), 'submitted', 5.50, 8.00, 1),
(2, 9,  DATE_SUB(NOW(), INTERVAL 27 DAY), DATE_SUB(NOW(), INTERVAL 27 DAY), 'submitted', 0.50, 8.00, 3),
(2, 10, DATE_SUB(NOW(), INTERVAL 27 DAY), DATE_SUB(NOW(), INTERVAL 27 DAY), 'submitted', 8.00, 8.00, 0);

-- Exam 3 (Computer Networks Basics) — one attempt currently in progress (student5, user id 9).
-- Started "just now" so the Resume flow is demonstrable with most of its 25 minutes left;
-- seeded from NOW() on the DB server, so a large clock skew between MySQL and PHP would
-- simply make it auto-submit on resume rather than break anything.
INSERT INTO exam_attempts (exam_id, student_id, started_at, submitted_at, status, score, total_marks, violation_count) VALUES
(3, 9, DATE_SUB(NOW(), INTERVAL 4 MINUTE), NULL, 'in_progress', NULL, 6.00, 0);

-- ---- Answers: Exam 1 attempts (attempt ids 1-4) ----
INSERT INTO attempt_answers (attempt_id, question_id, selected_option_ids, answer_text, is_correct, marks_awarded) VALUES
-- student1 (attempt 1): all correct
(1, 1, '1', NULL, 1, 1.00), (1, 2, '6', NULL, 1, 1.00), (1, 3, '9,10', NULL, 1, 2.00),
(1, 4, '13', NULL, 1, 1.00), (1, 5, '16', NULL, 1, 1.00), (1, 6, NULL, 'traversal', 1, 2.00),
-- student2 (attempt 2): Q2 and Q5 wrong
(2, 1, '1', NULL, 1, 1.00), (2, 2, '5', NULL, 0, -0.25), (2, 3, '9,10', NULL, 1, 2.00),
(2, 4, '13', NULL, 1, 1.00), (2, 5, '15', NULL, 0, -0.25), (2, 6, NULL, 'traversal', 1, 2.00),
-- student3 (attempt 3): Q1 wrong, Q3 incomplete selection (wrong), Q6 unanswered
(3, 1, '2', NULL, 0, -0.25), (3, 2, '6', NULL, 1, 1.00), (3, 3, '9', NULL, 0, -0.50),
(3, 4, '13', NULL, 1, 1.00), (3, 5, '16', NULL, 1, 1.00),
-- student4 (attempt 4): Q4 wrong, Q6 wrong
(4, 1, '1', NULL, 1, 1.00), (4, 2, '6', NULL, 1, 1.00), (4, 3, '9,10', NULL, 1, 2.00),
(4, 4, '14', NULL, 0, -0.25), (4, 5, '16', NULL, 1, 1.00), (4, 6, NULL, 'preorder', 0, -0.50);

-- ---- Answers: Exam 2 attempts (attempt ids 5-10) ----
INSERT INTO attempt_answers (attempt_id, question_id, selected_option_ids, answer_text, is_correct, marks_awarded) VALUES
-- student1 (attempt 5): all correct
(5, 7, '19', NULL, 1, 1.00), (5, 8, '22', NULL, 1, 1.00), (5, 9, '25,26,27', NULL, 1, 2.00),
(5, 10, '30', NULL, 1, 1.00), (5, 11, '31', NULL, 1, 1.00), (5, 12, NULL, 'DDL', 1, 2.00),
-- student2 (attempt 6): Q9 incomplete selection (wrong)
(6, 7, '19', NULL, 1, 1.00), (6, 8, '22', NULL, 1, 1.00), (6, 9, '25,26', NULL, 0, -0.50),
(6, 10, '30', NULL, 1, 1.00), (6, 11, '31', NULL, 1, 1.00), (6, 12, NULL, 'DDL', 1, 2.00),
-- student3 (attempt 7): Q7 wrong, Q12 unanswered
(7, 7, '17', NULL, 0, -0.25), (7, 8, '22', NULL, 1, 1.00), (7, 9, '25,26,27', NULL, 1, 2.00),
(7, 10, '30', NULL, 1, 1.00), (7, 11, '31', NULL, 1, 1.00),
-- student4 (attempt 8): Q10 and Q11 wrong
(8, 7, '19', NULL, 1, 1.00), (8, 8, '22', NULL, 1, 1.00), (8, 9, '25,26,27', NULL, 1, 2.00),
(8, 10, '29', NULL, 0, -0.25), (8, 11, '32', NULL, 0, -0.25), (8, 12, NULL, 'DDL', 1, 2.00),
-- student5 (attempt 9): everything wrong except fill-blank
(9, 7, '17', NULL, 0, -0.25), (9, 8, '21', NULL, 0, -0.25), (9, 9, '28', NULL, 0, -0.50),
(9, 10, '29', NULL, 0, -0.25), (9, 11, '32', NULL, 0, -0.25), (9, 12, NULL, 'ddl', 1, 2.00),
-- student6 (attempt 10): all correct
(10, 7, '19', NULL, 1, 1.00), (10, 8, '22', NULL, 1, 1.00), (10, 9, '25,26,27', NULL, 1, 2.00),
(10, 10, '30', NULL, 1, 1.00), (10, 11, '31', NULL, 1, 1.00), (10, 12, NULL, 'DDL', 1, 2.00);

-- ---- Answers: Exam 3 in-progress attempt (attempt id 11) — autosaved, ungraded until submit ----
INSERT INTO attempt_answers (attempt_id, question_id, selected_option_ids, answer_text, is_correct, marks_awarded) VALUES
(11, 13, '34', NULL, NULL, 0.00),
(11, 14, '37', NULL, NULL, 0.00);

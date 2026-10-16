
CREATE DATABASE IF NOT EXISTS cybersecurity_sudoku
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE cybersecurity_sudoku;

CREATE TABLE levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    level_number INT NOT NULL UNIQUE,
    difficulty ENUM('Easy','Medium','Hard','Expert') NOT NULL,
    unlock_score INT NOT NULL DEFAULT 0
);

CREATE TABLE puzzles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_id INT NOT NULL,
    initial_grid CHAR(81) NOT NULL,
    solution_grid CHAR(81) NOT NULL,
    FOREIGN KEY (level_id) REFERENCES levels(id)
);

CREATE TABLE threats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    prevention TEXT NOT NULL
);

CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    threat_id INT NOT NULL,
    level_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NOT NULL,
    option_d VARCHAR(500) NOT NULL,
    correct_option CHAR(1) NOT NULL,
    explanation TEXT NOT NULL,
    FOREIGN KEY (threat_id) REFERENCES threats(id),
    FOREIGN KEY (level_id) REFERENCES levels(id),
    CHECK (correct_option IN ('A','B','C','D'))
);

CREATE TABLE game_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    session_token CHAR(64) NOT NULL UNIQUE,
    puzzle_id INT NOT NULL,
    current_level INT NOT NULL DEFAULT 1,
    score INT NOT NULL DEFAULT 0,
    hints_used INT NOT NULL DEFAULT 0,
    status ENUM('active','completed','abandoned') NOT NULL DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (puzzle_id) REFERENCES puzzles(id)
);

CREATE TABLE session_answers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT NOT NULL,
    question_id INT NOT NULL,
    selected_option CHAR(1) NOT NULL,
    is_correct BOOLEAN NOT NULL,
    points_earned INT NOT NULL DEFAULT 0,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES game_sessions(id),
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

INSERT INTO levels (name, level_number, difficulty, unlock_score) VALUES
('Beginner', 1, 'Easy', 0),
('Defender', 2, 'Easy', 100),
('Analyst', 3, 'Medium', 250),
('Incident Responder', 4, 'Hard', 450),
('Cyber Expert', 5, 'Expert', 700);

INSERT INTO threats (name, description, prevention) VALUES
('Phishing', 'Fraudulent messages designed to steal information.', 'Verify senders and links before responding.'),
('Smishing', 'Phishing conducted through SMS messages.', 'Avoid suspicious links and verify requests independently.'),
('Vishing', 'Fraudulent attempts to obtain information through voice calls.', 'Never share OTPs or passwords over calls.'),
('Ransomware', 'Malware that encrypts files and demands payment.', 'Maintain backups and keep software updated.'),
('Man-in-the-Middle', 'An attacker intercepts communication between parties.', 'Use HTTPS and trusted networks.'),
('DDoS', 'An attack that overwhelms a service with excessive traffic.', 'Use traffic filtering and DDoS protection.'),
('SQL Injection', 'Malicious SQL input used to manipulate database queries.', 'Use prepared statements and parameterized queries.'),
('Account Compromise', 'Unauthorized access to a user account.', 'Use unique passwords and multifactor authentication.');

-- Quiz system tables

CREATE TABLE IF NOT EXISTS formation_quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formation_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    passing_score INT NOT NULL DEFAULT 70,
    time_limit_minutes INT DEFAULT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    CONSTRAINT FK_quiz_formation FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS formation_quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    choices JSON NOT NULL,
    correct_index INT NOT NULL DEFAULT 0,
    explanation TEXT DEFAULT NULL,
    position INT NOT NULL DEFAULT 0,
    points INT NOT NULL DEFAULT 1,
    CONSTRAINT FK_question_quiz FOREIGN KEY (quiz_id) REFERENCES formation_quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS formation_quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    student_id INT NOT NULL,
    total_points INT NOT NULL DEFAULT 0,
    earned_points INT NOT NULL DEFAULT 0,
    score_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    passed TINYINT(1) NOT NULL DEFAULT 0,
    answers JSON NOT NULL,
    started_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    CONSTRAINT FK_result_quiz FOREIGN KEY (quiz_id) REFERENCES formation_quizzes(id) ON DELETE CASCADE,
    CONSTRAINT FK_result_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

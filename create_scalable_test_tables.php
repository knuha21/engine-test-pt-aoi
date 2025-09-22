<?php
require_once 'config/database.php';

function createScalableTestTables() {
    $db = getDBConnection();
    
    $tables = [
        "test_types" => "CREATE TABLE test_types (
            id INT PRIMARY KEY AUTO_INCREMENT,
            type_code VARCHAR(20) UNIQUE NOT NULL,
            type_name VARCHAR(100) NOT NULL,
            description TEXT,
            time_limit INT NULL,
            total_questions INT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "test_categories" => "CREATE TABLE test_categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            test_type_id INT NOT NULL,
            category_code VARCHAR(50) NOT NULL,
            category_name VARCHAR(100) NOT NULL,
            description TEXT,
            weight DECIMAL(5,2) DEFAULT 1.0,
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (test_type_id) REFERENCES test_types(id),
            UNIQUE KEY unique_category_per_test (test_type_id, category_code)
        )",
        
        "test_questions" => "CREATE TABLE test_questions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            test_type_id INT NOT NULL,
            category_id INT NOT NULL,
            question_code VARCHAR(50) NOT NULL,
            question_text TEXT NOT NULL,
            question_type ENUM('multiple_choice', 'true_false', 'essay', 'numeric', 'pattern') DEFAULT 'multiple_choice',
            options JSON NULL,
            correct_answer VARCHAR(255) NOT NULL,
            points INT DEFAULT 1,
            difficulty ENUM('very_easy', 'easy', 'medium', 'hard', 'very_hard') DEFAULT 'medium',
            time_limit INT NULL,
            explanation TEXT,
            image_path VARCHAR(255) NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (test_type_id) REFERENCES test_types(id),
            FOREIGN KEY (category_id) REFERENCES test_categories(id),
            UNIQUE KEY unique_question_code (test_type_id, question_code)
        )",
        
        "test_sessions" => "CREATE TABLE test_sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            participant_id INT NOT NULL,
            test_type_id INT NOT NULL,
            session_code VARCHAR(50) NOT NULL UNIQUE,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            status ENUM('not_started', 'in_progress', 'completed', 'expired', 'cancelled') DEFAULT 'not_started',
            total_score DECIMAL(8,2) DEFAULT 0,
            max_score DECIMAL(8,2) DEFAULT 0,
            time_spent INT DEFAULT 0,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (participant_id) REFERENCES participants(id),
            FOREIGN KEY (test_type_id) REFERENCES test_types(id),
            INDEX idx_session_code (session_code),
            INDEX idx_participant_test (participant_id, test_type_id)
        )",
        
        "test_answers" => "CREATE TABLE test_answers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            session_id INT NOT NULL,
            question_id INT NOT NULL,
            participant_answer TEXT,
            is_correct BOOLEAN DEFAULT FALSE,
            points_earned DECIMAL(5,2) DEFAULT 0,
            answer_time INT DEFAULT 0,
            question_data JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES test_sessions(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES test_questions(id),
            UNIQUE KEY unique_session_question (session_id, question_id)
        )",
        
        "test_results" => "CREATE TABLE test_results (
            id INT PRIMARY KEY AUTO_INCREMENT,
            session_id INT NOT NULL,
            participant_id INT NOT NULL,
            test_type_id INT NOT NULL,
            overall_score DECIMAL(8,2) DEFAULT 0,
            percentile INT NULL,
            result_data JSON NOT NULL,
            interpretation TEXT,
            is_processed BOOLEAN DEFAULT FALSE,
            processed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES test_sessions(id),
            FOREIGN KEY (participant_id) REFERENCES participants(id),
            FOREIGN KEY (test_type_id) REFERENCES test_types(id),
            INDEX idx_participant_test (participant_id, test_type_id)
        )"
    ];
    
    foreach ($tables as $tableName => $sql) {
        try {
            $db->exec($sql);
            echo "✓ Table '$tableName' created successfully\n";
        } catch (PDOException $e) {
            echo "ⓘ Table '$tableName': " . $e->getMessage() . "\n";
        }
    }
}

createScalableTestTables();
?>
-- Power: The Game - Database Schema
-- MySQL Database for multiplayer web-based game

CREATE DATABASE IF NOT EXISTS power_game;
USE power_game;

-- Users/Players table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_wins INT DEFAULT 0,
    total_games INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Games table
CREATE TABLE IF NOT EXISTS games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    game_name VARCHAR(100) NOT NULL,
    created_by INT NOT NULL,
    game_duration INT NOT NULL DEFAULT 7200, -- seconds (2 hours)
    round_duration INT NOT NULL DEFAULT 120, -- seconds (2 minutes)
    max_players INT NOT NULL DEFAULT 4,
    current_round INT DEFAULT 0,
    game_status ENUM('waiting', 'in_progress', 'completed') DEFAULT 'waiting',
    winner_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (winner_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Game players (which players are in which game)
CREATE TABLE IF NOT EXISTS game_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_id INT NOT NULL,
    player_color ENUM('red', 'yellow', 'green', 'white') NOT NULL,
    is_ready BOOLEAN DEFAULT FALSE,
    is_eliminated BOOLEAN DEFAULT FALSE,
    total_power INT DEFAULT 0,
    UNIQUE KEY unique_game_player (game_id, user_id),
    UNIQUE KEY unique_game_color (game_id, player_color),
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Units on the board
CREATE TABLE IF NOT EXISTS units (
    unit_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    owner_color ENUM('red', 'yellow', 'green', 'white') NOT NULL,
    unit_type ENUM('infantry', 'regiment', 'tank', 'heavy_tank', 'fighter', 'bomber',
                   'destroyer', 'cruiser', 'megamissile', 'power_unit') NOT NULL,
    location VARCHAR(10) NOT NULL, -- e.g., 'RHQ', 'R1', 'N', 'S1', 'RR' (reserve)
    power_value INT NOT NULL,
    is_new BOOLEAN DEFAULT TRUE, -- newly created this turn, can't move
    INDEX idx_game_location (game_id, location),
    INDEX idx_game_owner (game_id, owner_color),
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Commands for each turn
CREATE TABLE IF NOT EXISTS commands (
    command_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    round_number INT NOT NULL,
    player_color ENUM('red', 'yellow', 'green', 'white') NOT NULL,
    command_order INT NOT NULL, -- 1-5
    command_type ENUM('move', 'upgrade', 'create_megamissile', 'buy') NOT NULL,
    from_location VARCHAR(10),
    to_location VARCHAR(10),
    unit_ids TEXT, -- JSON array of unit IDs involved
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_game_round (game_id, round_number),
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Flags (one per player per game)
CREATE TABLE IF NOT EXISTS flags (
    flag_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    owner_color ENUM('red', 'yellow', 'green', 'white') NOT NULL,
    current_location VARCHAR(10) NOT NULL, -- normally at HQ
    captured_by ENUM('red', 'yellow', 'green', 'white') DEFAULT NULL,
    UNIQUE KEY unique_game_flag (game_id, owner_color),
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Round state tracking
CREATE TABLE IF NOT EXISTS round_state (
    state_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    round_number INT NOT NULL,
    round_start_time TIMESTAMP NOT NULL,
    round_end_time TIMESTAMP NULL,
    players_submitted TEXT, -- JSON array of colors who submitted
    UNIQUE KEY unique_game_round (game_id, round_number),
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Battle log for history
CREATE TABLE IF NOT EXISTS battle_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    round_number INT NOT NULL,
    location VARCHAR(10) NOT NULL,
    description TEXT NOT NULL,
    log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default AI opponents (optional, for single player)
INSERT INTO users (username, password_hash, email) VALUES
('AI_General_Patton', '', 'ai@power.game'),
('AI_General_Montgomery', '', 'ai@power.game'),
('AI_General_Rommel', '', 'ai@power.game')
ON DUPLICATE KEY UPDATE username=username;

<?php
// Power: The Game - Configuration File
// Database and game settings

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'power_game');
define('DB_USER', 'root'); // Change this to your MySQL username
define('DB_PASS', ''); // Change this to your MySQL password

// Game Configuration
define('SITE_URL', 'http://localhost/power'); // Change to your URL
define('GAME_TITLE', 'Power: The Game');

// Session Configuration
session_start();

// Database Connection
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Game Constants
define('UNIT_STATS', [
    'infantry' => ['power' => 20, 'moves' => 2, 'group' => 1],
    'regiment' => ['power' => 60, 'moves' => 2, 'group' => 2],
    'tank' => ['power' => 30, 'moves' => 3, 'group' => 1],
    'heavy_tank' => ['power' => 90, 'moves' => 3, 'group' => 2],
    'fighter' => ['power' => 25, 'moves' => 5, 'group' => 1],
    'bomber' => ['power' => 75, 'moves' => 5, 'group' => 2],
    'destroyer' => ['power' => 10, 'moves' => 1, 'group' => 1],
    'cruiser' => ['power' => 50, 'moves' => 1, 'group' => 2],
    'power_unit' => ['power' => 1, 'moves' => 0, 'group' => 0],
    'megamissile' => ['power' => 0, 'moves' => 0, 'group' => 0]
]);

define('UPGRADE_MAP', [
    'infantry' => 'regiment',
    'tank' => 'heavy_tank',
    'fighter' => 'bomber',
    'destroyer' => 'cruiser'
]);

define('PURCHASE_COSTS', [
    'infantry' => 2,
    'tank' => 3,
    'fighter' => 3,
    'destroyer' => 2
]);

// Map sectors
define('COUNTRIES', ['R' => 'red', 'Y' => 'yellow', 'G' => 'green', 'W' => 'white']);
define('ISLANDS', ['N', 'S', 'E', 'X', 'B']); // North, South, East, X-Island, Black
define('SEA_LANES', ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10', 'S11', 'S12']);

// Initialize game state for a new player
function initializeGameForPlayer($db, $game_id, $color) {
    // Create flag at HQ
    $hq = strtoupper($color) . 'HQ';
    $stmt = $db->prepare("
        INSERT INTO flags (game_id, owner_color, current_location)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$game_id, $color, $hq]);

    // Give starting units (2 infantry, 2 tanks, 2 fighters, 2 destroyers)
    $starting_units = [
        ['type' => 'infantry', 'count' => 2],
        ['type' => 'tank', 'count' => 2],
        ['type' => 'fighter', 'count' => 2],
        ['type' => 'destroyer', 'count' => 2]
    ];

    $reserve = strtoupper($color) . 'R';

    foreach ($starting_units as $unit_group) {
        for ($i = 0; $i < $unit_group['count']; $i++) {
            $power = UNIT_STATS[$unit_group['type']]['power'];
            $stmt = $db->prepare("
                INSERT INTO units (game_id, owner_color, unit_type, location, power_value, is_new)
                VALUES (?, ?, ?, ?, ?, FALSE)
            ");
            $stmt->execute([$game_id, $color, $unit_group['type'], $reserve, $power]);
        }
    }
}

?>

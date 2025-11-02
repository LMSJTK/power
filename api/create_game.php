<?php
require_once '../config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../lobby.php');
}

$db = getDB();
$user_id = getCurrentUserId();

$game_name = trim($_POST['game_name'] ?? '');
$max_players = (int)($_POST['max_players'] ?? 4);
$game_duration = (int)($_POST['game_duration'] ?? 7200);
$round_duration = (int)($_POST['round_duration'] ?? 120);

// Validation
if (empty($game_name)) {
    $_SESSION['error'] = 'Game name is required.';
    redirect('../lobby.php');
}

if (!in_array($max_players, [2, 3, 4])) {
    $max_players = 4;
}

try {
    $db->beginTransaction();

    // Create game
    $stmt = $db->prepare("
        INSERT INTO games (game_name, created_by, game_duration, round_duration, max_players)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$game_name, $user_id, $game_duration, $round_duration, $max_players]);
    $game_id = $db->lastInsertId();

    // Add creator as first player (Red)
    $stmt = $db->prepare("
        INSERT INTO game_players (game_id, user_id, player_color)
        VALUES (?, ?, 'red')
    ");
    $stmt->execute([$game_id, $user_id]);

    // Initialize game state (units, flags, etc.)
    initializeGameForPlayer($db, $game_id, 'red');

    $db->commit();

    redirect("../game_room.php?id=$game_id");
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = 'Failed to create game: ' . $e->getMessage();
    redirect('../lobby.php');
}
?>

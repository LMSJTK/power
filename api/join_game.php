<?php
require_once '../config.php';
requireLogin();

$game_id = (int)($_GET['game_id'] ?? 0);
$user_id = getCurrentUserId();

if (!$game_id) {
    redirect('../lobby.php');
}

$db = getDB();

try {
    $db->beginTransaction();

    // Check if game exists and is waiting
    $stmt = $db->prepare("SELECT * FROM games WHERE game_id = ? AND game_status = 'waiting'");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();

    if (!$game) {
        throw new Exception('Game not found or already started.');
    }

    // Check if user is already in this game
    $stmt = $db->prepare("SELECT * FROM game_players WHERE game_id = ? AND user_id = ?");
    $stmt->execute([$game_id, $user_id]);
    if ($stmt->fetch()) {
        // Already in game, just redirect to game room
        $db->commit();
        redirect("../game_room.php?id=$game_id");
    }

    // Count current players
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM game_players WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $player_count = $stmt->fetch()['count'];

    if ($player_count >= $game['max_players']) {
        throw new Exception('Game is full.');
    }

    // Assign color (in order: red, yellow, green, white)
    $colors = ['red', 'yellow', 'green', 'white'];
    $stmt = $db->prepare("SELECT player_color FROM game_players WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $taken_colors = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $available_color = null;
    foreach ($colors as $color) {
        if (!in_array($color, $taken_colors)) {
            $available_color = $color;
            break;
        }
    }

    if (!$available_color) {
        throw new Exception('No available player slots.');
    }

    // Add player to game
    $stmt = $db->prepare("
        INSERT INTO game_players (game_id, user_id, player_color)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$game_id, $user_id, $available_color]);

    // Initialize game state for this player
    require_once 'create_game.php';
    initializeGameForPlayer($db, $game_id, $available_color);

    $db->commit();

    redirect("../game_room.php?id=$game_id");
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = $e->getMessage();
    redirect('../lobby.php');
}
?>

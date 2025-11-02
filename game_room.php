<?php
require_once 'config.php';
requireLogin();

$game_id = (int)($_GET['id'] ?? 0);
$user_id = getCurrentUserId();

if (!$game_id) {
    redirect('lobby.php');
}

$db = getDB();

// Get game details
$stmt = $db->prepare("
    SELECT g.*, u.username as host_name
    FROM games g
    JOIN users u ON g.created_by = u.user_id
    WHERE g.game_id = ?
");
$stmt->execute([$game_id]);
$game = $stmt->fetch();

if (!$game || $game['game_status'] !== 'waiting') {
    redirect('lobby.php');
}

// Get players in game
$stmt = $db->prepare("
    SELECT gp.*, u.username
    FROM game_players gp
    JOIN users u ON gp.user_id = u.user_id
    WHERE gp.game_id = ?
    ORDER BY gp.id
");
$stmt->execute([$game_id]);
$players = $stmt->fetchAll();

// Check if current user is in this game
$is_in_game = false;
$my_color = null;
foreach ($players as $player) {
    if ($player['user_id'] == $user_id) {
        $is_in_game = true;
        $my_color = $player['player_color'];
        break;
    }
}

if (!$is_in_game) {
    redirect('lobby.php');
}

$is_host = ($game['created_by'] == $user_id);

// Handle ready toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_ready'])) {
    $stmt = $db->prepare("
        UPDATE game_players
        SET is_ready = NOT is_ready
        WHERE game_id = ? AND user_id = ?
    ");
    $stmt->execute([$game_id, $user_id]);
    redirect("game_room.php?id=$game_id");
}

// Handle start game (host only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_game']) && $is_host) {
    // Check if all players are ready
    $all_ready = true;
    foreach ($players as $player) {
        if (!$player['is_ready'] && $player['user_id'] != $user_id) {
            $all_ready = false;
            break;
        }
    }

    if ($all_ready && count($players) >= 2) {
        // Start the game
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                UPDATE games
                SET game_status = 'in_progress', started_at = NOW()
                WHERE game_id = ?
            ");
            $stmt->execute([$game_id]);

            // Create first round
            $stmt = $db->prepare("
                INSERT INTO round_state (game_id, round_number, round_start_time, players_submitted)
                VALUES (?, 1, NOW(), '[]')
            ");
            $stmt->execute([$game_id]);

            $db->commit();
            redirect("game.php?id=$game_id");
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Failed to start game: " . $e->getMessage();
        }
    } else {
        $error = "All players must be ready before starting.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Room - <?php echo htmlspecialchars($game['game_name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <meta http-equiv="refresh" content="5">
</head>
<body>
    <div class="lobby-container">
        <header>
            <h1>Game Room: <?php echo htmlspecialchars($game['game_name']); ?></h1>
            <div class="user-info">
                <a href="lobby.php" class="btn btn-small">Back to Lobby</a>
            </div>
        </header>

        <div class="lobby-content">
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="section">
                <h2>Game Settings</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <p><strong>Host:</strong> <?php echo htmlspecialchars($game['host_name']); ?></p>
                        <p><strong>Max Players:</strong> <?php echo $game['max_players']; ?></p>
                        <p><strong>Your Color:</strong> <span class="color-badge color-<?php echo $my_color; ?>"><?php echo ucfirst($my_color); ?></span></p>
                    </div>
                    <div>
                        <p><strong>Game Duration:</strong> <?php echo ($game['game_duration'] / 60); ?> minutes</p>
                        <p><strong>Round Duration:</strong> <?php echo $game['round_duration']; ?> seconds</p>
                        <p><strong>Players:</strong> <?php echo count($players); ?>/<?php echo $game['max_players']; ?></p>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Players</h2>
                <table class="games-table">
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th>Color</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $player): ?>
                            <tr <?php echo ($player['user_id'] == $user_id) ? 'style="background: #e8f4f8;"' : ''; ?>>
                                <td>
                                    <?php echo htmlspecialchars($player['username']); ?>
                                    <?php if ($player['user_id'] == $game['created_by']): ?>
                                        <strong>(Host)</strong>
                                    <?php endif; ?>
                                    <?php if ($player['user_id'] == $user_id): ?>
                                        <strong>(You)</strong>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="color-badge color-<?php echo $player['player_color']; ?>">
                                        <?php echo ucfirst($player['player_color']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($player['is_ready']): ?>
                                        <span class="status status-in_progress">Ready</span>
                                    <?php else: ?>
                                        <span class="status status-waiting">Not Ready</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php for ($i = count($players); $i < $game['max_players']; $i++): ?>
                            <tr style="opacity: 0.5;">
                                <td colspan="3" class="text-center"><em>Waiting for player...</em></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
                    <form method="POST">
                        <?php
                        $my_ready = false;
                        foreach ($players as $player) {
                            if ($player['user_id'] == $user_id) {
                                $my_ready = $player['is_ready'];
                                break;
                            }
                        }
                        ?>
                        <button type="submit" name="toggle_ready" class="btn <?php echo $my_ready ? 'btn-success' : 'btn-primary'; ?>">
                            <?php echo $my_ready ? 'Ready!' : 'Not Ready'; ?>
                        </button>
                    </form>

                    <?php if ($is_host): ?>
                        <form method="POST">
                            <button type="submit" name="start_game" class="btn btn-success">
                                Start Game
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <h2>How to Play</h2>
                <div class="rules-summary">
                    <ul>
                        <li>You will be assigned one of four countries with your starting forces</li>
                        <li>Each round lasts <?php echo $game['round_duration']; ?> seconds to issue commands</li>
                        <li>You can issue up to <strong>5 commands</strong> per round</li>
                        <li>Move units to attack enemy sectors and capture their Flag</li>
                        <li>Only <strong>Infantry</strong> and <strong>Regiment</strong> units can capture Flags</li>
                        <li>Earn Power Units by occupying enemy countries</li>
                        <li>Upgrade 3 identical units into a powerful Group II unit</li>
                        <li>Win by capturing all enemy Flags or having the most power when time expires</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

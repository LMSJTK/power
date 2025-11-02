<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$user_id = getCurrentUserId();
$username = getCurrentUsername();

// Get user stats
$stmt = $db->prepare("SELECT total_wins, total_games FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_stats = $stmt->fetch();

// Get available games
$stmt = $db->query("
    SELECT g.*, u.username as creator_name,
           COUNT(gp.id) as player_count
    FROM games g
    JOIN users u ON g.created_by = u.user_id
    LEFT JOIN game_players gp ON g.game_id = gp.game_id
    WHERE g.game_status = 'waiting'
    GROUP BY g.game_id
    ORDER BY g.created_at DESC
");
$available_games = $stmt->fetchAll();

// Get user's current games
$stmt = $db->prepare("
    SELECT g.*, u.username as creator_name,
           COUNT(gp.id) as player_count,
           ugp.player_color as my_color
    FROM games g
    JOIN users u ON g.created_by = u.user_id
    JOIN game_players gp ON g.game_id = gp.game_id
    JOIN game_players ugp ON g.game_id = ugp.game_id AND ugp.user_id = ?
    WHERE g.game_status IN ('waiting', 'in_progress')
    GROUP BY g.game_id
    ORDER BY g.created_at DESC
");
$stmt->execute([$user_id]);
$my_games = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Lobby - Power: The Game</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="lobby-container">
        <header>
            <h1>Power: The Game - Lobby</h1>
            <div class="user-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></span>
                <span>Wins: <?php echo $user_stats['total_wins']; ?>/<?php echo $user_stats['total_games']; ?></span>
                <a href="logout.php" class="btn btn-small">Logout</a>
            </div>
        </header>

        <div class="lobby-content">
            <div class="section">
                <div class="section-header">
                    <h2>My Games</h2>
                </div>
                <?php if (empty($my_games)): ?>
                    <p class="empty-message">You're not in any games. Create or join one below!</p>
                <?php else: ?>
                    <table class="games-table">
                        <thead>
                            <tr>
                                <th>Game Name</th>
                                <th>Host</th>
                                <th>Players</th>
                                <th>Status</th>
                                <th>Your Color</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_games as $game): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($game['game_name']); ?></td>
                                    <td><?php echo htmlspecialchars($game['creator_name']); ?></td>
                                    <td><?php echo $game['player_count']; ?>/<?php echo $game['max_players']; ?></td>
                                    <td>
                                        <span class="status status-<?php echo $game['game_status']; ?>">
                                            <?php echo ucfirst($game['game_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="color-badge color-<?php echo $game['my_color']; ?>">
                                            <?php echo ucfirst($game['my_color']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($game['game_status'] === 'waiting'): ?>
                                            <a href="game_room.php?id=<?php echo $game['game_id']; ?>" class="btn btn-small">Enter Room</a>
                                        <?php else: ?>
                                            <a href="game.php?id=<?php echo $game['game_id']; ?>" class="btn btn-small btn-success">Play</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="section">
                <div class="section-header">
                    <h2>Available Games</h2>
                    <button onclick="showCreateGameModal()" class="btn btn-primary">Create New Game</button>
                </div>
                <?php if (empty($available_games)): ?>
                    <p class="empty-message">No games available. Be the first to create one!</p>
                <?php else: ?>
                    <table class="games-table">
                        <thead>
                            <tr>
                                <th>Game Name</th>
                                <th>Host</th>
                                <th>Players</th>
                                <th>Game Duration</th>
                                <th>Round Duration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_games as $game): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($game['game_name']); ?></td>
                                    <td><?php echo htmlspecialchars($game['creator_name']); ?></td>
                                    <td><?php echo $game['player_count']; ?>/<?php echo $game['max_players']; ?></td>
                                    <td><?php echo ($game['game_duration'] / 60); ?> min</td>
                                    <td><?php echo $game['round_duration']; ?>s</td>
                                    <td>
                                        <?php if ($game['player_count'] < $game['max_players']): ?>
                                            <a href="api/join_game.php?game_id=<?php echo $game['game_id']; ?>"
                                               class="btn btn-small btn-success">Join</a>
                                        <?php else: ?>
                                            <span class="text-muted">Full</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="section">
                <h2>Game Rules</h2>
                <div class="rules-summary">
                    <ul>
                        <li><strong>Objective:</strong> Capture all enemy Flags with Infantry/Regiment units</li>
                        <li><strong>Commands:</strong> Issue up to 5 commands per round</li>
                        <li><strong>Units:</strong> Infantry, Tanks, Fighters, Destroyers and their upgraded versions</li>
                        <li><strong>Special Weapon:</strong> MegaMissile destroys all units in a sector</li>
                        <li><strong>Power Economy:</strong> Earn Power Units by occupying enemy countries</li>
                        <li><strong>Victory:</strong> Capture all Flags OR have highest total power when time expires</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Game Modal -->
    <div id="createGameModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideCreateGameModal()">&times;</span>
            <h2>Create New Game</h2>
            <form action="api/create_game.php" method="POST">
                <div class="form-group">
                    <label for="game_name">Game Name:</label>
                    <input type="text" id="game_name" name="game_name" required>
                </div>
                <div class="form-group">
                    <label for="max_players">Maximum Players:</label>
                    <select id="max_players" name="max_players">
                        <option value="2">2 Players</option>
                        <option value="3">3 Players</option>
                        <option value="4" selected>4 Players</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="game_duration">Game Duration:</label>
                    <select id="game_duration" name="game_duration">
                        <option value="3600">1 Hour</option>
                        <option value="7200" selected>2 Hours</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="round_duration">Round Duration:</label>
                    <select id="round_duration" name="round_duration">
                        <option value="60">1 Minute</option>
                        <option value="90">1.5 Minutes</option>
                        <option value="120" selected>2 Minutes</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Create Game</button>
                <button type="button" onclick="hideCreateGameModal()" class="btn">Cancel</button>
            </form>
        </div>
    </div>

    <script src="js/lobby.js"></script>
</body>
</html>

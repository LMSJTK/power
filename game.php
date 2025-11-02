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
$stmt = $db->prepare("SELECT * FROM games WHERE game_id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch();

if (!$game) {
    redirect('lobby.php');
}

// Get my player info
$stmt = $db->prepare("SELECT * FROM game_players WHERE game_id = ? AND user_id = ?");
$stmt->execute([$game_id, $user_id]);
$my_player = $stmt->fetch();

if (!$my_player) {
    redirect('lobby.php');
}

$my_color = $my_player['player_color'];

// Get all players
$stmt = $db->prepare("
    SELECT gp.*, u.username
    FROM game_players gp
    JOIN users u ON gp.user_id = u.user_id
    WHERE gp.game_id = ?
    ORDER BY FIELD(gp.player_color, 'red', 'yellow', 'green', 'white')
");
$stmt->execute([$game_id]);
$all_players = $stmt->fetchAll();

// Get current round
$stmt = $db->prepare("
    SELECT * FROM round_state
    WHERE game_id = ?
    ORDER BY round_number DESC
    LIMIT 1
");
$stmt->execute([$game_id]);
$current_round = $stmt->fetch();

// Get all units on the board
$stmt = $db->prepare("SELECT * FROM units WHERE game_id = ?");
$stmt->execute([$game_id]);
$all_units = $stmt->fetchAll();

// Group units by location
$units_by_location = [];
foreach ($all_units as $unit) {
    if (!isset($units_by_location[$unit['location']])) {
        $units_by_location[$unit['location']] = [];
    }
    $units_by_location[$unit['location']][] = $unit;
}

// Get my commands for current round
$stmt = $db->prepare("
    SELECT * FROM commands
    WHERE game_id = ? AND round_number = ? AND player_color = ?
    ORDER BY command_order
");
$stmt->execute([$game_id, $current_round['round_number'] ?? 1, $my_color]);
$my_commands = $stmt->fetchAll();

// Calculate time remaining in round
$time_remaining = 0;
if ($current_round && $current_round['round_end_time'] === null) {
    $round_start = strtotime($current_round['round_start_time']);
    $round_duration = $game['round_duration'];
    $time_remaining = max(0, $round_duration - (time() - $round_start));
}

// Define map sectors (simplified version)
$map_sectors = [
    // Red country (top-left)
    'RHQ' => ['row' => 2, 'col' => 2, 'type' => 'hq', 'color' => 'red'],
    'R1' => ['row' => 1, 'col' => 2, 'type' => 'land', 'country' => 'red'],
    'R2' => ['row' => 2, 'col' => 1, 'type' => 'land', 'country' => 'red'],
    'R3' => ['row' => 2, 'col' => 3, 'type' => 'land', 'country' => 'red'],
    'R4' => ['row' => 3, 'col' => 2, 'type' => 'land', 'country' => 'red'],

    // Yellow country (top-right)
    'YHQ' => ['row' => 2, 'col' => 10, 'type' => 'hq', 'color' => 'yellow'],
    'Y1' => ['row' => 1, 'col' => 10, 'type' => 'land', 'country' => 'yellow'],
    'Y2' => ['row' => 2, 'col' => 9, 'type' => 'land', 'country' => 'yellow'],
    'Y3' => ['row' => 2, 'col' => 11, 'type' => 'land', 'country' => 'yellow'],
    'Y4' => ['row' => 3, 'col' => 10, 'type' => 'land', 'country' => 'yellow'],

    // Green country (bottom-right)
    'GHQ' => ['row' => 10, 'col' => 10, 'type' => 'hq', 'color' => 'green'],
    'G1' => ['row' => 9, 'col' => 10, 'type' => 'land', 'country' => 'green'],
    'G2' => ['row' => 10, 'col' => 9, 'type' => 'land', 'country' => 'green'],
    'G3' => ['row' => 10, 'col' => 11, 'type' => 'land', 'country' => 'green'],
    'G4' => ['row' => 11, 'col' => 10, 'type' => 'land', 'country' => 'green'],

    // White country (bottom-left)
    'WHQ' => ['row' => 10, 'col' => 2, 'type' => 'hq', 'color' => 'white'],
    'W1' => ['row' => 9, 'col' => 2, 'type' => 'land', 'country' => 'white'],
    'W2' => ['row' => 10, 'col' => 1, 'type' => 'land', 'country' => 'white'],
    'W3' => ['row' => 10, 'col' => 3, 'type' => 'land', 'country' => 'white'],
    'W4' => ['row' => 11, 'col' => 2, 'type' => 'land', 'country' => 'white'],

    // Islands
    'N' => ['row' => 1, 'col' => 6, 'type' => 'island'],
    'S' => ['row' => 11, 'col' => 6, 'type' => 'island'],
    'E' => ['row' => 6, 'col' => 11, 'type' => 'island'],
    'W' => ['row' => 6, 'col' => 1, 'type' => 'island'],
    'X' => ['row' => 6, 'col' => 6, 'type' => 'island'],

    // Sea Lanes
    'S1' => ['row' => 4, 'col' => 6, 'type' => 'sea'],
    'S2' => ['row' => 6, 'col' => 4, 'type' => 'sea'],
    'S3' => ['row' => 6, 'col' => 8, 'type' => 'sea'],
    'S4' => ['row' => 8, 'col' => 6, 'type' => 'sea'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playing: <?php echo htmlspecialchars($game['game_name']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        // Auto-refresh if round is active
        <?php if ($time_remaining > 0): ?>
        setTimeout(function() {
            location.reload();
        }, <?php echo ($time_remaining + 2) * 1000; ?>);
        <?php endif; ?>
    </script>
</head>
<body>
    <div class="game-container">
        <div class="game-board">
            <div class="section">
                <h2><?php echo htmlspecialchars($game['game_name']); ?></h2>

                <?php if ($game['game_status'] === 'completed'): ?>
                    <div class="alert alert-info">
                        <strong>Game Over!</strong>
                        <?php
                        if ($game['winner_id']) {
                            $stmt = $db->prepare("SELECT username FROM users WHERE user_id = ?");
                            $stmt->execute([$game['winner_id']]);
                            $winner = $stmt->fetch();
                            echo "Winner: " . htmlspecialchars($winner['username']);
                        } else {
                            echo "Time expired - Winner determined by total power";
                        }
                        ?>
                        <br><a href="lobby.php">Return to Lobby</a>
                    </div>
                <?php endif; ?>

                <?php if ($time_remaining > 0): ?>
                    <div class="timer <?php echo ($time_remaining < 10) ? 'warning' : ''; ?>">
                        Round <?php echo $current_round['round_number']; ?>:
                        <span id="timer"><?php echo gmdate("i:s", $time_remaining); ?></span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Resolving battles and moving to next round...
                    </div>
                <?php endif; ?>

                <!-- Simplified Map View -->
                <div style="background: #e0e0e0; padding: 20px; border-radius: 10px; min-height: 500px; position: relative;">
                    <?php foreach ($map_sectors as $sector_id => $sector): ?>
                        <div class="sector sector-<?php echo $sector['type']; ?>"
                             style="position: absolute;
                                    left: <?php echo ($sector['col'] * 60); ?>px;
                                    top: <?php echo ($sector['row'] * 60); ?>px;
                                    width: 50px;
                                    height: 50px;"
                             onclick="selectSector('<?php echo $sector_id; ?>')">
                            <div class="sector-label"><?php echo $sector_id; ?></div>
                            <?php if (isset($units_by_location[$sector_id])): ?>
                                <div class="sector-units">
                                    <?php
                                    $unit_counts = [];
                                    foreach ($units_by_location[$sector_id] as $unit) {
                                        $key = $unit['owner_color'] . '_' . $unit['unit_type'];
                                        if (!isset($unit_counts[$key])) {
                                            $unit_counts[$key] = ['color' => $unit['owner_color'], 'type' => $unit['unit_type'], 'count' => 0];
                                        }
                                        $unit_counts[$key]['count']++;
                                    }
                                    foreach ($unit_counts as $uc):
                                    ?>
                                        <div class="unit-icon color-<?php echo $uc['color']; ?>"
                                             title="<?php echo $uc['count']; ?> <?php echo $uc['type']; ?>">
                                            <?php echo substr($uc['type'], 0, 1); ?><?php echo $uc['count']; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="game-sidebar">
            <div class="section">
                <h3>Your Color: <span class="color-badge color-<?php echo $my_color; ?>"><?php echo ucfirst($my_color); ?></span></h3>

                <h4>Players</h4>
                <ul class="player-list">
                    <?php foreach ($all_players as $player): ?>
                        <li class="player-item color-<?php echo $player['player_color']; ?>"
                            style="background-color: rgba(<?php
                            echo $player['player_color'] === 'red' ? '220,53,69' :
                                ($player['player_color'] === 'yellow' ? '255,193,7' :
                                ($player['player_color'] === 'green' ? '40,167,69' : '248,249,250'));
                            ?>, 0.2);">
                            <span><?php echo htmlspecialchars($player['username']); ?></span>
                            <span><?php echo $player['total_power']; ?> power</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="command-pad">
                <h4>Commands (<?php echo count($my_commands); ?>/5)</h4>
                <ul class="command-list">
                    <?php if (empty($my_commands)): ?>
                        <li class="empty-message" style="padding: 10px;">No commands issued</li>
                    <?php else: ?>
                        <?php foreach ($my_commands as $cmd): ?>
                            <li class="command-item">
                                <span><?php echo strtoupper($cmd['command_type']); ?>:
                                    <?php echo $cmd['from_location']; ?> → <?php echo $cmd['to_location']; ?>
                                </span>
                                <button onclick="cancelCommand(<?php echo $cmd['command_id']; ?>)" class="btn btn-small">×</button>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>

                <div id="selectedSector" style="margin-top: 15px; padding: 10px; background: white; border-radius: 5px; display: none;">
                    <h5>Selected Sector: <span id="sectorName"></span></h5>
                    <div id="sectorUnits"></div>
                    <button onclick="clearSelection()" class="btn btn-small">Clear</button>
                </div>
            </div>

            <div style="margin-top: 15px;">
                <a href="lobby.php" class="btn">Leave Game</a>
            </div>
        </div>
    </div>

    <script src="js/game.js"></script>
    <script>
        let selectedSector = null;
        let gameId = <?php echo $game_id; ?>;
        let myColor = '<?php echo $my_color; ?>';

        function selectSector(sectorId) {
            selectedSector = sectorId;
            document.getElementById('selectedSector').style.display = 'block';
            document.getElementById('sectorName').textContent = sectorId;

            // In a full implementation, this would show units and allow commands
            console.log('Selected sector:', sectorId);
        }

        function clearSelection() {
            selectedSector = null;
            document.getElementById('selectedSector').style.display = 'none';
        }

        function cancelCommand(commandId) {
            if (confirm('Cancel this command?')) {
                window.location.href = 'api/cancel_command.php?id=' + commandId;
            }
        }

        // Update timer countdown
        <?php if ($time_remaining > 0): ?>
        let timeRemaining = <?php echo $time_remaining; ?>;
        setInterval(function() {
            timeRemaining--;
            if (timeRemaining <= 0) {
                document.getElementById('timer').textContent = '00:00';
                location.reload();
            } else {
                let minutes = Math.floor(timeRemaining / 60);
                let seconds = timeRemaining % 60;
                document.getElementById('timer').textContent =
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>

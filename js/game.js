// Power: The Game - Game Board JavaScript

let selectedFrom = null;
let selectedTo = null;

// Handle sector selection for move commands
document.addEventListener('DOMContentLoaded', function() {
    // Game board interactions will be handled here
    console.log('Power: The Game loaded');
});

// Submit a move command
function submitMoveCommand(from, to) {
    // Send AJAX request to create command
    fetch('api/submit_command.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            game_id: gameId,
            command_type: 'move',
            from_location: from,
            to_location: to
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to submit command');
    });
}

// Submit an upgrade command
function submitUpgradeCommand(location, unitIds) {
    fetch('api/submit_command.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            game_id: gameId,
            command_type: 'upgrade',
            location: location,
            unit_ids: unitIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to submit command');
    });
}

// End turn early
function endTurn() {
    if (confirm('End your turn now? You cannot issue more commands this round.')) {
        fetch('api/end_turn.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                game_id: gameId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
}

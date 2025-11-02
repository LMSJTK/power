// Power: The Game - Lobby JavaScript

function showCreateGameModal() {
    document.getElementById('createGameModal').style.display = 'block';
}

function hideCreateGameModal() {
    document.getElementById('createGameModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('createGameModal');
    if (event.target === modal) {
        hideCreateGameModal();
    }
}

// Auto-refresh lobby every 5 seconds
setInterval(function() {
    // Only refresh if not in a modal
    if (document.getElementById('createGameModal').style.display !== 'block') {
        location.reload();
    }
}, 5000);

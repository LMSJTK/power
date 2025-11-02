<?php
require_once 'config.php';

// Redirect to lobby if logged in, otherwise to login
if (isLoggedIn()) {
    redirect('lobby.php');
} else {
    redirect('login.php');
}
?>


<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';

$token = $_SESSION['game_token'] ?? '';

if ($token === '') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT gs.score, gs.hints_used, gs.status, l.name AS level_name
     FROM game_sessions gs
     JOIN levels l ON l.level_number = gs.current_level
     WHERE gs.session_token = ?
     LIMIT 1"
);

$stmt->execute([$token]);
$result = $stmt->fetch();

if (!$result || $result['status'] !== 'completed') {
    header('Location: game.php');
    exit;
}

unset($_SESSION['game_token']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Results | The Threat Matrix</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/game.css">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="index.php">THREAT<span>MATRIX</span></a>
        <span class="guest-label">GAME COMPLETED</span>
    </header>

    <main class="result-container">
        <p class="eyebrow">MISSION ACCOMPLISHED</p>
        <h1>Threat Matrix Completed!</h1>
        <p class="subtitle">You completed the Sudoku cybersecurity challenge.</p>

        <div class="result-card">
            <p>Level Completed</p>
            <h2><?= htmlspecialchars($result['level_name'], ENT_QUOTES, 'UTF-8') ?></h2>

            <p>Final Score</p>
            <strong class="final-score"><?= (int) $result['score'] ?></strong>

            <p>Hints Used: <?= (int) $result['hints_used'] ?></p>

            <a class="primary-button" href="index.php">Play Again</a>
        </div>
    </main>

    <footer>
        <p>Cybersecurity Sudoku: The Threat Matrix</p>
        <p>Learn cybersecurity through play.</p>
    </footer>
</body>
</html>

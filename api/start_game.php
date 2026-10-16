
<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/database.php';

requirePost();
requireCsrf();

$levelNumber = filter_input(INPUT_POST, 'level', FILTER_VALIDATE_INT);

if (!$levelNumber || $levelNumber < 1 || $levelNumber > 5) {
    jsonResponse(['success' => false, 'message' => 'Invalid level.'], 422);
}

$stmt = $pdo->prepare(
    'SELECT id, name, level_number FROM levels WHERE level_number = ?'
);
$stmt->execute([$levelNumber]);
$level = $stmt->fetch();

if (!$level) {
    jsonResponse(['success' => false, 'message' => 'Level not found.'], 404);
}

$stmt = $pdo->prepare(
    'SELECT id, initial_grid FROM puzzles WHERE level_id = ? ORDER BY RAND() LIMIT 1'
);
$stmt->execute([$level['id']]);
$puzzle = $stmt->fetch();

if (!$puzzle) {
    jsonResponse(['success' => false, 'message' => 'No puzzle available.'], 404);
}

$token = bin2hex(random_bytes(32));

$stmt = $pdo->prepare(
    'INSERT INTO game_sessions (session_token, puzzle_id, current_level)
     VALUES (?, ?, ?)'
);
$stmt->execute([$token, $puzzle['id'], $levelNumber]);

$_SESSION['game_token'] = $token;
$_SESSION['player_grid_game_id'] = (int) $pdo->lastInsertId();
$_SESSION['player_grid'] = $puzzle['initial_grid'];
$_SESSION['unlocked_cells'] = [];

jsonResponse([
    'success' => true,
    'level' => $level['name'],
    'level_number' => $levelNumber,
    'grid' => $puzzle['initial_grid'],
    'score' => 0,
    'csrf_token' => $_SESSION['csrf_token']
]);

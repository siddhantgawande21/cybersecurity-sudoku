
<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/database.php';

requirePost();
requireCsrf();

$token = $_SESSION['game_token'] ?? '';
$cellIndex = filter_input(INPUT_POST, 'cell_index', FILTER_VALIDATE_INT);

if ($token === '' || $cellIndex === false || $cellIndex === null || $cellIndex < 0 || $cellIndex > 80) {
    jsonResponse(['success' => false, 'message' => 'Invalid game or cell.'], 422);
}

$stmt = $pdo->prepare(
    "SELECT gs.id, gs.current_level, p.initial_grid
     FROM game_sessions gs
     JOIN puzzles p ON p.id = gs.puzzle_id
     WHERE gs.session_token = ? AND gs.status = 'active'
     LIMIT 1"
);
$stmt->execute([$token]);
$game = $stmt->fetch();

if (!$game || $game['initial_grid'][$cellIndex] !== '0') {
    jsonResponse(['success' => false, 'message' => 'This cell cannot be selected.'], 422);
}

$stmt = $pdo->prepare(
    'SELECT id FROM levels WHERE level_number = ?'
);
$stmt->execute([$game['current_level']]);
$level = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT id, question_text, option_a, option_b, option_c, option_d
     FROM questions
     WHERE level_id = ?
     ORDER BY RAND()
     LIMIT 1'
);
$stmt->execute([$level['id']]);
$question = $stmt->fetch();

if (!$question) {
    jsonResponse(['success' => false, 'message' => 'No questions are available for this level.'], 404);
}

$_SESSION['current_question_id'] = (int) $question['id'];
$_SESSION['current_cell_index'] = $cellIndex;
$_SESSION['current_game_id'] = (int) $game['id'];

jsonResponse([
    'success' => true,
    'question_id' => (int) $question['id'],
    'cell_index' => $cellIndex,
    'question' => $question['question_text'],
    'options' => [
        'A' => $question['option_a'],
        'B' => $question['option_b'],
        'C' => $question['option_c'],
        'D' => $question['option_d']
    ]
]);

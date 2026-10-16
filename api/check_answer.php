
<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/database.php';

requirePost();
requireCsrf();

$token = $_SESSION['game_token'] ?? '';
$questionId = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
$answer = strtoupper(trim($_POST['answer'] ?? ''));

if (
    $token === '' ||
    !$questionId ||
    !in_array($answer, ['A', 'B', 'C', 'D'], true) ||
    !isset(
        $_SESSION['current_question_id'],
        $_SESSION['current_cell_index'],
        $_SESSION['current_game_id']
    ) ||
    $questionId !== (int) $_SESSION['current_question_id']
) {
    jsonResponse(['success' => false, 'message' => 'Invalid answer request.'], 422);
}

$cellIndex = (int) $_SESSION['current_cell_index'];
$gameId = (int) $_SESSION['current_game_id'];

$stmt = $pdo->prepare(
    "SELECT gs.id, gs.score, gs.current_level, p.initial_grid
     FROM game_sessions gs
     JOIN puzzles p ON p.id = gs.puzzle_id
     WHERE gs.id = ? AND gs.session_token = ? AND gs.status = 'active'
     LIMIT 1"
);
$stmt->execute([$gameId, $token]);
$game = $stmt->fetch();

if (!$game) {
    jsonResponse(['success' => false, 'message' => 'Your game session is no longer active.'], 403);
}

if (
    $cellIndex < 0 ||
    $cellIndex > 80 ||
    $game['initial_grid'][$cellIndex] !== '0'
) {
    jsonResponse(['success' => false, 'message' => 'Invalid Sudoku cell.'], 422);
}

$stmt = $pdo->prepare(
    'SELECT q.correct_option, q.explanation, q.level_id
     FROM questions q
     WHERE q.id = ?'
);
$stmt->execute([$questionId]);
$question = $stmt->fetch();

if (!$question) {
    jsonResponse(['success' => false, 'message' => 'Question not found.'], 404);
}

$stmt = $pdo->prepare(
    'SELECT id FROM levels WHERE level_number = ?'
);
$stmt->execute([$game['current_level']]);
$level = $stmt->fetch();

if (!$level || (int) $question['level_id'] !== (int) $level['id']) {
    jsonResponse(['success' => false, 'message' => 'Question does not match your level.'], 403);
}

$correct = $answer === $question['correct_option'];
$points = $correct ? 10 : 0;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO session_answers
         (session_id, question_id, selected_option, is_correct, points_earned)
         VALUES (?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $gameId,
        $questionId,
        $answer,
        $correct ? 1 : 0,
        $points
    ]);

    if ($correct) {
        $stmt = $pdo->prepare(
            'UPDATE game_sessions
             SET score = score + ?
             WHERE id = ? AND status = ?'
        );
        $stmt->execute([$points, $gameId, 'active']);
    }

    $stmt = $pdo->prepare(
        'SELECT score FROM game_sessions WHERE id = ?'
    );
    $stmt->execute([$gameId]);
    $score = (int) $stmt->fetchColumn();

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Could not save your answer.'], 500);
}

$response = [
    'success' => true,
    'correct' => $correct,
    'score' => $score,
    'explanation' => $question['explanation']
];

if ($correct) {
    if (!isset($_SESSION['unlocked_cells'])) {
        $_SESSION['unlocked_cells'] = [];
    }

    if (!in_array($cellIndex, $_SESSION['unlocked_cells'], true)) {
        $_SESSION['unlocked_cells'][] = $cellIndex;
    }

    $response['unlocked_cell'] = $cellIndex;
}

unset(
    $_SESSION['current_question_id'],
    $_SESSION['current_cell_index'],
    $_SESSION['current_game_id']
);

jsonResponse($response);

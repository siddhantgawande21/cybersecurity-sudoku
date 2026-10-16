
<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/database.php';

requirePost();
requireCsrf();

$token = $_SESSION['game_token'] ?? '';
$cellIndex = filter_input(INPUT_POST, 'cell_index', FILTER_VALIDATE_INT);
$number = filter_input(INPUT_POST, 'number', FILTER_VALIDATE_INT);

if (
    $token === '' ||
    $cellIndex === false || $cellIndex === null || $cellIndex < 0 || $cellIndex > 80 ||
    $number === false || $number === null || $number < 1 || $number > 9
) {
    jsonResponse(['success' => false, 'message' => 'Invalid move.'], 422);
}

$stmt = $pdo->prepare(
    "SELECT gs.id, gs.score, gs.current_level, p.initial_grid,
            p.solution_grid
     FROM game_sessions gs
     JOIN puzzles p ON p.id = gs.puzzle_id
     WHERE gs.session_token = ? AND gs.status = 'active'
     LIMIT 1"
);
$stmt->execute([$token]);
$game = $stmt->fetch();

if (!$game) {
    jsonResponse(['success' => false, 'message' => 'Game session not found.'], 403);
}

$gameId = (int) $game['id'];

if (
    !isset($_SESSION['player_grid_game_id']) ||
    $_SESSION['player_grid_game_id'] !== $gameId
) {
    $_SESSION['player_grid_game_id'] = $gameId;
    $_SESSION['player_grid'] = $game['initial_grid'];
    $_SESSION['unlocked_cells'] = [];
}

$initialGrid = $game['initial_grid'];
$playerGrid = $_SESSION['player_grid'];

if ($initialGrid[$cellIndex] !== '0') {
    jsonResponse(['success' => false, 'message' => 'You cannot change a fixed cell.'], 422);
}

if (!in_array($cellIndex, $_SESSION['unlocked_cells'] ?? [], true)) {
    jsonResponse(['success' => false, 'message' => 'Answer the cyber question to unlock this cell.'], 403);
}

$row = intdiv($cellIndex, 9);
$col = $cellIndex % 9;

for ($i = 0; $i < 9; $i++) {
    $rowIndex = $row * 9 + $i;
    $colIndex = $i * 9 + $col;

    if ($rowIndex !== $cellIndex && $playerGrid[$rowIndex] === (string) $number) {
        jsonResponse(['success' => false, 'message' => 'That number already exists in this row.'], 422);
    }

    if ($colIndex !== $cellIndex && $playerGrid[$colIndex] === (string) $number) {
        jsonResponse(['success' => false, 'message' => 'That number already exists in this column.'], 422);
    }
}

$boxRow = intdiv($row, 3) * 3;
$boxCol = intdiv($col, 3) * 3;

for ($r = $boxRow; $r < $boxRow + 3; $r++) {
    for ($c = $boxCol; $c < $boxCol + 3; $c++) {
        $boxIndex = $r * 9 + $c;

        if ($boxIndex !== $cellIndex && $playerGrid[$boxIndex] === (string) $number) {
            jsonResponse(['success' => false, 'message' => 'That number already exists in this 3×3 box.'], 422);
        }
    }
}

if ($game['solution_grid'][$cellIndex] !== (string) $number) {
    jsonResponse([
        'success' => false,
        'message' => 'Incorrect digit for this cell. Try another number.'
    ], 422);
}

$playerGrid[$cellIndex] = (string) $number;
$_SESSION['player_grid'] = $playerGrid;

$complete = !str_contains($playerGrid, '0');
$solved = $complete && hash_equals($game['solution_grid'], $playerGrid);

if ($solved) {
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            "UPDATE game_sessions
             SET status = 'completed', completed_at = NOW(),
                 score = score + 100
             WHERE id = ? AND status = 'active'"
        );
        $stmt->execute([$gameId]);

        $stmt = $pdo->prepare(
            'SELECT score FROM game_sessions WHERE id = ?'
        );
        $stmt->execute([$gameId]);
        $finalScore = (int) $stmt->fetchColumn();

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log($e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Could not complete the game.'], 500);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Congratulations! Sudoku completed.',
        'grid' => $playerGrid,
        'complete' => true,
        'solved' => true,
        'score' => $finalScore
    ]);
}

jsonResponse([
    'success' => true,
    'message' => 'Correct move!',
    'grid' => $playerGrid,
    'complete' => $complete,
    'solved' => false,
    'score' => (int) $game['score']
]);

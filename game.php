
<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['game_token'])) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT gs.id, gs.score, gs.current_level, gs.status,
            p.initial_grid, l.name AS level_name
     FROM game_sessions gs
     JOIN puzzles p ON p.id = gs.puzzle_id
     JOIN levels l ON l.level_number = gs.current_level
     WHERE gs.session_token = ? AND gs.status = 'active'
     LIMIT 1"
);

$stmt->execute([$_SESSION['game_token']]);
$game = $stmt->fetch();

if (!$game) {
    unset($_SESSION['game_token']);
    header('Location: index.php');
    exit;
}

$grid = str_split($game['initial_grid']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <title>Play Sudoku | The Threat Matrix</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/game.css">
    <script src="assets/js/game.js" defer></script>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="index.php">THREAT<span>MATRIX</span></a>
        <span class="guest-label">GUEST MODE</span>
    </header>

    <main class="game-container">
        <div class="game-heading">
            <div>
                <p class="eyebrow">CYBERSECURITY TRAINING</p>
                <h1><?= htmlspecialchars($game['level_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="subtitle">Select an empty cell and answer a cyber question to unlock it.</p>
            </div>
            <a class="back-link" href="index.php">← Home</a>
        </div>

        <div class="game-stats">
            <div class="stat-card">
                <span>Score</span>
                <strong id="score"><?= (int) $game['score'] ?></strong>
            </div>
            <div class="stat-card">
                <span>Level</span>
                <strong><?= (int) $game['current_level'] ?>/5</strong>
            </div>
            <div class="stat-card">
                <span>Cells Filled</span>
                <strong id="filled-count">0/81</strong>
            </div>
        </div>

        <section class="game-layout">
            <div class="board-panel">
                <div id="sudoku-board" class="sudoku-board"
                     data-grid="<?= htmlspecialchars($game['initial_grid'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php foreach ($grid as $index => $digit): ?>
                        <button
                            type="button"
                            class="sudoku-cell <?= $digit !== '0' ? 'fixed-cell' : 'empty-cell' ?>"
                            data-index="<?= $index ?>"
                            <?= $digit !== '0' ? 'disabled' : '' ?>
                            aria-label="Row <?= intdiv($index, 9) + 1 ?>, column <?= ($index % 9) + 1 ?>">
                            <?= $digit !== '0' ? htmlspecialchars($digit, ENT_QUOTES, 'UTF-8') : '' ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="number-pad" id="number-pad">
                    <?php for ($number = 1; $number <= 9; $number++): ?>
                        <button type="button" class="number-button" data-number="<?= $number ?>">
                            <?= $number ?>
                        </button>
                    <?php endfor; ?>
                </div>

                <p id="game-message" class="status-message" role="status">
                    Select an empty cell to begin.
                </p>
            </div>

            <aside class="question-panel">
                <p class="eyebrow">THREAT KNOWLEDGE</p>
                <h2>Cyber Challenge</h2>
                <p id="question-placeholder">
                    Select an empty Sudoku cell to receive a cybersecurity question.
                </p>

                <div id="question-area" hidden>
                    <p id="question-text"></p>
                    <div id="question-options" class="question-options"></div>
                    <p id="question-feedback" role="status"></p>
                    <button type="button" id="next-question" class="primary-button" hidden>
                        Continue
                    </button>
                </div>

                <div class="game-rules">
                    <h3>Sudoku Rules</h3>
                    <p>Each row, column and 3×3 box must contain digits 1–9 without repetition.</p>
                    <p>Correct answers unlock cells. Sudoku rules still apply.</p>
                </div>
            </aside>
        </section>
    </main>

    <footer>
        <p>Cybersecurity Sudoku: The Threat Matrix</p>
        <p>Learn cybersecurity through play.</p>
    </footer>
</body>
</html>

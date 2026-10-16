
<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/database.php';

$levels = $pdo->query(
    'SELECT id, name, level_number, difficulty
     FROM levels ORDER BY level_number'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <title>Cybersecurity Sudoku | The Threat Matrix</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/home.js" defer></script>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="index.php">THREAT<span>MATRIX</span></a>
        <span class="guest-label">GUEST MODE</span>
    </header>

    <main class="container">
        <section class="hero">
            <p class="eyebrow">THINK SMART. STAY SECURE.</p>
            <h1>Cybersecurity Sudoku</h1>
            <p class="subtitle">
                Solve Sudoku puzzles, answer cyber-threat questions,
                and sharpen your security awareness.
            </p>
            <a class="primary-button" href="#levels">Explore Levels</a>
        </section>

        <section class="how-section">
            <h2>How to Play</h2>
            <div class="instruction-grid">
                <article class="info-card">
                    <span class="step-number">01</span>
                    <h3>Select a Cell</h3>
                    <p>Choose an empty cell in the Sudoku grid.</p>
                </article>
                <article class="info-card">
                    <span class="step-number">02</span>
                    <h3>Answer a Question</h3>
                    <p>Answer a cybersecurity question to unlock the cell.</p>
                </article>
                <article class="info-card">
                    <span class="step-number">03</span>
                    <h3>Complete the Grid</h3>
                    <p>Place valid digits and complete the puzzle.</p>
                </article>
            </div>
        </section>

        <section class="levels-section" id="levels">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">YOUR TRAINING PATH</p>
                    <h2>Choose Your Level</h2>
                </div>
                <span class="level-count">5 LEVELS</span>
            </div>

            <div class="level-grid">
                <?php foreach ($levels as $level): ?>
                    <article class="level-card">
                        <div class="level-top">
                            <span class="level-number">
                                LEVEL <?= (int) $level['level_number'] ?>
                            </span>
                            <span class="difficulty">
                                <?= htmlspecialchars($level['difficulty'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <h3><?= htmlspecialchars($level['name'], ENT_QUOTES, 'UTF-8') ?></h3>

                        <p>
                            <?= match ((int) $level['level_number']) {
                                1 => 'Learn the basics of online safety and common scams.',
                                2 => 'Recognize suspicious messages and fraud attempts.',
                                3 => 'Investigate malware and network threats.',
                                4 => 'Respond to serious cybersecurity incidents.',
                                5 => 'Test your knowledge with advanced threat scenarios.',
                                default => 'Improve your cybersecurity knowledge.'
                            } ?>
                        </p>

                        <button
                            class="start-button"
                            data-level="<?= (int) $level['level_number'] ?>"
                        >
                            Start Level <span>→</span>
                        </button>
                    </article>
                <?php endforeach; ?>
            </div>

            <p id="status-message" class="status-message" role="status"></p>
        </section>
    </main>

    <footer>
        <p>Cybersecurity Sudoku: The Threat Matrix</p>
        <p>Learn cybersecurity through play.</p>
    </footer>
</body>
</html>

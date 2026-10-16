
const board = document.getElementById('sudoku-board');
const cells = [...document.querySelectorAll('.sudoku-cell')];
const numberButtons = [...document.querySelectorAll('.number-button')];
const questionPlaceholder = document.getElementById('question-placeholder');
const questionArea = document.getElementById('question-area');
const questionText = document.getElementById('question-text');
const questionOptions = document.getElementById('question-options');
const questionFeedback = document.getElementById('question-feedback');
const nextQuestion = document.getElementById('next-question');
const gameMessage = document.getElementById('game-message');
const scoreDisplay = document.getElementById('score');
const filledCount = document.getElementById('filled-count');
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

let selectedCell = null;
let unlockedCell = null;
let currentQuestionId = null;
let currentCellIndex = null;
let questionAnswered = false;
let requestInProgress = false;

const grid = board.dataset.grid.split('');

function updateFilledCount() {
    const filled = grid.filter(value => value !== '0').length;
    filledCount.textContent = `${filled}/81`;
}

async function postData(url, values) {
    const formData = new FormData();

    Object.entries(values).forEach(([key, value]) => {
        formData.append(key, value);
    });

    const response = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
        body: formData
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Request failed.');
    }

    return data;
}

function showMessage(message) {
    gameMessage.textContent = message;
}

function clearQuestion() {
    questionPlaceholder.hidden = false;
    questionPlaceholder.textContent = 'Select an empty cell to receive a cybersecurity question.';
    questionArea.hidden = true;
    questionOptions.replaceChildren();
    questionFeedback.textContent = '';
    nextQuestion.hidden = true;
    questionAnswered = false;
}

async function selectCell(index) {
    if (requestInProgress) return;

    if (grid[index] !== '0') {
        showMessage('Choose an empty cell.');
        return;
    }

    selectedCell = index;
    currentCellIndex = index;
    unlockedCell = null;
    currentQuestionId = null;

    cells.forEach(cell => cell.classList.remove('selected-cell'));
    cells[index].classList.add('selected-cell');

    questionPlaceholder.hidden = false;
    questionPlaceholder.textContent = 'Loading question...';
    questionArea.hidden = true;
    questionOptions.replaceChildren();

    numberButtons.forEach(button => button.disabled = true);

    requestInProgress = true;

    try {
        const data = await postData('api/get_question.php', {
            cell_index: index
        });

        currentQuestionId = data.question_id;
        questionText.textContent = data.question;
        questionOptions.replaceChildren();

        Object.entries(data.options).forEach(([letter, option]) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'question-option';
            button.textContent = `${letter}. ${option}`;

            button.addEventListener('click', () => submitAnswer(letter));
            questionOptions.appendChild(button);
        });

        questionPlaceholder.hidden = true;
        questionArea.hidden = false;
        questionAnswered = false;
        showMessage('Answer the question to unlock this cell.');
    } catch (error) {
        questionPlaceholder.hidden = false;
        questionPlaceholder.textContent = error.message;
    } finally {
        requestInProgress = false;
    }
}

async function submitAnswer(answer) {
    if (requestInProgress || questionAnswered || currentQuestionId === null) {
        return;
    }

    requestInProgress = true;

    questionOptions.querySelectorAll('button').forEach(button => {
        button.disabled = true;
    });

    try {
        const data = await postData('api/check_answer.php', {
            question_id: currentQuestionId,
            answer
        });

        questionAnswered = true;
        scoreDisplay.textContent = data.score;

        questionFeedback.textContent = data.correct
            ? `Correct! ${data.explanation}`
            : `Incorrect. ${data.explanation}`;

        questionFeedback.style.color = data.correct ? '#56e0bd' : '#ff9c9c';

        if (data.correct) {
            unlockedCell = data.unlocked_cell;
            cells[unlockedCell].classList.add('unlocked-cell');
            numberButtons.forEach(button => button.disabled = false);
            showMessage('Cell unlocked! Choose a number.');
            nextQuestion.textContent = 'Choose a Number';
        } else {
            showMessage('Incorrect answer. Select the cell again to retry.');
            nextQuestion.textContent = 'Try Again';
        }

        nextQuestion.hidden = false;
    } catch (error) {
        questionOptions.querySelectorAll('button').forEach(button => {
            button.disabled = false;
        });
        questionFeedback.textContent = error.message;
    } finally {
        requestInProgress = false;
    }
}

async function placeNumber(number) {
    if (requestInProgress || unlockedCell === null || unlockedCell !== selectedCell) {
        showMessage('Select a cell and answer its question first.');
        return;
    }

    requestInProgress = true;
    numberButtons.forEach(button => button.disabled = true);

    try {
        const data = await postData('api/validate_move.php', {
            cell_index: unlockedCell,
            number
        });

        grid[unlockedCell] = String(number);
        cells[unlockedCell].textContent = String(number);
        cells[unlockedCell].classList.remove('empty-cell', 'unlocked-cell', 'selected-cell');
        cells[unlockedCell].classList.add('player-cell');

        unlockedCell = null;
        selectedCell = null;
        currentCellIndex = null;
        currentQuestionId = null;

        cells.forEach(cell => cell.classList.remove('selected-cell'));
        updateFilledCount();
        clearQuestion();

        showMessage(data.message);

        if (data.solved) {
            showMessage('Puzzle completed! Opening your results...');
            window.location.href = 'result.php';
            return;
        }

        if (data.complete && !data.solved) {
            showMessage('Grid filled, but it is not solved correctly. Check your entries.');
        }
    } catch (error) {
        showMessage(error.message);
        numberButtons.forEach(button => button.disabled = false);
    } finally {
        requestInProgress = false;
    }
}

cells.forEach((cell, index) => {
    cell.addEventListener('click', () => selectCell(index));
});

numberButtons.forEach(button => {
    button.disabled = true;
    button.addEventListener('click', () => {
        placeNumber(Number(button.dataset.number));
    });
});

nextQuestion.addEventListener('click', () => {
    if (unlockedCell !== null) {
        showMessage('Choose a number from the number pad.');
        return;
    }

    if (currentCellIndex !== null) {
        selectCell(currentCellIndex);
    }
});

updateFilledCount();

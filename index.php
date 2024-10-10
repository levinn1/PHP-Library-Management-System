<?php
session_start();

interface MediaItem {
    public function formatInfo(): string;
}

abstract class LibraryItem implements MediaItem {
    protected $title;
    protected $author;
    protected $year;

    public function __construct(string $title, string $author, int $year) {
        $this->title = $title;
        $this->author = $author;
        $this->year = $year;
    }

    protected function getCommonDetails(): string {
        return "{$this->title} by {$this->author}, Released in: {$this->year}";
    }
}

class DigitalPublication extends LibraryItem {
    private $fileSize;

    public function __construct(string $title, string $author, int $year, float $fileSize) {
        parent::__construct($title, $author, $year);
        $this->fileSize = $fileSize;
    }

    public function formatInfo(): string {
        return $this->getCommonDetails() . ", File Size: {$this->fileSize}MB";
    }
}

class PhysicalPublication extends LibraryItem {
    private $pages;

    public function __construct(string $title, string $author, int $year, int $pages) {
        parent::__construct($title, $author, $year);
        $this->pages = $pages;
    }

    public function formatInfo(): string {
        return $this->getCommonDetails() . ", Total Pages: {$this->pages}";
    }
}

class LibraryManager {
    public static function processSubmission(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $item = self::createItem();
            if ($item) {
                if (!isset($_SESSION['library_items'])) {
                    $_SESSION['library_items'] = [];
                }
                $_SESSION['library_items'][] = $item->formatInfo();
            }
        }
    }

    private static function createItem(): ?MediaItem {
        $type = $_POST['itemType'] ?? '';
        $title = $_POST['title'] ?? '';
        $author = $_POST['author'] ?? '';
        $year = (int)($_POST['year'] ?? 0);

        if ($type === 'digital') {
            $size = (float)($_POST['size'] ?? 0);
            return new DigitalPublication($title, $author, $year, $size);
        } elseif ($type === 'physical') {
            $pages = (int)($_POST['pages'] ?? 0);
            return new PhysicalPublication($title, $author, $year, $pages);
        }
        return null;
    }
}

LibraryManager::processSubmission();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-box { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 8px; border: 1px solid #ddd; }
        .input-row { margin-bottom: 10px; }
        label { display: inline-block; width: 150px; }
        .actions { text-align: center; margin-top: 15px; }
        .results { margin-top: 30px; padding: 15px; background-color: #eef; border-radius: 8px; }
        .toggle-buttons { display: flex; justify-content: space-around; margin-bottom: 20px; }
        .toggle-buttons button { padding: 10px 20px; cursor: pointer; background-color: #007BFF; color: white; border: none; border-radius: 4px; }
        .toggle-buttons button:hover { background-color: #0056b3; }
        .item-list ul { list-style-type: none; padding: 0; }
    </style>
</head>
<body>

    <h1>Register Library Items</h1>

    <div class="toggle-buttons">
        <button onclick="showForm('digitalForm')">New Digital Publication</button>
        <button onclick="showForm('physicalForm')">New Physical Publication</button>
    </div>

    <!-- Digital Publication Form -->
    <div id="digitalForm" class="form-box" style="display:none;">
        <form method="POST">
            <input type="hidden" name="itemType" value="digital">
            <div class="input-row">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" placeholder="e.g., The Pragmatic Programmer" required>
            </div>
            <div class="input-row">
                <label for="author">Author:</label>
                <input type="text" id="author" name="author" placeholder="e.g., Andy Hunt" required>
            </div>
            <div class="input-row">
                <label for="year">Release Year:</label>
                <input type="number" id="year" name="year" min="1500" max="2024" placeholder="e.g., 1999" required>
            </div>
            <div class="input-row">
                <label for="size">File Size (MB):</label>
                <input type="number" id="size" name="size" step="0.1" placeholder="e.g., 2.5" required>
            </div>
            <div class="actions">
                <button type="submit">Add Digital Item</button>
            </div>
        </form>
    </div>

    <!-- Physical Publication Form -->
    <div id="physicalForm" class="form-box" style="display:none;">
        <form method="POST">
            <input type="hidden" name="itemType" value="physical">
            <div class="input-row">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" placeholder="e.g., Clean Code" required>
            </div>
            <div class="input-row">
                <label for="author">Author:</label>
                <input type="text" id="author" name="author" placeholder="e.g., Robert C. Martin" required>
            </div>
            <div class="input-row">
                <label for="year">Release Year:</label>
                <input type="number" id="year" name="year" min="1500" max="2024" placeholder="e.g., 2008" required>
            </div>
            <div class="input-row">
                <label for="pages">Page Count:</label>
                <input type="number" id="pages" name="pages" placeholder="e.g., 464" required>
            </div>
            <div class="actions">
                <button type="submit">Add Physical Item</button>
            </div>
        </form>
    </div>

    <!-- Display Registered Items -->
    <?php if (!empty($_SESSION['library_items'])): ?>
        <div class="results">
            <h2>Registered Library Items:</h2>
            <div class="item-list">
                <ul>
                    <?php foreach ($_SESSION['library_items'] as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function showForm(formId) {
            document.getElementById('digitalForm').style.display = 'none';
            document.getElementById('physicalForm').style.display = 'none';
            document.getElementById(formId).style.display = 'block';
        }
    </script>

</body>
</html>

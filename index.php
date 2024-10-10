<?php
session_start();

class DataValidator {
    public static function validateString($str, $maxLength = 100): bool {
        return strlen($str) <= $maxLength && !empty(trim($str));
    }
    
    public static function validateYear($year): bool {
        return $year >= 1500 && $year <= date('Y');
    }
    
    public static function validateNumber($num, $min, $max): bool {
        return $num >= $min && $num <= $max;
    }
}

class ResourceMetadata {
    protected $metadata = [];
    
    public function __construct(array $data) {
        $this->metadata = $data;
    }
    
    public function get($key) {
        return $this->metadata[$key] ?? null;
    }
    
    public function asString(): string {
        return implode(' | ', $this->metadata);
    }
}

interface ResourceFactory {
    public function createResource(array $data): ?ResourceMetadata;
}

class DigitalResourceFactory implements ResourceFactory {
    public function createResource(array $data): ?ResourceMetadata {
        if (!$this->validateDigitalData($data)) return null;
        
        return new ResourceMetadata([
            'Type' => 'Digital Resource',
            'Name' => $data['title'],
            'By' => $data['author'],
            'Published' => $data['year'],
            'Digital Size' => $data['size'] . 'MB'
        ]);
    }
    
    private function validateDigitalData($data): bool {
        return DataValidator::validateString($data['title']) &&
               DataValidator::validateString($data['author']) &&
               DataValidator::validateYear($data['year']) &&
               DataValidator::validateNumber($data['size'], 1, 100);
    }
}

class PrintResourceFactory implements ResourceFactory {
    public function createResource(array $data): ?ResourceMetadata {
        if (!$this->validatePrintData($data)) return null;
        
        return new ResourceMetadata([
            'Type' => 'Print Resource',
            'Name' => $data['title'],
            'By' => $data['author'],
            'Published' => $data['year'],
            'Length' => $data['pages'] . ' pages'
        ]);
    }
    
    private function validatePrintData($data): bool {
        return DataValidator::validateString($data['title']) &&
               DataValidator::validateString($data['author']) &&
               DataValidator::validateYear($data['year']) &&
               is_numeric($data['pages']);
    }
}

class ResourceRegistry {
    private static $factories = [
        'digital' => DigitalResourceFactory::class,
        'physical' => PrintResourceFactory::class
    ];
    
    public static function processNewResource(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $resourceType = $_POST['itemType'] ?? '';
        if (!isset(self::$factories[$resourceType])) return;
        
        $factoryClass = self::$factories[$resourceType];
        $factory = new $factoryClass();
        
        $resource = $factory->createResource([
            'title' => $_POST['title'] ?? '',
            'author' => $_POST['author'] ?? '',
            'year' => (int)($_POST['year'] ?? 0),
            'size' => (float)($_POST['size'] ?? 0),
            'pages' => (int)($_POST['pages'] ?? 0)
        ]);
        
        if ($resource) {
            $_SESSION['resources'][] = $resource->asString();
        }
    }
}

ResourceRegistry::processNewResource();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Management System</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .resource-list { margin-top: 30px; }
        .resource-list li { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Resource Management System</h1>
    
    <form method="POST" id="resourceForm">
        <div class="form-group">
            <label for="itemType">Resource Type:</label>
            <select name="itemType" id="itemType" onchange="updateForm()">
                <option value="">Select Type</option>
                <option value="digital">Digital Resource</option>
                <option value="physical">Physical Resource</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required>
        </div>
        
        <div class="form-group">
            <label for="author">Creator:</label>
            <input type="text" id="author" name="author" required>
        </div>
        
        <div class="form-group">
            <label for="year">Year:</label>
            <input type="number" id="year" name="year" required>
        </div>
        
        <div class="form-group" id="sizeGroup" style="display:none">
            <label for="size">File Size (MB):</label>
            <input type="number" id="size" name="size" step="0.1">
        </div>
        
        <div class="form-group" id="pagesGroup" style="display:none">
            <label for="pages">Page Count:</label>
            <input type="number" id="pages" name="pages">
        </div>
        
        <button type="submit">Add Resource</button>
    </form>

    <?php if (!empty($_SESSION['resources'])): ?>
        <div class="resource-list">
            <h2>Registered Resources</h2>
            <ul>
                <?php foreach ($_SESSION['resources'] as $resource): ?>
                    <li><?= htmlspecialchars($resource) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <script>
        function updateForm() {
            const type = document.getElementById('itemType').value;
            document.getElementById('sizeGroup').style.display = type === 'digital' ? 'block' : 'none';
            document.getElementById('pagesGroup').style.display = type === 'physical' ? 'block' : 'none';
        }
    </script>
</body>
</html>

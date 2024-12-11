<?php
session_start();

// Authentication check
$password = "pass";
$authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

if (!$authenticated) {
    header("Location: index.php");
    exit();
}

$uploadDir = 'uploads/';
$dataFile = 'data/files.json';

// Check if the file exists and is readable
if (file_exists($dataFile) && is_readable($dataFile)) {
    $filesData = json_decode(file_get_contents($dataFile), true);
    if ($filesData === null) {
        $filesData = []; // Default to an empty array if JSON is malformed
    }
} else {
    $filesData = []; // Initialize as empty array if the file doesn't exist
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Handle file upload
        $uploadedFile = $_FILES['file'];
        $fileName = basename($uploadedFile['name']);
        
        // Generate a unique folder name using the file's short link or a unique ID
        $uniqueId = uniqid('file_', true);
        $fileFolder = $uploadDir . $uniqueId;

        // Create a directory for the file
        if (!mkdir($fileFolder, 0777, true)) {
            die("Failed to create directory for the file.");
        }

        // Set the full file path to store the file in the unique folder
        $filePath = $fileFolder . '/' . $fileName;

        // Move the uploaded file to the new folder
        if (move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
            // File successfully uploaded, now update the JSON data
            $newFile = [
                'original_name' => $fileName,
                'short_link' => $uniqueId, // Using unique ID as the short link
                'timestamp' => time(),
                'file_path' => $fileFolder, // Store the path to the file's folder
            ];

            // Add the new file to the files data array
            $filesData[] = $newFile;

            // Save the updated data back to the JSON file
            file_put_contents($dataFile, json_encode($filesData, JSON_PRETTY_PRINT));
            header("Location: index.php"); // Redirect back to index.php after upload
            exit();
        } else {
            echo "Error uploading file.";
        }
    }

    // Handle file deletion if requested
    if (isset($_POST['delete'])) {
        $shortLinkToDelete = $_POST['delete'];

        // Find the file to delete based on the short link
        $fileToDelete = null;
        foreach ($filesData as $key => $file) {
            if ($file['short_link'] === $shortLinkToDelete) {
                $fileToDelete = $file;
                unset($filesData[$key]); // Remove the file from the array
                break;
            }
        }

        if ($fileToDelete) {
            // Delete the folder and file from the server
            $fileFolderToDelete = $fileToDelete['file_path'];
            if (is_dir($fileFolderToDelete)) {
                // Delete all files inside the folder
                array_map('unlink', glob($fileFolderToDelete . '/*'));
                rmdir($fileFolderToDelete); // Remove the folder itself
            }

            // Re-index the array to fix keys
            $filesData = array_values($filesData);

            // Save the updated files data after deletion
            file_put_contents($dataFile, json_encode($filesData, JSON_PRETTY_PRINT));
        }

        header("Location: index.php"); // Redirect back after delete
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>File Upload</title>
</head>
<body class="bg-gray-900 text-white min-h-screen flex justify-center items-center">
    <div class="w-2/4">
        <h1 class="text-2xl font-bold mb-6">File Upload</h1>
        <form id="upload-form" enctype="multipart/form-data" method="POST">
            <!-- File input -->
            <input type="file" id="fileElem" name="file" class="hidden" onchange="handleFileSelect(event)">
            <label for="fileElem" class="bg-gray-800 p-4 text-center cursor-pointer">Drag and drop files or click to upload</label>
            
            <!-- Submit button -->
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 py-2 px-4 mt-4">Upload Files</button>
        </form>

        <h2 class="text-xl font-bold mt-6">Uploaded Files</h2>
        <ul class="mt-4">
            <?php foreach ($filesData as $file): ?>
                <li class="mb-4 flex justify-between items-center space-x-4">
                    <span class="text-xl text-gray-400">•••</span>
                    
                    <a href="file-download.php?id=<?php echo $file['short_link']; ?>"
                       class="text-indigo-400 hover:underline flex-grow">
                       <?php echo $file['original_name']; ?>
                    </a>

                    <div class="flex space-x-2">
                        <button onclick="copyToClipboard('<?php echo $file['short_link']; ?>')"
                                class="px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded">
                            Copy Link
                        </button>
                        <form method="POST" action="upload.php" class="inline">
                            <input type="hidden" name="delete" value="<?php echo $file['short_link']; ?>">
                            <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-500 rounded">
                                Delete
                            </button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>


</body>
</html>

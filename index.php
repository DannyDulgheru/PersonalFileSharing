<?php
session_start();
$password = "pass";
$authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && $_POST['password'] === $password) {
        $_SESSION['authenticated'] = true;
        $authenticated = true;
    } else {
        $error = "Invalid password.";
    }
}

$files = json_decode(file_get_contents('data/files.json'), true) ?? [];
usort($files, function($a, $b) {
    return $b['timestamp'] <=> $a['timestamp'];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Secure File Share</title>
</head>
<body class="bg-gray-900 text-white min-h-screen flex justify-center items-center">
    <div class="w-2/4">
        <?php if (!$authenticated): ?>
            <form method="POST" class="bg-gray-800 p-6 rounded shadow-md">
                <h1 class="text-xl font-bold mb-4">Login</h1>
                <input type="password" name="password" placeholder="Enter Password" required
                       class="w-full px-4 py-2 mb-4 rounded bg-gray-700 text-white focus:outline-none">
                <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-500 rounded">Login</button>
                <?php if (isset($error)) echo "<p class='text-red-500 mt-2'>$error</p>"; ?>
            </form>
        <?php else: ?>
            <h1 class="text-2xl font-bold mb-6">File Upload</h1>
            <form id="upload-form" enctype="multipart/form-data" method="POST" action="upload.php" class="mb-6">
                <!-- File input -->
                <input type="file" id="fileElem" name="file" class="hidden" onchange="handleFileSelect(event)">
                <label for="fileElem"
                       class="block w-full py-2 text-center bg-gray-800 border border-dashed border-gray-600 rounded cursor-pointer hover:bg-gray-700">
                    Drag and drop files or click to upload
                </label>

                <!-- File preview title -->
                <div id="file-preview" class="mt-4 text-gray-400">
                    <span id="file-preview-title">No file selected</span>
                </div>

                <button id="upload-button"
                        class="w-full py-2 bg-indigo-600 hover:bg-indigo-500 rounded mt-4" disabled>
                    Upload
                </button>
            </form>

            <div id="progress-bar" class="hidden mt-4">
                <div id="progress-label" class="mb-2">Uploading...</div>
                <div class="w-full bg-gray-700 rounded-full">
                    <div id="progress" class="bg-indigo-600 h-3 rounded-full" style="width: 0%;"></div>
                </div>
            </div>

            <h2 class="text-xl font-bold mt-6">Uploaded Files</h2>
            <ul class="mt-4">
                <?php foreach ($files as $file): ?>
                    <li class="mb-4 flex justify-between items-center space-x-4">
                        <span class="text-xl text-gray-400">•••</span>
                        
                        <a href="file-download.php?id=<?php echo $file['short_link']; ?>"
                           class="text-indigo-400 hover:underline flex-grow">
                           <?php echo $file['original_name']; ?>
                        </a>

                        <div class="flex space-x-2">
                            <button onclick="copyToClipboard('<?php echo $file['short_link']; ?>')"
                                    class="px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded" id="copyBtn<?php echo $file['short_link']; ?>">
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
        <?php endif; ?>
    </div>

    <script>
        // Handle file selection
        document.getElementById('fileElem').addEventListener('change', handleFileSelect);

        function handleFileSelect(event) {
            const file = event.target.files[0]; // Only handle one file
            if (file) {
                const previewTitle = document.getElementById('file-preview-title');
                previewTitle.textContent = file.name;  // Show file name in preview
                document.getElementById('upload-button').disabled = false;  // Enable upload button
            }
        }

        // Handle drag-and-drop
        const dropArea = document.querySelector('label[for="fileElem"]');

        dropArea.addEventListener('dragover', function(event) {
            event.preventDefault();
            dropArea.classList.add('bg-gray-600');
        });

        dropArea.addEventListener('dragleave', function(event) {
            dropArea.classList.remove('bg-gray-600');
        });

        dropArea.addEventListener('drop', function(event) {
            event.preventDefault();
            dropArea.classList.remove('bg-gray-600');
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = document.getElementById('fileElem');
                fileInput.files = files;  // Set the dropped file to the file input
                handleFileSelect({ target: { files: files } });
            }
        });

        // Function to copy the file URL to the clipboard
        function copyToClipboard(shortLink) {
            const fullUrl = window.location.origin + "/file-download.php?id=" + shortLink;  // Full URL
            navigator.clipboard.writeText(fullUrl).then(() => {
                // Find the button by its unique id
                const button = document.getElementById(`copyBtn${shortLink}`);
                
                // Change the button text and color
                button.textContent = 'Link is Copied';
                button.classList.remove('bg-gray-700', 'hover:bg-gray-600');
                button.classList.add('bg-green-600', 'hover:bg-green-500');
                
                // Revert back to original text and color after 1 second
                setTimeout(() => {
                    button.textContent = 'Copy Link';
                    button.classList.remove('bg-green-600', 'hover:bg-green-500');
                    button.classList.add('bg-gray-700', 'hover:bg-gray-600');
                }, 1000);
            });
        }
    </script>
</body>
</html>

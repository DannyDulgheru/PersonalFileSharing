<?php
// Session start is still there, but no password check
session_start();

// Retrieve the file data
$filesData = json_decode(file_get_contents('data/files.json'), true);
$shortLink = $_GET['id'];
$file = array_filter($filesData, function($f) use ($shortLink) {
    return $f['short_link'] === $shortLink;
});
$file = reset($file);  // Reset to get the first match (the file object)

if (!$file) {
    // If file is not found, handle the error
    echo "File not found.";
    exit();
}

$filePath = $file['file_path'] . '/' . $file['original_name']; // Build the full file path
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <title>Download File</title>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    <div class="text-center p-8 bg-gray-800 rounded-lg shadow-lg max-w-md w-full">
        <h1 class="text-4xl font-bold mb-4">Download File</h1>
        <?php if ($file): ?>
            <p class="text-lg mb-6">File Name: <span class="font-semibold"><?php echo $file['original_name']; ?></span></p>
            <form method="GET" action="<?php echo $filePath; ?>" download id="download-form">
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white text-lg font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 transition" id="download-btn">
                    Download
                </button>
            </form>

            <!-- Starting download button (hidden initially) -->
            <button id="starting-download-btn" class="px-8 py-3 bg-green-600 text-white text-lg font-medium rounded-lg hidden transition">
                Starting Download...
            </button>
        <?php else: ?>
            <p class="text-red-400">File not found.</p>
        <?php endif; ?>
    </div>

    <script>
        // Get the download button and the starting download button
        const downloadBtn = document.getElementById('download-btn');
        const startingDownloadBtn = document.getElementById('starting-download-btn');
        const downloadForm = document.getElementById('download-form');

        // Add an event listener for the download button
        downloadBtn.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent immediate form submission

            // Hide the original download button
            downloadBtn.style.display = 'none';

            // Show the "Starting Download" button
            startingDownloadBtn.style.display = 'inline-block';

            // Simulate the download action by redirecting after 3 seconds
            setTimeout(function() {
                window.location.href = downloadForm.action; // Start the download
            }, 3000);
        });
    </script>
</body>
</html>

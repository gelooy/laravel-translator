#!/usr/bin/php 
<?php
// Check if command line parameters are provided
if (count($argv) < 3) {
    echo "Usage: php laravel-translator.php <directory> <translation_directory>\n";
    exit(1);
}

// Get directory and translation directory from command line parameters
$directory = $argv[1] . '/';
$translationDirectory = $argv[2] . '/';

// Get API key from the environment variable
$api_key = getenv('OPENAI_API_KEY');

// Check if API key is set
if (empty($api_key)) {
    echo "API key not found. Please set the OPENAI_API_KEY environment variable.\n";
    exit(1);
}

// Read all files from the directory
$files = scandir($directory);
$files = array_diff($files, ['.', '..']);
foreach ($files as $file) {
    // Skip special cases . and ..
    if ($file === '.' || $file === '..') {
        continue;
    }

    // Check if the file is a PHP file
    if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
        continue;
    }

    $newFilePath = $translationDirectory . $file;
    if (file_exists($newFilePath)) {
        echo 'File already exists, skipping translation: ' . $newFilePath . PHP_EOL;
        continue;
    }
    // Read the file
    $filepath = $directory . $file;
    $fileContent = file_get_contents($filepath);

    $translation = translateText($fileContent, $api_key);

    // Save the translations to a new file
    file_put_contents($newFilePath, $translation);

    echo 'Translations created successfully in file: ' . $newFilePath . PHP_EOL;
}

echo 'All files have been processed.' . PHP_EOL;

/**
 * Function that sends a text translation request to ChatGPT.
 *
 * @param string $text
 * @param string $api_key
 * @return string
 */
function translateText($text, $api_key)
{
    // Create a prompt for ChatGPT
    $prompt = "Käännä tästä  \"" . $text . "\" ohjelmakoodista arvoparien englanninkieliset tekstit suomeksi. Älä käännä koodia tai kommentteja.";
    
    // Send an HTTP POST request to ChatGPT translation service
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'messages' => [ 0 => [ 'role' => 'user', 'content' => $prompt]],
        'max_tokens' => 1000,
        'temperature' => 0.1,
        'model' => 'gpt-3.5-turbo',
        'n' => 1,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    // Parse the response and return the translation
    $responseJson = json_decode($response, true);
    return $responseJson['choices'][0]['message']['content'];
}

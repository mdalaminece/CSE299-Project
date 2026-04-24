<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

$groqApiKey = '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';
$serpApiKey = '4dec6d57e0cc959a7a31b471678942061f9dd898';

// Fetch packages
$packages = fetch_all('SELECT * FROM packages');
$packageInfo = "Our Available Packages:\n";
foreach ($packages as $pkg) {
    $packageInfo .= "- " . $pkg['name'] . ": BDT " . $pkg['price'] . " for " . $pkg['duration_days'] . " days.\n";
}

$systemPrompt = "You are a helpful and human-like AI chatbot for Alamin Fitness, a gym. 
You answer questions related to health, gym, and fitness.
You must strictly avoid responding to any sensitive or private information, such as other users' emails, passwords, or any confidential data. If asked, politely refuse or say you don't know it.

Here is the information about the gym's packages. If the user asks about packages or prices, use this info:
$packageInfo

If the user asks something related to health, gym, and fitness that you do not confidently know or if they ask for the latest information, you can search Google.
To search Google, format your entire response EXACTLY as this JSON:
{\"action\": \"search\", \"query\": \"<search query>\"}

If you can answer the question directly, or just want to reply normally (without searching), format your entire response EXACTLY as this JSON:
{\"action\": \"reply\", \"message\": \"<your response>\"}

DO NOT include markdown blocks like ```json. Output ONLY the raw JSON object and nothing else. Ensure valid JSON format.
";

$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $message]
];

function callGroq($messages, $apiKey) {
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    
    $data = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 1024
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $res = json_decode($response, true);
    if ($httpCode !== 200) {
        return ['error' => $res['error']['message'] ?? 'API Error'];
    }
    return $res;
}

function searchGoogle($query, $apiKey) {
    if (empty($query)) return "No query provided.";
    $url = "https://serpapi.com/search.json?q=" . urlencode($query) . "&api_key=" . $apiKey;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) return "Search failed.";
    
    $data = json_decode($response, true);
    $resultsText = "";
    
    if (isset($data['organic_results']) && is_array($data['organic_results'])) {
        $count = 0;
        foreach ($data['organic_results'] as $res) {
            if ($count >= 3) break;
            $title = $res['title'] ?? '';
            $snippet = $res['snippet'] ?? '';
            if ($title || $snippet) {
                $resultsText .= "Title: $title\nSnippet: $snippet\n\n";
                $count++;
            }
        }
    }
    
    return $resultsText ?: "No relevant search results found.";
}

$groqRes = callGroq($messages, $groqApiKey);

if (isset($groqRes['error'])) {
    echo json_encode(['reply' => 'Sorry, I am having trouble connecting to my brain right now. Please try again later.']);
    exit;
}

$content = $groqRes['choices'][0]['message']['content'] ?? '';

// Try parsing JSON
$parsed = json_decode($content, true);

if ($parsed && isset($parsed['action'])) {
    if ($parsed['action'] === 'search') {
        $searchQuery = $parsed['query'] ?? str_replace('"', '', $message);
        $searchResults = searchGoogle($searchQuery, $serpApiKey);
        
        $messages[] = ['role' => 'assistant', 'content' => $content];
        $messages[] = ['role' => 'user', 'content' => "Google Search Results for '$searchQuery':\n" . $searchResults . "\nNow, answer the user's question using the exact format: {\"action\": \"reply\", \"message\": \"<your response>\"}."];
        
        $groqRes2 = callGroq($messages, $groqApiKey);
        $content2 = $groqRes2['choices'][0]['message']['content'] ?? '';
        $parsed2 = json_decode($content2, true);
        
        if ($parsed2 && isset($parsed2['message'])) {
            echo json_encode(['reply' => $parsed2['message']]);
            exit;
        } else {
            // Fallback
            echo json_encode(['reply' => trim(preg_replace('/```json|```/', '', $content2))]);
            exit;
        }
    } elseif ($parsed['action'] === 'reply') {
        echo json_encode(['reply' => $parsed['message'] ?? '']);
        exit;
    }
}

// Global fallback
$fallbackReply = trim(preg_replace('/```json|```/', '', $content));
echo json_encode(['reply' => $fallbackReply]);

<?php
header('Content-Type: application/json');
require 'config.php';
require 'db_connect.php';

date_default_timezone_set('Asia/Dhaka');

// --- Helper: Send Error ---
function send_error($msg) {
    echo json_encode(['error' => $msg]);
    exit;
}

// --- Helper: Get Current Database Schema ---
function get_db_schema_context($conn) {
    $schema = "";
    $result = $conn->query("SHOW TABLES");
    if (!$result) return "No tables found or error retrieving schema.";

    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        $schema .= "Table: $table\n";
        // Get columns
        $cols = $conn->query("SHOW COLUMNS FROM $table");
        if ($cols) {
            while ($col = $cols->fetch_assoc()) {
                $schema .= "  - " . $col['Field'] . " (" . $col['Type'] . ")";
                if ($col['Key'] == 'PRI') $schema .= " [PK]";
                if ($col['Extra'] == 'auto_increment') $schema .= " [AI]";
                $schema .= "\n";
            }
        }
        $schema .= "\n";
    }
    return $schema;
}

function normalize_text_for_matching($text) {
    $text = strtolower($text);
    $text = str_replace(
        ['\r', '\n', "\r", "\n", '.', ',', '!', '?'],
        ' ',
        $text
    );
    return preg_replace('/\s+/', ' ', trim($text));
}

function normalize_bangla_digits($text) {
    return strtr($text, [
        '০' => '0',
        '১' => '1',
        '২' => '2',
        '৩' => '3',
        '৪' => '4',
        '৫' => '5',
        '৬' => '6',
        '৭' => '7',
        '৮' => '8',
        '৯' => '9'
    ]);
}

function is_facebook_post_request($message) {
    $message = normalize_bangla_digits($message);
    $normalized = normalize_text_for_matching($message);

    $action_keywords = [
        'facebook post', 'fb post', 'schedule post', 'post koro', 'post dao',
        'post dite', 'post koro', 'schedule koro', 'schedule dao', 'promote',
        'promotion', 'offer post', 'discount post', 'facebook e post',
        'fb te post', 'page e post', 'campaign post', 'facebook e', 'fb te',
        'schedule', 'post', 'পোস্ট', 'পোস্ট দাও', 'পোস্ট করো', 'শিডিউল',
        'শিডিউল করো', 'ফেসবুকে পোস্ট', 'ফেসবুক পোস্ট'
    ];

    $topic_keywords = [
        'offer', 'discount', 'course', 'courses', 'batch', 'admission',
        'learning', 'student', 'students', 'training', 'class', 'classes',
        'package', 'packages', 'promo', 'promotion', 'sell', 'offer',
        'discount', 'course', 'batch', 'ad', 'offer', 'অফার', 'ডিসকাউন্ট',
        'কোর্স', 'ক্লাস', 'ব্যাচ', 'স্টুডেন্ট', 'শিক্ষার্থী', 'ভর্তি'
    ];

    $has_action = false;
    foreach ($action_keywords as $keyword) {
        if (strpos($normalized, $keyword) !== false) {
            $has_action = true;
            break;
        }
    }

    if (!$has_action && strpos($normalized, 'post') !== false) {
        $has_action = true;
    }

    if (!$has_action) {
        return false;
    }

    foreach ($topic_keywords as $keyword) {
        if (strpos($normalized, $keyword) !== false) {
            return true;
        }
    }

    return strpos($normalized, 'facebook') !== false || strpos($normalized, 'fb') !== false || mb_strpos($message, 'ফেসবুক') !== false;
}

function extract_scheduled_datetime($message) {
    $message = normalize_bangla_digits($message);
    $timezone = new DateTimeZone('Asia/Dhaka');
    $now = new DateTime('now', $timezone);
    $base = clone $now;
    $normalized = normalize_text_for_matching($message);

    if (
        strpos($normalized, 'tomorrow') !== false ||
        strpos($normalized, 'kalke') !== false ||
        strpos($normalized, 'kal') !== false ||
        mb_strpos($message, 'কালকে') !== false ||
        mb_strpos($message, 'আগামীকাল') !== false ||
        mb_strpos($message, 'কাল ') !== false
    ) {
        $base->modify('+1 day');
    } elseif (
        strpos($normalized, 'today') !== false ||
        strpos($normalized, 'ajke') !== false ||
        strpos($normalized, 'aj ') !== false ||
        substr($normalized, -2) === 'aj' ||
        mb_strpos($message, 'আজকে') !== false ||
        mb_strpos($message, 'আজ ') !== false
    ) {
        // Keep current date.
    }

    $hour = null;
    $minute = 0;
    $ampm = null;

    if (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/i', $normalized, $matches)) {
        $hour = (int) $matches[1];
        $minute = isset($matches[2]) ? (int) $matches[2] : 0;
        $ampm = strtolower($matches[3]);
    } elseif (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(tay|ta|tar|টার|টা)\b/u', $message, $matches)) {
        $hour = (int) $matches[1];
        $minute = isset($matches[2]) ? (int) $matches[2] : 0;

        if (
            strpos($normalized, 'night') !== false ||
            strpos($normalized, 'rate') !== false ||
            strpos($normalized, 'raat') !== false ||
            strpos($normalized, 'pm') !== false ||
            mb_strpos($message, 'রাত') !== false
        ) {
            $ampm = 'pm';
        } elseif (
            strpos($normalized, 'morning') !== false ||
            strpos($normalized, 'sokal') !== false ||
            strpos($normalized, 'shokal') !== false ||
            strpos($normalized, 'am') !== false ||
            mb_strpos($message, 'সকাল') !== false
        ) {
            $ampm = 'am';
        }
    } elseif (preg_match('/\bat\s+(\d{1,2})(?::(\d{2}))?\b/i', $normalized, $matches)) {
        $hour = (int) $matches[1];
        $minute = isset($matches[2]) ? (int) $matches[2] : 0;
    }

    if ($hour === null) {
        $base->modify('+10 minutes');
        return $base->format('Y-m-d H:i:s');
    }

    if ($ampm === 'pm' && $hour < 12) {
        $hour += 12;
    } elseif ($ampm === 'am' && $hour === 12) {
        $hour = 0;
    } elseif ($ampm === null) {
        $looks_evening = (
            strpos($normalized, 'night') !== false ||
            strpos($normalized, 'evening') !== false ||
            strpos($normalized, 'rate') !== false ||
            strpos($normalized, 'raat') !== false ||
            mb_strpos($message, 'রাত') !== false
        );

        if ($looks_evening && $hour < 12) {
            $hour += 12;
        }
    }

    $base->setTime($hour, $minute, 0);

    if ($base < $now && strpos($normalized, 'today') === false && strpos($normalized, 'ajke') === false && strpos($normalized, 'aj ') === false) {
        $base->modify('+1 day');
    }

    return $base->format('Y-m-d H:i:s');
}

function generate_scheduled_post_content($user_message) {
    $system_prompt = <<<EOT
You are an intelligent AI assistant integrated into a database-driven chatbot system.

Your task is to prepare content for scheduling a Facebook marketing post.

Rules:
1. Understand English, Banglish, and Bangla requests.
2. Generate a short, engaging, business-friendly Facebook post message.
3. Add emojis only if they fit naturally.
4. Generate an AI image prompt related to courses, learning, students, batches, training, or offers.
5. Do NOT include visible text instructions inside the image prompt. Never say things like "write 20% OFF" or "add headline text".
6. Return JSON only in this format:
{
  "message": "short facebook post",
  "image_prompt": "descriptive visual prompt"
}
EOT;

    return call_groq($system_prompt, $user_message, true);
}

// --- Helper: Call Groq AI ---
function call_groq($system_prompt, $user_message, $json_mode = false) {
    if (!defined('GROQ_API_KEY')) {
        return ['error' => 'GROQ_API_KEY not defined in config.php'];
    }
    $api_key = GROQ_API_KEY;
    $url = "https://api.groq.com/openai/v1/chat/completions";

    $data = [
        "model" => "llama-3.3-70b-versatile",
        "messages" => [
            ["role" => "system", "content" => $system_prompt],
            ["role" => "user", "content" => $user_message]
        ],
        "temperature" => 0.3 // A bit of character for human response
    ];
    
    if ($json_mode) {
        $data["response_format"] = ["type" => "json_object"];
        $data["temperature"] = 0; // Deterministic for SQL
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return ['error' => 'Groq API Connection Error: ' . curl_error($ch)];
    }
    curl_close($ch);

    $decoded = json_decode($response, true);
    if (!isset($decoded['choices'][0]['message']['content'])) {
        return ['error' => 'Invalid response from AI Provider'];
    }

    $content = $decoded['choices'][0]['message']['content'];
    
    if ($json_mode) {
        $json_cmd = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'AI returned invalid JSON: ' . $content];
        }
        return $json_cmd;
    }

    return ['content' => trim($content)];
}

// --- Main Execution Handler ---

// 1. Get Input
$input = json_decode(file_get_contents('php://input'), true);
$user_msg = $input['message'] ?? '';

if (empty($user_msg)) {
    send_error("Message is required.");
}

// 2. Get Context (Current Schema)
$schema_context = get_db_schema_context($conn);

// 3. Step 1: SQL Generation
$used_facebook_scheduler = false;

if (is_facebook_post_request($user_msg)) {
    $post_content = generate_scheduled_post_content($user_msg);

    if (isset($post_content['error'])) {
        send_error($post_content['error']);
    }

    $post_message = trim($post_content['message'] ?? '');
    $image_prompt = trim($post_content['image_prompt'] ?? '');
    $scheduled_time = extract_scheduled_datetime($user_msg);

    if ($post_message === '' || $image_prompt === '') {
        send_error("AI could not generate the Facebook post content.");
    }

    $post_message_sql = $conn->real_escape_string($post_message);
    $image_prompt_sql = $conn->real_escape_string($image_prompt);
    $scheduled_time_sql = $conn->real_escape_string($scheduled_time);

    $sql = "INSERT INTO scheduled_posts (message, image_prompt, scheduled_time, status) VALUES ('{$post_message_sql}', '{$image_prompt_sql}', '{$scheduled_time_sql}', 'pending');";
    $is_select = false;
    $used_facebook_scheduler = true;
} else {
    $sql_sys_prompt = <<<EOT
You are an expert MySQL Database Engineer.
Your goal is to convert natural language requests into accurate, optimized SQL queries.

### Current Database Schema:
$schema_context

### Rules:
1. ACCURATE DATA RETRIEVAL: Generate exact SQL for the required data requested.
2. MULTI-TABLE JOIN HANDLING: Automatically use JOIN queries if data is spread across tables. (e.g. users JOIN bookings JOIN packages)
3. FLEXIBLE INTERPRETATION: Understand informal language, transliterated text (e.g. "karim er mail"), and typos. 
4. PERMISSIONS: You support SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER.
5. BEST PRACTICES: Use proper indexing context and safe query structures.
6. FUZZY SEARCHING: When searching for a user or record by name, ALWAYS use `LIKE '%value%'` instead of `=` because names in the database might be full names (e.g., 'Karim' should match 'Karim Client').

### Output Format (JSON ONLY):
{
  "sql": "THE_SQL_QUERY_HERE",
  "is_select": true/false
}

If the user request is completely unclear or impossible, return:
{
  "error": "Explanation of why the request cannot be fulfilled."
}
EOT;

    $sql_response = call_groq($sql_sys_prompt, $user_msg, true);

    if (isset($sql_response['error'])) {
        send_error($sql_response['error']);
    }

    $sql = $sql_response['sql'] ?? '';
    $is_select = $sql_response['is_select'] ?? false;
}

if (empty($sql)) {
    send_error("AI could not formulate a valid SQL query.");
}

// 4. Execute SQL
try {
    $data_result = [];
    $is_success = false;
    $rows_affected = 0;
    
    if ($is_select || stripos(trim($sql), 'SELECT') === 0 || stripos(trim($sql), 'SHOW') === 0) {
        $result = $conn->query($sql);
        if (!$result) throw new Exception($conn->error);
        
        while ($row = $result->fetch_assoc()) {
            $data_result[] = $row;
        }
        $is_success = true;
    } else {
        if ($conn->query($sql) === TRUE) {
            $is_success = true;
            $rows_affected = $conn->affected_rows;
        } else {
            throw new Exception($conn->error);
        }
    }
    
    // 5. Step 2: Human-like Response Generation
    $human_sys_prompt = <<<EOT
You are an intelligent database assistant connected to a MySQL database.
Your goal: Be accurate like a database engine, but communicate like a human.

Your responsibilities:
1. ACCURATE DATA RETRIEVAL: State the data requested accurately based on the database results.
2. HUMAN-LIKE RESPONSE STYLE: Respond like a helpful human assistant. Use natural conversational English.
   Example: ❌ "Query executed successfully", ✅ "Sure! Karim purchased the Premium Package."
3. CONTEXTUAL UNDERSTANDING: If user says 'his', understand from context. Answer directly.
4. ERROR HANDLING: If data is empty/not found, respond politely (e.g., 'I couldn't find any record for that. Could you check the name or try again?').
5. CLEAN OUTPUT: Avoid unnecessary technical SQL details unless specifically requested. Just return the conversational text. No markdown coding blocks.
EOT;

    $db_outcome_json = json_encode([
        'query_success' => $is_success,
        'rows_returned' => count($data_result),
        'data' => $data_result,
        'rows_affected_by_write' => $rows_affected
    ]);
    
    if ($used_facebook_scheduler) {
        $scheduled_preview = extract_scheduled_datetime($user_msg);
        $chat_text = "Your Facebook post has been scheduled for $scheduled_preview.";
    } else {
        $human_user_prompt = "The user asked: \"$user_msg\"\nThe system executed this SQL query to fulfill it: \"$sql\"\nThe resulting database output is: $db_outcome_json\n\nPlease construct your helpful, human-like response directly answering the user's intent based on this data.";
        
        $human_response = call_groq($human_sys_prompt, $human_user_prompt, false);
        
        $chat_text = isset($human_response['content']) ? $human_response['content'] : "Query executed successfully.";
    }
    
    // 6. Return Payload
    $final_response = [
        'chat_response' => $chat_text,
        'sql_executed' => $sql
    ];
    
    if (!empty($data_result)) {
        $final_response['data'] = $data_result;
    }
    
    echo json_encode($final_response);

} catch (Exception $e) {
    echo json_encode([
        'error' => "SQL Execution Failed: " . $e->getMessage(),
        'sql_attempted' => $sql
    ]);
}

$conn->close();
?>

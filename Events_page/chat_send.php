<?php
// chat_send.php - Enhanced debugging version
session_start();
require_once 'db_connect.php';
require_once 'config.php';
header('Content-Type: application/json');

// Enhanced logging
function debug_log($msg, $data = null) {
  $log = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
  if ($data !== null) {
    $log .= ' | Data: ' . print_r($data, true);
  }
  error_log($log . "\n", 3, __DIR__ . '/chat_send_debug.log');
}

debug_log('=== CHAT SEND REQUEST START ===');
debug_log('SESSION', $_SESSION);
debug_log('POST', $_POST);
debug_log('FILES', $_FILES);

if (!isset($_SESSION['username'])) {
  debug_log('ERROR: No session username');
  http_response_code(401);
  echo json_encode(['error'=>'Login required']);
  exit;
}

$eventId = (int)($_POST['event_id'] ?? 0);
$message = trim((string)($_POST['message'] ?? ''));

debug_log('Parsed eventId', $eventId);
debug_log('Parsed message', $message);

if ($eventId <= 0) {
  debug_log('ERROR: Invalid event_id');
  http_response_code(400);
  echo json_encode(['error'=>'Missing event_id']);
  exit;
}

try {
  $cm = new ConnectionManager();
  $db = $cm->connect();

  $u = $db->prepare("SELECT id, username FROM users WHERE username=?");
  $u->execute([$_SESSION['username']]);
  $me = $u->fetch(PDO::FETCH_ASSOC);
  
  if (!$me) {
    debug_log('ERROR: User not found in database');
    throw new RuntimeException('User not found');
  }

  $userId = (int)$me['id'];
  $sbUserId = "user_" . $userId;
  debug_log('User resolved', ['userId' => $userId, 'sbUserId' => $sbUserId]);

  // Get channel URL
  $q = $db->prepare("SELECT channel_url FROM event_chat_channel WHERE event_id=?");
  $q->execute([$eventId]);
  $row = $q->fetch(PDO::FETCH_ASSOC);
  
  if (!$row || empty($row['channel_url'])) {
    debug_log('ERROR: No channel found for event_id', $eventId);
    throw new RuntimeException('Channel not ready. Please refresh and try again.');
  }

  $channelUrl = $row['channel_url'];
  $chanEnc = rawurlencode($channelUrl);
  debug_log('Channel found', ['channelUrl' => $channelUrl, 'encoded' => $chanEnc]);

  $SB_HOST = rtrim(SENDBIRD_API_HOST, '/');
  $SB_TOKEN = SENDBIRD_API_TOKEN;

  // Check for file upload
  $hasFile = isset($_FILES['file']) 
    && is_array($_FILES['file'])
    && ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
    && is_uploaded_file($_FILES['file']['tmp_name']);

  debug_log('Has file?', $hasFile);

  if ($hasFile) {
    debug_log('Processing FILE message');
    
    $origName = $_FILES['file']['name'] ?: 'file';
    $mime = $_FILES['file']['type'] ?: 'application/octet-stream';
    $tmpPath = $_FILES['file']['tmp_name'];
    
    debug_log('File details', [
      'name' => $origName,
      'mime' => $mime,
      'size' => filesize($tmpPath),
      'tmp' => $tmpPath
    ]);

    $cfile = new CURLFile($tmpPath, $mime, $origName);

    $fields = [
      'message_type' => 'FILE',
      'user_id' => $sbUserId,
      'file' => $cfile,
      'file_name' => $origName,
    ];
    
    if ($message !== '') {
      $fields['message'] = $message;
    }
    
    if (strpos($mime, 'image/') === 0) {
      $fields['thumbnail_sizes'] = json_encode([[320,320],[640,640]]);
    }

    $url = $SB_HOST . "/v3/group_channels/$chanEnc/messages";
    debug_log('Sending to URL', $url);

    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => ['Api-Token: ' . $SB_TOKEN],
      CURLOPT_POSTFIELDS => $fields,
      CURLOPT_TIMEOUT => 90,
      CURLOPT_VERBOSE => true,
    ]);
    
    $resp = curl_exec($ch);
    $cerr = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    debug_log('FILE Response', [
      'http' => $http,
      'error' => $cerr,
      'response' => substr($resp, 0, 500)
    ]);

    if ($http >= 400 || $resp === false) {
      throw new RuntimeException("File upload failed (HTTP $http): " . substr($resp, 0, 200));
    }
    
    echo $resp;
    exit;
  }

  // TEXT message
  if ($message === '') {
    debug_log('ERROR: Empty message with no file');
    throw new RuntimeException('Message cannot be empty');
  }

  debug_log('Processing TEXT message');
  
  $payload = [
    'message_type' => 'MESG',
    'user_id' => $sbUserId,
    'message' => $message
  ];
  
  $url = $SB_HOST . "/v3/group_channels/$chanEnc/messages";
  debug_log('Sending to URL', $url);
  debug_log('Payload', $payload);

  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Api-Token: ' . $SB_TOKEN
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30,
  ]);
  
  $resp = curl_exec($ch);
  $cerr = curl_error($ch);
  $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  debug_log('TEXT Response', [
    'http' => $http,
    'error' => $cerr,
    'response' => substr($resp, 0, 500)
  ]);

  if ($http >= 400 || $resp === false) {
    throw new RuntimeException("Send failed (HTTP $http): " . substr($resp, 0, 200));
  }

  echo $resp ?: json_encode(['ok' => true]);

} catch (Throwable $e) {
  debug_log('EXCEPTION', $e->getMessage());
  http_response_code(200);
  echo json_encode(['error' => $e->getMessage()]);
}

debug_log('=== CHAT SEND REQUEST END ===');
?>
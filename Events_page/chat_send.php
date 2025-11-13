<?php
// chat_send.php - Updated with better timeout handling
declare(strict_types=1);
session_start();
spl_autoload_register(
    function ($class) {
        require_once "model/$class.php";
    }
);
require_once 'config.php';
require_once 'sendbird_helper.php';

header('Content-Type: application/json');

function log_err(string $msg) {
  error_log('[chat_send] ' . $msg . "\n", 3, __DIR__ . '/logs/chat_send.log');
}

if (!isset($_SESSION['username'])) { 
  http_response_code(401); 
  echo json_encode(['error'=>'Login required']); 
  exit; 
}

$eventId = (int)($_POST['event_id'] ?? 0);
$message = trim((string)($_POST['message'] ?? ''));
if ($eventId <= 0) { 
  http_response_code(400); 
  echo json_encode(['error'=>'Missing event_id']); 
  exit; 
}

try {
  $cm = new ConnectionManager(); 
  $db = $cm->getConnection();

  $u = $db->prepare("SELECT id, username FROM users WHERE username=?");
  $u->execute([$_SESSION['username']]);
  $me = $u->fetch(PDO::FETCH_ASSOC);
  if (!$me) throw new RuntimeException('User not found');

  $userId   = (int)$me['id'];
  $sbUserId = "user_" . $userId;

  $q = $db->prepare("SELECT channel_url FROM event_chat_channel WHERE event_id=?");
  $q->execute([$eventId]);
  $row = $q->fetch(PDO::FETCH_ASSOC);
  if (!$row || empty($row['channel_url'])) {
    throw new RuntimeException('Channel not ready for this event');
  }

  $channelUrl = $row['channel_url'];
  $chanEnc = rawurlencode($channelUrl);
  $SB_HOST  = rtrim(SENDBIRD_API_HOST, '/');
  $SB_TOKEN = SENDBIRD_API_TOKEN;

  $hasFile = isset($_FILES['file']) && is_array($_FILES['file'])
           && ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
           && is_uploaded_file($_FILES['file']['tmp_name']);

  if ($hasFile) {
    // FILE UPLOAD with extended timeout
    $origName = $_FILES['file']['name'] ?: 'file';
    $mime     = $_FILES['file']['type'] ?: 'application/octet-stream';
    $cfile    = new CURLFile($_FILES['file']['tmp_name'], $mime, $origName);

    $fields = [
      'message_type'  => 'FILE',
      'user_id'       => $sbUserId,
      'file'          => $cfile,
      'file_name'     => $origName,
    ];
    if ($message !== '') $fields['message'] = $message;
    if (strpos($mime, 'image/') === 0) {
      $fields['thumbnail_sizes'] = json_encode([[320,320],[640,640]]);
    }

    $url = $SB_HOST . "/v3/group_channels/$chanEnc/messages";
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => ['Api-Token: '.$SB_TOKEN],
      CURLOPT_POSTFIELDS => $fields,
      CURLOPT_TIMEOUT => 120, // Extended timeout for file uploads
      CURLOPT_CONNECTTIMEOUT => 15,
      CURLOPT_TCP_KEEPALIVE => 1,
      CURLOPT_TCP_KEEPIDLE => 30,
      CURLOPT_TCP_KEEPINTVL => 10,
    ]);
    
    // Add HTTP/2 support
    if (defined('CURL_HTTP_VERSION_2_0')) {
      curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
    }
    
    $resp = curl_exec($ch);
    $cerr = curl_error($ch);
    $errno = curl_errno($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    curl_close($ch);

    if ($totalTime > 10) {
      log_err("Slow file upload: {$totalTime}s for {$origName}");
    }

    if ($errno !== 0) {
      log_err("cURL error $errno: $cerr for FILE upload");
      throw new RuntimeException("File upload failed: $cerr");
    }

    if ($http >= 400 || $resp === false) {
      log_err("HTTP $http FILE send failed. Body=" . substr((string)$resp,0,500));
      throw new RuntimeException("Sendbird FILE failed (HTTP $http)");
    }
    
    echo $resp; 
    exit;
  }

  // TEXT MESSAGE with retry logic
  $sb = new SendbirdHelper();
  $result = $sb->request('POST', "/v3/group_channels/$channelUrl/messages", [
    'message_type' => 'MESG',
    'user_id' => $sbUserId,
    'message' => $message
  ], ['timeout' => 30]);

  echo json_encode($result);

} catch (Throwable $e) {
  log_err('Exception: ' . $e->getMessage());
  http_response_code(200);
  echo json_encode(['error'=>$e->getMessage()]);
}
?>
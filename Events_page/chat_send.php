<?php
// chat_send.php — stable Sendbird message sender with auto-fix for "User not found"
declare(strict_types=1);
session_start();
require_once 'db_connect.php';
require_once 'config.php';
header('Content-Type: application/json');

// ===== AUTH CHECK =====
if (!isset($_SESSION['username'])) {
  http_response_code(401);
  echo json_encode(['error' => true, 'message' => 'Login required']);
  exit;
}

// ===== INPUTS =====
$eventId = (int)($_POST['event_id'] ?? 0);
$message = trim((string)($_POST['message'] ?? ''));

if ($eventId <= 0) {
  http_response_code(400);
  echo json_encode(['error' => true, 'message' => 'Missing event_id']);
  exit;
}
if ($message === '') {
  http_response_code(400);
  echo json_encode(['error' => true, 'message' => 'Empty message']);
  exit;
}

// ===== DB CONNECTION =====
$cm = new ConnectionManager();
$db = $cm->connect();

// Resolve current user
$stmt = $db->prepare("SELECT id, username, role FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$me) {
  http_response_code(403);
  echo json_encode(['error' => true, 'message' => 'User not found in DB']);
  exit;
}

$userId = (int)$me['id'];
$username = (string)$me['username'];
$role = strtolower((string)$me['role']);
$isAdmin = $role === 'admin';

// Get channel URL for this event
$q = $db->prepare("SELECT channel_url FROM event_chat_channel WHERE event_id = ?");
$q->execute([$eventId]);
$row = $q->fetch(PDO::FETCH_ASSOC);
if (!$row || empty($row['channel_url'])) {
  http_response_code(400);
  echo json_encode(['error' => true, 'message' => 'Channel not found for this event']);
  exit;
}
$channelUrl = $row['channel_url'];

// ===== SENDBIRD CONFIG =====
$appId = SENDBIRD_APP_ID;
$apiToken = SENDBIRD_API_TOKEN;

// Canonical Sendbird user ID (consistent everywhere)
$sbUserId = 'u_' . $userId; // <-- keep this the same everywhere

// ===== CURL WRAPPER =====
function sb_request(string $method, string $path, ?array $payload, string $apiToken): array {
  global $appId;
  $url = "https://api-$appId.sendbird.com$path";
  $ch = curl_init($url);
  $headers = [
    'Content-Type: application/json; charset=utf-8',
    'Api-Token: ' . $apiToken,
  ];
  curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
  ]);
  if ($payload !== null) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
  }
  $response = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$status, $response ? json_decode($response, true) : null];
}

// ===== 1) UPSERT USER =====
[$codeU, $resU] = sb_request('PUT', '/v3/users/' . rawurlencode($sbUserId), [
  'user_id'  => $sbUserId,
  'nickname' => $username,
], $apiToken);

if ($codeU >= 400) {
  http_response_code(500);
  echo json_encode(['error' => true, 'message' => 'Failed to upsert user', 'detail' => $resU]);
  exit;
}

// ===== 2) ENSURE MEMBERSHIP =====
[$codeM, $resM] = sb_request('GET', '/v3/group_channels/' . rawurlencode($channelUrl) . '/members/' . rawurlencode($sbUserId), null, $apiToken);

if ($codeM === 404) {
  // User not in channel → invite and accept
  sb_request('POST', '/v3/group_channels/' . rawurlencode($channelUrl) . '/invite', [
    'user_ids' => [$sbUserId],
  ], $apiToken);

  sb_request('PUT', '/v3/group_channels/' . rawurlencode($channelUrl) . '/accept', [
    'user_id' => $sbUserId,
  ], $apiToken);
}

// ===== 3) SEND MESSAGE =====
[$codeS, $resS] = sb_request('POST', '/v3/group_channels/' . rawurlencode($channelUrl) . '/messages', [
  'message_type' => 'MESG',
  'user_id'      => $sbUserId,
  'message'      => $message,
], $apiToken);

if ($codeS >= 400) {
  http_response_code(400);
  echo json_encode(['error' => true, 'message' => 'Send failed', 'detail' => $resS]);
  exit;
}

// ===== SUCCESS =====
echo json_encode([
  'ok' => true,
  'message_id' => $resS['message_id'] ?? null,
  'timestamp' => $resS['created_at'] ?? null,
]);
?>
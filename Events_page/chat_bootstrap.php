<?php
// chat_bootstrap.php - Updated with retry logic and better timeouts
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

if (!isset($_SESSION['username'])) {
  http_response_code(200); echo json_encode(['ok'=>false,'error'=>'Login required']); exit;
}

$eventId = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
if ($eventId <= 0) {
  http_response_code(200); echo json_encode(['ok'=>false,'error'=>'Missing event_id']); exit;
}

try {
  $cm = new ConnectionManager(); $db = $cm->getConnection();

  // who am i
  $u = $db->prepare("SELECT id, username, role FROM users WHERE username=?");
  $u->execute([$_SESSION['username']]);
  $me = $u->fetch(PDO::FETCH_ASSOC);
  if (!$me) throw new RuntimeException('User not found');

  $userId   = (int)$me['id'];
  $username = (string)$me['username'];
  $isAdmin  = strtolower((string)$me['role']) === 'admin';
  $sbUserId = "user_" . $userId;

  // event title for channel name
  $et = $db->prepare("SELECT title FROM events WHERE id=?");
  $et->execute([$eventId]);
  $event = $et->fetch(PDO::FETCH_ASSOC);
  $eventTitle = $event ? trim((string)$event['title']) : '';
  $channelName = $eventTitle !== '' ? $eventTitle : "Event #$eventId";

  // Initialize Sendbird helper
  $sb = new SendbirdHelper();

  // ensure user
  try { 
    $sb->request('GET', "/v3/users/$sbUserId");
  } catch(Throwable $e) { 
    $sb->request('POST', '/v3/users', [
      'user_id' => $sbUserId,
      'nickname' => $username
    ]);
  }

  // ensure channel row
  $sel = $db->prepare("SELECT channel_url FROM event_chat_channel WHERE event_id=?");
  $sel->execute([$eventId]); 
  $row = $sel->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    $chResp = $sb->request('POST', '/v3/group_channels', [
      'name'        => $channelName,
      'is_distinct' => false,
      'inviter_id'  => $sbUserId,
      'user_ids'    => [$sbUserId],
      'custom_type' => 'event_chat',
      'data'        => json_encode(['event_id'=>$eventId])
    ]);
    $channelUrl = $chResp['channel_url'] ?? '';
    if (!$channelUrl) throw new RuntimeException('Channel creation failed');
    $ins = $db->prepare("INSERT INTO event_chat_channel(event_id, channel_url) VALUES(?,?)");
    $ins->execute([$eventId,$channelUrl]);
  } else {
    $channelUrl = $row['channel_url'];
    // sync channel name if title changed
    $sb->request('PUT', "/v3/group_channels/$channelUrl", ['name'=>$channelName]);
  }

  // ensure membership
  $chan = $sb->request('GET', "/v3/group_channels/$channelUrl");
  $isMember = false; 
  foreach(($chan['members']??[]) as $m) {
    if(($m['user_id']??'')===$sbUserId){ 
      $isMember=true; 
      break; 
    }
  }
  
  if(!$isMember) { 
    $sb->request('POST', "/v3/group_channels/$channelUrl/invite", ['user_ids'=>[$sbUserId]]);
    $sb->request('PUT', "/v3/group_channels/$channelUrl/join");
  }

  echo json_encode([
    'ok' => true,
    'channel_url' => $channelUrl,
    'user_id' => $sbUserId,
    'is_admin' => $isAdmin,
    'channel_name' => $channelName
  ]);

} catch(Throwable $e){
  http_response_code(200);
  error_log('chat_bootstrap: '.$e->getMessage());
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
?>
<?php

session_start();

if (!isset($_SESSION['username'])){
  echo "
  <script>
    alert('Please login to access this page');
    window.location.href = 'Login.php';
  </script>";

  exit();
}

spl_autoload_register(
  function ($class) {
    require_once "model/$class.php";
  }
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>My Events – SMU</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="events_style.css">

  <script src='https://unpkg.com/axios/dist/axios.min.js'></script>
</head>
<body>
<div class="container py-4">
  <!-- Logo/Brand Header -->
  <div class="wbname d-flex align-items-center justify-content-center">
    <img src="pictures/omni_logo.png" alt="Omni Logo" class="omni-logo me-2">
    <h1 class="omni-title mb-0">OMNI</h1>
  </div>

  <br>
  
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light" id="navbarid">
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
      <div class="navbar-nav" id="navitems">
        <a class="nav-item nav-link ula nvit" href="events.php">Events</a>
        <a class="nav-item nav-link ula nvit" href="#">Account</a>
        <a class="nav-item nav-link ula nvit" href="#">My Events</a>
        <a class="nav-item nav-link ula nvit" href="dashboard.php">Dashboard</a>
      </div>
      <div class="navbar-nav ms-auto">
        <a class="nav-item nav-link ula nvit me-3" id="logout" href="logout.php">Logout</a>
      </div>
    </div>
  </nav>

  <!-- Page Header -->
  <div class="text-center mb-4">
    <h2 class="fw-bold" style="color:#041373;">My Saved Events</h2>
    <p class="text-muted mb-0"><strong>Manage your saved events and add them to your calendar.</strong></p>
  </div>

  <!-- Action Buttons
  <div class="d-flex justify-content-end gap-2 mb-4">
    <button id="clearAll" class="btn btn-outline-danger btn-sm">
      <i class="bi bi-trash3 me-1"></i>Clear All
    </button>
  </div> -->

  <!-- Events Grid -->
  <div id="myEventsContainer" class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3">
  </div>
  
  <p id="emptyState" class="text-muted mt-4 text-center" style="display:none;">
    No saved events yet. Go to <a href="events.php">Events</a> to add some.
  </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php 
  $dao = new EventCollectionDAO();

  $currentUser = $dao->getUserId($_SESSION["username"]);
  $user_events_obj = $dao->getUsersEvents($currentUser);

  $user_events_arr = array_map(function ($events) {
    return [
      'id' => $events->getId(),
      'title' => $events->getTitle(),
      'category' => $events->getCategory(),
      'date' => $events->getDate(),
      'start_time' => $events->getStartTime(),
      'end_time' => $events->getEndTime(),
      'location' => $events->getLocation(),
      'picture' => $events->getPicture(),
      'startISO' => $events->getStartISO(),
      'endISO' => $events->getEndISO(),
    ];
  }, $user_events_obj);

  // Get a PDO
$cm = new ConnectionManager();
$db = $cm->getConnection();

// Prepare once for speed
$seenStmt = $db->prepare("
  SELECT last_seen
  FROM chat_reads
  WHERE user_id = ? AND event_id = ?
  LIMIT 1
");

foreach ($user_events_arr as &$e) {
  $seenStmt->execute([$currentUser, $e['id']]);
  $lastSeen = $seenStmt->fetchColumn();

  // Simple rule: unread if never opened OR opened before the event's start
  $startTs = strtotime($e['startISO'] ?? $e['date']);
  $e['hasUnread'] = (!$lastSeen) || (strtotime($lastSeen) < $startTs);
}
unset($e);

  $user_events_json = json_encode($user_events_arr);
?>

<script>
// Shared with events page
const MY_EVENTS_KEY = 'smu_my_events_v1';
let loadMyEvents = <?= $user_events_json ?>;
const saveMyEvents = (list) => localStorage.setItem(MY_EVENTS_KEY, JSON.stringify(list));

// Get category classes for colored header strips
function getCategoryClass(categories) {
  if (!categories) return '';
  const catArray = Array.isArray(categories) ? categories : [categories];
  // Return first category class found
  if (catArray.includes('tech')) return 'tech';
  if (catArray.includes('arts')) return 'arts';
  if (catArray.includes('sports')) return 'sports';
  if (catArray.includes('career')) return 'career';
  return '';
}

// Google link builder
function toUTCBasic(iso){ const d=new Date(iso),pad=n=>String(n).padStart(2,'0'); return `${d.getUTCFullYear()}${pad(d.getUTCMonth()+1)}${pad(d.getUTCDate())}T${pad(d.getUTCHours())}${pad(d.getUTCMinutes())}${pad(d.getUTCSeconds())}Z`; }
function googleCalUrl({title, startISO, endISO, location, details=""}){
  const u = new URL('https://calendar.google.com/calendar/render');
  u.searchParams.set('action','TEMPLATE');
  u.searchParams.set('text', title);
  u.searchParams.set('dates', `${toUTCBasic(startISO)}/${toUTCBasic(endISO)}`);
  if(location) u.searchParams.set('location', location);
  if(details)  u.searchParams.set('details', details);
  return u.toString();
}

function formatDate(startISO) {
  const optsDate = {
    weekday: 'short',
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  };
  let s = new Date(startISO);
  let dateText = s.toLocaleDateString(undefined, optsDate);
  return dateText;
}

function card(e){
  console.log(e);
  const dateText = formatDate(e.startISO);
  const picture = e.picture || 'placeholder.jpg';
  const categoryClass = getCategoryClass(e.category);
  
  return `
  <div class="col">
    <div class="event-card ${categoryClass}">
      <img class="event-thumb" src="${picture}" alt="${e.title}">
      <div class="event-body">
        <h5 class="event-title">${e.title}</h5>
        <ul class="meta-list">
          <li><i class="bi bi-calendar2-event"></i>${dateText}</li>
          <li><i class="bi bi-clock"></i>${e.start_time} - ${e.end_time}</li>
          <li><i class="bi bi-geo-alt"></i>${e.location || ''}</li>
        </ul>
        
        <!-- Actions Row -->
        <div class="event-actions">
          <a class="btn btn-primary flex-grow-1" href="${googleCalUrl(e)}" target="_blank" rel="noopener">
            <i class="bi bi-calendar-plus me-1"></i>Add to Google
          </a>
          <a class="btn btn-outline-primary position-relative"
            href="chat.php?event_id=${e.id}"
            target="_blank" 
            rel="noopener"
            title="Chat">
            <i class="bi bi-chat-dots"></i>
            ${e.hasUnread
              ? '<span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>'
              : ''
            }
          </a>
          <button class="btn btn-outline-danger" 
                  data-eid="${e.id}" 
                  data-remove="${e.title}|${e.startISO}"
                  title="Remove">
            <i class="bi bi-trash3"></i>
          </button>
        </div>
      </div>
    </div>
  </div>`;
}

function render(list){
  const cont = document.getElementById('myEventsContainer');
  const empty = document.getElementById('emptyState');
  if (!list.length) {
    cont.innerHTML = '';
    empty.style.display = 'block';
    return;
  }
  empty.style.display = 'none';
  cont.innerHTML = list.map(card).join('');
}

// document.getElementById('clearAll').addEventListener('click', () => {
//   if (confirm('Clear all saved events?')) {
//     loadMyEvents = [];
//     removeAllEvents();
//     render(loadMyEvents);
//   }
// });

document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-remove]');
  if (!btn) return;
  const [title, startISO] = btn.dataset.remove.split('|');
  loadMyEvents = loadMyEvents.filter(ev => !(ev.title === title && ev.startISO === startISO));
  console.log(btn.dataset.eid);
  removeEvents(btn.dataset.eid);
  removePoints();

  render(loadMyEvents);
});

function removeEvents(eventID) {
  let userID = <?= $currentUser ?>;
  let url = "axios/sql_updating.php";

  axios.get(url, { params:
    {
    "personID": userID,
    "eventID": eventID,
    "option": "remove"
    }
  })
    .then(response => {
        console.log(response.data);
    })
    .catch(error => {
        console.log(error.message);
    });
}

function removeAllEvents() {
  let userID = <?= $currentUser ?>;
  let url = "axios/sql_updating.php";

  axios.get(url, { params:
    {
    "personID": userID,
    "option": "removeAll"
    }
  })
    .then(response => {
        console.log(response.data);
    })
    .catch(error => {
        console.log(error.message);
    });
}

function removePoints() {
  let userID = <?= $currentUser ?>;
  let url = "axios/sql_updating.php";

  axios.get(url, { params:
    {
    "personID": userID,
    "option": "removePts"
    }
  })
    .then(response => {
        console.log(response.data);
        
    })
    .catch(error => {
        console.log(error.message);
    });
}

render(loadMyEvents);
</script>
</body>
</html>
<?php
declare(strict_types=1);
session_start();
require_once 'db_connect.php';
require_once 'config.php';

if (!isset($_SESSION['username'])) {
  echo "<script>alert('Login required'); location.href='Login.php';</script>";
  exit;
}

$cm = new ConnectionManager();
$db = $cm->connect();

// Resolve current user
$u = $db->prepare("SELECT id, role FROM users WHERE username = ?");
$u->execute([$_SESSION['username']]);
$me = $u->fetch(PDO::FETCH_ASSOC);
if (!$me) { echo "<script>alert('User not found'); location.href='Login.php';</script>"; exit; }
$myId = (int)$me['id'];

// Fetch ONLY events this user created
$sql = "
  SELECT e.id, e.title, e.picture, e.category, e.date, e.start_time, e.end_time, e.location, e.details
  FROM events e
  JOIN event_person ep ON ep.event_id = e.id
  WHERE ep.person_id = :uid
    AND (COALESCE(ep.role,'') = 'creator')
  ORDER BY e.date DESC, e.start_time ASC
";
$st = $db->prepare($sql);
$st->execute([':uid' => $myId]);
$events = $st->fetchAll(PDO::FETCH_ASSOC);

// Convert to JSON with ISO datetime format for JavaScript
$events_json = array_map(function($ev) {
  $date = $ev['date'];
  $startISO = $date . 'T' . $ev['start_time'] . ':00+08:00';
  $endISO = $date . 'T' . $ev['end_time'] . ':00+08:00';
  
  return [
    'id' => $ev['id'],
    'title' => $ev['title'],
    'category' => $ev['category'],
    'date' => $ev['date'],
    'start_time' => $ev['start_time'],
    'end_time' => $ev['end_time'],
    'location' => $ev['location'],
    'picture' => $ev['picture'],
    'details' => $ev['details'],
    'startISO' => $startISO,
    'endISO' => $endISO
  ];
}, $events);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <title>Manage My Events</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"/>
  <style>
    body {
      background: #f5f7fa;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .container-fluid {
      height: 100vh;
    }
    
    .sidebar {
      background: #041373;
      color: white;
      min-width: 200px;
    }
    
    .sidebar .nav-link {
      color: rgba(255,255,255,0.8);
      padding: 0.75rem 1rem;
      transition: all 0.2s;
    }
    
    .sidebar .nav-link:hover {
      color: white;
      background: rgba(255,255,255,0.1);
      border-radius: 4px;
    }
    
    .top-nav {
      background: white;
      border-bottom: 1px solid #e0e6ed;
    }
    
    .wbname h1 {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 700;
      color: #2c3e50;
    }
    
    .new-event-btn {
      margin: 1.5rem;
      padding: 0.75rem 1.5rem;
      background: #0d6efd;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
    }
    
    .new-event-btn:hover {
      background: #0b5ed7;
    }
    
    .kanban-board {
      display: flex;
      gap: 1.5rem;
      padding: 0 1.5rem 1.5rem;
      overflow-x: auto;
      height: calc(100vh - 200px);
    }
    
    .kanban-column {
      flex: 1;
      min-width: 320px;
      background: #e9ecef;
      border-radius: 8px;
      padding: 1rem;
      display: flex;
      flex-direction: column;
    }
    
    .kanban-column h3 {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: #495057;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #dee2e6;
    }
    
    .kanban-column.text h3 {
      color: black;
    }
    
    .event-cards {
      flex: 1;
      overflow-y: auto;
    }
    
    .event-card {
      background: white;
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 1rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: all 0.2s;
    }
    
    .event-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .event-card h4 {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 0.75rem;
      color: #212529;
    }
    
    .event-thumb {
      width: 100%;
      height: 120px;
      object-fit: cover;
      border-radius: 6px;
      margin-bottom: 0.75rem;
    }
    
    .event-meta {
      font-size: 0.85rem;
      color: #6c757d;
      margin-bottom: 0.75rem;
    }
    
    .event-meta-item {
      display: flex;
      align-items: center;
      gap: 0.35rem;
      margin-bottom: 0.35rem;
    }
    
    .event-meta i {
      font-size: 0.9rem;
    }
    
    .cat-chip {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-bottom: 0.75rem;
    }
    
    .cat-tech { background: #e8f3ff; color: #0b63b6; }
    .cat-arts { background: #fde7ff; color: #9b2aa8; }
    .cat-sports { background: #e6ffef; color: #0d7a3a; }
    .cat-career { background: #fff6e0; color: #7a5d00; }
    
    .event-actions {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    
    .icon-btn {
      width: 2rem;
      height: 2rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      border: 1px solid #dee2e6;
      background: white;
      cursor: pointer;
      transition: all 0.2s;
      color: black;
    }
    
    .icon-btn:hover {
      background: #f8f9fa;
      border-color: #adb5bd;
    }
    
    .form-text.mono {
      font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      font-size: 0.8rem;
    }
  </style>
</head>
<body>

<div class="container-fluid">
  <div class="row h-100">
    <!-- Sidebar -->
    <aside class="col-auto sidebar d-flex flex-column p-4">
      <ul class="navbar-nav ps-0">
        <li><a class="nav-link" href="#">Manage Events</a></li>
        <li><a class="nav-link" href="#">Statistics</a></li>
        <li><a class="nav-link" href="#">Chat</a></li>
        <li><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="col d-flex flex-column p-0">
      <header class="top-nav d-flex justify-content-between align-items-center px-4 py-3">
        <div class="wbname">
          <h1>Omni</h1>
        </div>
        <div class="d-flex align-items-center gap-3">
          <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
          <a class="btn btn-outline-primary btn-sm" href="logout.php">Logout</a>
        </div>
      </header>

      <button class="new-event-btn" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle"></i> Add New Event
      </button>

      <section class="kanban-board">
        <div class="kanban-column text">
          <h3>Previous Events</h3>
          <div class="event-cards" id="previousEvents"></div>
        </div>

        <div class="kanban-column text">
          <h3>Ongoing Events</h3>
          <div class="event-cards" id="ongoingEvents"></div>
        </div>

        <div class="kanban-column text">
          <h3>Future Events</h3>
          <div class="event-cards" id="futureEvents"></div>
        </div>
      </section>
    </main>
  </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" action="admin_events_api.php" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Create Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Category</label>
            <select class="form-select" name="category" required>
              <option value="tech">Tech</option>
              <option value="arts">Arts</option>
              <option value="sports">Sports</option>
              <option value="career">Career</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="date" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Start Time</label>
            <input type="time" class="form-control" name="start_time" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">End Time</label>
            <input type="time" class="form-control" name="end_time" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Location</label>
            <input class="form-control" name="location">
          </div>
          <div class="col-md-6">
            <label class="form-label">Event Image</label>
            <input type="file" class="form-control" name="picture_file" accept="image/*">
          </div>
          <div class="col-12">
            <label class="form-label">Details</label>
            <textarea class="form-control" name="details" rows="4"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary" type="submit">Create</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" action="admin_events_api.php" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Edit Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" id="evIdEdit">
        <input type="hidden" name="existing_picture" id="evPictureExisting">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" id="evTitleEdit" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Category</label>
            <select class="form-select" name="category" id="evCategoryEdit" required>
              <option value="tech">Tech</option>
              <option value="arts">Arts</option>
              <option value="sports">Sports</option>
              <option value="career">Career</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" class="form-control" name="date" id="evDateEdit" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Start Time</label>
            <input type="time" class="form-control" name="start_time" id="evStartEdit" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">End Time</label>
            <input type="time" class="form-control" name="end_time" id="evEndEdit" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Location</label>
            <input class="form-control" name="location" id="evLocationEdit">
          </div>
          <div class="col-md-6">
            <label class="form-label">Event Image</label>
            <input type="file" class="form-control" name="picture_file" accept="image/*">
            <div class="form-text">Leave empty to keep current image.</div>
            <img id="evPicturePreview" class="mt-2 rounded w-100" style="max-height:120px; object-fit:cover;" alt=""/>
          </div>
          <div class="col-12">
            <label class="form-label">Details</label>
            <textarea class="form-control" name="details" id="evDetailsEdit" rows="4"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const events = <?= json_encode($events_json, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;

function getEventStatus(event) {
  const now = new Date().getTime();
  const start = new Date(event.startISO).getTime();
  const end = new Date(event.endISO).getTime();
  
  if (end < now) return 'previous';
  if (start <= now && now <= end) return 'ongoing';
  return 'future';
}

function formatDate(dateStr) {
  const date = new Date(dateStr);
  const options = { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' };
  return date.toLocaleDateString(undefined, options);
}

function getCategoryClass(category) {
  const cats = {
    'tech': 'cat-tech',
    'arts': 'cat-arts',
    'sports': 'cat-sports',
    'career': 'cat-career'
  };
  return cats[category?.toLowerCase()] || 'cat-tech';
}

function createEventCard(event) {
  const imgSrc = event.picture || 'https://via.placeholder.com/640x360?text=Event';
  const catClass = getCategoryClass(event.category);
  const catLabel = event.category ? event.category.charAt(0).toUpperCase() + event.category.slice(1) : 'Tech';
  
  return `
    <div class="event-card">
      <img class="event-thumb" src="${imgSrc}" alt="">
      <h4>${event.title}</h4>
      <span class="cat-chip ${catClass}">${catLabel}</span>
      <div class="event-meta">
        <div class="event-meta-item">
          <i class="bi bi-calendar-event"></i>
          <span>${formatDate(event.date)}</span>
        </div>
        <div class="event-meta-item">
          <i class="bi bi-clock"></i>
          <span>${event.start_time} – ${event.end_time}</span>
        </div>
        ${event.location ? `
        <div class="event-meta-item">
          <i class="bi bi-geo-alt"></i>
          <span>${event.location}</span>
        </div>` : ''}
        ${event.details ? `
        <div class="event-meta-item">
          <i class="bi bi-info-circle"></i>
          <span>${event.details}</span>
        </div>` : ''}
      </div>
      <div class="event-actions">
        <a class="icon-btn" href="chat.php?event_id=${event.id}" title="Open chat">
          <i class="bi bi-chat-dots"></i>
        </a>
        <button class="icon-btn" title="Edit" onclick='openEdit(${JSON.stringify(event)})'>
          <i class="bi bi-pencil-square"></i>
        </button>
        <button class="icon-btn" title="Delete" onclick="doDelete(${event.id})">
          <i class="bi bi-trash3"></i>
        </button>
      </div>
    </div>
  `;
}

function renderEvents() {
  const previousCol = document.getElementById('previousEvents');
  const ongoingCol = document.getElementById('ongoingEvents');
  const futureCol = document.getElementById('futureEvents');
  
  previousCol.innerHTML = '';
  ongoingCol.innerHTML = '';
  futureCol.innerHTML = '';
  
  events.forEach(event => {
    const status = getEventStatus(event);
    const cardHtml = createEventCard(event);
    
    if (status === 'previous') {
      previousCol.innerHTML += cardHtml;
    } else if (status === 'ongoing') {
      ongoingCol.innerHTML += cardHtml;
    } else {
      futureCol.innerHTML += cardHtml;
    }
  });
}

function openEdit(event) {
  document.getElementById('evIdEdit').value = event.id;
  document.getElementById('evTitleEdit').value = event.title || '';
  document.getElementById('evCategoryEdit').value = (event.category || 'tech').toLowerCase();
  document.getElementById('evDateEdit').value = event.date || '';
  document.getElementById('evStartEdit').value = event.start_time || '';
  document.getElementById('evEndEdit').value = event.end_time || '';
  document.getElementById('evLocationEdit').value = event.location || '';
  document.getElementById('evDetailsEdit').value = event.details || '';
  document.getElementById('evPictureExisting').value = event.picture || '';
  
  const prev = document.getElementById('evPicturePreview');
  prev.src = event.picture || 'https://via.placeholder.com/640x360?text=Event';

  const modal = new bootstrap.Modal(document.getElementById('editModal'));
  modal.show();
}

async function doDelete(id) {
  if (!confirm('Delete this event?')) return;
  const fd = new FormData();
  fd.set('action', 'delete');
  fd.set('id', String(id));
  await fetch('admin_events_api.php', { method: 'POST', body: fd });
  location.reload();
}

// Initial render
renderEvents();
</script>
</body>
</html>
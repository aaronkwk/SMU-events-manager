<?php
declare(strict_types=1);
session_start();
spl_autoload_register(
  function ($class) {
    require_once "model/$class.php";
  }
);

if (!isset($_SESSION['username'])) {
  echo "<script>alert('Login required'); location.href='Login.php';</script>";
  exit;
}

$dao = new EventCollectionDAO();
$currentUser = $dao->getUserId($_SESSION["username"]);

$all_events_obj = $dao->getEvents();
$all_events_arr = array_map(function ($events) {
  return [
      'id' => $events->getId(),
      'title' => $events->getTitle(),
      'category' => $events->getCategory(),
      'date' => $events->getDate(),
      'start_time' => $events->getStartTime(),
      'end_time' => $events->getEndTime(),
      'location' => $events->getLocation(),
      'details' => $events->getDetails(),
      'picture' => $events->getPicture(),
      'startISO' => $events->getStartISO(),
      'endISO' => $events->getEndISO(),
  ];
}, $all_events_obj);
$all_events_json = json_encode($all_events_arr);

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
      'details' => $events->getDetails(),
      'picture' => $events->getPicture(),
      'startISO' => $events->getStartISO(),
      'endISO' => $events->getEndISO(),
  ];
}, $user_events_obj);
$user_events_json = json_encode($user_events_arr);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Event Statistics - Omni</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="events_style.css">
  <script src='https://unpkg.com/axios/dist/axios.min.js'></script>
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      /* font-family: 'Poppins', sans-serif; */
      background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
      min-height: 100vh;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at 20% 20%, rgba(186, 181, 173, 0.1) 0%, transparent 50%),
                  radial-gradient(circle at 80% 80%, rgba(37, 47, 113, 0.1) 0%, transparent 50%);
      pointer-events: none;
      z-index: 0;
    }

    .container-fluid {
      height: 100vh;
    }

    .sidebar {
      background: #041373;
      color: white;
      min-width: 200px;
      position: relative;
      z-index: 1;
    }

    .sidebar a {
      display: block;
      color: rgb(255, 255, 251);
      text-decoration: none;
      margin-bottom: 0.75rem;
      font-weight: 500;
      padding: 8px 0;
      transition: color 0.3s ease;
    }

    .sidebar a:hover {
      color: rgb(191, 156, 96);
    }

    .ula {
      position: relative;
    }

    .ula::after {
      content: '';
      position: absolute;
      left: 0;
      bottom: 0;
      width: 100%;
      height: 2px;
      background-color: rgb(191, 156, 96);
      transform: scaleX(0);
      transform-origin: bottom right;
      transition: transform 0.5s ease-out;
    }

    .ula:hover::after {
      transform: scaleX(1);
      transform-origin: bottom left;
    }

    .top-nav {
      background: white;
      border-bottom: 1px solid #e0e6ed;
      position: relative;
      z-index: 1;
    }

    .wbname {
      color: #041373;
      font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
      font-weight: bolder;
      font-size: 27.4px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 10px;
      margin-bottom: 0px;
    }

    .main-content {
      overflow-y: auto;
      height: calc(100vh - 80px);
      padding: 40px 60px;
      position: relative;
      z-index: 1;
    }

    .section-title {
      font-size: 38px;
      font-weight: 800;
      color: #041373;
      margin-bottom: 35px;
      letter-spacing: -0.5px;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .section-divider {
      height: 2px;
      background: linear-gradient(to right, rgba(191, 156, 96, 0.3), transparent);
      margin: 60px 0;
    }

    .leaderboard-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
      margin-bottom: 40px;
    }

    .event-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 12px 40px rgba(4, 19, 115, 0.15);
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      border: 2px solid rgba(255, 255, 255, 0.6);
      position: relative;
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .event-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(191, 156, 96, 0.05) 0%, transparent 100%);
      opacity: 0;
      transition: opacity 0.5s ease;
      pointer-events: none;
      z-index: 1;
    }

    .event-card:hover::before {
      opacity: 1;
    }

    .event-card:hover {
      transform: translateY(-10px) scale(1.02);
      box-shadow: 0 25px 60px rgba(4, 19, 115, 0.25);
      border-color: rgba(191, 156, 96, 0.5);
    }

    .rank-badge-wrapper {
      position: absolute;
      top: 15px;
      left: 15px;
      z-index: 10;
    }

    .rank-badge {
      font-weight: 800;
      background: linear-gradient(135deg, #041373 0%, #0a1d99 100%);
      color: white;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      box-shadow: 0 6px 20px rgba(4, 19, 115, 0.4);
      border: 3px solid white;
    }

    .rank-badge.gold {
      background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
      color: #041373;
      animation: pulse 2s ease-in-out infinite;
    }

    .rank-badge.silver {
      background: linear-gradient(135deg, #C0C0C0 0%, #808080 100%);
    }

    .rank-badge.bronze {
      background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%);
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.08); }
    }

    .event-thumb {
      width: 100%;
      height: 220px;
      object-fit: cover;
      transition: transform 0.6s ease;
    }

    .event-card:hover .event-thumb {
      transform: scale(1.15);
    }

    .event-body {
      padding: 30px;
      position: relative;
      z-index: 2;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .event-title {
      font-size: 20px;
      font-weight: 700;
      color: #041373;
      margin-bottom: 20px;
      line-height: 1.4;
      transition: color 0.3s ease;
    }

    .event-card:hover .event-title {
      color: rgb(191, 156, 96);
    }

    .meta-list {
      list-style: none;
      padding: 0;
      margin: 0 0 20px 0;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .meta-list li {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 14px;
      color: #5a6c7d;
      font-weight: 500;
    }

    .meta-list i {
      color: rgb(191, 156, 96);
      font-size: 18px;
      min-width: 20px;
    }

    .signup-badge {
      background: linear-gradient(135deg, rgba(191, 156, 96, 0.15) 0%, rgba(191, 156, 96, 0.05) 100%);
      color: rgb(120, 95, 58);
      padding: 12px 20px;
      border-radius: 14px;
      font-weight: 700;
      font-size: 15px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: auto;
      border: 2px solid rgba(191, 156, 96, 0.3);
      transition: all 0.3s ease;
    }

    .event-card:hover .signup-badge {
      background: linear-gradient(135deg, rgba(191, 156, 96, 0.25) 0%, rgba(191, 156, 96, 0.15) 100%);
      border-color: rgba(191, 156, 96, 0.5);
      transform: translateY(-2px);
    }

    @media (max-width: 1200px) {
      .leaderboard-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 30px 20px;
      }

      .section-title {
        font-size: 28px;
      }

      .leaderboard-grid {
        grid-template-columns: 1fr;
      }
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .omni-logo {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(4, 19, 115, 0.2);
      transition: all 0.3s ease;
    }

    .omni-logo:hover {
      transform: translateY(-3px) rotate(5deg);
      box-shadow: 0 10px 30px rgba(4, 19, 115, 0.3);
    }

    .logo-text {
      font-size: 32px;
      font-weight: 800;
      background: linear-gradient(135deg, #041373 0%, rgb(191, 156, 96) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      letter-spacing: -0.5px;
    }
  </style>
</head>

<body>
  <div class="container-fluid">
    <div class="row h-100">
      <!-- Sidebar -->
      <aside class="col-auto sidebar d-flex flex-column p-4">
        <ul class="navbar-nav ps-0">
          <li><a class="nav-link ula" href="manage_events_admin.php" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Manage Events</a></li>
          <li><a class="nav-link ula" href="statistics.php" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">Statistics</a></li>
        </ul>
      </aside>

      <!-- Main Content -->
      <main class="col d-flex flex-column p-0">
        <header class="top-nav d-flex justify-content-between align-items-center px-4 py-3">
          <div class="logo">
            <img src="pictures/omni_logo.png" alt="Omni Logo" class="omni-logo">
            <div class="logo-text">OMNI</div>
          </div>
          <div class="d-flex align-items-center gap-3">
            <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a class="btn btn-outline-primary btn-sm" href="logout.php">Logout</a>
          </div>
        </header>

        <div class="main-content">
          <!-- Top 3 Leaderboard -->
          <div class="leaderboard-section">
            <h2 class="section-title">
              <i class="bi bi-trophy-fill" style="color: rgb(191, 156, 96);"></i>
              Top 3 Leaderboard
            </h2>
            <div id="leaderboardContainer" class="leaderboard-grid"></div>
          </div>

          <div class="section-divider"></div>

          <!-- My Event Statistics -->
          <div class="my-events-section">
            <h2 class="section-title">
              <i class="bi bi-bar-chart-fill" style="color: rgb(191, 156, 96);"></i>
              My Event Statistics
            </h2>
            <div id="myeventsContainer" class="leaderboard-grid"></div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    let allEvents = <?= $all_events_json ?>;
    console.log(allEvents);
    const events = <?php echo $user_events_json; ?>;
    console.log(events);
    const container = document.getElementById("leaderboardContainer");

    // adding the ranking to every event json object (allEvents)
    getAllEventRankings().then(() => {      
      allEvents.sort((a, b) => b.signups - a.signups);
      console.log(allEvents);
      let top3Events = allEvents.slice(0, 3);

      const rankClasses = ['gold', 'silver', 'bronze'];

      top3Events.forEach((e, index) => {
        const rank = index + 1;
        const card = document.createElement("div");
        card.innerHTML = `
          <div class="event-card ${e.category}">
            <div class="rank-badge-wrapper">
              <div class="rank-badge ${rankClasses[index]}">#${rank}</div>
            </div>
            <img class="event-thumb" src="${e.picture}" alt="${e.title}">
            <div class="event-body">
              <h5 class="event-title">${e.title}</h5>
              <ul class="meta-list">
                <li><i class="bi bi-calendar2-event"></i>${e.date}</li>
                <li><i class="bi bi-geo-alt"></i>${e.location}</li>
                <li><i class="bi bi-info-circle"></i>${e.details}</li>
              </ul>
              <div class="signup-badge">
                <i class="bi bi-people-fill"></i>
                ${e.signups} Signups
              </div>
            </div>
          </div>
        `;
        container.appendChild(card);
      });
    });

    let myEventsContainer = document.getElementById("myeventsContainer");
    events.forEach((e) => {
      const card = document.createElement("div");
      card.innerHTML = `
        <div class="event-card ${e.category}">
          <img class="event-thumb" src="${e.picture}" alt="${e.title}">
          <div class="event-body">
            <h5 class="event-title">${e.title}</h5>
            <ul class="meta-list">
              <li><i class="bi bi-calendar2-event"></i>${e.date}</li>
              <li><i class="bi bi-geo-alt"></i>${e.location}</li>
              <li><i class="bi bi-geo-alt"></i>${e.details}</li>
            </ul>
            <div id="signups-${e.id}" class="signup-badge">
              <i class="bi bi-people-fill"></i>
              <span>Loading...</span>
            </div>
          </div>
        </div>
      `;
      myEventsContainer.appendChild(card);

      // Fetch signup counts
      fillSignups(e.id);
    });

    function fillSignups(eid) {
  let userID = <?= $currentUser ?>;
  let url = "axios/sql_updating.php";

  console.log(`Fetching signups for event ${eid}`); // Debug log

  axios.get(url, { params:
    {
    "personID": userID,
    "eventID": eid,
    "option": "getCount"
    }
  })
    .then(response => {
        console.log(`Event ${eid} response:`, response.data); // Debug log
        let count = response.data;
        const element = document.getElementById(`signups-${eid}`);
        console.log(`Element found:`, element); // Debug log
        
        if (element) {
          element.innerHTML = `
            <i class="bi bi-people-fill"></i>
            <span>${count} Signups</span>
          `;
        } else {
          console.error(`Element signups-${eid} not found!`);
        }
    })
    .catch(error => {
        console.error(`Error for event ${eid}:`, error);
        const element = document.getElementById(`signups-${eid}`);
        if (element) {
          element.innerHTML = `
            <i class="bi bi-people-fill"></i>
            <span>Error</span>
          `;
        }
    });
}

    function getAllEventRankings() {
      let userID = <?= $currentUser ?>;
      let url = "axios/sql_updating.php";

      const requestPromises = allEvents.map((event) => {
        return axios.get(url, { params: 
          {
            "personID": userID,
            "eventID": event.id,
            "option": "getCount"
          }
        })
        .then(response => {
          console.log(response.data); 
          let count = response.data;
          event.signups = count; 
        })
        .catch(error => {
          console.log(`Error fetching count for event ${event.id}: ${error.message}`);
          event.signups = 0;
        });
      });

      return Promise.all(requestPromises);
    }
  </script>
</body>
</html>
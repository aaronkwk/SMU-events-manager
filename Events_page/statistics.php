<?php



declare(strict_types=1);
session_start();
spl_autoload_register(
  function ($class) {
    require_once "model/$class.php";
  }
);

// require_once 'config.php';

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
      'details' => $events->getDetails(),
      'start_time' => $events->getStartTime(),
      'end_time' => $events->getEndTime(),
      'location' => $events->getLocation(),
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

$user_events_json = json_encode($user_events_arr);    // USER EVENTS



// ✅ New query that counts signups & saves per event
// $connMgr = new ConnectionManager();
// $conn = $connMgr->getConnection();

// $sql = "SELECT e.id, e.title, e.category, e.date, e.location, e.picture,
//                COUNT(DISTINCT ep.person_id) AS saves,
//         FROM event e
//         LEFT JOIN event_person ep ON e.id = ep.event_id
//         LEFT JOIN signup_info si ON e.id = si.event_id ( google claendar adds )
//         GROUP BY e.id
//         ORDER BY (COUNT(DISTINCT ep.person_id) + COUNT(DISTINCT si.person_id)) DESC";

// $stmt = $conn->prepare($sql);
// $stmt->execute();
// $events_obj = $stmt->fetchAll(PDO::FETCH_ASSOC);

// $stmt = null;
// $conn = null;


?>

<!DOCTYPE html>
<html lang="en">
<head>

<div class="container-fluid h-100">
  <div class="row h-100">

    <!-- sidebar -->
    <aside class="col-auto sidebar d-flex flex-column p-4" id="navbarid">
      <ul class="navbar-nav ps-0">
        <li><a class="nav-item nav-link ula nvit" href="manage_events_admin.php">Manage Events</a></li>
        <li><a class="nav-item nav-link ula nvit" href="statistics.php">Statistics</a></li>
        <li><a class="nav-item nav-link ula nvit" href="chat.php">Chat</a></li>
      </ul>
    </aside>

  <main class="col d-flex flex-column p-0">
    <header class="top-nav d-flex justify-content-between align-items-center px-4 py-3">
      <div class="wbname">
        <h1>Omni</h1>
      </div>
      <div class="d-flex align-items-end gap-3">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.location.href='logout.php'">Logout</button>
      </div>
    </header>


  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Event Leaderboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="events_style.css"> <!-- reuse your current styling -->

  <script src='https://unpkg.com/axios/dist/axios.min.js'></script>
  <style>
    body {
      background: #f7f8fa;
    }
    .leaderboard-title {
      font-size: 1.8rem;
      font-weight: 700;
      color: #333;
      text-align: center;
      margin-top: 20px;
      margin-bottom: 20px;
    }
    .rank-badge {
      font-weight: bold;
      background: #007bff;
      color: white;
      border-radius: 50%;
      width: 38px;
      height: 38px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
    }
  </style>
</head>

<body>
  <div class="container mt-4">
    <h2 class="leaderboard-title">🏆 Top 3 Leaderboard</h2>
    <div id="leaderboardContainer" class="row g-3"></div>
  </div>

  <hr class="my-5">

  <div class="container mt-4">
    <h2 class="leaderboard-title">📊 My Event Statistics</h2>
      <div id="myeventsContainer" class="row g-3"></div>
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

      top3Events.forEach((e, index) => {
        const rank = index + 1;
        const card = document.createElement("div");
        card.className = "col-md-4";
        // console.log(count);
        card.innerHTML = `
          <div class="event-card ${e.category}">
            <img class="event-thumb" src="${e.picture}" alt="${e.title}">
            <div class="event-body">
              <div class="d-flex align-items-center mb-2">
                <div class="rank-badge me-2">#${rank}</div>
                <h5 class="event-title mb-0">${e.title}</h5>
              </div>
              <ul class="meta-list">
                <li><i class="bi bi-calendar2-event"></i>${e.date}</li>
                <li><i class="bi bi-geo-alt"></i>${e.location}</li>
                <li><i class="bi bi-info-circle"></i>${e.details}</li>
              </ul>
              <div class="event-actions d-flex flex-column">
              
                <p>🔖 ${e.signups} Signups</p>
                
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
      card.className = "col-md-4";
      card.innerHTML = `
        <div class="event-card ${e.category}">
          <img class="event-thumb" src="${e.picture}" alt="${e.title}">
          <div class="event-body">
            <h5 class="event-title mb-0">${e.title}</h5>
            <ul class="meta-list">
              <li><i class="bi bi-calendar2-event"></i>${e.date}</li>
              <li><i class="bi bi-geo-alt"></i>${e.location}</li>
            </ul>
            <p id="signups-${e.id}">🔖 Signups: ...</p>
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

      axios.get(url, { params:
        {
        "personID": userID,
        "eventID": eid,
        "option": "getCount"
        }
      })
        .then(response => {
            console.log(response.data);
            let count = response.data;
            document.getElementById(`signups-${eid}`).innerHTML = ` <strong>Signups: ${count} </strong>`
            
        })
        .catch(error => {
            console.log(error.message);
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
          event.signups = 0; // if cannot find data
        });
    });

  return Promise.all(requestPromises);
    }

  </script>
</body>
</html>
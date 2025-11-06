<?php
session_start();
spl_autoload_register(
    function ($class) {
        require_once "model/$class.php";
    }
);

if (!isset($_SESSION['username'])) {
    echo "
  <script>
    alert('Please login to access this page');
    window.location.href = 'Login.php';
  </script>";

    exit();
}

$username = $_SESSION['username'];

$dao = new EventCollectionDAO;

$userID = $dao->getUserId($username);
$user_events_obj = $dao->getUsersEvents($userID);
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


$user_events_json = json_encode($user_events_arr);

$all_users_obj = $dao->getUsers();
$all_users_arr = array_map(function ($user) {
    return [
        'id' => $user->getId(),
        'username' => $user->getUsername(),
        'school' => $user->getSchool(),
        'points' => $user->getPoints()
    ];
}, $all_users_obj);


$all_users_json = json_encode($all_users_arr);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Omni Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="events_style.css">
    <style>
        /* styling for dashboard */
        .dashboard-header {
            text-align: center;
            color: #041373;
            font-weight: bolder;
            margin: 20px 0;
        }

        .card-custom {
            border-radius: 14px;
            background: #fff;
            border: 1px solid #ececf4;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
            padding: 20px;
            transition: .18s ease;
        }

        .card-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, .1);
        }


        .leaderboard-table th {
            background-color: #041373;
            color: white;
        }

        .leaderboard-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .search-bar {
            margin-bottom: 15px;
            max-width: 300px;
        }
    </style>
</head>

<body>

    <div class="container py-4">
        <div class="wbname d-flex align-items-center justify-content-center">
            <img src="pictures/omni_logo.png" alt="Omni Logo" class="omni-logo me-2">
            <h1 class="omni-title mb-0">OMNI</h1>
        </div>

        <br>
        <nav class="navbar navbar-expand-lg navbar-light" id="navbarid">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav" id="navitems">
                    <a class="nav-item nav-link ula nvit" href="events.php">Events </a>
                    <!-- <a class="nav-item nav-link ula nvit" href="#">Daily Challenge</a> -->
                    <a class="nav-item nav-link ula nvit" href="my_events_user.php">My Events</a>
                    <a class="nav-item nav-link ula nvit" href="#">Dashboard</a>
                </div>
                <div class="navbar-nav ms-auto"><a class="nav-item nav-link ula nvit me-3" id="logout" href="logout.php">Logout</a></div>
            </div>
        </nav>


        <div class="container">
            <div class="row">
                <!-- My Stats -->
                <div class="col-lg-4 mb-4">
                    <div class="card-custom">
                        <h4><?= $username ?>'s Stats</h4>
                        <p><strong>Total Points:</strong> <span id="userPoints"></span></p>
                        <p><strong>Rank:</strong> <span id="userRank"></span></p>
                        <div class="d-flex align-items-center">
                            <div id="userBadge" class="d-flex align-items-center"></div>
                        </div>
                    </div>
                </div>

                <!-- Leaderboard -->
                <div class="col-lg-8 mb-4">
                    <div class="card-custom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4>Leaderboard</h4>
                            <input type="text" id="searchInput" class="form-control form-control-sm search-bar" placeholder="Search user...">
                        </div>
                        <table class="table leaderboard-table mt-3">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Username</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody id="leaderboardBody"></tbody>
                        </table>
                        <div class="text-center">
                            <!-- <button class="btn btn-outline-primary btn-sm">View More</button> -->
                            <nav aria-label="Page navigation">
                                <ul id="pagination" class="pagination justify-content-center"></ul>
                            </nav>
                        </div>
                    </div>
                </div>

            </div>
            <!-- Recent Activity -->
            <div class="row">
                <div class="col-lg-12 mb-5">
                    <div class="card-custom">
                        <h4>Recent Activity</h4>
                        <div class="row" id="recentEvents"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Helper fns
        function formatDate(startISO) {
            const optsDate = {
                weekday: 'short',
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            };
            const optsTime = {
                hour: '2-digit',
                minute: '2-digit'
            };

            let s = new Date(startISO);
            let dateText = s.toLocaleDateString(undefined, optsDate);
            return dateText;
        }

        // pages
        let currentPage = 1;
        let numRankings = 20;

        function renderLeaderboardPage(page, ldrboard) {
            let leaderboardBody = document.getElementById("leaderboardBody");
            leaderboardBody.innerHTML = "";
            let start = (page - 1) * numRankings;
            let end = start + numRankings;
            pagedLeaderboard = ldrboard.slice(start, end);

            ldrboard.slice(start, end).forEach((u, i) => {
                leaderboardBody.innerHTML += `
                    <tr>
                        <td>${start + i + 1}</td>
                        <td>${u.username}</td>
                        <td>${u.points}</td>
                    </tr>`;
            });
        }

        let pagination = document.getElementById("pagination");

        function renderPagination(ldrboard) {
            pagination.innerHTML = "";
            let totalPages = Math.ceil(ldrboard.length / numRankings);

            const createPageItem = (pageNum, label = pageNum, disabled = false, active = false) => {
                let li = document.createElement("li");
                li.className = `page-item ${disabled ? "disabled" : ""} ${active ? "active" : ""}`;
                li.innerHTML = `<a class="page-link" href="#">${label}</a>`;
                if (!disabled && !active) {
                    li.addEventListener("click", e => {
                        e.preventDefault();
                        currentPage = pageNum;
                        renderLeaderboardPage(currentPage, leaderboard);
                        renderPagination(leaderboard);
                        leaderboardBody.scrollIntoView({
                            behavior: "smooth",
                            block: "start"
                        });
                    });
                }
                return li;
            };

            pagination.appendChild(createPageItem(currentPage - 1, "Previous", currentPage === 1));

            // how many pages shown at one time
            let windowSize = 3;
            let startPage = Math.max(1, currentPage - windowSize);
            let endPage = Math.min(totalPages, currentPage + windowSize);
            for (let i = startPage; i <= endPage; i++) {
                pagination.appendChild(createPageItem(i, i, false, currentPage === i));
            }

            // Next button
            pagination.appendChild(createPageItem(currentPage + 1, "Next", currentPage === totalPages));
        }



        let leaderboard = <?= $all_users_json ?>;
        leaderboard.sort((a, b) => b.points - a.points);

        let username = "<?= $username ?>";
        let user = leaderboard.find(u => u.username === username) || leaderboard[0];

        // Populate leaderboard
        renderLeaderboardPage(currentPage, leaderboard);
        renderPagination(leaderboard);


        // Personal card
        document.getElementById("userPoints").innerText = user.points;
        document.getElementById("userRank").innerText = leaderboard.indexOf(user) + 1;

        // show me ur badge
        let badgeContainer = document.getElementById("userBadge");
        let badgePic = "bronze_badge";
        let badgeText = "Bronze";
        if (user.points >= 30 && user.points < 80) {
            badgePic = "silver_badge";
            badgeText = "Silver";
        } else if (user.points >= 80) {
            badgePic = "gold_badge";
            badgeText = "Gold";
        }
        badgeContainer.innerHTML = `<img src="pictures/${badgePic}.png" style="height:50px; width=auto" class="me-2"><strong>${badgeText} Badge</strong>`;

        // Recent activity
        let all_user_events = <?= $user_events_json ?>;

        function filterRecentEvents(events) {
            const now = new Date();
            const twoWeeksAgo = new Date();
            twoWeeksAgo.setDate(now.getDate() - 14);

            return events.filter(event => {
                const eventDate = new Date(event.dateISO);
                return eventDate >= twoWeeksAgo && eventDate <= now;
            });
        }


        recentEvents = filterRecentEvents(all_user_events);

        let recentContainer = document.getElementById("recentEvents");
        recentEvents.forEach(ev => {
            recentContainer.innerHTML += `
        <div class="col-md-4 mb-3">
          <div class="event-card ${ev.category}">
            <img class="event-thumb" src="${ev.picture}" alt="${ev.title}">
            <div class="event-body">
              <div class="d-flex justify-content-between align-items-start">
                <h5 class="event-title mb-1">${ev.title}</h5>
              </div>
              <ul class="meta-list">
                <li><i class="bi bi-calendar2-event"></i>${formatDate(ev.startISO)}</li>
                <li><i class="bi bi-clock"></i>${ev.start_time} - ${ev.end_time}</li>
                <li><i class="bi bi-geo-alt"></i>${ev.location}</li>
              </ul>
            </div>
          </div>    
        </div>`;
        });

        // Search filter
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let filter = this.value.toLowerCase();
            let rows = leaderboardBody.getElementsByTagName("tr");
            for (let r of rows) {
                let usernameCell = r.cells[1].textContent.toLowerCase();
                r.style.display = usernameCell.includes(filter) ? "" : "none";
            }
        });
    </script>
</body>

</html>
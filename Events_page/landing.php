<?php
spl_autoload_register(
  function ($class) {
    require_once "model/$class.php";
  }
);

$dao = new EventCollectionDAO();

// Get all events
$events_obj = $dao->getEvents();
$events_arr = array_map(function ($event) {
  return [
    'id' => $event->getId(),
    'title' => $event->getTitle(),
    'category' => $event->getCategory(),
    'date' => $event->getDate(),
    'start_time' => $event->getStartTime(),
    'end_time' => $event->getEndTime(),
    'location' => $event->getLocation(),
    'details' => $event->getDetails(),
    'picture' => $event->getPicture(),
    'startISO' => $event->getStartISO(),
    'endISO' => $event->getEndISO(),
  ];
}, $events_obj);

$events_json = json_encode($events_arr);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>OMNI - SMU Events</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="landing_style.css">
<script src='https://unpkg.com/axios/dist/axios.min.js'></script>
</head>

<body>
<div class="page-container">
  <div class="header-row">
    <div class="logo">
      <img src="pictures/omni_logo.png" alt="Omni Logo" class="omni-logo">
      <div class="logo-text">OMNI</div>
    </div>
    <div class="auth-buttons">
      <button class="btn btn-login" onclick="openLoginModal()">Log In</button>
      <button class="btn btn-signup" onclick="openRegisterModal()">Sign Up</button>
    </div>
  </div>
  
  <div class="hero-section">
    <h1>Discover Events at SMU</h1>
    <p class="subtitle">Explore exciting events happening around campus - Find events you like and save them to My Events.</p>
  </div>

  <!-- Trending Event -->
  <div id="trending"></div>

  <!-- Events Section -->
  <div class="events-section">
    <div class="section-header">
      <h2 class="section-title">All Events</h2>
    </div>
    
    <div class="events-grid" id="eventsGrid"></div>
    
    <div class="carousel-controls">
      <button class="carousel-btn" id="prevBtn" onclick="changeSlide(-1)">
        <i class="bi bi-chevron-left"></i>
      </button>
      <div class="carousel-dots" id="carouselDots"></div>
      <button class="carousel-btn" id="nextBtn" onclick="changeSlide(1)">
        <i class="bi bi-chevron-right"></i>
      </button>
    </div>
  </div>
</div>

<!-- Login Modal -->
<div class="auth-overlay" id="loginOverlay">
  <div class="auth-modal-card">
    <button class="close-btn" onclick="closeLoginModal()">×</button>
    <div class="form-narrow">
      <h1 class="brand">Hello Again!</h1>

      <form method="POST" action="Login.php" novalidate>
        <div class="segment" role="radiogroup" aria-label="Login as">
          <input type="radio" id="login-user" name="loginrole" value="user" checked>
          <label for="login-user"><i class="bi bi-person"></i> User</label>

          <input type="radio" id="login-admin" name="loginrole" value="admin">
          <label for="login-admin"><i class="bi bi-shield-lock"></i> Admin</label>

          <span class="slider" aria-hidden="true"></span>
        </div>

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="loginEmail">Username/Email</label>
            <input id="loginEmail" name="username" type="text" class="form-control" placeholder="Enter your username or email">
          </div>
          <div class="col-12">
            <label class="form-label" for="loginPwd">Password</label>
            <input id="loginPwd" type="password" name="password" class="form-control" placeholder="Enter your password">
          </div>
        </div>

        <button class="btn btn-cta my-3" type="submit">Login</button>
      </form>

      <p class="mt-3 text-center text-muted">
        Don't have an account? <a href="#" onclick="switchToRegister(event)" style="color: #5563DE; text-decoration: none; font-weight: 600;">Register</a>
      </p>
    </div>
  </div>
</div>

<!-- Register Modal -->
<div class="auth-overlay" id="registerOverlay">
  <div class="auth-modal-card">
    <button class="close-btn" onclick="closeRegisterModal()">×</button>
    <div class="form-narrow">
      <h1 class="brand">Welcome!</h1>

      <form id="regForm" method="POST" action="register.php" novalidate>
        <div class="segment" role="radiogroup" aria-label="Register as">
          <input type="radio" id="reg-user" name="regrole" value="user" checked>
          <label for="reg-user"><i class="bi bi-person"></i> User</label>

          <input type="radio" id="reg-admin" name="regrole" value="admin">
          <label for="reg-admin"><i class="bi bi-shield-lock"></i> Admin</label>

          <span class="slider" aria-hidden="true"></span>
        </div>

        <div class="row g-3">
          <div class="col-md-6 field">
            <label class="form-label" for="username">Username</label>
            <input id="username" name="username" class="form-control" minlength="3" maxlength="24" pattern="^[a-zA-Z0-9_\.]+$" placeholder="Your username">
            <div class="tick" id="usernameTick">✓</div>
          </div>

          <div class="col-md-6 field">
            <label class="form-label" for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$" required placeholder="your@email.com">
          </div>

          <div class="col-md-6 field">
            <label class="form-label" for="password">Password</label>
            <input id="password" name="password" class="form-control" type="password" autocomplete="new-password" placeholder="Create password">
            <div class="tick" id="pwTick">✓</div>
            <div id="pwRulesBox" class="pw-box">
              <div class="pw-rules" aria-live="polite">
                <div class="rule" data-rule="len"><span class="dot"></span> At least 10 characters</div>
                <div class="rule" data-rule="upper"><span class="dot"></span> Uppercase letter (A-Z)</div>
                <div class="rule" data-rule="lower"><span class="dot"></span> Lowercase letter (a-z)</div>
                <div class="rule" data-rule="num"><span class="dot"></span> Number (0-9)</div>
                <div class="rule" data-rule="sym"><span class="dot"></span> Symbol (!@#$…)</div>
                <div class="rule" data-rule="space"><span class="dot"></span> No spaces</div>
              </div>
            </div>
          </div>

          <div class="col-md-6 field">
            <label class="form-label" for="confirmPassword">Confirm password</label>
            <input id="confirmPassword" name="confirmPassword" class="form-control" type="password" autocomplete="new-password" placeholder="Re-enter password">
            <div class="tick" id="cpwTick">✓</div>
          </div>

          <div class="col-md-4">
            <label class="form-label" for="year">Year</label>
            <select id="year" class="form-select" name="year">
              <option value="" selected disabled>Select</option>
              <option>1</option>
              <option>2</option>
              <option>3</option>
              <option>4</option>
              <option>5</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="school">School</label>
            <select id="school" class="form-select" name="school">
              <option value="" selected disabled>Select</option>
              <option>School of Accountancy</option>
              <option>School of Business</option>
              <option>School of Economics</option>
              <option>School of Computing & Information Systems</option>
              <option>School of Law</option>
              <option>School of Social Sciences</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="major">Major</label>
            <input id="major" class="form-control" name="major" placeholder="Your major">
          </div>

          <div id="adminFields" class="col-12 d-none">
            <label class="form-label" for="clubOffice">Club / Office</label>
            <input id="clubOffice" class="form-control" name="club" placeholder="Your club or office">
          </div>
        </div>

        <button class="btn btn-cta mt-3" type="submit">Create Account</button>
      </form>

      <p class="mt-3 text-center text-muted">
        Already registered? <a href="#" onclick="switchToLogin(event)" style="color: #5563DE; text-decoration: none; font-weight: 600;">Sign in</a>
      </p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Load events from PHP
let events = <?= $events_json ?>;

let currentSlide = 0;
const eventsPerPage = 3;
const totalSlides = Math.ceil(events.length / eventsPerPage);

// Find trending event
function getAllEventRankings() {
  let url = "axios/sql_updating.php";

  const requestPromises = events.map((event) => {
    return axios.get(url, { params: 
      {
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

function formatDate(dateStr) {
  const date = new Date(dateStr);
  const options = { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' };
  return date.toLocaleDateString('en-US', options);
}

document.addEventListener('DOMContentLoaded', () => {
  getAllEventRankings().then(() => {      
    events.sort((a, b) => b.signups - a.signups);
    console.log(events);
    let trendingEvent = events[0];
    console.log(trendingEvent);

    // displaying the trending event
    if(trendingEvent) {
      document.getElementById("trending").innerHTML = `
        <div class="featured-event" onclick="showSignInPrompt()">
          <div class="featured-image">
            <img src="${trendingEvent.picture}" alt="${trendingEvent.title}">
          </div>
          <div class="featured-details">
            <span class="trending-badge"><i class="bi bi-lightning-fill"></i> Featured Event</span>
            <h3>${trendingEvent.title}</h3>
            <div class="event-meta">
              <div class="event-meta-item">
                <i class="bi bi-calendar-event"></i>
                <span>${formatDate(trendingEvent.date)}</span>
              </div>
              <div class="event-meta-item">
                <i class="bi bi-clock"></i>
                <span>${trendingEvent.start_time} - ${trendingEvent.end_time}</span>
              </div>
              <div class="event-meta-item">
                <i class="bi bi-geo-alt-fill"></i>
                <span>${trendingEvent.location}</span>
              </div>
            </div>
          </div>
        </div>`;
    }
  });
  // const trendingEvent = getTrendingEvent();
  // if(trendingEvent) {
  //   document.getElementById("trending").innerHTML = `
  //     <div class="featured-event" onclick="showSignInPrompt()">
  //       <div class="featured-image">
  //         <img src="${trendingEvent.picture}" alt="${trendingEvent.title}">
  //       </div>
  //       <div class="featured-details">
  //         <span class="trending-badge"><i class="bi bi-lightning-fill"></i> Featured Event</span>
  //         <h3>${trendingEvent.title}</h3>
  //         <div class="event-meta">
  //           <div class="event-meta-item">
  //             <i class="bi bi-calendar-event"></i>
  //             <span>${formatDate(trendingEvent.date)}</span>
  //           </div>
  //           <div class="event-meta-item">
  //             <i class="bi bi-clock"></i>
  //             <span>${trendingEvent.start_time} - ${trendingEvent.end_time}</span>
  //           </div>
  //           <div class="event-meta-item">
  //             <i class="bi bi-geo-alt-fill"></i>
  //             <span>${trendingEvent.location}</span>
  //           </div>
  //         </div>
  //       </div>
  //     </div>`;
  // }

  renderSlide(currentSlide);
  renderDots();
  updateButtons();
});

function renderSlide(slideIndex) {
  const grid = document.getElementById('eventsGrid');
  const startIdx = slideIndex * eventsPerPage;
  const endIdx = startIdx + eventsPerPage;
  const slideEvents = events.slice(startIdx, endIdx);
  
  grid.innerHTML = slideEvents.map(event => `
    <div class="event-card" onclick="showSignInPrompt()">
      <div class="event-image-wrapper">
        <img src="${event.picture}" alt="${event.title}" class="event-image">
      </div>
      <div class="event-content">
        <div class="event-title">${event.title}</div>
        <div class="event-info">
          <div class="event-info-item">
            <i class="bi bi-calendar-event"></i>
            <span>${formatDate(event.date)}</span>
          </div>
          <div class="event-info-item">
            <i class="bi bi-clock"></i>
            <span>${event.start_time} - ${event.end_time}</span>
          </div>
          <div class="event-info-item">
            <i class="bi bi-geo-alt"></i>
            <span>${event.location}</span>
          </div>
        </div>
      </div>
    </div>
  `).join('');
}

function renderDots() {
  const dotsContainer = document.getElementById('carouselDots');
  dotsContainer.innerHTML = '';
  for(let i = 0; i < totalSlides; i++) {
    const dot = document.createElement('div');
    dot.className = 'dot' + (i === currentSlide ? ' active' : '');
    dot.onclick = () => goToSlide(i);
    dotsContainer.appendChild(dot);
  }
}

function changeSlide(direction) {
  currentSlide += direction;
  if(currentSlide < 0) currentSlide = 0;
  if(currentSlide >= totalSlides) currentSlide = totalSlides - 1;
  renderSlide(currentSlide);
  renderDots();
  updateButtons();
}

function goToSlide(index) {
  currentSlide = index;
  renderSlide(currentSlide);
  renderDots();
  updateButtons();
}

function updateButtons() {
  document.getElementById('prevBtn').disabled = currentSlide === 0;
  document.getElementById('nextBtn').disabled = currentSlide === totalSlides - 1;
}

// Auth Modal Functions
function showSignInPrompt() {
  // Show the sign-in required modal first
  const modal = document.createElement('div');
  modal.className = 'modal active';
  modal.innerHTML = `
    <div class="modal-content">
      <button class="modal-close" onclick="this.closest('.modal').remove(); document.body.style.overflow='auto'">×</button>
      <h2>Sign In Required</h2>
      <p>Please sign in to view event details and save events to your calendar.</p>
      <div class="modal-buttons">
        <button class="modal-btn modal-btn-login" onclick="this.closest('.modal').remove(); openLoginModal()">Log In</button>
        <button class="modal-btn modal-btn-signup" onclick="this.closest('.modal').remove(); openRegisterModal()">Sign Up</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
  document.body.style.overflow = 'hidden';
  
  // Close when clicking outside
  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      modal.remove();
      document.body.style.overflow = 'auto';
    }
  });
}

function openLoginModal() {
  document.getElementById('loginOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeLoginModal() {
  document.getElementById('loginOverlay').classList.remove('active');
  document.body.style.overflow = 'auto';
}

function openRegisterModal() {
  document.getElementById('registerOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeRegisterModal() {
  document.getElementById('registerOverlay').classList.remove('active');
  document.body.style.overflow = 'auto';
}

function switchToRegister(e) {
  e.preventDefault();
  closeLoginModal();
  setTimeout(() => openRegisterModal(), 300);
}

function switchToLogin(e) {
  e.preventDefault();
  closeRegisterModal();
  setTimeout(() => openLoginModal(), 300);
}

// Close modals when clicking outside
document.getElementById('loginOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLoginModal();
});

document.getElementById('registerOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeRegisterModal();
});

// Close with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeLoginModal();
    closeRegisterModal();
  }
});

// Register Form Validation
const adminFields = document.getElementById('adminFields');
const regAdmin = document.getElementById('reg-admin');
const regUser = document.getElementById('reg-user');

function toggleAdmin() {
  adminFields.classList.toggle('d-none', !regAdmin.checked);
}

regAdmin.addEventListener('change', toggleAdmin);
regUser.addEventListener('change', toggleAdmin);

// Email validation
const email = document.getElementById('email');
email.addEventListener('input', () => {
  if (email.validity.typeMismatch || email.validity.patternMismatch) {
    email.setCustomValidity("Please enter a valid email (must include '@' and a domain).");
  } else {
    email.setCustomValidity("");
  }
});

// Password validation
const pw = document.getElementById('password');
const cpw = document.getElementById('confirmPassword');
const pwBox = document.getElementById('pwRulesBox');
const pwTick = document.getElementById('pwTick');
const cpwTick = document.getElementById('cpwTick');

const rules = {
  len: v => v.length >= 10,
  upper: v => /[A-Z]/.test(v),
  lower: v => /[a-z]/.test(v),
  num: v => /\d/.test(v),
  sym: v => /[^A-Za-z0-9\s]/.test(v),
  space: v => !/\s/.test(v),
};

function showUnmetRules(resultMap) {
  const started = (pw.value || '').length > 0;
  const allGood = Object.values(resultMap).every(Boolean);
  pwBox.style.display = (!allGood && started) ? 'block' : 'none';

  for (const [key, ok] of Object.entries(resultMap)) {
    const row = pwBox.querySelector(`.rule[data-rule="${key}"]`);
    if (!row) continue;
    row.style.display = 'flex';
    row.classList.toggle('met', ok);
    if (ok) {
      setTimeout(() => { row.style.display = 'none'; }, 600);
    }
  }
}

function validatePw() {
  const v = pw.value || '';
  const res = Object.fromEntries(Object.entries(rules).map(([k, fn]) => [k, fn(v)]));
  const allGood = Object.values(res).every(Boolean);

  pwTick.classList.toggle('show', allGood && v.length > 0);
  showUnmetRules(res);

  const matches = v.length > 0 && v === (cpw.value || '');
  cpwTick.classList.toggle('show', matches);
}

pw.addEventListener('input', validatePw);
cpw.addEventListener('input', validatePw);

// Username validation
const username = document.getElementById('username');
const usernameTick = document.getElementById('usernameTick');

username.addEventListener('input', () => {
  const valid = username.validity.valid && username.value.length >= 3;
  usernameTick.classList.toggle('show', valid);
});
</script>
</body>
</html>
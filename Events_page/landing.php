<?php
spl_autoload_register(
  function ($class) {
    require_once "model/$class.php";
  }
);

$dao = new EventCollectionDAO();
$all_events_obj = $dao->getEvents();
$events_arr = array_map(function ($event) {
    return [
      'id' => $event->getId(),
      'title' => $event->getTitle(),
      'category' => $event->getCategory(),
      'date' => $event->getDate(),
      'start_time' => $event->getStartTime(),
      'end_time' => $event->getEndTime(),
      'location' => $event->getLocation(),
      'picture' => $event->getPicture(),
      'startISO' => $event->getStartISO(),
      'endISO' => $event->getEndISO(),
    ];
}, $all_events_obj);

$events_json = json_encode($events_arr);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>OMNI – SMU Events</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="events_style.css">
<script src='https://unpkg.com/axios/dist/axios.min.js'></script>
<style>
/* body, html {margin:0; padding:0; height:100%; font-family:"Poppins",sans-serif;} */
.maincontainer {display:flex; height:100vh; margin:0; padding:0;}
.left {flex:2; background:whitesmoke; color:goldenrod; display:flex; flex-direction:column; justify-content:start; align-items:center; padding:2rem; overflow-y:auto;}
.right {flex:1; background-color:rgb(20,27,77); display:flex; justify-content:center; align-items:center; padding:3rem;}
.signup-card {background:whitesmoke; padding:2.5rem; border-radius:15px; box-shadow:0 10px 40px rgba(0,0,0,0.1); width:100%; max-width:400px;}
.signup-card h2 {color:black; font-weight:600; margin-bottom:1.5rem; text-align:center;}
.btn-cta {width:100%; padding:.85rem; border-radius:50px; border:none; background:#e8e8e8; font-size:1rem; font-weight:600; color:#333; transition:all 0.3s ease; cursor:pointer; margin-bottom:1rem;}
.btn-cta:hover {background:#d8d8d8; transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.15);}
.divider {display:flex; align-items:center; text-align:center; margin:1.5rem 0; color:#666; font-weight:500;}
.divider::before, .divider::after {content:''; flex:1; border-bottom:1px solid #ddd;}
.divider span {padding:0 1rem;}
.left::-webkit-scrollbar {width:8px;}
.left::-webkit-scrollbar-thumb {background:rgba(255,255,255,0.3); border-radius:4px;}
</style>
</head>
<body>
<div class="maincontainer">
  <a href="test.php">Go to test.php</a>
  <!-- Left 2/3: Landing content -->
  <div class="left container py-4">
    <div class="wbname d-flex align-items-center justify-content-center">
      <img src="pictures/omni_logo.png" alt="Omni Logo" class="omni-logo me-2" style="height:50px;">
      <h1 class="omni-title mb-0">OMNI</h1>
    </div>
    <br>
    <div class="text-center mb-4">
      <h2 class="fw-bold" style="color:#041373;">Discover Events at SMU</h2>
      <p class="text-muted mb-0"><strong>Explore events in the school - Find events you like and save them to My Events.</strong></p>
    </div>

    <!-- Trending Event -->
    <div id="trending"></div>

    <!-- Carousel -->
    <div id="eventsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="6500">
      <div class="carousel-inner" id="carouselInner"></div>
      <div class="carousel-indicators" id="carouselDots"></div>
      <button class="carousel-control-prev" type="button" data-bs-target="#eventsCarousel" data-bs-slide="prev">
        <i class="bi bi-chevron-left"></i><span class="visually-hidden">Prev</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#eventsCarousel" data-bs-slide="next">
        <i class="bi bi-chevron-right"></i><span class="visually-hidden">Next</span>
      </button>
    </div>
  </div>

  <!-- Right 1/3: Sign Up / Log In -->
  <div class="right">
    <div class="signup-card">
      <h2>Sign Up Now!</h2>     
      <button class="btn-cta" onclick="window.location.href='register.php'">Sign Up</button>     
      <div class="divider"><span>OR</span></div>
      <h2>Already have an account?</h2> 
      <button class="btn-cta" onclick="window.location.href='login.php'">Log In</button>  
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const events = <?= $events_json ?>;

  // Display trending event (just the first)
  if(events.length>0){
    const trendingEvent = events[0];
    document.getElementById("trending").innerHTML=`
      <div class="event-card accent-sky d-flex flex-column flex-md-row align-items-center p-3 mb-3">
        <img src="${trendingEvent.picture}" alt="Featured Event" class="event-thumb me-md-3 mb-3 mb-md-0" style="max-width:320px; border-radius:10px;">
        <div>
          <h2 class="mb-2" style="color:#041373;"><strong>Trending Event</strong></h2>
          <h3 class="mb-2" style="color:#041373;"><strong>${trendingEvent.title}</strong></h3>
          <p class="mb-1"><i class="bi bi-calendar-event"></i> <strong>${formatDate(trendingEvent.startISO)} • ${trendingEvent.start_time} - ${trendingEvent.end_time}</strong></p>
        </div>
      </div>`;
  }

  // Simple carousel rendering (no user events, no clashes)
  function cardTemplate(e){
    return `<div class="col-12 col-sm-6 col-lg-3">
      <div class="event-card ${e.category}">
        <img class="event-thumb" src="${e.picture}" alt="${e.title}">
        <div class="event-body">
          <h5 class="event-title mb-1">${e.title}</h5>
          <ul class="meta-list">
            <li><i class="bi bi-calendar2-event"></i>${formatDate(e.startISO)}</li>
            <li><i class="bi bi-clock"></i>${e.start_time} - ${e.end_time}</li>
            <li><i class="bi bi-geo-alt"></i>${e.location}</li>
          </ul>
        </div>
      </div>
    </div>`;
  }

  function renderCarousel(list){
    const inner = document.getElementById('carouselInner');
    const dots  = document.getElementById('carouselDots');
    if(!inner||!dots) return;

    inner.innerHTML=''; dots.innerHTML='';
    for(let i=0;i<list.length;i+=4){
      const chunk=list.slice(i,i+4);
      const cols = chunk.map(cardTemplate).join('');
      inner.innerHTML += `<div class="carousel-item${i===0?' active':''}"><div class="row g-3">${cols}</div></div>`;
      dots.innerHTML += `<button type="button" data-bs-target="#eventsCarousel" data-bs-slide-to="${i/4}" class="${i===0?'active':''}" aria-label="Slide ${i/4+1}"></button>`;
    }
  }

  function formatDate(startISO) {
    const optsDate = {
      weekday: 'short',
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    };
    const optsTime = { hour:'2-digit', minute:'2-digit' };

    let s = new Date(startISO);
    let dateText = s.toLocaleDateString(undefined, optsDate);
    return dateText;
  }
  renderCarousel(events);
});
</script>
</body>
</html>

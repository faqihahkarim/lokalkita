<?php
session_start();
require 'script/db.php';

// GET ALL EXPERIENCES
$expQuery = $conn->query("
    SELECT e.exp_id, e.exp_title, e.state, e.category, 
           e.min_price, e.max_price,
           COALESCE(e.rating, 0) AS rating
    FROM experience e
    WHERE e.status = 'Approved'
");

$experiences = []; // PREPARE ARRAY

// Default values
$display_rating = 0;
$total_reviews  = 0;

while ($row = $expQuery->fetch_assoc()) {

    // ============================
    // RATING LOGIC (PER EXPERIENCE)
    // ============================
    if (!empty($row['rating']) && $row['rating'] > 0) {
        // Scraped rating
        $row['display_rating'] = $row['rating'];
    } else {
        // User average rating
        $avg_sql = "SELECT AVG(rating) AS avg_rating
                    FROM review
                    WHERE exp_id = ?";
        $avg_stmt = $conn->prepare($avg_sql);
        $avg_stmt->bind_param("i", $row['exp_id']);
        $avg_stmt->execute();
        $avg_data = $avg_stmt->get_result()->fetch_assoc();

        $row['display_rating'] = $avg_data['avg_rating'] ?? 0;
    }

    // ============================
    // IMAGE LOGIC
    // ============================
    $imgQ = $conn->prepare("
        SELECT img_path FROM experience_images 
        WHERE exp_id=? ORDER BY seq_no ASC LIMIT 1
    ");
    $imgQ->bind_param("i", $row['exp_id']);
    $imgQ->execute();
    $imgRes = $imgQ->get_result();

    if ($imgRes->num_rows > 0) {
        $img = $imgRes->fetch_assoc()['img_path'];
        $row['img'] = "../host/uploads/experience/" . $img;
    } else {
        $row['img'] = "pic/default_exp.png";
    }

    $experiences[] = $row;
}


// FETCH LIKED EXPERIENCES
$likedIds = [];
$q = $conn->query("SELECT exp_id FROM user_like WHERE user_id = " . intval($_SESSION['user_id']));
while ($r = $q->fetch_assoc()) {
    $likedIds[] = $r['exp_id'];
}

// FETCH SAVED EXPERIENCES
$savedIds = [];
$q2 = $conn->query("SELECT exp_id FROM user_save WHERE user_id = " . intval($_SESSION['user_id']));
while ($r2 = $q2->fetch_assoc()) {
    $savedIds[] = $r2['exp_id'];
}

/* newly added experience use average rating calculation */
$avg_sql = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews
            FROM review
            WHERE exp_id = ?";
$avg_stmt = $conn->prepare($avg_sql);
$avg_stmt->bind_param("i", $exp_id);
$avg_stmt->execute();
$avg_data = $avg_stmt->get_result()->fetch_assoc();

$avg_rating    = $avg_data['avg_rating'] ?? 0;
$total_reviews = $avg_data['total_reviews'] ?? 0;

?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LokalKita - Discover CBT in Malaysia</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


  <!-- Favicon -->
  <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />

  <!-- Styles -->
  <link rel="stylesheet" href="mainpage.css" />
  <style>
    .load-more-btn {
      background: #111;
      color: #fff;
      padding: 12px 28px;
      border-radius: 999px;
      border: none;
      font-family: Poppins, sans-serif;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.25s ease;
    }

    .load-more-btn:hover {
      background: #333;
      transform: translateY(-1px);
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header>
    <h1 class="logo">LokalKita</h1>

    <!-- Hamburger icon -->
    <div class="menu-toggle" id="menu-toggle">
      <i class="fas fa-grip-horizontal"></i>
    </div>


    <nav id="navbar">
      <ul>
        <li><a href="#popular">Browse</a></li>
        <li><a href="user_dashboard.php">Profile</a></li>
        <!--li><a href="../host/host_signup.html">Be Our Host</a></li-->
        <li><a href="user_login.html">Logout</a></li>
      </ul>
    </nav>
  </header>


  <!-- Hero -->
  <section class="hero" id="hero">
    <h1 class="hero-title">
      <span class="line-up">Discover 
        <span class="circle-highlight">CBT</span>
      </span><br />
      <span class="line-down">in Malaysia</span>
    </h1>
    <p>Dive into Malaysia's gems made by local communities.</p>
  </section>

  <section id="popular">
  <h3>Our Experiences</h3>

  <!-- FILTER BAR -->
  <div class="toolbar">
    <input id="searchInput" type="text" placeholder="Search experiences…">
    <select id="stateSelect">
      <option value="">All States</option>
      <option>Johor</option><option>Kedah</option><option>Kelantan</option><option>Kuala Lumpur</option>
      <option>Labuan</option><option>Melaka</option><option>Negeri Sembilan</option><option>Pahang</option>
      <option>Penang</option><option>Perak</option><option>Perlis</option><option>Putrajaya</option>
      <option>Sabah</option><option>Sarawak</option><option>Selangor</option>
      <option>Terengganu</option>
    </select>

    <select id="budgetSelect">
      <option value="">Any Budget</option>
      <option value="0-99">Below RM100</option>
      <option value="100-299">RM100 – RM299</option>
      <option value="300-599">RM300 – RM599</option>
      <option value="600-999">RM600 – RM999</option>
      <option value="1000-99999">RM1000+</option>
    </select>
  </div>

  <!-- CATEGORY CHIPS -->
  <div class="filters">
    <button class="chip active" data-filter="all">All</button>
    <button class="chip" data-filter="culture & heritage">Culture & Heritage</button>
    <button class="chip" data-filter="festivals & performances">Festivals & Performances</button>
    <button class="chip" data-filter="food & culinary">Food & Culinary</button>
    <button class="chip" data-filter="arts & crafts">Arts & Crafts</button>
    <button class="chip" data-filter="nature & eco">Nature & Eco</button>
    <button class="chip" data-filter="homestay">Homestay</button>
  </div>

  <!-- EXPERIENCE GRID -->
    <p id="aiHint" style="display:none; font-size:14px; opacity:0.7;">
      🔍 Showing Top 10 AI-recommended experiences
    </p>

    <br>

  <div class="grid" id="expGrid">

    <?php foreach ($experiences as $exp): ?>
      <a href="experience-detail.php?exp_id=<?= $exp['exp_id'] ?>" class="card-link">
        <article class="card"
          data-category="<?= strtolower($exp['category']) ?>"
          data-state="<?= $exp['state'] ?>"
          data-price="<?= $exp['min_price'] ?>">

          <div class="card-media">
            <img 
                src="<?= $exp['img'] ?>" 
                class="rec-img"
                onerror="this.onerror=null; this.src='../pic/default.png';"
            >

            <span class="badge"><?= ucfirst($exp['category']) ?></span>
          </div>

          <div class="content">
            <h4><?= $exp['exp_title'] ?></h4>
            <p class="meta">📍 <?= $exp['state'] ?> • RM<?= $exp['min_price'] ?></p>

            <div class="rating">
                <i class="fa-solid fa-star"></i>
                <?php if ($exp['display_rating'] > 0): ?>
                    <span class="rating-num"><?= number_format($exp['display_rating'], 1) ?></span>
                <?php else: ?>
                    <span class="rating-num">No rating yet</span>
                <?php endif; ?>
            </div>


          </div>

        
          <div class="card-actions">
            <?php
              $expId = $exp['exp_id'];
              $isLiked = in_array($expId, $likedIds);
              $isSaved = in_array($expId, $savedIds);
            ?>

              <button class="btn-like <?= $isLiked ? 'active' : '' ?>" data-exp="<?= $expId ?>">
                  <i class="<?= $isLiked ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
              </button>

              <button class="btn-save <?= $isSaved ? 'active' : '' ?>" data-exp="<?= $expId ?>">
                  <i class="<?= $isSaved ? 'fa-solid' : 'fa-regular' ?> fa-bookmark"></i>
              </button>
          </div>

        </article>
      </a>
    <?php endforeach; ?>

  </div>

  <div style="text-align:center; margin:40px 0;">
    <button id="loadMoreBtn" class="load-more-btn">
      Load More
    </button>
  </div>


</section>
<script>

let aiMode=false;
const aiHint = document.getElementById("aiHint");

// Toggle filter inputs based on AI mode
function toggleFilters(disabled) {
  stateSelect.disabled = disabled;
  budgetSelect.disabled = disabled;

  chips.forEach(chip => {
    chip.style.pointerEvents = disabled ? "none" : "auto";
    chip.style.opacity = disabled ? "0.5" : "1";
  });
}


// Elements
const cards = Array.from(document.querySelectorAll('.card-link'));
const chips = document.querySelectorAll('.chip');
const searchInput = document.getElementById("searchInput");
const stateSelect = document.getElementById("stateSelect");
const budgetSelect = document.getElementById("budgetSelect");

let activeCategory = "all";

function withinBudget(price, range) {
  if (!range) return true;
  const [min, max] = range.split("-").map(Number);
  return price >= min && price <= max;
}

function matchesSearch(article, q) {
  if (!q) return true;
  q = q.toLowerCase();

  return (
    article.querySelector("h4").textContent.toLowerCase().includes(q) ||
    article.querySelector(".meta").textContent.toLowerCase().includes(q)
  );
}

// Apply filters to cards
function applyFilters() {
  if (aiMode) return;   // skip traditional filtering in AI mode

  const q = searchInput.value.trim();
  const selectedState = stateSelect.value;
  const budgetRange = budgetSelect.value;

  cards.forEach(card => {
    const art = card.querySelector(".card");

    const category = art.dataset.category;
    const state = art.dataset.state;
    const price = Number(art.dataset.price);

    const catOk = activeCategory === "all" || category === activeCategory;
    const stateOk = !selectedState || state === selectedState;
    const budgetOk = withinBudget(price, budgetRange);
    const searchOk = matchesSearch(art, q);

    card.style.display = (catOk && stateOk && budgetOk && searchOk) ? "" : "none";
  });
}



// Event listeners
chips.forEach(chip =>
  chip.addEventListener("click", () => {
    chips.forEach(c => c.classList.remove("active"));
    chip.classList.add("active");
    activeCategory = chip.dataset.filter;
    applyFilters();
  })
);


searchInput.addEventListener("input", () => setTimeout(applyFilters, 200));
stateSelect.addEventListener("change", applyFilters);
budgetSelect.addEventListener("change", applyFilters);



// Calling for search recommendations
searchInput.addEventListener("input", () => {
  clearTimeout(aiSearchTimeout);

  const q = searchInput.value.trim();

  // NORMAL MODE
  if (q.length < 3) {
    aiMode = false;
    toggleFilters(false);
    aiHint.style.display = "none";   // hide AI hint
    applyFilters();
    return;
  }

  // AI MODE
aiMode = true;
toggleFilters(true);
aiHint.style.display = "block";

// Add this line to stop the "flicker"
if (aiSearchTimeout) clearTimeout(aiSearchTimeout); 

aiSearchTimeout = setTimeout(() => {
    // Note: Make sure the path is correct (e.g., "user/search_ai.php" if this JS is in the root)
    fetch("user/search_ai.php?q=" + encodeURIComponent(q)) 
      .then(res => res.json())
      .then(data => {
          // Add a simple check to ensure data exists
          if (data && !data.error) {
              renderAIResults(data);
          } else {
              console.error("AI Error:", data.error);
              // Handle "No results" here if you like
          }
      })
      .catch(err => console.error("Fetch error:", err));
}, 500);
});




document.querySelectorAll(".btn-like").forEach(btn => {
    btn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();

        const exp_id = btn.dataset.exp;

        fetch("script/like_save.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "exp_id=" + exp_id + "&type=like"
        })
        .then(r => r.json())
        .then(res => {

            const icon = btn.querySelector("i");

            if (res.status === "liked") {
                btn.classList.add("active");
                icon.classList.remove("fa-regular");
                icon.classList.add("fa-solid");
            } else {
                btn.classList.remove("active");
                icon.classList.remove("fa-solid");
                icon.classList.add("fa-regular");
            }
        });
    });
});

document.querySelectorAll(".btn-save").forEach(btn => {
    btn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();

        const exp_id = btn.dataset.exp;

        fetch("script/like_save.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "exp_id=" + exp_id + "&type=save"
        })
        .then(r => r.json())
        .then(res => {

            const icon = btn.querySelector("i");

            if (res.status === "saved") {
                btn.classList.add("active");
                icon.classList.remove("fa-regular");
                icon.classList.add("fa-solid");
            } else {
                btn.classList.remove("active");
                icon.classList.remove("fa-solid");
                icon.classList.add("fa-regular");
            }
        });
    });
});

// Calling for search recommendations
let aiSearchTimeout;

searchInput.addEventListener("input", () => {
  clearTimeout(aiSearchTimeout);

  const q = searchInput.value.trim();

  // Bila query pendek → balik ke filter biasa
  if (q.length < 3) {
    aiMode = false;
    applyFilters();
    return;
  }

  aiMode = true;

  aiSearchTimeout = setTimeout(() => {
    fetch("search_ai.php?q=" + encodeURIComponent(q))
      .then(res => res.json())
      .then(data => {
        renderAIResults(data);
      });
  }, 500);
});


// Render AI search results
function renderAIResults(data) {
  const grid = document.getElementById("expGrid");
  grid.innerHTML = "";

  if (data.length === 0) {
    grid.innerHTML = "<p>No recommendations found.</p>";
    return;
  }

  data.forEach(exp => {
    grid.innerHTML += `
      <a href="experience-detail.php?exp_id=${exp.exp_id}" class="card-link">
        <article class="card">
          <div class="card-media">
            <img 
              src="${exp.img}"
              class="rec-img"
              onerror="this.onerror=null; this.src='../pic/default.png';"
            >
            <span class="badge">${exp.category}</span>
          </div>

          <div class="content">
            <h4>${exp.exp_title}</h4>
            <p class="meta">📍 ${exp.state} • RM${exp.min_price}</p>
            <div class="rating">
              <i class="fa-solid fa-star"></i>
              <span>${parseFloat(exp.rating).toFixed(1)}</span>
            </div>
          </div>
        </article>
      </a>
    `;
  });
}


</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const cards = Array.from(document.querySelectorAll("#expGrid .card-link"));
  const loadMoreBtn = document.getElementById("loadMoreBtn");

  const ITEMS_PER_LOAD = 15;
  let visibleCount = ITEMS_PER_LOAD;

  // Hide all cards first
  cards.forEach((card, index) => {
    if (index >= ITEMS_PER_LOAD) {
      card.style.display = "none";
    }
  });

  // Hide button if not needed
  if (cards.length <= ITEMS_PER_LOAD) {
    loadMoreBtn.style.display = "none";
  }

  loadMoreBtn.addEventListener("click", () => {
    const nextVisible = visibleCount + ITEMS_PER_LOAD;

    cards.forEach((card, index) => {
      if (index < nextVisible) {
        card.style.display = "block";
      }
    });

    visibleCount = nextVisible;

    // Hide button when all shown
    if (visibleCount >= cards.length) {
      loadMoreBtn.style.display = "none";
    }
  });
});
</script>


</body>
</html>
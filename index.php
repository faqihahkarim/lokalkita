<?php
require 'db.php';

$sql = "
  SELECT 
    e.exp_id,
    e.exp_title,
    e.category,
    e.state,
    e.min_price,
    e.rating,
    (
      SELECT img_path 
      FROM experience_images 
      WHERE exp_id = e.exp_id 
      ORDER BY seq_no ASC 
      LIMIT 1
    ) AS first_img
  FROM experience e
  WHERE e.rating BETWEEN 4.5 AND 5.0
    AND e.status = 'Approved'
  ORDER BY e.rating DESC
  LIMIT 20
";

$result = $conn->query($sql);
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
  <link href="pic/logo2.png" sizes="32x16" rel="shortcut icon" />

  <!-- Styles -->
  <link rel="stylesheet" href="landingcss.css" />
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
        <li><a href="#popular">Experiences</a></li>
        <li><a href="#join-us">Join Us</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="login/login_type.php">Login</a></li>
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

  <!-- Popular Experiences -->
  <section id="popular">
    <h3>Popular Experiences</h3>

    <!-- Category filter chips -->
    <div class="filters" aria-label="Filter experiences by category">
      <button class="chip active" data-filter="all">All</button>
      <button class="chip" data-filter="culture and heritage">Culture & Heritage</button>
      <button class="chip" data-filter="festival and performance">Festival & Performance</button>
      <button class="chip" data-filter="food and culinary">Food & Culinary</button>
      <button class="chip" data-filter="arts and crafts">Arts & Crafts</button>
      <button class="chip" data-filter="nature and eco">Nature & Eco</button>
      <button class="chip" data-filter="homestay">Homestay</button>
    </div>

    <div class="grid">

      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($exp = $result->fetch_assoc()): ?>

          <?php
            $rawImg = trim($exp['first_img'] ?? '');
            $imgPath = $rawImg !== ''
              ? "host/uploads/experience/" . htmlspecialchars($rawImg)
              : "host/img/default.png";
          ?>

          <a href="index_exp.php?exp_id=<?= (int)$exp['exp_id']; ?>" class="card-link" 
          style="text-decoration: none; color: inherit; display: block;">
            <article class="card" data-category="<?= htmlspecialchars(strtolower($exp['category'])); ?>">
              <div class="card-media">
                <img 
                  src="<?= $imgPath; ?>" 
                  loading="lazy"
                  alt="<?= htmlspecialchars($exp['exp_title']); ?>"
                  onerror="this.onerror=null; this.src='host/img/default.png';"
                />
                <span class="badge"><?= htmlspecialchars($exp['category']); ?></span>
              </div>

              <div class="content">
                <h4><?= htmlspecialchars($exp['exp_title']); ?></h4>

                <p>Experience highly rated by users.</p>

                <p style="font-size:0.9rem;">
                  <i class="fa-solid fa-location-dot" style="color:#e74c3c; margin-right:6px;"></i>
                  <?= htmlspecialchars($exp['state']); ?> · RM <?= number_format($exp['min_price'], 0); ?>
                </p>

                <p style="font-size:0.9rem;">
                  <i class="fa-solid fa-star" style="color:#FFEE8C; margin-right:6px;"></i>
                  <?= number_format($exp['rating'], 1); ?>
                </p>
              </div>
            </article>
          </a>

        <?php endwhile; ?>
      <?php else: ?>
        <p style="text-align:center;">No top-rated experiences found.</p>
      <?php endif; ?>

      </div>


    
    <div class="load-more-wrap">
      <button class="btn-load">Load more</button>
    </div>
    
  </section>

    <section class="join-us" id="join-us">
      <h3>Join Us</h3>
      <div class="join-wrap">

        <!-- Host Card -->
        <div class="join-container host">
          <div class="join-card">
            <div class="card-content">
              <h4>Interested to be a Host?</h4>
              <p>Join the LokalKita family, share your business's and unique cultural experiences.</p>
              <a href="host/host_signup.html" class="btn-cta">Join as Host</a>
            </div>
          </div>
          <img src="pic/boy.png" alt="Mascot Boy" class="mascot mascot-left">
        </div>

        <!-- User Card -->
        <div class="join-container user">
          <div class="join-card">
            <div class="card-content">
              <h4>Want to Explore?</h4>
              <p>Discover Malaysia’s hidden gems and save your favourites.</p>
              <a href="user/user_signup.html" class="btn-cta">Join as User</a>
            </div>
          </div>
          <img src="pic/girl.png" alt="Mascot Girl" class="mascot mascot-right">
        </div>

      </div>
    </section>



  <!-- About -->
  <section class="about" id="about">
  <div class="about-wrap">
    <div class="about-media">
      <div class="about-photo tilt-left">
        <img src="pic/labu-sayong.jpg" alt="Handicrafts: Labu Sayong">
        <p>HANDICRAFTS: LABU SAYONG</p>
      </div>
      <div class="about-photo tilt-right">
        <img src="pic/batik.png" alt="Textile: Batik">
        <p>TEXTILE: BATIK</p>
      </div>
      <div class="about-photo tilt-left">
        <img src="pic/mengkuang.jpg" alt="Handicrafts: Mengkuang Mat">
        <p>HANDICRAFTS: MENGKUANG MAT</p>
      </div>
    </div>
    
    <div class="about-content">
      <h3>About Us</h3>
      <p>
        LokalKita is a community-based tourism platform that connects travellers with authentic cultural and heritage experiences across Malaysia. 
        We empower local communities by showcasing their unique arts, crafts, food, and traditions while promoting sustainable tourism.
      </p>
      <a class="btn-cta" href="#popular">Start Exploring</a>
    </div>
  </div>
</section>


  <!-- Footer -->
  <footer>
    <p>&copy; 2025 LokalKita. All rights reserved.</p>
  </footer>

  <!-- Minimal JS: filters + like/save toggles -->
  <script>
    // Filter by category
    document.querySelectorAll('.chip').forEach(chip => {
      chip.addEventListener('click', () => {
        document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        const filter = chip.dataset.filter;
        document.querySelectorAll('#popular .card').forEach(card => {
          const match = filter === 'all' || card.dataset.category === filter;
          card.style.display = match ? '' : 'none';
        });
      });
    });

  </script>

    <!-- Scroll Animation JS -->
      <script src="https://unpkg.com/scrollreveal"></script>
      <script>
        ScrollReveal({
          distance: '50px',
          duration: 1000,
          easing: 'ease-out',
          reset: false
        });

        // Sections
        ScrollReveal().reveal('.hero-title', { origin: 'bottom', delay: 200 });
        ScrollReveal().reveal('.hero p', { origin: 'bottom', delay: 400 });
        ScrollReveal().reveal('#popular h3', { origin: 'bottom', delay: 200 });
        ScrollReveal().reveal('.card', { interval: 150, origin: 'bottom' });
        ScrollReveal().reveal('.join-us h3', { origin: 'bottom', delay: 200 });
        ScrollReveal().reveal('.join-card', { interval: 200, origin: 'bottom' });
        ScrollReveal().reveal('.about-content', { origin: 'left', delay: 300 });
        ScrollReveal().reveal('.about-media', { origin: 'right', delay: 400 });

        // 🎉 Mascot entrance animations
        ScrollReveal().reveal('.mascot-left', {
          origin: 'left',
          distance: '120px',
          delay: 600,
          duration: 1200,
          easing: 'ease-out',
          opacity: 0,
          beforeReveal: el => {
            el.style.transform = 'translateY(-10px) rotate(-10deg)';
          }
        });

        ScrollReveal().reveal('.mascot-right', {
          origin: 'right',
          distance: '120px',
          delay: 600,
          duration: 1200,
          easing: 'ease-out',
          opacity: 0,
          beforeReveal: el => {
            el.style.transform = 'translateY(-10px) rotate(10deg)';
          }
        });
      </script>


        <script>
          const toggle = document.getElementById('menu-toggle');
          const navbar = document.getElementById('navbar');
          const icon = toggle.querySelector('i');

          toggle.addEventListener('click', () => {
            navbar.classList.toggle('active');
            // Toggle between bars and X
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
          });

          // Optional: close menu when clicking a link
          document.querySelectorAll('nav a').forEach(link => {
            link.addEventListener('click', () => {
              navbar.classList.remove('active');
              icon.classList.add('fa-bars');
              icon.classList.remove('fa-times');
            });
          });
        </script>


<!-- POPUP OVERLAY -->
<div id="join-popup" class="popup-overlay">
  <div class="popup-box">

    <img src="pic/boy.png" class="popup-mascot" alt="Mascot">

    <h2>Enjoy the experience so far?</h2>
    <p>Join us to explore more amazing CBT adventures!</p>

    <div class="popup-buttons">
      <a href="register_host.php" class="popup-btn host">Join as Host</a>
      <a href="register_user.php" class="popup-btn traveler">Join as Traveller</a>
    </div>

    <div class="popup-optout">
      <label>
        <input type="checkbox" id="popup-optout">
        Do not show this again
      </label>
    </div>


    <span class="popup-close">&times;</span>
  </div>
</div>

<script>
  const popup = document.getElementById("join-popup");
  const closePopup = document.querySelector(".popup-close");
  const optout = document.getElementById("popup-optout");

  // Prevent popup if user opted out
  if (localStorage.getItem("hideJoinPopup") === "true") {
    popup.style.display = "none";
  } else {
    window.addEventListener("scroll", () => {
      const isBottom =
        window.innerHeight + window.scrollY >= document.body.offsetHeight - 50;

      if (isBottom) {
        popup.style.display = "flex";
      }
    });
  }

  // Close popup
  closePopup.addEventListener("click", () => {
    if (optout.checked) {
      localStorage.setItem("hideJoinPopup", "true");
    }
    popup.style.display = "none";
  });

 // Load More still triggers popup
  document.querySelector(".btn-load")?.addEventListener("click", () => {
    if (localStorage.getItem("hideJoinPopup") !== "true") {
      popup.style.display = "flex";
    }
  });


</script>



</body>
</html>

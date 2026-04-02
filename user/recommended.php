<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recommended For You | PakejKita</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="recommended.css" />
  <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />
</head>
<body>

    <div class="bg-wrapper">
        <img src="../pic/p1.png" alt="Background" class="bg-image">
    </div>

  <!-- ===== NAVBAR (Same as Dashboard) ===== -->
  <header class="navbar">
    <div class="logo">
      
      <h3>LokalKita</h3>
    </div>
    <div class="nav-right">
      <ul class="nav-links">
        <li><a href="main_page.php">Home</a></li>
        <li><a href="#">Browse</a></li>
        <li><a href="user_dashboard.php">Profile</a></li>
      </ul>
    </div>
  </header>

  <!-- ===== PAGE CONTENT ===== -->
  <main class="recommend-container">
    <!-- LEFT COLUMN -->
    <section class="left-col">
      <h3>Recommended For You</h3>

      <div class="recommend-card">
        <img src="../pic/loka.jpg" alt="Labuan Buntal Hunting" />
        <div class="recommend-info">
          <h4>Labuan Buntal Hunting</h4>
          <p>Buntal is a fish that blob</p>
          <span>Sea Adventure</span>
          <p class="duration">2 days 1 night</p>
        </div>
      </div>

      <div class="recommend-card">
        <img src="../pic/dash.jpg" alt="Johor Cuisine Hunting" />
        <div class="recommend-info">
          <h4>Johor Cuisine Hunting</h4>
          <p>Laksa Johor is so delicious</p>
          <span>Food Hunting</span>
          <p class="duration">2 days 1 night</p>
        </div>
      </div>
    </section>

    <!-- RIGHT COLUMN -->
    <aside class="right-col">
      <h3>Explore More</h3>

      <div class="explore-card">
        <img src="pic/heritage.jpg" alt="Heritage Village" />
        <div class="explore-bar"></div>
      </div>

      <div class="explore-card">
        <img src="pic/fruit.jpg" alt="Fruit Experience" />
        <div class="explore-bar"></div>
      </div>
    </aside>
  </main>

</body>
</html>

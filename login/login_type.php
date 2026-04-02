<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Choose Login Type | LokalKita</title>
  <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="logintype.css" />
</head>
<body>
  <!-- Background with overlay -->
  <div class="bg-image"></div>
  <div class="overlay"></div>

  <!-- Header -->
  <header>
    <h1>LokalKita</h1>
    <nav>
      <ul>
        <li><a href="../index.php">Home</a></li>
      </ul>
    </nav>
  </header>

  <!-- Content -->
  <div class="login-type-container">
    <h1 class="title">Choose Your Role</h1>
    <div class="role-cards">
      <a href="../user/user_login.html" class="role-card">
        <div class="icon">👤</div>
        <h2>User</h2>
        <p>Discover cultural experiences and save your favourites.</p>
      </a>
      <a href="../host/host_login.html" class="role-card">
        <div class="icon">🏡</div>
        <h2>Host</h2>
        <p>Share your local arts, crafts, and experiences with travellers.</p>
      </a>
      <a href="../admin/admin_login.html" class="role-card">
        <div class="icon">🛡️</div>
        <h2>Admin</h2>
        <p>Manage system integrity and approve community content.</p>
      </a>
    </div>
  </div>
</body>
</html>

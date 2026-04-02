<?php
session_start();
require 'script/db.php';

// Validate experience ID
$exp_id = $_GET['id'] ?? 0;
if ($exp_id == 0) {
    die("Invalid experience ID.");
}

// ---------------------------------------------------------------------
// 1. FETCH EXPERIENCE DATA
// ---------------------------------------------------------------------
$sql = "SELECT * FROM experience WHERE exp_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exp_id);
$stmt->execute();
$result = $stmt->get_result();
$exp = $result->fetch_assoc();

if (!$exp) {
    die("Experience not found.");
}

// ---------------------------------------------------------------------
// 2. FETCH ALL IMAGES (ORDER BY seq_no)
// ---------------------------------------------------------------------
$sql2 = "SELECT * FROM experience_images WHERE exp_id = ? ORDER BY seq_no ASC";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $exp_id);
$stmt2->execute();
$images = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Approval Details - LokalKita</title>
  <link rel="stylesheet" href="dashboard.css">
  <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="logo">LokalKita</div>
      <ul class="nav-links">
        <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
        <li><a href="approval.php"><i class="fas fa-box"></i> <span>Approval</span></a></li>
        <li><a href="user_list.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
        <li><a href="host_list.php"><i class="fas fa-user-tie"></i> <span>Hosts</span></a></li>
      </ul>

    <a href="admin_login.html" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </aside>

  <!-- Toggle Button -->
  <button id="toggle-btn" class="toggle-btn">☰</button>

  <main class="main">
    <header>
      <h2>Approval Details</h2>
    </header>

     <div class="approval-detail">

        <!-- IMAGES -->
        <div class="carousel-container">

          <!-- Main Image -->
          <div class="carousel-main">
              <button class="carousel-btn left" onclick="prevImage()">&#10094;</button>

              <img id="carouselMainImg" 
                  src="../host/uploads/experience/<?php echo $images[0]['img_path'] ?? ''; ?>">

              <button class="carousel-btn right" onclick="nextImage()">&#10095;</button>
          </div>

          <!-- Thumbnails -->
          <div class="carousel-thumbs">
              <?php foreach ($images as $index => $img): ?>
                  <img class="thumb <?php echo $index === 0 ? 'active' : ''; ?>"
                      src="../host/uploads/experience/<?php echo $img['img_path']; ?>"
                      onclick="showImage(<?php echo $index; ?>)">
              <?php endforeach; ?>
          </div>
      </div>


        <!-- DETAILS -->
        <h3><?php echo $exp['exp_title']; ?></h3>

        <p><?php echo $exp['exp_desc']; ?></p>

        <p><strong>Tags:</strong> <?php echo $exp['tags']; ?></p>
        <p><strong>Category:</strong> <?php echo $exp['category']; ?></p>
        <p><strong>State:</strong> <?php echo $exp['state']; ?></p>
        <p><strong>Location:</strong> <?php echo $exp['location']; ?></p>

        <p><strong>Price:</strong> RM <?php echo number_format($exp['min_price'], 2); ?> – RM <?php echo number_format($exp['max_price'], 2); ?></p>

        <p><strong>Operating Days:</strong> <?php echo $exp['open_day']; ?></p>
        <p><strong>Closing Days:</strong> <?php echo $exp['close_day']; ?></p>
        <p><strong>Hours:</strong> <?php echo $exp['operating_hours']; ?></p>

        <p><strong>Contact:</strong> <?php echo $exp['contact_info']; ?></p>
        <p><strong>Website Link:</strong>
            <?php if ($exp['web_link']): ?>
                <a href="<?php echo $exp['web_link']; ?>" target="_blank"><?php echo $exp['web_link']; ?></a>
            <?php else: ?>
                -
            <?php endif; ?>
        </p>


        <br>
        <!-- COMMENT BOX (Hidden by default) -->
          <div class="comment-box" id="commentBox" style="display:none;">
              <h3>Send Feedback to Host</h3>
              <p class="comment-subtitle">Tell the host what needs to be improved.</p>

              <textarea id="adminComment" class="comment-textarea" required></textarea>

              <button class="btn-send" onclick="rejectExperience(<?php echo $exp_id; ?>)">
                  Send Back to Host
              </button>
          </div>

          <?php if ($exp['status'] !== 'Approved'): ?>
                <div class="actions">
                    <button class="btn-approve" onclick="approveExperience(<?php echo $exp_id; ?>)">
                        ✔ Approve
                    </button>

                    <button class="btn-reject" onclick="showCommentBox()">
                        ✖ Reject
                    </button>
                </div>
            <?php endif; ?>



    </div>

    <!--back button-->
    <a href="approval.php" class="btn-back">Back to Approval List</a>

    <!-- Popup -->
     <div id="popup" class="popup">
      <div class="popup-content">
        <p id="popup-message"></p>
        <button onclick="closePopup()">OK</button>
      </div>
    </div>

  </main>

    <script>
      const toggleBtn = document.getElementById("toggle-btn");
      const sidebar = document.getElementById("sidebar");

      toggleBtn.addEventListener("click", () => {
          sidebar.classList.toggle("collapsed");
      });

      function showPopup(type) {
          const popup = document.getElementById("popup");
          const message = document.getElementById("popup-message");

          if (type === "success") {
              message.textContent = "Success";
              popup.style.display = "flex";
          } else {
              document.getElementById("commentBox").style.display = "block";
              window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
          }
      }

      function submitComment() {
          let comment = document.getElementById("adminComment").value.trim();
          const popup = document.getElementById("popup");
          const message = document.getElementById("popup-message");

          if (comment === "") {
              alert("Please write a comment before sending back.");
              return;
          }

          message.textContent = "Feedback sent back to host!";
          popup.style.display = "flex";

          document.getElementById("adminComment").value = "";
          document.getElementById("commentBox").style.display = "none";
      }

      // close popoup fx
      function closePopup() {
          document.getElementById("popup").style.display = "none";
      }



      //carousel functionality
     
      let currentIndex = 0;
      let images = [
          <?php foreach ($images as $img) {
              echo "'../host/uploads/experience/{$img['img_path']}',";
          } ?>
      ];

      function showImage(i) {
          currentIndex = i;
          document.getElementById("carouselMainImg").src = images[i];

          // highlight selected thumbnail
          document.querySelectorAll(".thumb").forEach((t, idx) => {
              t.classList.toggle("active", idx === i);
          });
      }

      function nextImage() {
          currentIndex = (currentIndex + 1) % images.length;
          showImage(currentIndex);
      }

      function prevImage() {
          currentIndex = (currentIndex - 1 + images.length) % images.length;
          showImage(currentIndex);
      }

    </script>

    <script>
        function showCommentBox() {
            document.getElementById('commentBox').style.display = 'block';
        }

        /* -----------------------------
          APPROVE EXPERIENCE (AJAX)
        ------------------------------ */
        function approveExperience(exp_id) {
            fetch("script/approve_experience.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "exp_id=" + exp_id
            })
            .then(res => res.text())
            .then(data => {
                Swal.fire({
                    icon: "success",
                    title: "Experience Approved!",
                    text: "The experience has been published.",
                    confirmButtonColor: "#28a745"
                }).then(() => {
                    window.location.href = "approval.php";
                });
            });
        }

        /* -----------------------------
          REJECT EXPERIENCE (AJAX)
        ------------------------------ */
        function rejectExperience(exp_id) {
            let comment = document.getElementById("adminComment").value;

            if (comment.trim() === "") {
                Swal.fire({
                    icon: "warning",
                    title: "Comment required",
                    text: "Please explain why you are rejecting this experience."
                });
                return;
            }

            fetch("script/reject_experience.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "exp_id=" + exp_id + "&admin_comment=" + encodeURIComponent(comment)
            })
            .then(res => res.text())
            .then(data => {
                Swal.fire({
                    icon: "error",
                    title: "Experience Rejected",
                    text: "Feedback sent to host.",
                    confirmButtonColor: "#d33"
                }).then(() => {
                    window.location.href = "approval.php";
                });
            });
        }
      </script>


</body>
</html>

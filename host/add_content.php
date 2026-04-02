<?php
session_start();
if (!isset($_SESSION['host_id'])) {
  header("Location: host_login.html");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Host Dashboard - LokalKita</title>
  <link rel="stylesheet" href="dashboard.css">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="../pic/logo2.png" sizes="32x16" rel="shortcut icon" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="logo">LokalKita</div>

    <ul class="nav-links">
      <li>
        <a href="host_dashboard.php">
          <i class="fas fa-home"></i> <span>Dashboard</span>
        </a>
      </li>

      <!-- PROFILE DROPDOWN -->
      <li class="has-submenu">
        <a href="#" class="submenu-toggle">
          <i class="fas fa-user"></i> 
          <span>Profile</span>
          <i class="fas fa-chevron-down arrow"></i>
        </a>
        <ul class="submenu">
          <li>
            <a href="myprofile.php">
              <i class="fas fa-id-card"></i> My Profile
            </a>
          </li>
          <li>
            <a href="profile.php">
              <i class="fas fa-edit"></i> Edit Profile
            </a>
          </li>
        </ul>
      </li>

      <li>
        <a href="list.php">
          <i class="fas fa-list"></i> <span>My Contents</span>
        </a>
      </li>

      <li>
        <a href="add_content.php" class="active">
          <i class="fas fa-plus-circle"></i> <span>New Content</span>
        </a>
      </li>
    </ul>

    <a href="host_login.html" class="logout">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </aside>

  <!-- Toggle Button -->
  <button id="toggle-btn" class="toggle-btn"><i class="fas fa-bars"></i></button>

  <!-- Main Content -->
  <main class="main">
    <section class="content-form">
      <h2>Add New Content</h2>
      <p>Fill in the details of your experience. You can upload between 1 to 5 images.</p>

      <div class="form-wizard">
        <!-- Steps Indicator -->
        <div class="steps">
          <div class="step active" onclick="goStep(1)" id="step1Btn">1. Basic Info</div>
          <div class="step" onclick="goStep(2)" id="step2Btn">2. Price & Schedule</div>
          <div class="step" onclick="goStep(3)" id="step3Btn">3. Images</div>
        </div>

        <!-- FORM -->
        <form action="script/save_content.php" method="POST" enctype="multipart/form-data" id="experienceForm">
          <!-- STEP 1: BASIC INFO -->
          <div class="step-panel active" id="step1">
            <div class="section-card">
              <h3 class="section-title">Basic Information</h3>
              <p class="section-subtitle">Tell travellers what your activity is about.</p>

              <div class="field-row">
                <div class="field">
                  <label>Title</label>
                  <input type="text" name="title" placeholder="e.g. Mengkuang Weaving Workshop" required>
                </div>
                <div class="field">
                  <label>Category</label>
                  <select name="category" required>
                    <option value="">Select Category</option>
                    <option value="Culture & Heritage">Culture & Heritage</option>
                    <option value="Festival & Performance">Festival & Performance</option>
                    <option value="Food & Culinary">Food & Culinary</option>
                    <option value="Arts & Crafts">Arts & Crafts</option>
                    <option value="Nature & Eco">Nature & Eco</option>
                    <option value="Homestay">Homestay</option>
                  </select>
                </div>
              </div>

              <div class="field">
                <label>Description</label>
                <textarea name="desc" rows="3" placeholder="Describe what guests will experience..." required></textarea>
              </div>

              <div class="field">
                <label>Tags</label>
                <input type="text" name="tags" placeholder="e.g. cultural, village, weaving">
                <small class="small-text">Use simple words separated by comma to help users find your content.</small>
              </div>

              <div class="field-row">
                <div class="field">
                  <label>State</label>
                  <select name="state" required>
                    <option value="">Select State</option>
                    <option value="Johor">Johor</option>
                    <option value="Kedah">Kedah</option>
                    <option value="Kelantan">Kelantan</option>
                    <option value="Melaka">Melaka</option>
                    <option value="Negeri Sembilan">Negeri Sembilan</option>
                    <option value="Pahang">Pahang</option>
                    <option value="Perak">Perak</option>
                    <option value="Perlis">Perlis</option>
                    <option value="Penang">Penang</option>
                    <option value="Sabah">Sabah</option>
                    <option value="Sarawak">Sarawak</option>
                    <option value="Selangor">Selangor</option>
                    <option value="Terengganu">Terengganu</option>
                    <option value="WP Kuala Lumpur">WP Kuala Lumpur</option>
                    <option value="WP Labuan">WP Labuan</option>
                    <option value="WP Putrajaya">WP Putrajaya</option>
                  </select>
                </div>
                <div class="field">
                  <label>Location</label>
                  <input type="text" name="location" placeholder="e.g. Kampung Hulu, Kuala Kangsar">
                </div>
              </div>

              <div class="wizard-buttons">
                <span></span>
                <button type="button" class="btn-next" onclick="goStep(2)">Next →</button>
              </div>
            </div>
          </div>

          <!-- STEP 2: PRICE & SCHEDULE -->
          <div class="step-panel" id="step2">
            <div class="section-card">
              <h3 class="section-title">Price & Schedule</h3>
              <p class="section-subtitle">Let guests know when and how much.</p>

              <div class="field-row">
                <div class="field">
                  <label>Minimum Price (RM)</label>
                  <input type="number" name="min_price" step="0.01" placeholder="e.g. 50" required>
                </div>
                <div class="field">
                  <label>Maximum Price (RM)</label>
                  <input type="number" name="max_price" step="0.01" placeholder="e.g. 120" required>
                </div>
              </div>

              <div class="field-row">
                <div class="field">
                  <label>Operating Days</label>
                  <input type="text" name="open_day" placeholder="e.g. Monday – Friday" required>
                </div>
                <div class="field">
                  <label>Closing Days</label>
                  <input type="text" name="close_day" placeholder="e.g. Saturday – Sunday" required>
                </div>
                <div class="field">
                  <label>Operating Hours</label>
                  <input type="text" name="operating_hours" placeholder="e.g. 9.00 a.m – 5.00 p.m" required>
                </div>
              </div>

              <div class="field-row">
                <div class="field">
                  <label>Contact Info</label>
                  <input type="text" name="contact_info" placeholder="Phone number / WhatsApp" required>
                </div>
                <div class="field">
                  <label>More Info (Website / Social Link)</label>
                  <input type="url" name="more_info" placeholder="e.g. https://facebook.com/yourpage">
                </div>
              </div>

              <div class="wizard-buttons">
                <button type="button" class="btn-back" onclick="goStep(1)">← Back</button>
                <button type="button" class="btn-next" onclick="goStep(3)">Next →</button>
              </div>
            </div>
          </div>

          <!-- STEP 3: IMAGES -->
          <div class="step-panel" id="step3">
            <div class="section-card">
              <h3 class="section-title">Images</h3>
              <p class="section-subtitle">Upload clear photos of your activity or place. At least 1, maximum 5.</p>

              <div class="field">
                <label>Upload Images (1–5)</label>
                <div class="image-upload">
                  <input type="file" name="images[]" accept="image/*" multiple required id="imageInput">
                  <small class="small-text">Tap here and choose photos from your phone or computer.</small>
                </div>
              </div>

              <div id="preview" class="preview"></div>

              <div class="wizard-buttons">
                <button type="button" class="btn-back" onclick="goStep(2)">← Back</button>
                <div class="wizard-right-buttons">
                  <button type="button" class="btn-draft" onclick="saveAsDraft()">Save as Draft</button>
                  <button type="submit" class="btn-submit">Submit</button>
                </div>
              </div>
            </div>
          </div>
          <input type="hidden" name="save_type" id="save_type" value="submit">

        </form>
      </div>

      <!-- DRAFT POPUP (UI only for now) -->
      <div id="draftPopup" class="popup">
        <div class="popup-content">
          <h3>Save as Draft?</h3>
          <p>Do you want to save this content as a draft?</p>
          <div class="popup-actions">
            <button class="btn-draft" onclick="saveDraft()">Yes, Save</button>
            <button class="btn-back" onclick="closeDraftPopup()">Cancel</button>
          </div>
        </div>
      </div>

    </section>
  </main>

<script>

  function saveAsDraft() {
    document.getElementById('save_type').value = "draft";
    document.getElementById('experienceForm').submit();
}


  // STEP / WIZARD NAVIGATION WITH REQUIRED HANDLING
  function goStep(step) {
  // Hide panels & disable required
  document.querySelectorAll(".step-panel").forEach(p => {
    p.classList.remove("active");
    p.querySelectorAll("[required]").forEach(f => f.required = false);
  });

  // Activate selected step & enable required
  const activePanel = document.getElementById("step" + step);
  activePanel.classList.add("active");
  activePanel.querySelectorAll("[required]").forEach(f => f.required = true);

  // Step header highlight
  document.querySelectorAll(".steps .step").forEach(s => s.classList.remove("active"));
  document.getElementById("step" + step + "Btn").classList.add("active");
}


  // Initialize step 1 required fields enabled
  document.addEventListener("DOMContentLoaded", () => {
    goStep(1);
  });

  // IMAGE PREVIEW (1–5 images)
  const imageInput = document.getElementById("imageInput");
  const preview    = document.getElementById("preview");

  if (imageInput) {
    imageInput.addEventListener("change", () => {
      preview.innerHTML = "";

      const files = Array.from(imageInput.files);
      if (files.length > 5) {
        alert("You can upload a maximum of 5 images.");
        imageInput.value = "";
        return;
      }

      files.forEach(file => {
        if (!file.type.startsWith("image/")) return;

        const reader = new FileReader();
        reader.onload = e => {
          const img = document.createElement("img");
          img.src   = e.target.result;
          preview.appendChild(img);
        };
        reader.readAsDataURL(file);
      });
    });
  }

  // DRAFT POPUP (placeholder behavior)
  function openDraftPopup() {
    document.getElementById("draftPopup").style.display = "flex";
  }

  function closeDraftPopup() {
    document.getElementById("draftPopup").style.display = "none";
  }

  

  // Sidebar submenu
  document.querySelectorAll(".submenu-toggle").forEach(btn => {
    btn.addEventListener("click", function(e) {
      e.preventDefault();
      const parent = this.parentElement;
      parent.classList.toggle("open");

      const submenu = parent.querySelector(".submenu");
      submenu.style.display = submenu.style.display === "block" ? "none" : "block";
    });
  });
</script>

<script src="dashboard.js"></script>
</body>
</html>

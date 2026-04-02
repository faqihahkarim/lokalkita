let selected = [];

document.querySelectorAll(".pref-card").forEach(card => {
    card.addEventListener("click", () => {
        card.classList.toggle("selected");

        let id = card.getAttribute("data-id");

        if (selected.includes(id)) {
            selected = selected.filter(v => v !== id);
        } else {
            selected.push(id);
        }
    });
});


document.getElementById("submitBtn").addEventListener("click", () => {

    fetch("script/save_preferences.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ preferences: selected })
    })
    .then(res => res.text())
    .then(data => {
        if (data === "OK") {
            alert("Preferences saved!");
            window.location = "main_page.php"; // go to dashboard/home
        }
    });
});

document.getElementById("submitBtn").addEventListener("click", () => {

    // Show loading
    document.getElementById("loadingOverlay").style.display = "flex";

    fetch("script/save_preferences.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ preferences: selected })
    })
    .then(res => res.text())
    .then(data => {

        // Hide loading
        document.getElementById("loadingOverlay").style.display = "none";

        if (data === "OK") {

            // Show popup
            let popup = document.getElementById("successPopup");
            popup.style.display = "block";

            // Fade out popup & redirect
            setTimeout(() => {
                popup.style.display = "none";
                window.location = "main_page.php";
            }, 1800);
        }
    });
});

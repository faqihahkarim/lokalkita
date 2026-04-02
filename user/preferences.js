// Toggle selections
document.querySelectorAll(".pref-card").forEach(card => {
    card.addEventListener("click", () => {
        card.classList.toggle("selected");
    });
});

// Submit selection
document.getElementById("submitBtn").addEventListener("click", () => {
    const selected = [...document.querySelectorAll(".pref-card.selected")]
        .map(c => c.dataset.value);

    // Send to PHP via POST
    fetch("save_preferences.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({preferences: selected})
    })
    .then(res => res.text())
    .then(data => {
        alert("Preferences saved!");
        window.location.href = "main_page.php";
    });
});

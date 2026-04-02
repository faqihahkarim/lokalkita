console.log("memory-lane.js loaded");

document.addEventListener("DOMContentLoaded", () => {

    /* ==========================
       CLICK IMAGE AREA → OPEN FILE
    ========================== */
    document.querySelectorAll(".image-area").forEach(area => {
        area.addEventListener("click", () => {
            const input = area.querySelector(".img-input");
            input.click();
        });
    });

    /* ==========================
       IMAGE PREVIEW
    ========================== */
    document.querySelectorAll(".img-input").forEach(input => {

        input.addEventListener("change", function () {

            const area = this.closest(".image-area");
            const img = area.querySelector(".preview-img");
            const placeholder = area.querySelector(".placeholder");

            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = e => {
                img.src = e.target.result;
                img.style.display = "block";
                placeholder.style.display = "none";
            };
            reader.readAsDataURL(file);
        });

    });

    /* ==========================
       CAPTION AUTOSAVE (PER IMAGE)
    ========================== */
    document.querySelectorAll(".caption-input").forEach((input, index) => {
        const key = "memoryCaption_" + index;

        const saved = localStorage.getItem(key);
        if (saved) input.value = saved;

        input.addEventListener("input", () => {
            localStorage.setItem(key, input.value);
        });
    });

    /* ==========================
       THOUGHT AUTOSAVE
    ========================== */
    const thoughtBox = document.getElementById("thoughtBox");
    if (thoughtBox) {
        const saved = localStorage.getItem("memoryThought");
        if (saved) thoughtBox.value = saved;

        thoughtBox.addEventListener("input", () => {
            localStorage.setItem("memoryThought", thoughtBox.value);
        });
    }

});

document.addEventListener("DOMContentLoaded", function () {
  // Like button
  const likeButtons = document.querySelectorAll(".like");

  likeButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const icon = this.querySelector("i");
      const countSpan = this.querySelector("span");

      if (!icon || !countSpan) return;

      const isLiked = icon.classList.contains("ri-heart-fill");

      icon.classList.toggle("ri-heart-line", isLiked);
      icon.classList.toggle("ri-heart-fill", !isLiked);
      icon.style.color = isLiked ? "" : "red";

      let count = parseInt(countSpan.textContent.replace(/[^\d]/g, ""));
      count = isLiked ? count - 1 : count + 1;
      countSpan.textContent = count >= 1000 ? (count / 1000).toFixed(1) + "K" : count.toString();

      // Trigger heart pulse animation
      icon.classList.remove("heart-pulse");
      void icon.offsetWidth; // force reflow
      icon.classList.add("heart-pulse");
    });
  });

  // Bookmark toggle
  const bookmarkIcons = document.querySelectorAll(".ri-bookmark-line, .ri-bookmark-fill");

  bookmarkIcons.forEach((icon) => {
    icon.parentElement?.addEventListener("click", function () {
      const isFilled = icon.classList.contains("ri-bookmark-fill");

      icon.classList.toggle("ri-bookmark-fill", !isFilled);
      icon.classList.toggle("ri-bookmark-line", isFilled);
      icon.style.color = isFilled ? "" : "gold";
    });
  });
});
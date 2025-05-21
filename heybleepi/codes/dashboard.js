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

  const homeLink = document.querySelector(".home-link");
  const feed = document.getElementById("mainFeed");

  if (homeLink && feed) {
    homeLink.addEventListener("click", function (e) {
      e.preventDefault(); // prevent link reload
      feed.scrollIntoView({ behavior: "smooth" });
    });
  }

  // Enable "Post" button only when there's a text
  const postTextarea = document.querySelector(".create-post-input");
  const postButton = document.querySelector(".create-post-actions .btn");

  if (postTextarea && postButton) {
    const togglePostButton = () => {
      postButton.disabled = postTextarea.value.trim() === "";
    };

    // Initial state
    togglePostButton();

    // Check on input
    postTextarea.addEventListener("input", togglePostButton);
  }

  // Suggestions "See More" button
  const seeMoreBtn = document.getElementById("seeMoreBtn");
  const suggestionList = document.getElementById("suggestionList");

  if (seeMoreBtn && suggestionList) {
    const allSuggestions = Array.from(suggestionList.querySelectorAll(".suggestion"));
    let visibleCount = 2; // Show only 2 at a time

    // Hide all except first N
    allSuggestions.forEach((item, index) => {
      if (index >= visibleCount) item.classList.add("hidden");
    });

    seeMoreBtn.addEventListener("click", function () {
      const nextSet = allSuggestions.slice(visibleCount, visibleCount + 2);
      nextSet.forEach((item) => item.classList.remove("hidden"));
      visibleCount += 2;

      if (visibleCount >= allSuggestions.length) {
        seeMoreBtn.style.display = "none";
      }
    });
  }

  // Toggle notification dropdown
  const notificationBtn = document.getElementById("notificationBtn");
  const notificationDropdown = document.getElementById("notification_dropdown");
  const markAllReadBtn = document.getElementById("markAllReadBtn");
  const notificationWrapper = document.getElementById("notification_wrapper");
  const badge = document.getElementById("notification_count");

  // Notification Badge
  if (badge && parseInt(badge.textContent) > 0) {
    notificationWrapper.classList.add("has-unread");
  }

  if (markAllReadBtn) {
    markAllReadBtn.addEventListener("click", () => {
      badge.style.display = "none";
      notificationWrapper.classList.remove("has-unread");
      notificationDropdown.classList.remove("visible");
    });
  }

  if (notificationBtn && notificationDropdown) {
    notificationBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      notificationDropdown.classList.toggle("visible");
    });

    document.addEventListener("click", function () {
      notificationDropdown.classList.remove("visible");
    });

    notificationDropdown.addEventListener("click", function (e) {
      e.stopPropagation(); // prevent dropdown from closing when clicked inside
    });

    // Mark as read
    if (markAllReadBtn) {
      markAllReadBtn.addEventListener("click", () => {
        const badge = document.getElementById("notification_count");
        if (badge) badge.style.display = "none";
        notificationDropdown.classList.remove("visible");
      });
    }
  }
});
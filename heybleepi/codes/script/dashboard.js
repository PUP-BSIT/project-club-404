document.addEventListener("DOMContentLoaded", function () {
  // Like Button
  attachDynamicListeners();

  // ENABLE "POST" BUTTON ONLY WHEN TEXTAREA HAS CONTENT
  const postTextarea = document.querySelector(".create-post-input");
  const postButton = document.querySelector("form button.btn--primary");

  if (postTextarea && postButton) {
    const togglePostButton = () => {
      postButton.disabled = postTextarea.value.trim() === "";
    };
    togglePostButton(); // Initial check
    postTextarea.addEventListener("input", togglePostButton);
  }

  // Home
  const homeLink = document.querySelector(".home-link");
  const feed = document.getElementById("mainFeed");

  if (homeLink && feed) {
    homeLink.addEventListener("click", function (e) {
      e.preventDefault();
      feed.scrollIntoView({ behavior: "smooth" });
    });
  }

  // Suggestions "See more"
  const seeMoreBtn = document.getElementById("seeMoreBtn");
  const suggestionList = document.getElementById("suggestion_list");

  if (seeMoreBtn && suggestionList) {
    const allSuggestions = Array.from(suggestionList.querySelectorAll(".suggestion"));
    let visibleCount = 2;

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

  // Notification Dropdown
  const notificationBtn = document.getElementById("notificationBtn");
  const notificationDropdown = document.getElementById("notification_dropdown");
  const markAllReadBtn = document.getElementById("markAllReadBtn");
  const notificationWrapper = document.getElementById("notification_wrapper");
  const badge = document.getElementById("notification_count");

  if (badge && parseInt(badge.textContent) > 0) {
    notificationWrapper.classList.add("has-unread");
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
      e.stopPropagation();
    });

    if (markAllReadBtn) {
      markAllReadBtn.addEventListener("click", () => {
        badge.style.display = "none";
        notificationWrapper.classList.remove("has-unread");
        notificationDropdown.classList.remove("visible");
      });
    }
  }
});

// Dynamic Post Creation
function createPost() {
  const input = document.getElementById("postInput");
  const text = input.value.trim();

  if (!text) return;

  const newPost = `
    <article class="glass post">
      <header class="post-header">
        <img class="avatar avatar--sm" src="${CURRENT_USER_AVATAR}" alt="">
        <div>
          <h4>${CURRENT_USER_NAME}</h4>
          <time>Just now</time>
        </div>
        <button class="icon-btn"><i class="ri-more-fill"></i></button>
      </header>

      <p>${text}</p>

      <footer class="post-footer">
        <div class="post-actions">
          <button class="icon-btn like">
            <i class="ri-heart-line"></i>
            <span>0</span>
          </button>
          <button class="icon-btn"><i class="ri-chat-1-line"></i><span>0</span></button>
          <button class="icon-btn"><i class="ri-share-forward-line"></i><span>0</span></button>
        </div>
        <button class="icon-btn"><i class="ri-bookmark-line"></i></button>
      </footer>
    </article>
  `;

  const container = document.getElementById("postsContainer");
  container.insertAdjacentHTML("afterbegin", newPost);
  input.value = "";

  attachDynamicListeners(); // attach to new post
}

// Attach dynamic event listeners for like, bookmark, and comment actions
function attachDynamicListeners() {
  // Like buttons
  document.querySelectorAll(".like").forEach((button) => {
    button.onclick = function () {
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
    };
  });

  // Bookmark buttons
  document.querySelectorAll(".ri-bookmark-line, .ri-bookmark-fill").forEach((icon) => {
    icon.parentElement?.addEventListener("click", function () {
      const isFilled = icon.classList.contains("ri-bookmark-fill");
      icon.classList.toggle("ri-bookmark-fill", !isFilled);
      icon.classList.toggle("ri-bookmark-line", isFilled);
      icon.style.color = isFilled ? "" : "gold";
    });
  });
}

document.addEventListener("DOMContentLoaded", () => {
  // Delete
  document.querySelectorAll(".btn-delete-comment").forEach(btn => {
    btn.addEventListener("click", () => {
      const commentId = btn.dataset.id;
      fetch("comment_actions.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `comment_id=${commentId}&action=delete`
      })
        .then(res => res.text())
        .then(resp => {
          if (resp === "deleted") {
            btn.closest(".comment").remove();
          }
        });
    });
  });

  // Edit
  document.querySelectorAll(".btn-edit-comment").forEach(btn => {
    btn.addEventListener("click", () => {
      const commentDiv = btn.closest(".comment");
      const commentId = btn.dataset.id;
      const textEl = commentDiv.querySelector(".comment-text");

      const currentText = textEl.textContent;
      const input = document.createElement("input");
      input.value = currentText;
      input.style.width = "100%";

      const saveBtn = document.createElement("button");
      saveBtn.textContent = "Save";
      saveBtn.className = "btn--sm";

      textEl.replaceWith(input);
      btn.replaceWith(saveBtn);

      saveBtn.addEventListener("click", () => {
        fetch("comment_actions.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `comment_id=${commentId}&action=edit&new_text=${encodeURIComponent(input.value)}`
        })
          .then(res => res.text())
          .then(resp => {
            const span = document.createElement("span");
            span.className = "comment-text";
            span.textContent = resp;
            input.replaceWith(span);
            saveBtn.replaceWith(btn); // restore Edit button
          });
      });
    });
  });
});

// Like
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".like-button").forEach(button => {
    button.addEventListener("click", function () {
      const postId = this.getAttribute("data-post-id");
      const icon = this.querySelector("i");
      const countSpan = this.querySelector("span");

      fetch("like_toggle.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `post_id=${postId}`
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            icon.className = data.liked ? "ri-heart-fill" : "ri-heart-line";
            this.classList.toggle("liked", data.liked);
            countSpan.textContent = data.total;
          }
        });
    });
  });
});
document.addEventListener("DOMContentLoaded", function () {
  // Like Button
  attachDynamicListeners();

  // ENABLE "POST" BUTTON ONLY WHEN TEXTAREA HAS CONTENT
  const postTextarea = document.querySelector("form[action='profile.php'] .create-post-input");
  const postButton = document.querySelector("form[action='profile.php'] .btn--primary");
  const imageInput = document.getElementById("postImageInput");
  const videoInput = document.getElementById("postVideoInput");

  if (postTextarea && postButton) {
    const togglePostButton = () => {
      const hasText = postTextarea.value.trim() !== "";
      const hasImages = imageInput?.files?.length > 0;
      const hasVideos = videoInput?.files?.length > 0;
      postButton.disabled = !(hasText || hasImages || hasVideos);
    };

    togglePostButton(); // initial

    postTextarea.addEventListener("input", togglePostButton);
    imageInput?.addEventListener("change", togglePostButton);
    videoInput?.addEventListener("change", togglePostButton);
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
});

// Edit comment for profile
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.btn-edit-comment').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const commentDiv = btn.closest('.comment');
      const commentId = btn.getAttribute('data-id');
      const commentTextSpan = commentDiv.querySelector('span');
      const oldText = commentTextSpan.textContent;

      // Prevent multiple edit forms
      if (commentDiv.querySelector('form')) return;

      // Create edit form
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'edit_comment_profile.php';
      form.style.display = 'inline';
      form.innerHTML = `
        <input type="hidden" name="comment_id" value="${commentId}">
        <input type="text" name="comment_text" value="${oldText}" required style="width:60%;">
        <button type="submit" class="btn btn--primary btn--sm">Save</button>
        <button type="button" class="btn btn--secondary btn--sm btn-cancel-edit">Cancel</button>
      `;

      // Hide old text and buttons
      commentTextSpan.style.display = 'none';
      btn.style.display = 'none';
      const deleteBtn = commentDiv.querySelector('.btn-delete-comment');
      if (deleteBtn) deleteBtn.style.display = 'none';

      commentDiv.appendChild(form);

      // Cancel button logic
      form.querySelector('.btn-cancel-edit').onclick = function () {
        form.remove();
        commentTextSpan.style.display = '';
        btn.style.display = '';
        if (deleteBtn) deleteBtn.style.display = '';
      };
    });
  });
});

// Delete comment for profile
document.querySelectorAll('.btn-delete-comment').forEach(function (btn) {
  btn.addEventListener('click', function () {
    if (!confirm('Are you sure you want to delete this comment?')) return;
    const commentId = btn.getAttribute('data-id');
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'delete_comment_profile.php';
    form.innerHTML = `<input type="hidden" name="comment_id" value="${commentId}">`;
    document.body.appendChild(form);
    form.submit();
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

// Share
document.querySelectorAll(".share-button").forEach(button => {
  button.addEventListener("click", function () {
    const postId = this.getAttribute("data-post-id");
    const icon = this.querySelector("i");
    const countSpan = this.querySelector("span");

    fetch("share_toggle.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `post_id=${postId}`
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          icon.className = data.shared ? "ri-share-forward-fill" : "ri-share-forward-line";
          icon.style.color = data.shared ? "gold" : "";
          this.classList.toggle("shared", data.shared);
          countSpan.textContent = data.total;
        }
      });
  });
});

document.querySelectorAll('.share-button').forEach(button => {
  button.addEventListener('click', () => {
    button.innerHTML = '<i class="ri-share-forward-fill" style="color: #ff6bc4;"></i> Shared';
  });
});

// Edit comment for dashboard
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.btn-edit-comment-dashboard').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const commentDiv = btn.closest('.comment');
      const commentId = btn.getAttribute('data-id');
      const commentTextSpan = commentDiv.querySelector('.comment-text');
      const oldText = commentTextSpan.textContent;

      // Prevent multiple edit forms
      if (commentDiv.querySelector('form')) return;

      // Create edit form
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'edit_comment_dashboard.php';
      form.style.display = 'inline';
      form.innerHTML = `
        <input type="hidden" name="comment_id" value="${commentId}">
        <input type="text" name="comment_text" value="${oldText}" required style="width:60%;">
        <button type="submit" class="btn btn--primary btn--sm">Save</button>
        <button type="button" class="btn btn--secondary btn--sm btn-cancel-edit">Cancel</button>
      `;

      // Hide old text and buttons
      commentTextSpan.style.display = 'none';
      btn.style.display = 'none';
      const deleteBtn = commentDiv.querySelector('.btn-delete-comment-dashboard');
      if (deleteBtn) deleteBtn.style.display = 'none';

      commentDiv.appendChild(form);

      // Cancel button logic
      form.querySelector('.btn-cancel-edit').onclick = function () {
        form.remove();
        commentTextSpan.style.display = '';
        btn.style.display = '';
        if (deleteBtn) deleteBtn.style.display = '';
      };
    });
  });
});

// Delete comment for dashboard
document.querySelectorAll('.btn-delete-comment-dashboard').forEach(function (btn) {
  btn.addEventListener('click', function () {
    if (!confirm('Are you sure you want to delete this comment?')) return;
    const commentId = btn.getAttribute('data-id');
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'delete_comment_dashboard.php';
    form.innerHTML = `<input type="hidden" name="comment_id" value="${commentId}">`;
    document.body.appendChild(form);
    form.submit();
  });
});

// EDIT AND DELETE POST (DASHBOARD & PROFILE)
document.addEventListener('DOMContentLoaded', () => {
  // Toggle 3-dot menu
  document.querySelectorAll('.toggle-options').forEach(button => {
    button.addEventListener('click', (e) => {
      e.stopPropagation();
      const dropdown = button.nextElementSibling;
      dropdown.classList.toggle('hidden');
    });
  });

  // Close dropdowns on outside click
  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown').forEach(dropdown => dropdown.classList.add('hidden'));
  });

  // Inline Edit for Posts
  document.querySelectorAll('.btn-edit-post').forEach(btn => {
    btn.addEventListener('click', () => {
      const postId = btn.dataset.id;
      const postContentDiv = document.querySelector(`.post-content[data-post-id="${postId}"]`);
      const postTextP = postContentDiv.querySelector('.post-text');
      const originalText = postTextP.textContent;

      // Detect current page (dashboard or profile)
      const isDashboard = window.location.pathname.includes("dashboard.php");
      const actionURL = isDashboard ? "edit_post_dashboard.php" : "edit_post_profile.php";


      // Prevent duplicate form
      if (postContentDiv.querySelector('form')) return;

      // Create form
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = actionURL;
      form.innerHTML = `
        <input type="hidden" name="post_id" value="${postId}">
        <input type="text" name="new_content" value="${originalText}" required style="width:80%;">
        <button type="submit" class="btn--sm btn--primary">Save</button>
        <button type="button" class="btn--sm btn--secondary cancel-edit">Cancel</button>
      `;

      postTextP.replaceWith(form);

      // Cancel logic
      form.querySelector('.cancel-edit').addEventListener('click', () => {
        form.replaceWith(postTextP);
      });
    });
  });
});

document.getElementById("removeImageBtn")?.addEventListener("click", function () {
  const input = document.getElementById("postImageInput");
  const preview = document.getElementById("imagePreview");
  const container = document.getElementById("imagePreviewContainer");

  preview.src = "";
  container.style.display = "none";
  input.value = "";
});

document.getElementById("removeVideoBtn")?.addEventListener("click", function () {
  const input = document.getElementById("postVideoInput");
  const preview = document.getElementById("videoPreview");
  const container = document.getElementById("videoPreviewContainer");

  preview.src = "";
  container.style.display = "none";
  input.value = "";
});

document.querySelectorAll('.photo-grid img, .photo-grid video').forEach(item => {
  item.addEventListener('click', function () {
    const type = this.getAttribute('data-type');
    const src = this.getAttribute('data-src');
    const lightboxContent = document.getElementById('lightboxContent');

    if (type === 'image') {
      lightboxContent.innerHTML = `<img src="${src}" alt="Preview" />`;
    } else if (type === 'video') {
      lightboxContent.innerHTML = `<video src="${src}" controls autoplay></video>`;
    }

    document.getElementById('lightbox').style.display = 'flex';
  });
});

// Lightbox for post media
function openLightbox(mediaHtml) {
  const lightbox = document.getElementById('lightbox');
  const content = document.getElementById('lightboxContent');
  content.innerHTML = mediaHtml;
  lightbox.style.display = 'flex';
}

function closeLightbox() {
  document.getElementById('lightbox').style.display = 'none';
  document.getElementById('lightboxContent').innerHTML = '';
}

// Attach click listeners to post images and videos
document.addEventListener('DOMContentLoaded', function () {
  function attachMediaListeners() {
    document.querySelectorAll('.post-media-grid img').forEach(img => {
      img.style.cursor = 'pointer';
      img.onclick = function () {
        openLightbox(`<img src='${img.src}' style='max-width:90vw;max-height:80vh;border-radius:16px;'>`);
      };
    });
    document.querySelectorAll('.post-media-grid video').forEach(video => {
      video.style.cursor = 'pointer';
      video.onclick = function () {
        openLightbox(`<video src='${video.querySelector('source').src}' controls autoplay style='max-width:90vw;max-height:80vh;border-radius:16px;'></video>`);
      };
    });
  }
  attachMediaListeners();
});

// Media Preview Handlers
function setupMediaPreviewHandlers() {
  const imageInput = document.getElementById('postImageInput');
  const videoInput = document.getElementById('postVideoInput');
  const grid = document.getElementById('mediaPreviewGrid');

  if (imageInput && grid) {
    imageInput.addEventListener('change', function (e) {
      for (let file of this.files) {
        // Prevent duplicate previews for the same file
        if ([...grid.querySelectorAll('img')].some(img => img.src === URL.createObjectURL(file))) continue;
        const reader = new FileReader();
        reader.onload = function (e) {
          const preview = document.createElement('div');
          preview.className = 'media-preview';
          preview.innerHTML = `
            <img src="${e.target.result}" alt="Preview">
            <button type="button" class="remove-media" onclick="this.parentElement.remove();">×</button>
          `;
          grid.appendChild(preview);
        }
        reader.readAsDataURL(file);
      }
    });
  }
  if (videoInput && grid) {
    videoInput.addEventListener('change', function (e) {
      for (let file of this.files) {
        if ([...grid.querySelectorAll('video source')].some(source => source.src === URL.createObjectURL(file))) continue;
        const preview = document.createElement('div');
        preview.className = 'media-preview';
        preview.innerHTML = `
          <video controls>
            <source src="${URL.createObjectURL(file)}" type="video/mp4">
          </video>
          <button type="button" class="remove-media" onclick="this.parentElement.remove();">×</button>
        `;
        grid.appendChild(preview);
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', setupMediaPreviewHandlers);

// Allow closing lightbox by clicking outside content or pressing ESC
document.getElementById('lightbox').addEventListener('click', function (e) {
  if (e.target === this) closeLightbox();
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeLightbox();
});
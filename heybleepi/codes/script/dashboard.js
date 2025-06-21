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

// Search Users
document.addEventListener("DOMContentLoaded", function () {
  const input = document.getElementById("searchInput");
  const resultsContainer = document.getElementById("searchResults");

  if (!input || !resultsContainer) return;

  input.addEventListener("input", function () {
    const query = input.value.trim();

    if (query.length < 1) {
      resultsContainer.innerHTML = "";
      resultsContainer.classList.remove("visible");
      return;
    }

    fetch(`search_users.php?q=${encodeURIComponent(query)}`)
      .then(res => res.text())
      .then(html => {
        resultsContainer.innerHTML = html;
        resultsContainer.classList.toggle("visible", html.trim().length > 0);
      });
  });

  document.addEventListener("click", function (e) {
    if (!resultsContainer.contains(e.target) && e.target !== input) {
      resultsContainer.innerHTML = "";
      resultsContainer.classList.remove("visible");
    }
  });
});

// Dropdown toggle option for comment
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.toggle-comment-options').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const dropdown = btn.closest('.comment-options').querySelector('.comment-dropdown');
      document.querySelectorAll('.comment-dropdown').forEach(d => {
        if (d !== dropdown) d.classList.add('hidden');
      });
      dropdown.classList.toggle('hidden');
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.comment-dropdown').forEach(d => d.classList.add('hidden'));
  });
});

// Get user's location and store it in the hidden input
const openCageApiKey = "8653b83ddf764a60b2fd8df561100fdd"; // Get this from opencagedata.com

let map;
let mapInitialized = false;
let selectedPlaceName = null;
let currentMarker = null;

// DOM elements
const openMapBtn = document.getElementById("openMapModal");
const modal = document.getElementById("mapModal");
const cancelMapBtn = document.getElementById("cancelMapModal");
const confirmBtn = document.getElementById("confirmLocationBtn");
const locationInput = document.getElementById("postLocation");

// Modal logic
openMapBtn.addEventListener("click", function (e) {
  e.preventDefault();
  e.stopPropagation();

  console.log("Opening map modal...");
  modal.style.display = "block";

  setTimeout(() => {
    if (!mapInitialized) {
      initMap();
    } else {
      map.invalidateSize(); // Leaflet equivalent of resize
    }
  }, 300);
});

document.querySelector("form").addEventListener("submit", function (e) {
  // Prevent accidental submission if modal is open
  const modal = document.getElementById("mapModal");
  if (modal.style.display === "block") {
    console.log("Blocking form submit while map modal is open");
    e.preventDefault();
  }
});

// OpenCage geocoding functions
async function geocodeAddress(address) {
  try {
    const response = await fetch(
      `https://api.opencagedata.com/geocode/v1/json?q=${encodeURIComponent(address)}&key=${openCageApiKey}&limit=5`
    );
    const data = await response.json();

    if (data.results && data.results.length > 0) {
      return data.results;
    }
    return [];
  } catch (error) {
    console.error("Geocoding error:", error);
    return [];
  }
}

async function reverseGeocode(lat, lng) {
  try {
    const response = await fetch(
      `https://api.opencagedata.com/geocode/v1/json?q=${lat}+${lng}&key=${openCageApiKey}`
    );
    const data = await response.json();

    if (data.results && data.results.length > 0) {
      return data.results[0];
    }
    return null;
  } catch (error) {
    console.error("Reverse geocoding error:", error);
    return null;
  }
}

// Create custom search control that uses existing geocoder div
function createSearchControl() {
  const geocoderDiv = document.getElementById('geocoder');

  const searchInput = document.createElement('input');
  searchInput.type = 'text';
  searchInput.placeholder = 'Search for a place...';
  searchInput.style.cssText = `
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    box-sizing: border-box;
    outline: none;
    font-family: inherit;
  `;

  const searchResults = document.createElement('div');
  searchResults.className = 'search-results';
  searchResults.style.cssText = `
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  `;

  // Make geocoder div relative for positioning
  geocoderDiv.style.position = 'relative';
  geocoderDiv.innerHTML = '';
  geocoderDiv.appendChild(searchInput);
  geocoderDiv.appendChild(searchResults);

  // Search functionality with debouncing
  let searchTimeout;
  searchInput.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    if (query.length < 3) {
      searchResults.style.display = 'none';
      return;
    }

    searchTimeout = setTimeout(async () => {
      const results = await geocodeAddress(query);
      displaySearchResults(results, searchResults, searchInput);
    }, 300);
  });

  // Hide results when clicking outside
  document.addEventListener('click', (e) => {
    if (!geocoderDiv.contains(e.target)) {
      searchResults.style.display = 'none';
    }
  });

  return geocoderDiv;
}

function displaySearchResults(results, container, searchInput) {
  container.innerHTML = '';

  if (results.length === 0) {
    const noResults = document.createElement('div');
    noResults.style.cssText = `
      padding: 12px;
      color: #666;
      font-style: italic;
      font-size: 14px;
    `;
    noResults.textContent = 'No locations found';
    container.appendChild(noResults);
    container.style.display = 'block';
    return;
  }

  results.forEach(result => {
    const resultItem = document.createElement('div');
    resultItem.style.cssText = `
      padding: 12px;
      cursor: pointer;
      border-bottom: 1px solid #eee;
      font-size: 14px;
      transition: background-color 0.2s ease;
    `;
    resultItem.textContent = result.formatted;

    resultItem.addEventListener('mouseenter', () => {
      resultItem.style.backgroundColor = '#f8f9fa';
    });

    resultItem.addEventListener('mouseleave', () => {
      resultItem.style.backgroundColor = 'white';
    });

    resultItem.addEventListener('click', () => {
      selectLocation(result);
      container.style.display = 'none';
      searchInput.value = result.formatted;
    });

    container.appendChild(resultItem);
  });

  container.style.display = 'block';
}

function selectLocation(location) {
  const { lat, lng } = location.geometry;
  selectedPlaceName = location.formatted;

  // Update map view
  map.setView([lat, lng], 15);

  // Remove existing marker
  if (currentMarker) {
    map.removeLayer(currentMarker);
  }

  // Add new marker
  currentMarker = L.marker([lat, lng]).addTo(map);

  // Update confirm button state
  if (confirmBtn) {
    confirmBtn.disabled = false;
    confirmBtn.textContent = `Use This Location`;
    confirmBtn.style.opacity = '1';
  }
}

function initMap() {
  if (mapInitialized) return;
  mapInitialized = true;

  // Initialize Leaflet map
  map = L.map('map').setView([14.5995, 120.9842], 10); // Philippines center

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  // Initialize the search control in your existing geocoder div
  createSearchControl();

  // Add click handler for map
  map.on('click', async (e) => {
    const { lat, lng } = e.latlng;

    // Show loading state
    if (confirmBtn) {
      confirmBtn.textContent = 'Loading location...';
      confirmBtn.disabled = true;
      confirmBtn.style.opacity = '0.6';
    }

    // Reverse geocode the clicked location
    const result = await reverseGeocode(lat, lng);

    if (result) {
      selectLocation(result);
      // Update search input with selected location
      const searchInput = document.querySelector('#geocoder input');
      if (searchInput) {
        searchInput.value = result.formatted;
      }
    } else {
      // Even if reverse geocoding fails, allow user to use the coordinates
      selectedPlaceName = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

      // Remove existing marker
      if (currentMarker) {
        map.removeLayer(currentMarker);
      }

      // Add new marker
      currentMarker = L.marker([lat, lng]).addTo(map);

      if (confirmBtn) {
        confirmBtn.textContent = 'Use This Location';
        confirmBtn.disabled = false;
        confirmBtn.style.opacity = '1';
      }
    }
  });

  setTimeout(() => map.invalidateSize(), 200);
}

// Confirm location selection
if (confirmBtn) {
  confirmBtn.addEventListener('click', () => {
    if (selectedPlaceName && locationInput) {
      locationInput.value = selectedPlaceName;
      modal.style.display = 'none';

      // Trigger change event for other listeners
      locationInput.dispatchEvent(new Event('change'));

      console.log('Location selected:', selectedPlaceName);
    }
  });
}

// Cancel modal for exiting without selection
if (cancelMapBtn) {
  cancelMapBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    selectedPlaceName = null;

    // Clear search input
    const searchInput = document.querySelector('#geocoder input');
    if (searchInput) {
      searchInput.value = '';
    }

    // Reset results
    const searchResults = document.querySelector('#geocoder .search-results');
    if (searchResults) {
      searchResults.style.display = 'none';
    }

    if (currentMarker) {
      map.removeLayer(currentMarker);
      currentMarker = null;
    }

    // Reset confirm button
    if (confirmBtn) {
      confirmBtn.textContent = 'Use This Location';
      confirmBtn.disabled = true;
      confirmBtn.style.opacity = '0.6';
    }
  });
}

// Close modal when clicking outside
modal?.addEventListener('click', (e) => {
  if (e.target === modal) {
    modal.style.display = 'none';
    selectedPlaceName = null;

    // Clear search input
    const searchInput = document.querySelector('#geocoder input');
    if (searchInput) {
      searchInput.value = '';
    }

    if (currentMarker) {
      map.removeLayer(currentMarker);
      currentMarker = null;
    }

    // Reset confirm button
    if (confirmBtn) {
      confirmBtn.textContent = 'Use This Location';
      confirmBtn.disabled = true;
      confirmBtn.style.opacity = '0.6';
    }
  }
});

// Get user's current location
function getCurrentLocation() {
  if (navigator.geolocation) {
    // Show loading state
    if (confirmBtn) {
      confirmBtn.textContent = 'Getting your location...';
      confirmBtn.disabled = true;
      confirmBtn.style.opacity = '0.6';
    }

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const { latitude, longitude } = position.coords;

        // Reverse geocode current location
        const result = await reverseGeocode(latitude, longitude);

        if (result && map) {
          map.setView([latitude, longitude], 15);

          // Add marker for current location
          if (currentMarker) {
            map.removeLayer(currentMarker);
          }

          currentMarker = L.marker([latitude, longitude])
            .addTo(map)
            .bindPopup('Your current location')
            .openPopup();

          selectedPlaceName = result.formatted;

          // Update search input
          const searchInput = document.querySelector('#geocoder input');
          if (searchInput) {
            searchInput.value = result.formatted;
          }

          // Enable confirm button
          if (confirmBtn) {
            confirmBtn.textContent = 'Use This Location';
            confirmBtn.disabled = false;
            confirmBtn.style.opacity = '1';
          }
        }
      },
      (error) => {
        console.error("Geolocation error:", error);
        alert("Unable to get your location. Please search for a place or click on the map.");

        // Reset confirm button
        if (confirmBtn) {
          confirmBtn.textContent = 'Use This Location';
          confirmBtn.disabled = true;
          confirmBtn.style.opacity = '0.6';
        }
      }
    );
  } else {
    alert("Geolocation is not supported by this browser.");
  }
}

// Export functions for external use if needed
window.mapFunctions = {
  getCurrentLocation,
  geocodeAddress,
  reverseGeocode
};
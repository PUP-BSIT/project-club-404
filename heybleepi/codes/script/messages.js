// --- Emoji Picker, Edit, Update, Cancel, 3-dots, and Delete Modal Logic ---
document.addEventListener('DOMContentLoaded', function () {
  // --- Emoji Picker ---
  const emojiBtn = document.getElementById("emojiBtn");
  const commentTextarea = document.getElementById("comment");

  const emojiPicker = document.createElement("div");
  emojiPicker.id = "emojiPicker";
  emojiPicker.className = "emoji-picker-popup";
  emojiPicker.innerHTML = `
    <div class="picker-controls">
      <label class="stay-open-toggle">
        <input type="checkbox" id="stayOpenCheckbox"> Keep open
      </label>
      <button class="close-picker-btn" id="closePickerBtn">
        <i class="ri-close-line"></i>
      </button>
    </div>
    <div class="emoji-container"></div>
  `;
  document.body.appendChild(emojiPicker);

  const emojiContainer = emojiPicker.querySelector(".emoji-container");
  const stayOpenCheckbox = emojiPicker.querySelector("#stayOpenCheckbox");
  const closePickerBtn = emojiPicker.querySelector("#closePickerBtn");

  const emojiCategories = {
    Smileys_Emoticons: [
      "😀", "😃", "😄", "😁", "😆", "😅", "😂", "🤣", "😊", "😇", "🙂",
      "🙃", "😉", "😌", "😍", "🥰", "😘", "😗", "😙", "😚", "😋", "😛",
      "😝", "😜", "🤪", "🤨", "🧐", "🤓", "😎", "🤩", "🥳", "😏", "😒",
      "😞", "😔", "😟", "😕", "🙁", "☹️", "😣", "😖", "😫", "😩", "🥺",
      "😢", "😭", "😤", "😠", "😡", "🤬", "🤯", "😳", "🥵", "🥶", "😱",
      "😨", "😰", "😥", "😓", "🤗", "🤔", "🤭"],
    Hearts: [
      "❤️", "🧡", "💛", "💚", "💙", "💜", "🖤", "🤍", "🤎", "💔", "❣️",
      "💕", "💞", "💓", "💗", "💖", "💘", "💝", "💟"],
    Hands: [
      "👍", "👎", "👌", "🤌", "🤏", "✌️", "🤞", "🤟", "🤘", "🤙", "👈",
      "👉", "👆", "🖕", "👇", "☝️", "👏", "🙌", "👐", "🤲", "🤝", "🙏"],
    Objects: [
      "🔥", "⭐", "✨", "💫", "⚡", "💥", "💢", "💨", "💦", "💧", "🌟",
      "⚽", "🏀", "🏈", "⚾", "🥎", "🎾", "🏐", "🏉", "🥏", "🎱", "🪀",
      "🏓", "🏸", "🏒", "🏑", "🥍", "🏏", "🪃", "🥅"],
    Food: [
      "🍎", "🍊", "🍋", "🍌", "🍉", "🍇", "🍓", "🫐", "🍈", "🍒", "🍑",
      "🥭", "🍍", "🥥", "🥝", "🍅", "🍆", "🥑", "🥦", "🥬", "🥒", "🌶️",
      "🫑", "🌽", "🥕", "🫒", "🧄", "🧅", "🥔", "🍠", "🥐"]
  };

  for (const [category, emojis] of Object.entries(emojiCategories)) {
    const categoryHTML = `
      <div class="emoji-category">
        <div class="emoji-category-title">${category.replace('_', ' ')}</div>
        <div class="emoji-grid">
          ${emojis.map(emoji => `<span class="emoji-item" 
            data-emoji="${emoji}">${emoji}</span>`).join("")}
        </div>
      </div>
    `;
    emojiContainer.innerHTML += categoryHTML;
  }

  emojiBtn.addEventListener("click", function (e) {
    e.preventDefault();
    e.stopPropagation();
    commentTextarea.focus();
    toggleEmojiPicker();
  });

  closePickerBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    hideEmojiPicker();
  });

  emojiPicker.addEventListener("click", function (e) {
    if (e.target.classList.contains("emoji-item")) {
      const emoji = e.target.getAttribute("data-emoji");
      insertEmojiAtCursor(emoji);

      if (!stayOpenCheckbox.checked) {
        hideEmojiPicker();
      }
    }
  });

  document.addEventListener("click", function (e) {
    if (!emojiPicker.contains(e.target) && e.target !== emojiBtn) {
      hideEmojiPicker();
    }
  });

  function toggleEmojiPicker() {
    if (emojiPicker.classList.contains('visible')) {
      hideEmojiPicker();
    } else {
      emojiPicker.classList.add('visible');
      emojiBtn.innerHTML = '<i class="ri-emotion-fill"></i>';
      positionEmojiPicker();
    }
  }

  function positionEmojiPicker() {
    const btnRect = emojiBtn.getBoundingClientRect();
    const pickerWidth = 320;
    const pickerHeight = 400;

    let left = btnRect.right - pickerWidth;
    let top = btnRect.bottom + 10;

    if (left < 10) left = 10;
    if (top + pickerHeight > window.innerHeight) {
      top = btnRect.top - pickerHeight - 10;
    }

    emojiPicker.style.left = `${left}px`;
    emojiPicker.style.top = `${top}px`;
  }

  function hideEmojiPicker() {
    emojiPicker.classList.remove('visible');
    emojiBtn.innerHTML = '<i class="ri-emotion-line"></i>';
  }

  function insertEmojiAtCursor(emoji) {
    const start = commentTextarea.selectionStart;
    const end = commentTextarea.selectionEnd;
    const text = commentTextarea.value;

    commentTextarea.value =
      text.substring(0, start) + emoji + text.substring(end);

    commentTextarea.selectionStart = commentTextarea.selectionEnd =
      start + emoji.length;

    commentTextarea.focus();
  }

  // --- 3 dots action menu toggle ---
  document.addEventListener('click', function (e) {
    // Close all open menus first
    document.querySelectorAll('.action-menu').forEach(menu => {
      menu.style.display = 'none';
    });

    // If the 3 dots button is clicked
    if (e.target.closest('.action-menu-btn')) {
      e.stopPropagation();
      const btn = e.target.closest('.action-menu-btn');
      const menu = btn.nextElementSibling;
      if (menu && menu.classList.contains('action-menu')) {
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
      }
    }
  });

  // --- Edit button logic ---
  document.addEventListener('click', function (e) {
    if (e.target.closest('.comment-edit')) {
      e.preventDefault();
      const editBtn = e.target.closest('.comment-edit');
      const mainForm = document.getElementById("commentForm");
      const textarea = document.getElementById("comment");
      const messageBox = editBtn.closest('.message-preview');
      const messageId = editBtn.getAttribute('data-id');
      const messageText = messageBox.querySelector('.preview-text p').textContent;

      // Set textarea value to message
      textarea.value = messageText;

      // Remove any existing update_id input
      const existingUpdateInput = mainForm.querySelector('input[name="update_id"]');
      if (existingUpdateInput) existingUpdateInput.remove();

      // Add new update_id input
      const updateIdInput = document.createElement('input');
      updateIdInput.type = 'hidden';
      updateIdInput.name = 'update_id';
      updateIdInput.value = messageId;
      mainForm.appendChild(updateIdInput);

      // Show update/cancel, hide send
      document.getElementById("addBtn").style.display = "none";
      document.getElementById("updateBtn").style.display = "inline-block";
      document.getElementById("cancelBtn").style.display = "inline-block";

      textarea.focus();
    }
  });

  // --- Delete button logic with modal ---
  let deleteTargetBtn = null;
  document.addEventListener('click', function (e) {
    if (e.target.closest('.comment-delete')) {
      e.preventDefault();
      deleteTargetBtn = e.target.closest('.comment-delete');
      document.getElementById('deleteModal').style.display = 'flex';
    }
  });

  document.getElementById('confirmDeleteBtn').onclick = function () {
    if (!deleteTargetBtn) return;
    const messageId = deleteTargetBtn.getAttribute('data-id');
    fetch("messages.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "delete_id=" + encodeURIComponent(messageId) + "&ajax=1",
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Remove the parent div wrapping the comment-box, not the container
          const messageDiv = deleteTargetBtn.closest('.comment-box').parentElement;
          if (messageDiv) messageDiv.remove();
        } else {
          alert("Failed to delete message.");
        }
        document.getElementById('deleteModal').style.display = 'none';
        deleteTargetBtn = null;
      })
      .catch(error => {
        alert("Failed to delete message.");
        document.getElementById('deleteModal').style.display = 'none';
        deleteTargetBtn = null;
      });
  };

  document.getElementById('cancelDeleteBtn').onclick = function () {
    document.getElementById('deleteModal').style.display = 'none';
    deleteTargetBtn = null;
  };

  // --- Cancel button logic ---
  const cancelBtn = document.getElementById("cancelBtn");
  if (cancelBtn) {
    cancelBtn.addEventListener("click", function () {
      const mainForm = document.getElementById("commentForm");
      mainForm.reset();

      // Remove update_id input if it exists
      const updateIdInput = mainForm.querySelector('input[name="update_id"]');
      if (updateIdInput) updateIdInput.remove();

      // Reset buttons
      document.getElementById("addBtn").style.display = "inline-block";
      document.getElementById("updateBtn").style.display = "none";
      document.getElementById("cancelBtn").style.display = "none";
    });
  }

  // --- Form submit (Add or Update) ---
  const commentForm = document.getElementById("commentForm");
  if (commentForm) {
    commentForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const form = this;
      const updateIdInput = form.querySelector('input[name="update_id"]');
      const comment = document.getElementById("comment").value.trim();

      if (!comment) {
        alert("Please enter a message");
        return;
      }

      if (updateIdInput) {
        // Update message
        const update_id = updateIdInput.value;
        fetch("messages.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "update_id=" + encodeURIComponent(update_id) +
            "&comment=" + encodeURIComponent(comment) +
            "&ajax=1",
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Update message in DOM
              document.querySelectorAll('.message-preview').forEach(box => {
                const editBtn = box.querySelector('.comment-edit');
                if (editBtn && editBtn.getAttribute('data-id') == update_id) {
                  box.querySelector('.preview-text p').textContent = comment;
                }
              });

              // Reset form and buttons
              form.reset();
              updateIdInput.remove();
              document.getElementById("addBtn").style.display = "inline-block";
              document.getElementById("updateBtn").style.display = "none";
              document.getElementById("cancelBtn").style.display = "none";
            } else {
              alert("Failed to update message.");
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert("Failed to update message.");
          });
      } else {
        // Add new message
        fetch("messages.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "comment=" + encodeURIComponent(comment) + "&ajax=1",
        })
          .then(response => response.json())
          .then(data => {
            if (data.success && data.message) {
              addNewMessageToDOM(data.message);
              form.reset();
            } else {
              alert("Failed to add message.");
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert("Failed to add message.");
          });
      }
    });
  }

 // --- Add new message at the top of the list ---
 function addNewMessageToDOM(messageData) {
  const container = document.querySelector('.message-preview');
  const noMessagesElement = document.querySelector('.no-messages');
  if (noMessagesElement) noMessagesElement.remove();

  const createdAt = new Date(messageData.created_at);

    // Format date as "Month Day, Year"
    const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
    const date = createdAt.toLocaleDateString('en-US', dateOptions);

  // Format time manually to avoid "at"
  let hours = createdAt.getHours();
  const minutes = createdAt.getMinutes().toString().padStart(2, '0');
  const ampm = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12 || 12;

  const time = `${hours}:${minutes} ${ampm}`;
  const formattedTime = `${date} ${time}`;

  const newMessage = document.createElement('div');
  newMessage.className = '';
  newMessage.innerHTML = `
      <div class="comment-box">
          <div class="comment-header">
              <img src="../assets/profile/${messageData.profile_picture ||
                'rawr.png'}"
                  alt="Avatar" class="avatar avatar--sm" />
              <div class="preview-text">
                  <h4>${messageData.user_name}</h4>
                  <p>${messageData.message}</p>
              </div>
          </div>
          <span class="timestamp">${formattedTime}</span>
          <span class="comment-actions">
              <button
                type="button"
                class="action-menu-btn"
                data-id="${messageData.id}">
                  <i class="ri-more-2-fill"></i>
              </button>
              <div class="action-menu" style="display: none;">
                  <button class="comment-edit" data-id="${messageData.id}">
                      Edit
                  </button>
                  <button class="comment-delete" data-id="${messageData.id}">
                      Delete
                  </button>
              </div>
          </span>
      </div>
  `;

    // Insert at the top of the messages list
    const firstMessage = container.firstChild;
    if (firstMessage) {
      container.insertBefore(newMessage, firstMessage);
    } else {
      container.appendChild(newMessage);
    }

    // Scroll to top to show the new message
    container.scrollTop = 0;
  }
});

// Notification dropdown toggle
document.getElementById('notificationBtnSidebar').addEventListener('click', function(e) {
  e.stopPropagation();
  const dropdown = document.getElementById('notification_dropdown');
  dropdown.classList.toggle('hidden');
  
  // Close other open dropdowns if any
  document.querySelectorAll('.notification-dropdown:not(#notification_dropdown)').forEach(d => {
    d.classList.add('hidden');
  });
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
  const dropdown = document.getElementById('notification_dropdown');
  const button = document.getElementById('notificationBtnSidebar');
  
  if (!dropdown.contains(e.target) && !button.contains(e.target)) {
    dropdown.classList.add('hidden');
  }
});

// Mark all as read
document.getElementById('markAllReadBtn')?.addEventListener('click', function() {
  const badge = document.getElementById('notification_count');
  if (badge) {
    badge.style.display = 'none';
  }
});

// Sidebar menu for settings and logout
document.addEventListener('DOMContentLoaded', function () {
  const moreBtn = document.getElementById('sidebarMoreBtn');
  const moreMenu = document.getElementById('sidebarMoreMenu');

  if (moreBtn && moreMenu) {
    moreBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      moreMenu.classList.toggle('hidden');
    });

    // Hide menu when clicking outside
    document.addEventListener('click', function (e) {
      if (!moreMenu.contains(e.target) && !moreBtn.contains(e.target)) {
        moreMenu.classList.add('hidden');
      }
    });
  }
});

document.addEventListener('DOMContentLoaded', function() {
  const messagePreview = document.querySelector('.message-preview');
  if (messagePreview) {
    messagePreview.scrollTop = 0;
  }
});

document.addEventListener('DOMContentLoaded', function() {
  // Notification Dropdown
  const notifBtn = document.getElementById('notificationBtnSidebar');
  const notifDropdown = document.getElementById('notification_dropdown');
  if (notifBtn && notifDropdown) {
    notifBtn.addEventListener('click', function(e) {
      e.preventDefault();
      notifDropdown.classList.toggle('show');
    });
    // Optional: Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
      if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
        notifDropdown.classList.remove('show');
      }
    });
  }

  // More Menu Popup
  const moreBtn = document.getElementById('sidebarMoreBtn');
  const moreMenu = document.getElementById('sidebarMoreMenu');
  if (moreBtn && moreMenu) {
    moreBtn.addEventListener('click', function(e) {
      e.preventDefault();
      moreMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', function(e) {
      if (!moreMenu.contains(e.target) && !moreBtn.contains(e.target)) {
        moreMenu.classList.add('hidden');
      }
    });
  }
});
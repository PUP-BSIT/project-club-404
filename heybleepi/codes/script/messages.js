// Edit action
document.querySelectorAll(".comment-edit").forEach(function (span) {
  span.addEventListener("click", function () {
    this.previousElementSibling.submit();
  });
});

// Delete action
document.querySelectorAll(".comment-delete").forEach(function (span) {
  span.addEventListener("click", function () {
    this.previousElementSibling.submit();
  });
});

// Simple emoji picker functionality
document.addEventListener("DOMContentLoaded", function () {
  const emojiBtn = document.getElementById("emojiBtn");
  const commentTextarea = document.getElementById("comment");

  const emojiPicker = document.createElement("div");
  emojiPicker.id = "emojiPicker";
  emojiPicker.className = "emoji-picker-popup";
  
  const pickerHTML = `
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
  emojiPicker.innerHTML = pickerHTML;
  
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
      "😨", "😰", "😥", "😓", "🤗", "🤔", "🤭",
    ],
    Hearts: [
      "❤️", "🧡", "💛", "💚", "💙", "💜", "🖤", "🤍", "🤎", "💔", "❣️",
      "💕", "💞", "💓", "💗", "💖", "💘", "💝", "💟",
    ],
    Hands: [
      "👍", "👎", "👌", "🤌", "🤏", "✌️", "🤞", "🤟", "🤘", "🤙", "👈",
      "👉", "👆", "🖕", "👇", "☝️", "👏", "🙌", "👐", "🤲", "🤝", "🙏",
    ],
    Objects: [
      "🔥", "⭐", "✨", "💫", "⚡", "💥", "💢", "💨", "💦", "💧", "🌟",
      "⚽", "🏀", "🏈", "⚾", "🥎", "🎾", "🏐", "🏉", "🥏", "🎱", "🪀",
      "🏓", "🏸", "🏒", "🏑", "🥍", "🏏", "🪃", "🥅",
    ],
    Food: [
      "🍎", "🍊", "🍋", "🍌", "🍉", "🍇", "🍓", "🫐", "🍈", "🍒", "🍑",
      "🥭", "🍍", "🥥", "🥝", "🍅", "🍆", "🥑", "🥦", "🥬", "🥒", "🌶️",
      "🫑", "🌽", "🥕", "🫒", "🧄", "🧅", "🥔", "🍠", "🥐",
    ],
  };

  for (const [category, emojis] of Object.entries(emojiCategories)) {
    const categoryHTML = `
      <div class="emoji-category">
        <div class="emoji-category-title">${category.replace('_', ' ')}</div>
        <div class="emoji-grid">
          ${emojis
            .map(
              (emoji) =>
                `<span class="emoji-item" data-emoji="${emoji}">${emoji}</span>`
            )
            .join("")}
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

  closePickerBtn.addEventListener("click", function(e) {
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
});
// Profile Picture Preview
document.getElementById('file_input').addEventListener('change', function () {
  const file = this.files[0];
  if (file) {
    const preview = document.getElementById('profile_image');
    preview.src = URL.createObjectURL(file);
  }
});

// Enable textarea for Bio
function enableTextArea() {
  const textarea = document.getElementById("bio");
  textarea.removeAttribute("readonly");
  textarea.focus();

  const button = document.querySelector(".change-bio");
  button.textContent = "Editing Bio...";
  button.disabled = true;
}

// Change Cover and Preview
function changeCover() {
  const coverInput = document.getElementById('cover_input');
  coverInput.click();

  coverInput.onchange = function () {
    const file = this.files[0];
    if (file) {
      const preview = document.getElementById('cover_preview_div');
      preview.style.backgroundImage = `url(${URL.createObjectURL(file)})`;
    }
  };
}

// Create loading overlay if not already created
function createLoadingOverlay() {
  if (!document.getElementById('loadingOverlay')) {
    const overlay = document.createElement('div');
    overlay.id = 'loadingOverlay';
    overlay.className = 'loading-overlay';
    overlay.innerHTML = `
      <div class="loading-box">Saving changes...</div>
    `;
    document.body.appendChild(overlay);
  }
}

// AJAX submit with loading
document.getElementById('profile_form').addEventListener('submit', function (e) {
  e.preventDefault();

  createLoadingOverlay();
  document.getElementById('loadingOverlay').style.display = 'flex';

  const formData = new FormData(this);

  fetch('profile_edit.php', {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
    .then(response => response.json())
    .then(data => {
      document.getElementById('loadingOverlay').style.display = 'none';

      if (data.success) {
        const toast = document.getElementById('successToast');
        toast.style.display = 'block';
        setTimeout(() => {
          toast.style.display = 'none';
        }, 1500);
      } else {
        alert("Error saving changes.");
      }
    })
    .catch(err => {
      document.getElementById('loadingOverlay').style.display = 'none';
      console.error(err);
      alert("An error occurred.");
    });
});

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

// Logout confirmation modal
function openLogoutModal() {
  document.getElementById('logoutConfirmModal').classList.remove('hidden');
}

function closeLogoutModal() {
  document.getElementById('logoutConfirmModal').classList.add('hidden');
}
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

// AJAX submit
document.getElementById('profile_form').addEventListener('submit', function (e) {
  e.preventDefault();

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
      console.error(err);
      alert("An error occurred.");
    });
});

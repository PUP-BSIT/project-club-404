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
//Changes the profile image
const fileInput = document.getElementById('file_input');
const profileImage = document.getElementById('profile_image');

fileInput.addEventListener('change', function () {
  const file = this.files[0];

  if (file) {
    const reader = new FileReader();

    reader.onload = function (e) {
      profileImage.src = e.target.result; 
    };

    reader.readAsDataURL(file); 
  }
});

function alertUserOnSave() {
  const saveButton = document.querySelector(".save-changes");
  alert("Changes saved successfully!");
}

function enableTextArea() {
  enableText = document.getElementById("bio");
  enableText.disabled = false;
}

function changeCover() {
  const changeCover = document.getElementById("cover_input");
  changeCover.click();

  //Changes the cover image
  cover_input.onchange = function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        const cover_preview_div = document.getElementById("cover_preview_div");
        cover_preview_div.style.backgroundImage = `url(${e.target.result})`;
      };
      reader.readAsDataURL(file);
    }
  };
}




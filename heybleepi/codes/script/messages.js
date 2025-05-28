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

// Show selected image or file names
document.getElementById("imageInput").addEventListener("change", function () {
  if (this.files.length > 0) {
    alert("Selected image: " + this.files[0].name);
  }
});

document.getElementById("fileInput").addEventListener("change", function () {
  if (this.files.length > 0) {
    alert("Selected file: " + this.files[0].name);
  }
});

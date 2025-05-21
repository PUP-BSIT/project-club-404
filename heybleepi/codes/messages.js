// Edit action
document.querySelectorAll(".comment-edit").forEach(function (span) {
  span.addEventListener("click", function () {
    this.previousElementSibling.submit();
  });
});

// Delete action
document.querySelectorAll(".comment-delete").forEach(function (span) {
  span.addEventListener("click", function () {
    if (confirm("Delete this comment?")) {
      this.previousElementSibling.submit();
    }
  });
});

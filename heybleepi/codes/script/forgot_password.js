document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".toggle-password").forEach(icon => {
    icon.addEventListener("click", function () {
      const input = document.querySelector(this.getAttribute("toggle"));
      if (!input) return;

      const type = input.getAttribute("type") === "password" ? "text" : "password";
      input.setAttribute("type", type);

      this.classList.toggle("ri-eye-off-line");
      this.classList.toggle("ri-eye-line");
    });
  });
});
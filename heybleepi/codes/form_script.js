tailwind.config = {
  theme: {
    extend: {
      colors: { primary: "#8A6FDF", secondary: "#FF9EE5" },
      borderRadius: {
        none: "0px",
        sm: "4px",
        DEFAULT: "8px",
        md: "12px",
        lg: "16px",
        xl: "20px",
        "2xl": "24px",
        "3xl": "32px",
        full: "9999px",
        button: "8px",
      },
    },
  },
};

// Generate stars
document.addEventListener("DOMContentLoaded", function () {
  const starsContainer = document.getElementById("stars-container");
  const starCount = 100;
  for (let i = 0; i < starCount; i++) {
    const star = document.createElement("div");
    star.classList.add("star");
    const size = Math.random() * 2 + 1;
    star.style.width = `${size}px`;
    star.style.height = `${size}px`;
    star.style.left = `${Math.random() * 100}%`;
    star.style.top = `${Math.random() * 100}%`;
    star.style.animationDelay = `${Math.random() * 4}s`;
    starsContainer.appendChild(star);
  }
});

// Tab switching
document.addEventListener("DOMContentLoaded", function () {
  const loginTab = document.getElementById("login-tab");
  const registerTab = document.getElementById("register-tab");
  const loginForm = document.getElementById("login-form");
  const registerForm = document.getElementById("register-form");

  loginTab.addEventListener("click", function () {
    loginTab.classList.add("tab-active");
    registerTab.classList.remove("tab-active");
    loginForm.classList.remove("hidden");
    registerForm.classList.add("hidden");
  });

  registerTab.addEventListener("click", function () {
    registerTab.classList.add("tab-active");
    loginTab.classList.remove("tab-active");
    registerForm.classList.remove("hidden");
    loginForm.classList.add("hidden");
  });
});

// Password visibility toggle
document.addEventListener("DOMContentLoaded", function () {
  const toggleButtons = document.querySelectorAll(".ri-eye-line");
  toggleButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const input = this.closest("div").querySelector("input");
      if (input.type === "password") {
        input.type = "text";
        this.classList.remove("ri-eye-line");
        this.classList.add("ri-eye-off-line");
      } else {
        input.type = "password";
        this.classList.remove("ri-eye-off-line");
        this.classList.add("ri-eye-line");
      }
    });
  });
});
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

// Login and Register form submission
function switchToLogin(event) {
  event.preventDefault(); // Prevent default button behavior

  document.getElementById("login-form").classList.remove("hidden");
  document.getElementById("register-form").classList.add("hidden");

  document.getElementById("login-tab").classList.add("tab-active");
  document.getElementById("register-tab").classList.remove("tab-active");
}

function switchToRegister(event) {
  event.preventDefault();

  document.getElementById("register-form").classList.remove("hidden");
  document.getElementById("login-form").classList.add("hidden");

  document.getElementById("register-tab").classList.add("tab-active");
  document.getElementById("login-tab").classList.remove("tab-active");
}

// Function to attach event listeners for form submissions
function attachFormEventListeners() {
  const loginButton = document.querySelector(
    '#login-form button[type="submit"]'
  );
  const registerButton = document.querySelector(
    '#register-form button[type="submit"]'
  );

  if (loginButton) {
    loginButton.addEventListener("click", loginSubmit);
  }

  if (registerButton) {
    registerButton.addEventListener("click", registerSubmit);
  }
}

// Call the function to attach event listeners
attachFormEventListeners();

// Remove inline onclick attributes for elements related to login and register submissions
document.addEventListener("DOMContentLoaded", function () {
  document
    .querySelectorAll('[onclick^="loginSubmit"], [onclick^="registerSubmit"]')
    .forEach((el) => {
      el.removeAttribute("onclick");
    });
});

// Automatically switch to login tab and show success message after registration
document.addEventListener("DOMContentLoaded", function () {
  if (window.location.search.includes("registration=success")) {
    document.getElementById("register-tab").classList.remove("tab-active");
    document.getElementById("login-tab").classList.add("tab-active");
    document.getElementById("register-form").classList.add("hidden");
    document.getElementById("login-form").classList.remove("hidden");

    alert("Registration successful! Please login.");

    history.replaceState({}, document.title, window.location.pathname);
  }
});

// Checks username status
document.addEventListener("DOMContentLoaded", function () {
  const usernameInput = document.getElementById("username");
  const status = document.getElementById("username_status");

  usernameInput.addEventListener("input", function () {
    const username = this.value.trim();
    if (username.length < 3) {
      status.textContent = "Username must be at least 3 characters.";
      status.style.color = "orange";
      return;
    }

    fetch(`check_username.php?username=${encodeURIComponent(username)}`)
      .then(res => res.text())
      .then(data => {
        if (data === "taken") {
          status.textContent = "Username is already taken.";
          status.style.color = "red";
        } else if (data === "available") {
          status.textContent = "Username is available!";
          status.style.color = "green";
        } else {
          status.textContent = "";
        }
      })
      .catch(() => {
        status.textContent = "Could not check username.";
        status.style.color = "gray";
      });
  });
});
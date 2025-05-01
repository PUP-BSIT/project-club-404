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
=======
function showRegisterForm() {
	dynamicForm.innerHTML = `
					<input 
						type="text" 
						id="first_name" 
						class="input-text"
						placeholder="👤 First Name" 
						required>

					<input 
						type="text" 
						id="last_name" 
						class="input-text"
						placeholder="👤 Last Name" 
						required>

					<input 
						type="date" 
						id="birthdate" 
						class="input-text"
						placeholder="📅 Birthdate" 
						required>

					<input 
						type="text" 
						id="phone" 
						class="input-text"
						placeholder="📞 Phone Number" 
						required>

					<input 
						type="text" 
						id="email_reg"
						class="email input-text" 
						placeholder="✉️ Email Address"
						required>

					<input 
						type="password" 
						id="password_reg"
						class="password input-text" 
						placeholder="🔒 Password" 
						required>

					<div id="terms_and_conditions">
						<input type="checkbox" id="terms_policies" required>
						<label for="terms_policies">I agree to the 
							<a href="#" id="terms_policies">Terms of Service</a> and 
							<a href="#">Policies</a></label>
					</div>
					<button type="button" id="create_acc_btn">Create an account</button>`					
}

function showLoginForm() {
	dynamicForm.innerHTML = `
					<input 
						type="text" 
						id="email_login"
						class="email input-text"
						placeholder="✉️ Email" 
						required>

				<input 
						type="password" 
						id="password_login"
						class="password input-text" 
						placeholder="🔒 Password" 
						required>

					<div id="selections_container">
						<div>
							<input type="checkbox" id="remember_me">
							<label for="remember_me">Remember Me</label>
						</div>
						<a href="#" id="forgot_password">Forgot Password?</a>
					</div>
					<button type="button">Login</button>
					<p>or continue with</p>
					<div id="social_media_container">
						<a href="#" class="social-media-hyperlinks">f</a>
						<a href="#" class="social-media-hyperlinks">G</a>
						<a href="#" class="social-media-hyperlinks">𝕏</a>
					</div>`
}

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
const dynamicForm = document.querySelector("#dynamic_form");

function showRegisterForm() {
	dynamicForm.innerHTML = `
					<input 
						type="text" 
						id="first_name" 
						class="input-text"
						placeholder="First Name" 
						required>

					<input 
						type="text" 
						id="last_name" 
						class="input-text"
						placeholder="Last Name" 
						required>

					<input 
						type="date" 
						id="birthdate" 
						class="input-text"
						placeholder="Birthdate" 
						required>

					<input 
						type="text" 
						id="phone" 
						class="input-text"
						placeholder="Phone Number" 
						required>

					<input 
						type="text" 
						id="email_reg"
						class="email input-text" 
						placeholder="Email Address"
						required>

					<input 
						type="password" 
						id="password_reg"
						class="password input-text" 
						placeholder="Password" 
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
						placeholder="Email" 
						required>

				<input 
						type="password" 
						id="password_login"
						class="password input-text" 
						placeholder="Password" 
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



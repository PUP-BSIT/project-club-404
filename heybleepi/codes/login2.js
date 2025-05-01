const dynamicForm = document.querySelector("#dynamic_form");

function showRegisterForm() {
	dynamicForm.innerHTML = `
					<input type="text" id="first_name" placeholder="First Name" required>
					<input type="text" id="last_name" placeholder="Last Name" required>
					<input type="date" id="birthdate" placeholder="Birthdate" required>
					<input type="text" id="phone" placeholder="Phone Number" required>
					<input type="text" id="email" placeholder="Email Address" required>
					<input type="password" id="password" placeholder="Password" required>
					<input type="checkbox" id="terms_policies" required>
					<label for="terms_policies">I agree to the 
						<a href="#">Terms of Service</a> and 
						<a href="#">Policies</a></label>
					<button type="button">Create an account</button>
					<p>or continue with</p>
					<a href="#">f</a>
					<a href="#">G</a>
					<a href="#">𝕏</a>`
					
}

function showLoginForm() {
	dynamicForm.innerHTML = `
					<input type="text" id="email" placeholder="Email" required>
					<input type="password" id="password" placeholder="Password" required>
					<div>
						<input type="checkbox" id="remember_me" name="remember_me">
						<label for="remember_me">Remember Me</label>
						<a href="#" id="forgot_password">Forgot Password?</a>
					</div>
					<button type="button">SLogin</button>`
}



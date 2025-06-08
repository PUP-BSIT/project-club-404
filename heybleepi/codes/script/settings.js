let accountInformation = document.querySelector('#account_information_container'); 
let privacySettings= document.querySelector('#privacy_settings_container');
let section = document.querySelector('#section'); 
let accountInformationBtn = document.querySelector('#acct_info_btn');
let privacySettingsBtn = document.querySelector('#privacy_settings_btn');
let logoutPrompt = document.querySelector('#logout_prompt');
let deleteAccPrompt = document.querySelector('#delete_acc');

// change this from delete_account.php endpoint to web domain
const deleteEndpointLocation = "delete_account.php" 
// change this from update_settings.php endpoint to web domain
const updateEndpointLocation = "update_settings.php"; 
// change this from update_password.php endpoint to web domain
const updatePasswordEndpoint = "update_password.php";

function switchToAccountInformation() {
  privacySettings.classList.add('hide-privacy-settings');
  privacySettings.classList.remove('show-privacy-settings');
  accountInformation.classList.remove('hide-account-info');
  accountInformation.classList.add('show-account-info');
  accountInformationBtn.disabled = true;
  privacySettingsBtn.disabled = false;
}

function switchToPrivacySettings() {
  accountInformation.classList.add('hide-account-info');
  accountInformation.classList.remove('show-account-info');
  privacySettings.classList.remove('hide-privacy-settings');
  privacySettings.classList.add('show-privacy-settings');
  accountInformationBtn.disabled = false;
  privacySettingsBtn.disabled = true;
}

function showLogoutPrompt() {
  logoutPrompt.classList.remove('hide-overlay');
  logoutPrompt.classList.add('show-overlay');
}

function hideLogoutPrompt() {
  logoutPrompt.classList.add('hide-overlay');
  logoutPrompt.classList.remove('show-overlay');
}

function showDeleteAccountPrompt() {
  deleteAccPrompt.classList.remove('hide-delete-acc-prompt');
  deleteAccPrompt.classList.add('show-delete-acc-prompt');
}

function hideDeleteAccountPrompt() {
  deleteAccPrompt.classList.add('hide-delete-acc-prompt');
  deleteAccPrompt.classList.remove('show-delete-acc-prompt');
}

function showPassword() {
  const oldPassword = document.querySelector("#old_password");
  const newPassword = document.querySelector("#new_password");
  const confirmPassword = document.querySelector("#confirm_new_password");
  const showPassBtn = document.querySelector("#show_password");

  if(oldPassword.type == "password") {
    oldPassword.type = "text";
    newPassword.type = "text";
    confirmPassword.type = "text";
    return;
  } 

  oldPassword.type = "password";
  newPassword.type = "password";
  confirmPassword.type = "password";
}

// Function to find and delete the account in the database.
function deleteAccount(id) {
  fetch(deleteEndpointLocation, {
    method: "POST",
  })
  .then((response) => response.text())
  .then((responseText) => {
    console.log(responseText);
    alert(responseText);
    // window.location.reload(); // reloads the window.
  })
  .catch((error) => {
    console.error("Fetch error:", error);
    alert("Something went wrong while deleting the account.");
  });
}

function updateAccountInformation() {
  const firstNameInput = document.querySelector("#first_name");
  const middleNameInput = document.querySelector("#middle_name");
  const lastNameInput = document.querySelector("#last_name");
  const usernameInput = document.querySelector("#username_input");
  const emailInput = document.querySelector("#email");
  const birthdateInput = document.querySelector("#birthdate");

  fetch(updateEndpointLocation, {
    method: "POST",
    headers: {
      "Content-type": "application/x-www-form-urlencoded",  
    },
    body: `user_name=${usernameInput.value}&first_name=${firstNameInput.value}&` +
          `middle_name=${middleNameInput.value}&last_name=${lastNameInput.value}&` +
          `email=${emailInput.value}&birthdate=${birthdateInput.value}`
  })
  .then((response) => response.text())
  .then(responseText => {
    alert(responseText);
    window.location.reload();
  })
}

function changePassword() {
  const currentPasswordInput = document.querySelector("#old_password");
  const newPasswordInput = document.querySelector("#new_password");
  const confirmPasswordInput = document.querySelector("#confirm_new_password");

  fetch(updatePasswordEndpoint, {
    method: "POST",
    headers: {
      "Content-type": "application/x-www-form-urlencoded",  
    },
    body: `password=${currentPasswordInput.value}&` +
          `new_password=${newPasswordInput.value}`
  })
  .then((response) => response.text())
  .then(responseText => {
    if(!newPasswordInput.value && !confirmPasswordInput.value) {
          alert("Please fill the fields.")
    }

    if (newPasswordInput.value != confirmPasswordInput.value) {
      alert("New password and Confirm password are not the same.");
      return;
    }

    alert(responseText);
    window.location.reload();
  })
}

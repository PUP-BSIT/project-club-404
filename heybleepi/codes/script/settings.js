let accountInformation = document.querySelector('#account_information_container'); 
let privacySettings= document.querySelector('#privacy_settings_container');
let section = document.querySelector('#section'); 
let accountInformationBtn = document.querySelector('#acct_info_btn');
let privacySettingsBtn = document.querySelector('#privacy_settings_btn');
let logoutPrompt = document.querySelector('#logout_prompt');
let deleteAccPrompt = document.querySelector('#delete_acc');
const endpoint = "delete_account.php" // change the endpoint to web domain

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

// Function to find and delete the account in the database.
function deleteAccount(id) {
  fetch(endpoint, {
    method: "POST",
    headers: {
      "Content-type": "application/x-www-form-urlencoded",
    },
    body: `id=${id}`,
  })
  .then((response) => response.text())
  .then((responseText) => {
    alert(responseText);
    window.location.reload(); // reloads the window.
  })
}


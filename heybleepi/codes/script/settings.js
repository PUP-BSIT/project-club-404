let accountInformationHTML = `<div id="account_information_container">
                                <h2>Account Information</h2>
                                <p>Here you can view and edit your account 
                                  information.</p>
                                <form>
                                  <div>
                                    <label for="first_name">First Name</label>
                                    <input
                                      type="text"
                                      placeholder="First Name"
                                      id="first_name">
                                  </div>

                                  <div>
                                    <label>Middle Name</label>
                                    <input
                                      type="text"
                                      placeholder="Middle Name"
                                      id="middle_name">
                                  </div>

                                  <div>
                                    <label>Last Name</label>
                                    <input
                                      type="text"
                                      placeholder="Last Name"
                                      id="last_name">
                                  </div>

                                  <div>
                                    <label>Username</label>
                                    <input
                                      type="text"
                                      placeholder="Username"
                                      id="username">
                                  </div>

                                  <div>
                                    <label>Email</label>
                                    <input
                                      type="email"
                                      placeholder="Email"
                                      id="email">
                                  </div>

                                  <div>
                                    <label>Birthdate</label>
                                    <input
                                      type="text"
                                      placeholder="Birthdate"
                                      id="birthdate">
                                  </div>

                                  <button
                                    type="button"
                                    id="save_btn">
                                      Save Changes
                                  </button>
                                </form>
                                <div id="connected_accts_container">
                                  <h4>Connected Accounts</h4>
                                  <div class="account-buttons">
                                    <button class="account-btn">
                                      <img 
                                        src="./assets/connected_accounts/devhive.jpg" 
                                        alt="devhive">
                                          Connect to Devhive
                                    </button>
                                    <button class="account-btn">
                                      <img 
                                        src="./assets/connected_accounts/hershell.png" 
                                        alt="hershell">
                                          Connect to Hershell
                                    </button>
                                  </div>
                                </div>
                              </div>`
let privacySettingsHTML = `<div id="privacy_settings_container">
                            <h2>Privacy & Security</h2>
                            <p>Here you can manage your password.</p>
                            <form>
                              <div>
                                <label>Old Password</label>
                                <input 
                                  type="text" 
                                  placeholder="Old Password"
                                  id="old_password">
                              </div>

                              <div>
                                <label>New Password</label>
                                <input 
                                  type="text" 
                                  placeholder="New Password"
                                  id="new_password">
                              </div>

                              <div>
                                <label>Confirm New Password</label>
                                <input 
                                  type="text" 
                                  placeholder="Confirm New Password"
                                  id="confirm_new_password">
                              </div>
                              <button 
                                type="button"
                                id="change_password_btn">
                                  Change Password
                              </button>
                            </form>
                            <button 
                              type="button"
                              id="delete_account_btn">
                                Delete Account
                            </button>
                          </div>`
let section = document.querySelector('#section'); 
let accountInformationBtn = document.querySelector('#acct_info_btn');
let privacySettingsBtn = document.querySelector('#privacy_btn');

function switchToAccountInformation() {
  section.innerHTML = accountInformationHTML;
  accountInformationBtn.disabled = true;
  privacySettingsBtn.disabled = false;
}

function switchToPrivacySettings() {
  section.innerHTML = privacySettingsHTML;
  accountInformationBtn.disabled = false;
  privacySettingsBtn.disabled = true;
}



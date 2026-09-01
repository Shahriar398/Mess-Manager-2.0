let bangla = false;

function changeLanguage() {
  const button = document.getElementById("language-button");
  const title = document.getElementById("title");

  const nameLabel = document.getElementById("name-label");
  const emailLabel = document.getElementById("email-label");
  const passwordLabel = document.getElementById("password-label");
  const confirmPasswordLabel = document.getElementById(
    "confirm-password-label",
  );

  const name = document.getElementById("name");
  const email = document.getElementById("email");
  const password = document.getElementById("password");
  const confirmPassword = document.getElementById("confirm-password");

  const createButton = document.getElementById("create-button");

  const loginButton = document.getElementById("login-button");

  if (bangla === false) {
    button.innerHTML = "EN";

    title.innerHTML = "আপনার অ্যাকাউন্ট তৈরি করুন<br>ইমেইল দিয়ে";

    nameLabel.innerHTML = "আপনার নাম";

    emailLabel.innerHTML = "আপনার ইমেইল";

    passwordLabel.innerHTML = "আপনার পাসওয়ার্ড";

    confirmPasswordLabel.innerHTML = "পাসওয়ার্ড নিশ্চিত করুন";

    name.placeholder = "যেমন: মোঃ রহিম";

    email.placeholder = "যেমন: rahim@gmail.com";

    password.placeholder = "কমপক্ষে ৬টি অক্ষর";

    confirmPassword.placeholder = "পাসওয়ার্ড নিশ্চিত করুন";

    createButton.value = "অ্যাকাউন্ট তৈরি করুন";

    loginButton.innerHTML = "ইতিমধ্যে অ্যাকাউন্ট আছে? লগ ইন করুন।";

    bangla = true;
  } else {
    button.innerHTML = "বং";

    title.innerHTML = "Create Account<br>with Email";

    nameLabel.innerHTML = "Your Name";

    emailLabel.innerHTML = "Your Email";

    passwordLabel.innerHTML = "Enter Password";

    confirmPasswordLabel.innerHTML = "Confirm Password";

    name.placeholder = "e.g. Md Rahim";

    email.placeholder = "e.g. rahim@gmail.com";

    password.placeholder = "minimum 6 characters";

    confirmPassword.placeholder = "Confirm Password";

    createButton.value = "Create Account";

    loginButton.innerHTML = "Already have an account? Login.";

    bangla = false;
  }
}

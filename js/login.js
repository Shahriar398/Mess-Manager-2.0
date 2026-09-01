var bangla = false;

function changeLanguage() {
  var button = document.querySelector(".language");

  var title = document.getElementById("title");

  var emailLabel = document.getElementById("email-label");

  var passwordLabel = document.getElementById("password-label");

  var email = document.getElementById("email");

  var password = document.getElementById("password");

  var forgot = document.getElementById("forgot");

  var loginButton = document.getElementById("login-button");

  var registerText = document.getElementById("register-text");

  var createButton = document.getElementById("create-button");

  if (bangla == false) {
    button.innerHTML = "EN";

    title.innerHTML = "আপনার অ্যাকাউন্টে লগ ইন করুন<br>ইমেইল দিয়ে";

    emailLabel.innerHTML = "আপনার ইমেইল";

    passwordLabel.innerHTML = "আপনার পাসওয়ার্ড";

    email.placeholder = "আপনার ইমেইল";

    password.placeholder = "কমপক্ষে ৬টি অক্ষর";

    forgot.innerHTML = "পাসওয়ার্ড ভুলে গেছেন?";

    loginButton.value = "লগ ইন";

    registerText.innerHTML =
      "আপনার কি <span>Mess Manager</span>-এ অ্যাকাউন্ট নেই?<br>ইমেইল দিয়ে আপনার অ্যাকাউন্ট তৈরি করুন";

    createButton.innerHTML = "অ্যাকাউন্ট তৈরি করুন";

    bangla = true;
  } else {
    button.innerHTML = "বং";

    title.innerHTML = "Log in to your account<br>with Email";

    emailLabel.innerHTML = "Your Email";

    passwordLabel.innerHTML = "Enter Your Password";

    email.placeholder = "Your Email";

    password.placeholder = "minimum 6 characters";

    forgot.innerHTML = "Forgot Password?";

    loginButton.value = "Login";

    registerText.innerHTML =
      "Don't have an account in <span>Mess Manager?</span><br>Create your account with Email";

    createButton.innerHTML = "Create Account";

    bangla = false;
  }
}

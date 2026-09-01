/* =====================================================
   MOBILE NAVIGATION
===================================================== */

const menuBtn = document.getElementById("menuBtn");

const navMenu = document.getElementById("navMenu");

/*
    Open and close mobile menu
*/

menuBtn.addEventListener("click", function () {
  navMenu.classList.toggle("active");

  /*
        Change hamburger icon
        to close icon
    */

  const icon = menuBtn.querySelector("i");

  if (navMenu.classList.contains("active")) {
    icon.classList.remove("fa-bars");

    icon.classList.add("fa-xmark");
  } else {
    icon.classList.remove("fa-xmark");

    icon.classList.add("fa-bars");
  }
});

/* =====================================================
   CLOSE MENU AFTER CLICKING A LINK
===================================================== */

const navLinks = navMenu.querySelectorAll("a");

navLinks.forEach(function (link) {
  link.addEventListener("click", function () {
    navMenu.classList.remove("active");

    const icon = menuBtn.querySelector("i");

    icon.classList.remove("fa-xmark");

    icon.classList.add("fa-bars");
  });
});

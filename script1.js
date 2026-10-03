const themeToggle = document.getElementById("themeToggle");
themeToggle.addEventListener("click", () => {
  document.body.classList.toggle("dark");
  themeToggle.textContent = document.body.classList.contains("dark") ? "☀️" : "🌙";
});

// Logout confirmation
function confirmLogout() {
  const confirmAction = confirm("Are you sure you want to log out?");
  if (confirmAction) {
    window.location.href = "logout.php";
  }
}

<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['email'])) {
    header("location: index.php");
    exit();
}

$email = $_SESSION['email'];
$query = "SELECT * FROM users WHERE email='$email'";
$result = $conn->query($query);
$user = $result->fetch_assoc();
$student_id = $user['student_id'];
$name = $user['name'] ?? 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style1.css">
    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "logout.php";
            }
        }
    </script>
</head>
<body>
<div class="dashboard">
    <header>
        <h1>🎓 Admin Dashboard</h1>
        <button id="themeToggle">🌙</button>
    </header>

    <section class="welcome-card">
        <h2>Welcome, <span><?php echo htmlspecialchars($name); ?></span> 👋</h2>
        <p><strong>ID:</strong> <?php echo htmlspecialchars($student_id); ?></p>
       
    </section>

    <div class="menu">
        <a href="ad_att.php" class="card">📊 Attendance</a>
        <a href="ad_fees.php" class="card">💰 Fees</a>
        <a href="#" onclick="confirmLogout()" class="card logout">🚪 Logout</a>
    </div>
</div>
<script>
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

</script>
</body>
</html>

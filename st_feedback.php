<?php
session_start();
include 'connect.php';

if(!isset($_SESSION['email'])){
    header("location: index.php");
    exit();
}

$email = $_SESSION['email'];
$get_id = "SELECT student_id FROM users WHERE email='$email'";
$result = $conn->query($get_id);
$row = $result->fetch_assoc();
$student_id = $row['student_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Feedback</title>
<style>
  :root {
    --bg: #f4f7fb;
    --card: #ffffff;
    --accent: #1678ff;
    --muted: #eeeff1ff;
    --radius: 12px;
    --gap: 16px;
  }

  body {
    margin: 0;
    font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    background: #006989;
    color: #f6f7fa;
    padding: 24px;
  }

  .wrap {
    max-width: 800px;
    margin: 0 auto;
  }

  header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 20px;
  }

  h1 {
    font-size: 22px;
    margin: 0;
  }

  p.lead {
    margin: 0;
    color: var(--muted);
    font-size: 14px;
  }

  form {
    display: flex;
    flex-direction: column;
    gap: var(--gap);
  }

  .feedback-card {
    background: #f6f7fa;
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: 0 6px 18px rgba(16, 24, 40, 0.06);
    border: 1px solid rgba(15, 23, 42, 0.04);
  }

  label {
    color: #000000; /* Changed label color to black */
    font-weight: 600;
    font-size: 15px;
    margin-bottom: 8px;
    display: block;
  }

  input[type="text"],
  textarea,
  select {
    width: 100%;
    padding: 10px 12px;
    font-size: 15px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    outline: none;
    transition: border-color 0.2s ease;
    resize: vertical;
  }

  input:focus,
  textarea:focus,
  select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(22,120,255,0.15);
  }

  textarea {
    min-height: 100px;
  }

  .btn {
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.2s ease;
    align-self: flex-end;
  }

  .btn:hover {
    background: #005edb;
  }

  .msg {
    background: #e0f2fe;
    color: #0369a1;
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 14px;
  }

  @media (max-width: 480px) {
    h1 { font-size: 18px; }
    label { font-size: 14px; }
    .btn { width: 100%; }
  }
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div>
      <h1>💬 Student Feedback</h1>
      <p class="lead">We value your opinion — share your feedback on each subject below.</p>
    </div>
  </header>

  <form method="POST" action="">
    <div class="feedback-card">
      <label for="subject">Select Subject</label>
      <select name="subject" id="subject" required>
        <option value="">-- Choose Subject --</option>
        <option>Computer Networks</option>
        <option>DBMS</option>
        <option>Cyber Security</option>
        <option>R and Data Mining</option>
        <option>NME</option>
      </select>
    </div>

    <div class="feedback-card">
      <label for="rating">Rating</label>
      <select name="rating" id="rating" required>
        <option value="">-- Select Rating --</option>
        <option>Excellent</option>
        <option>Good</option>
        <option>Average</option>
        <option>Poor</option>
      </select>
    </div>

    <div class="feedback-card">
      <label for="comment">Your Feedback</label>
      <textarea name="comment" id="comment" placeholder="Write your feedback here..." required></textarea>
    </div>

    <button type="submit" class="btn">Submit Feedback</button>

    <?php
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $subject = $_POST['subject'];
        $rating = $_POST['rating'];
        $comment = $_POST['comment'];

        $insert = "INSERT INTO feedback (student_id, subject, rating, comment) VALUES ('$student_id', '$subject', '$rating', '$comment')";
        if($conn->query($insert)){
            echo "<p class='msg'>✅ Feedback submitted successfully!</p>";
        } else {
            echo "<p class='msg' style='background:#fee2e2;color:#991b1b;'>❌ Error submitting feedback.</p>";
        }
    }
    ?>
  </form>
</div>
</body>
</html>

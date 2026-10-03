<?php 
include 'connect.php';

if(isset($_POST['signUp'])){
    $student_id = $_POST['student_id']; // Fixed: changed $username to $student_id
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Use prepared statements for security
    $checkEmail = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmail);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0){
        echo "Email Address Already Exists!";
        $stmt->close();
    } else {
        $stmt->close();
        
        
        
        $insertQuery = "INSERT INTO users (student_id, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        
        // Fixed: Using correct variable names
        $stmt->bind_param("ssss", $student_id, $email, $password, $role);
        
        if($stmt->execute()){
            header("location: index.php");
            exit();
        } else {
            echo "Error: " . $stmt->error; // Fixed: changed $conn->error to $stmt->error
        }
        $stmt->close();
    }
}

if(isset($_POST['signIn'])){
   $email = $_POST['email'];
   $password = $_POST['password'];
   
   // Use prepared statements
   $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
   $stmt = $conn->prepare($sql);
   $stmt->bind_param("ss", $email, $password);
   $stmt->execute();
   $result = $stmt->get_result();
   
   if($result->num_rows > 0){
        session_start();
        $row = $result->fetch_assoc();
        $_SESSION['email'] = $row['email'];
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role'] = $row['role']; // Assuming you have a 'role' column in your users table
        
        // Redirect based on user role
        if($row['role'] == 'admin') {
            header("Location: ad_dashboard.php");
        } else {
            header("Location: st_dashboard.php");
        }
        exit();
   } else {
        echo "Not Found, Incorrect Email or Password";
         header("location: index.php");
   }
   $stmt->close();
}

$conn->close();
?>
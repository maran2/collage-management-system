<?php

session_start();

$errors = [
     'signIn' => $_SESSION['login_error'] ?? '',
     'signup' => $_SESSION['register_error'] ??''

];

$activeForm = $_SESSION['active_form'] ?? 'signIn';

session_unset();

function showError ($error) {
        return !empty($error) ? "<p class='error-message'>$error</p>" : '';

}

function isActiveForm($formName, $activeForm) {

       return $formName === $activeForm? 'active': '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register & Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
</head>

<body >
     <div class="container <?=isActiveForm('signup',$activeForm);  ?>" id="signup" style="display:none;">
      <h1 class="form-title">Register</h1>
      <?= showError($error['signup']); ?>
      <form method="post"  action="register.php" autocomplete="off">

        <div class="input-group">
           <i class="fas fa-user"></i>
           <input type="text" name="student_id" id="student_id" placeholder="User Id" required>
           
        </div>
      
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" id="email" placeholder="Email" required>
            
        </div>
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="password" placeholder="Password" required>
        </div>
         <div class="input-group">
           <i class="fas fa-users"></i>
           <select name="role" required>
             <option value="">--Select Role--</option>
             <option value="student">Student</option>
             <option value="admin">Admin</option>
           </select>
         </div>
          <input type="submit" class="btn" value="Sign Up" name="signUp">
       
      </form>
     
      <div class="links">
        <p>Already Have Account ?</p>

        <button id="signInButton"><i class="fa fa-sign-in"></i>Sign In</button>
      </div>
    </div>

    <div class="container <?=isActiveForm('signIn',$activeForm);  ?>" id="signIn">
        <h1 class="form-title">Sign In</h1>
         <?= showError($error['signIn']); ?>
        <form method="post" action="register.php">
          <div class="input-group">
              <i class="fas fa-envelope"></i>
              <input type="email" name="email" id="email" placeholder="Email" required>
              
          </div>
          <div class="input-group">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" id="SigninPassword" placeholder="Password" required>
              
          </div>
              <input type="submit" class="btn" value="Sign In" name="signIn"><br>
        </form>
        <div class="links">
          <p>Don't have account yet?</p> 
          
          <button id="signUpButton" ><i class="fa fa-sign-out"></i> Sign Up</button>
        </div>
      </div>
      <script src="script.js"></script>
</body>
</html>

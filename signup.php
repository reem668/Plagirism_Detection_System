<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <title>Login and Registration</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <script>
      document.addEventListener("keydown", function(e) {
      // Detect Ctrl + Shift + A
  if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === "x") {
    e.preventDefault();
    const adminBox = document.getElementById("admin-key-box");
    
    if (adminBox.style.display === "block") {
      // Hide & clear the field
      adminBox.style.display = "none";
      document.getElementById("admin_key").value = "";
    } else {
      // Show the field
      adminBox.style.display = "block";
      document.getElementById("admin_key").focus();
    }
  }
});
</script>


  </head>
  <body>
    <div class="container">
      <input type="checkbox" id="flip">
      <div class="cover">
        <div class="front">
          <img src="uploads/frontImg.jpg" alt="Front Image">
          <div class="text">
            <span class="text-1">Every new friend is a <br> new adventure</span>
            <span class="text-2">Let's get connected</span>
          </div>
        </div>
        <div class="back">
          <img class="backImg" src="uploads/backImg.jpg" alt="Back Image">
          <div class="text">
            <span class="text-1">Complete miles of journey <br> with one step</span>
            <span class="text-2">Let's get started</span>
          </div>
        </div>
      </div>
      <div class="forms">
        <div class="form-content">
          <div class="login-form">
            <div class="title">Login</div>
             <form action="login_process.php" method="post">
              <div class="input-boxes">
                <div  id="admin-key-box" class="input-box admin-key" style="display:none;">
                   <i class="fas fa-key"></i>
                      <input type="text" id="admin_key" name="admin_key" placeholder="Enter Admin Secret Key">
                </div>

                <div class="input-box">
                  <i class="fas fa-envelope"></i>
                  <input type="text" id="login-email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="input-box">
                  <i class="fas fa-lock"></i>
                  <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                </div>
                <div class="text"><a href="#" >Forgot password?</a></div>
                <div class="button input-box">
                  <input type="submit" value="Submit">
                </div>
                <div class="text sign-up-text">Don't have an account? <label for="flip">Signup now</label></div>
              </div>
            </form>
          </div>
          <div class="signup-form">
            <div class="title">Signup</div>
             <form action="signup_process.php" method="post"> 
              <div class="input-boxes">
                <div class="input-box">
                  <i class="fas fa-user"></i>
                  <input type="text" id="signup-name"  name="name" placeholder="Enter your name" required>
                </div>
                <div class="input-box">
                  <i class="fas fa-envelope"></i>
                  <input type="email" id="signup-email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="input-box">
                  <i class="fas fa-phone"></i>
                  <input type="tel" id="signup-mobile" name="mobile" placeholder="Enter your mobile number" required pattern="\d{11}" title="Please enter a 11-digit mobile number">
                </div>
                <div class="input-box">
                  <label for="country">Country:</label>
                  <select id="signup-country" name="country" required>
                    <option value="">Select Country</option>
                    <option value="us">United States</option>
                    <option value="ca">Canada</option>
                    <option value="in">India</option>
                    <option value="uk">United Kingdom</option>
                    <option value="eg">Egypt</option>
                    <option value="au">Australia</option>
                    <option value="fr">France</option>
                    <option value="de">Germany</option>
                    <option value="br">Brazil</option>
                    <option value="za">South Africa</option>
                    <option value="mx">Mexico</option>
                    <option value="jp">Japan</option>
                    <option value="kr">South Korea</option>
                    <option value="cn">China</option>
                    <option value="ru">Russia</option>
                    <option value="ae">United Arab Emirates</option>
                    <option value="it">Italy</option>
                    <option value="es">Spain</option>
                  </select>
                </div>
                <div class="input-box">
                  <label for="role">Role:</label>
                  <input type="radio" id="role-student" name="role" value="student" required>
                  <label for="role-student">Student</label>
                  <input type="radio" id="role-instructor" name="role" value="instructor" required>
                  <label for="role-instructor">Instructor</label>
                </div>
                <div class="input-box">
                  <i class="fas fa-lock"></i>
                  <input type="password" id="signup-password" name="password" placeholder="Enter your password" required>
                </div>
                <div class="input-box">
                  <i class="fas fa-lock"></i>
                  <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required>
                </div>
                <div class="button input-box">
                  <input type="submit" value="Submit">
                </div>
                <div class="text sign-up-text">Already have an account? <label for="flip">Login now</label></div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </body>
  <?php
if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
    echo "
    <script>
      alert('Signup successful! You can now log in.');
      // Automatically uncheck the flip so it shows login side
      window.onload = function() {
        document.getElementById('flip').checked = false;
      };
    </script>
    ";
}
?>

</html>
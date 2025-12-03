<?php
/**
 * Login/Signup Page
 * Redirects to dashboard if already logged in
 */

require_once __DIR__ . '/Helpers/SessionManager.php';
require_once __DIR__ . '/Middleware/AuthMiddleware.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

// Initialize
$session = SessionManager::getInstance();
$auth = new AuthMiddleware();

// Redirect if already authenticated
$auth->redirectIfAuthenticated();

// Display auth errors if any
$authError = $_SESSION['auth_error'] ?? null;
unset($_SESSION['auth_error']);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <title>Login and Registration - Similyze</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <script>
      document.addEventListener("keydown", function(e) {
      // Detect Ctrl + Shift + X for admin key field
      if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === "x") {
        e.preventDefault();
        const adminBox = document.getElementById("admin-key-box");
        
        if (adminBox.style.display === "block") {
          adminBox.style.display = "none";
          document.getElementById("admin_key").value = "";
        } else {
          adminBox.style.display = "block";
          document.getElementById("admin_key").focus();
        }
      }
    });

    // Handle forgot password link
    function showForgotPassword(e) {
      e.preventDefault();
      clearAllForms();
      document.getElementById('flip-forgot').checked = true;
    }

    function backToLogin(e) {
      e.preventDefault();
      clearAllForms();
      document.getElementById('flip-forgot').checked = false;
    }

    // Clear all form fields
    function clearAllForms() {
      // Clear login form
      document.getElementById('login-email').value = '';
      document.getElementById('login-password').value = '';
      document.getElementById('admin_key').value = '';
      document.getElementById('admin-key-box').style.display = 'none';
      
      // Clear signup form
      document.getElementById('signup-name').value = '';
      document.getElementById('signup-email').value = '';
      document.getElementById('signup-mobile').value = '';
      document.getElementById('signup-country').value = '';
      document.getElementById('signup-password').value = '';
      document.getElementById('confirm-password').value = '';
      
      // Uncheck role radio buttons
      const roleRadios = document.querySelectorAll('input[name="role"]');
      roleRadios.forEach(radio => radio.checked = false);
      
      // Clear forgot password form
      document.getElementById('forgot-name').value = '';
      document.getElementById('forgot-email').value = '';
      document.getElementById('forgot-mobile').value = '';
      document.getElementById('forgot-password').value = '';
      document.getElementById('forgot-confirm-password').value = '';
    }

    // Add event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
      // Clear forms when switching from login to signup
      const flipCheckbox = document.getElementById('flip');
      flipCheckbox.addEventListener('change', function() {
        clearAllForms();
      });

      // Clear forms when switching to/from forgot password
      const flipForgotCheckbox = document.getElementById('flip-forgot');
      flipForgotCheckbox.addEventListener('change', function() {
        clearAllForms();
      });
    });
</script>
  </head>
  <body>
    <?php if ($authError): ?>
      <script>
        alert('<?= htmlspecialchars($authError, ENT_QUOTES) ?>');
      </script>
    <?php endif; ?>
    
    <div class="container">
      <input type="checkbox" id="flip">
      <input type="checkbox" id="flip-forgot">
      
      <div class="cover">
        <div class="front">
          <img src="/Plagirism_Detection_System/storage/uploads/frontImg.jpg" alt="Front Image">
          <div class="text">
            <span class="text-1">Every new friend is a <br> new adventure</span>
            <span class="text-2">Let's get connected</span>
          </div>
        </div>
        <div class="back">
          <img class="backImg" src="/Plagirism_Detection_System/storage/uploads/backImg.jpg" alt="Back Image">
          <div class="text">
            <span class="text-1">Complete miles of journey <br> with one step</span>
            <span class="text-2">Let's get started</span>
          </div>
        </div>
      </div>

      <div class="forms">
        <div class="form-content">
          <!-- LOGIN FORM -->
          <div class="login-form">
            <div class="title">Login</div>
            <form action="Controllers/AuthController.php?action=login" method="post">
              <div class="input-boxes">
                <div id="admin-key-box" class="input-box admin-key" style="display:none;">
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
                <div class="text"><a href="#" onclick="showForgotPassword(event)">Forgot password?</a></div>
                <div class="button input-box">
                  <input type="submit" value="Login">
                </div>
                <div class="text sign-up-text">Don't have an account? <label for="flip">Signup now</label></div>
              </div>
            </form>
          </div>

          <!-- SIGNUP FORM -->
          <div class="signup-form">
            <div class="title">Signup</div>
            <form action="Controllers/AuthController.php?action=signup" method="post"> 
              <div class="input-boxes">
                <div class="input-box">
                  <i class="fas fa-user"></i>
                  <input type="text" id="signup-name" name="name" placeholder="Enter your name" required minlength="3">
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
                  <input type="password" id="signup-password" name="password" placeholder="Enter your password" required pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\/])[A-Za-z\d!@#$%^&*\/]{8,}$" title="Must have 8+ chars, uppercase, number, special char">
                </div>
                <div class="input-box">
                  <i class="fas fa-lock"></i>
                  <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required>
                </div>
                <div class="button input-box">
                  <input type="submit" value="Signup">
                </div>
                <div class="text sign-up-text">Already have an account? <label for="flip">Login now</label></div>
              </div>
            </form>
          </div>

          <!-- FORGOT PASSWORD FORM -->
          <div class="forgot-form">
            <div class="title">Reset Password</div>
            <form action="Controllers/AuthController.php?action=forgot_password" method="post">
              <div class="input-boxes">
                <div class="input-box">
                  <i class="fas fa-user"></i>
                  <input type="text" id="forgot-name" name="name" placeholder="Enter your name" required>
                </div>
                <div class="input-box">
                  <i class="fas fa-envelope"></i>
                  <input type="email" id="forgot-email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="input-box">
                  <i class="fas fa-phone"></i>
                  <input type="tel" id="forgot-mobile" name="mobile" placeholder="Enter your mobile number" required pattern="\d{11}">
                </div>
                <div class="input-box">
                  <i class="fas fa-lock"></i>
                  <input type="password" id="forgot-password" name="password" placeholder="Enter new password" required>
                </div>
                <div class="input-box">
                  <i class="fas fa-lock"></i>
                  <input type="password" id="forgot-confirm-password" name="confirm-password" placeholder="Confirm new password" required>
                </div>
                <div class="button input-box">
                  <input type="submit" value="Reset Password">
                </div>
                <div class="text sign-up-text"><a href="#" onclick="backToLogin(event)">Back to Login</a></div>
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
      window.onload = function() {
        document.getElementById('flip').checked = false;
        clearAllForms();
      };
    </script>
    ";
}

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    echo "
    <script>
      alert('Password reset successful! You can now log in with your new password.');
      window.onload = function() {
        document.getElementById('flip').checked = false;
        document.getElementById('flip-forgot').checked = false;
        clearAllForms();
      };
    </script>
    ";
}

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    echo "
    <script>
      alert('You have been logged out successfully.');
      clearAllForms();
    </script>
    ";
}
?>
</html>
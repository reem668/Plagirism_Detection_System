<?php
/**
 * Login/Signup Page
 * Redirects to dashboard if already logged in
 */


require_once __DIR__ . '/app/Helpers/SessionManager.php';
require_once __DIR__ . '/app/Middleware/AuthMiddleware.php';

use Helpers\SessionManager;
use Middleware\AuthMiddleware;

// Define BASE_URL constant
define('BASE_URL', '/Plagirism_Detection_System');

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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <script>
      // Google Sign-In function
      async function signInWithGoogle(context, event) {
        // Prevent form submission if event is provided
        if (event) {
          event.preventDefault();
          event.stopPropagation();
        }
        
        // For signup only, get the selected role from the form
        if (context === 'signup') {
          const roleRadios = document.querySelectorAll('#signup-role-selection input[name="role"]');
          let selectedRole = null;
          
          // Check which role is selected
          roleRadios.forEach(radio => {
            if (radio.checked) {
              selectedRole = radio.value;
            }
          });
          
          // VALIDATION: User MUST select a role before proceeding
          if (!selectedRole) {
            alert('Please select whether you are a Student or Instructor before continuing with Google signup.');
            return false;
          }
          
          // Store role in session via AJAX before redirecting
          try {
            await fetch('<?= BASE_URL ?>/app/Controllers/AuthController.php?action=set_google_role&role=' + selectedRole, {
              method: 'GET',
              credentials: 'same-origin'
            });
            // After setting role, redirect to Google OAuth
            window.location.href = '<?= BASE_URL ?>/app/Controllers/AuthController.php?action=google_auth';
          } catch (error) {
            console.error('Failed to set role:', error);
            alert('Failed to set role. Please try again.');
            return false;
          }
        } else {
          // For login, redirect immediately without setting role
          // No role needed for existing users
          window.location.href = '<?= BASE_URL ?>/app/Controllers/AuthController.php?action=google_auth';
        }
        
        return false; // Prevent any default behavior
      }

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
    
    <!-- 3D Floating Robot Icons Background -->
    <div class="floating-shape shape-1">
      <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <!-- Robot Icon -->
        <rect x="30" y="25" width="40" height="50" rx="5" fill="rgba(59, 130, 246, 0.4)" stroke="rgba(59, 130, 246, 0.6)" stroke-width="2"/>
        <circle cx="42" cy="40" r="4" fill="rgba(30, 58, 138, 0.6)"/>
        <circle cx="58" cy="40" r="4" fill="rgba(30, 58, 138, 0.6)"/>
        <rect x="42" y="50" width="16" height="8" rx="2" fill="rgba(30, 58, 138, 0.5)"/>
        <rect x="20" y="30" width="10" height="20" rx="3" fill="rgba(96, 165, 250, 0.4)" stroke="rgba(59, 130, 246, 0.6)" stroke-width="2"/>
        <rect x="70" y="30" width="10" height="20" rx="3" fill="rgba(96, 165, 250, 0.4)" stroke="rgba(59, 130, 246, 0.6)" stroke-width="2"/>
        <rect x="35" y="75" width="12" height="15" rx="2" fill="rgba(96, 165, 250, 0.4)" stroke="rgba(59, 130, 246, 0.6)" stroke-width="2"/>
        <rect x="53" y="75" width="12" height="15" rx="2" fill="rgba(96, 165, 250, 0.4)" stroke="rgba(59, 130, 246, 0.6)" stroke-width="2"/>
      </svg>
    </div>
    <div class="floating-shape shape-2">
      <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <!-- Document/Scan Icon -->
        <rect x="25" y="20" width="50" height="60" rx="3" fill="none" stroke="rgba(96, 165, 250, 0.6)" stroke-width="3"/>
        <line x1="35" y1="35" x2="65" y2="35" stroke="rgba(59, 130, 246, 0.5)" stroke-width="2"/>
        <line x1="35" y1="45" x2="65" y2="45" stroke="rgba(59, 130, 246, 0.5)" stroke-width="2"/>
        <line x1="35" y1="55" x2="55" y2="55" stroke="rgba(59, 130, 246, 0.5)" stroke-width="2"/>
        <circle cx="70" cy="30" r="8" fill="rgba(147, 197, 253, 0.4)" stroke="rgba(59, 130, 246, 0.6)" stroke-width="2"/>
        <line x1="66" y1="30" x2="74" y2="30" stroke="rgba(30, 58, 138, 0.7)" stroke-width="2"/>
        <line x1="70" y1="26" x2="70" y2="34" stroke="rgba(30, 58, 138, 0.7)" stroke-width="2"/>
      </svg>
    </div>
    <div class="floating-shape shape-3">
      <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <!-- Shield/Check Icon -->
        <path d="M50 20 L35 25 L35 45 Q35 65 50 75 Q65 65 65 45 L65 25 Z" fill="rgba(147, 197, 253, 0.3)" stroke="rgba(59, 130, 246, 0.6)" stroke-width="3"/>
        <path d="M42 50 L48 56 L58 42" stroke="rgba(30, 58, 138, 0.8)" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>
    <div class="floating-shape shape-4">
      <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <!-- Magnifying Glass Icon -->
        <circle cx="40" cy="40" r="20" fill="none" stroke="rgba(59, 130, 246, 0.6)" stroke-width="3"/>
        <line x1="55" y1="55" x2="75" y2="75" stroke="rgba(96, 165, 250, 0.6)" stroke-width="3" stroke-linecap="round"/>
      </svg>
    </div>
    <div class="floating-shape shape-5">
      <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <!-- Code/Scan Icon -->
        <rect x="20" y="30" width="60" height="40" rx="5" fill="none" stroke="rgba(96, 165, 250, 0.6)" stroke-width="3"/>
        <line x1="30" y1="45" x2="70" y2="45" stroke="rgba(59, 130, 246, 0.5)" stroke-width="2"/>
        <line x1="30" y1="55" x2="70" y2="55" stroke="rgba(59, 130, 246, 0.5)" stroke-width="2"/>
        <circle cx="25" cy="40" r="3" fill="rgba(59, 130, 246, 0.6)"/>
        <circle cx="75" cy="60" r="3" fill="rgba(96, 165, 250, 0.6)"/>
      </svg>
    </div>
    
    <div class="container">
      <input type="checkbox" id="flip">
      <input type="checkbox" id="flip-forgot">
      
      <div class="forms-wrapper">
        <div class="forms">
          <!-- LOGIN FORM -->
          <div class="form-content login-form">
            <div class="title">Login</div>
            <p class="subtitle">Welcome back! Please login to your account</p>
              <form action="app/Controllers/AuthController.php?action=login" method="post">
                <div id="admin-key-box" class="input-box admin-key" style="display:none;">
                  <i class="fas fa-key"></i>
                  <input type="text" id="admin_key" name="admin_key" placeholder="Enter Admin Secret Key">
                </div>

                <div class="input-box">
                  <label>Email Address</label>
                  <i class="fas fa-envelope"></i>
                  <input type="email" id="login-email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="input-box">
                  <label>Password</label>
                  <i class="fas fa-lock"></i>
                  <input type="password" id="login-password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="form-options">
                  <label>
                    <input type="checkbox"> Remember me
                  </label>
                  <a href="#" onclick="showForgotPassword(event)">Forgot password?</a>
                </div>
                
                <div class="button">
                  <input type="submit" value="LOGIN">
                </div>
                <div class="divider">
                  <span>OR</span>
                </div>
                <div class="google-signin-wrapper">
                  <button type="button" class="google-signin-btn" onclick="signInWithGoogle('login', event); return false;">
                    <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                      <g fill="#000" fill-rule="evenodd">
                        <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"/>
                        <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.21 1.18-.84 2.18-1.79 2.91l2.78 2.15c1.9-1.75 2.69-4.32 2.69-7.56z" fill="#4285F4"/>
                        <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"/>
                        <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.78-2.15c-.76.53-1.78.9-3.18.9-2.38 0-4.4-1.57-5.12-3.74L.96 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"/>
                      </g>
                    </svg>
                    Continue with Google
                  </button>
                </div>
                <div class="text sign-up-text">Don't have an account? <label for="flip">Signup now</label></div>
              </form>
          </div>

          <!-- SIGNUP FORM -->
          <div class="form-content signup-form">
            <div class="title">Create Account</div>
            <p class="subtitle">Join us to protect academic integrity</p>
              <form action="app/Controllers/AuthController.php?action=signup" method="post"> 
                <div class="input-box">
                  <label>Full Name</label>
                  <i class="fas fa-user"></i>
                  <input type="text" id="signup-name" name="name" placeholder="Enter your name" required minlength="3">
                </div>
                
                <div class="input-box">
                  <label>Email Address</label>
                  <i class="fas fa-envelope"></i>
                  <input type="email" id="signup-email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="input-box">
                  <label>Mobile Number</label>
                  <i class="fas fa-phone"></i>
                  <input type="tel" id="signup-mobile" name="mobile" placeholder="Enter your mobile number" required pattern="\d{11}" title="Please enter a 11-digit mobile number">
                </div>
                
                <div class="input-box select-box">
                  <label>Country</label>
                  <i class="fas fa-globe"></i>
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
                  <label>Role</label>
                  <div class="role-selection" id="signup-role-selection">
                    <div class="role-option">
                      <input type="radio" id="signup-role-student" name="role" value="student" required>
                      <label for="signup-role-student">Student</label>
                    </div>
                    <div class="role-option">
                      <input type="radio" id="signup-role-instructor" name="role" value="instructor" required>
                      <label for="signup-role-instructor">Instructor</label>
                    </div>
                  </div>
                </div>
                
                <div class="input-box">
                  <label>Password</label>
                  <i class="fas fa-lock"></i>
                  <input type="password" id="signup-password" name="password" placeholder="Enter your password" required pattern="^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\/])[A-Za-z\d!@#$%^&*\/]{8,}$" title="Must have 8+ chars, uppercase, number, special char">
                </div>
                
                <div class="input-box">
                  <label>Confirm Password</label>
                  <i class="fas fa-lock"></i>
                  <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required>
                </div>
                
                <div class="button">
                  <input type="submit" value="SIGNUP">
                </div>
                <div class="divider">
                  <span>OR</span>
                </div>
                <div class="google-signin-wrapper">
                  <button type="button" class="google-signin-btn" onclick="signInWithGoogle('signup', event); return false;">
                    <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                      <g fill="#000" fill-rule="evenodd">
                        <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"/>
                        <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.21 1.18-.84 2.18-1.79 2.91l2.78 2.15c1.9-1.75 2.69-4.32 2.69-7.56z" fill="#4285F4"/>
                        <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"/>
                        <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.78-2.15c-.76.53-1.78.9-3.18.9-2.38 0-4.4-1.57-5.12-3.74L.96 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"/>
                      </g>
                    </svg>
                    Continue with Google
                  </button>
                </div>
                <div class="text sign-up-text">Already have an account? <label for="flip">Login now</label></div>
              </form>
          </div>

          <!-- FORGOT PASSWORD FORM -->
          <div class="form-content forgot-form">
            <div class="title">Reset Password</div>
            <p class="subtitle">Enter your details to reset your password</p>
              <form action="app/Controllers/AuthController.php?action=forgot_password" method="post">
                <div class="input-box">
                  <label>Full Name</label>
                  <i class="fas fa-user"></i>
                  <input type="text" id="forgot-name" name="name" placeholder="Enter your name" required>
                </div>
                
                <div class="input-box">
                  <label>Email Address</label>
                  <i class="fas fa-envelope"></i>
                  <input type="email" id="forgot-email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="input-box">
                  <label>Mobile Number</label>
                  <i class="fas fa-phone"></i>
                  <input type="tel" id="forgot-mobile" name="mobile" placeholder="Enter your mobile number" required pattern="\d{11}">
                </div>
                
                <div class="input-box">
                  <label>New Password</label>
                  <i class="fas fa-lock"></i>
                  <input type="password" id="forgot-password" name="password" placeholder="Enter new password" required>
                </div>
                
                <div class="input-box">
                  <label>Confirm New Password</label>
                  <i class="fas fa-lock"></i>
                  <input type="password" id="forgot-confirm-password" name="confirm-password" placeholder="Confirm new password" required>
                </div>
                
                <div class="button">
                  <input type="submit" value="RESET PASSWORD">
                </div>
                
                <div class="text sign-up-text"><a href="#" onclick="backToLogin(event)">Back to Login</a></div>
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
<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); 
ini_set('session.use_strict_mode', 1);
session_set_cookie_params([
    'lifetime' => 86400, // 1 day
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();
require 'Admin/includes/db.php';
require_once 'Admin/includes/mailer.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$login_error = '';
$register_error = '';
$register_success = '';
$flip_to_register = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login_submit'])) {
        $identifier = trim($_POST['identifier']);
        $password = $_POST['password'];

        if (empty($identifier) || empty($password)) {
            $login_error = 'Please fill in all fields.';
        } else {
            // First, try admin login by username
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$identifier]);
            $admin = $stmt->fetch();

            if ($admin) {
                if (password_verify($password, $admin['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION = [
                        'user_id' => $admin['id'],
                        'username' => $admin['username'],
                        'role' => 'admin',
                        'logged_in' => true
                    ];
                    header('Location: Admin/index.php');  // redirect to admin dashboard
                    exit();
                } else {
                    $login_error = 'Invalid password.';
                }
            } else {
                // If not admin, try user login by email
                $cleanEmail = strtolower(preg_replace('/\s+/', '', $identifier));
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$cleanEmail]);
                $user = $stmt->fetch();

                if ($user) {
                    if (password_verify($password, $user['password_hash'])) {
                        session_regenerate_id(true);
                        $_SESSION = [
                            'user_id' => $user['id'],
                            'username' => $user['name'],
                            'email' => $user['email'],
                            'role' => 'user',
                            'logged_in' => true
                        ];
                        header('Location: index.php');
                        exit();
                    } else {
                        $login_error = 'Invalid password.';
                    }
                } else {
                    $login_error = 'Account not found. Please check your credentials.';
                }
            }
        }
    } elseif (isset($_POST['register_submit'])) {
        // REGISTER FORM PROCESSING
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        $flip_to_register = true;

        // Validate inputs
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            $register_error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $register_error = 'Invalid email address.';
        } elseif ($password !== $confirm_password) {
            $register_error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $register_error = 'Password must be at least 8 characters.';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $register_error = 'Email is already registered.';
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$name, $email, $password_hash])) {
                    // Send welcome email
                    $subject = "Welcome to Lumière!";
                    $htmlBody = "
                        <h1>Welcome, " . htmlspecialchars($name) . "!</h1>
                        <p>Thank you for registering at Lumière.</p>
                        <p>Start exploring our collection today!</p>
                        <br>
                        <p>Best regards,<br>Lumière Team</p>
                    ";
                    $plainBody = "Welcome, $name!\n\nThank you for registering at Lumière.\n\nStart exploring our collection today!\n\nBest regards,\nLumière Team";

                    sendEmail($email, $name, $subject, $htmlBody, $plainBody);

                    // Auto-login after registration
                    $userId = $pdo->lastInsertId();
                    session_regenerate_id(true);
                    $_SESSION = [
                        'user_id' => $userId,
                        'username' => $name,
                        'email' => $email,
                        'role' => 'user',
                        'logged_in' => true
                    ];
                    
                    header('Location: index.php');
                    exit();
                } else {
                    $register_error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Lumière - Login / Register</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
  .form-container {
    perspective: 1000px;
    height: 100%;
    position: relative;
  }
  
  .form-flip {
    transform-style: preserve-3d;
    transition: transform 0.6s ease-in-out;
    height: 100%;
    position: relative;
  }
  
  .form-flip.flipped {
    transform: rotateY(180deg);
  }
  
  .form-front, .form-back {
    backface-visibility: hidden;
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 1.5rem;
  }
  
  .form-back {
    transform: rotateY(180deg);
  }
  
  .input-field:focus + label, 
  .input-field:not(:placeholder-shown) + label {
    transform: translateY(-24px) scale(0.8);
    color: #6366f1;
  }
  
  .gradient-bg {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }
  
  /* Prevent scroll during animation */
  body.form-animating {
    overflow: hidden;
  }
  
  /* Mobile adjustments */
  @media (max-width: 767px) {
    .form-front, .form-back {
      padding: 1rem;
      justify-content: flex-start;
      padding-top: 2rem;
    }
    
    .branding-section {
      display: none !important;
    }
  }
  
  /* Desktop adjustments */
  @media (min-width: 768px) {
    .form-front, .form-back {
      padding: 3rem;
      justify-content: center;
    }
    
    .mobile-branding {
      display: none !important;
    }
  }
</style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-md md:max-w-6xl flex flex-col md:flex-row rounded-2xl overflow-hidden shadow-2xl mx-auto bg-white min-h-[600px]">
    
    <!-- Branding Section (Desktop only) -->
    <div class="branding-section hidden md:flex gradient-bg text-white p-12 flex-1 flex-col justify-center items-center text-center">
      <div class="mb-8">
        <h1 class="text-4xl md:text-5xl font-bold mb-2">LUMIÈRE</h1>
        <p class="text-lg md:text-xl opacity-90">Illuminate Your Style</p>
      </div>
      <div class="w-32 h-32 sm:w-48 sm:h-48 md:w-64 md:h-64 relative mb-8 mx-auto">
        <div class="absolute inset-0 bg-white bg-opacity-10 rounded-full blur-xl"></div>
        <div class="absolute inset-4 bg-white bg-opacity-20 rounded-full blur-md"></div>
        <div class="relative z-10 w-full h-full flex items-center justify-center">
          <i class="fas fa-gem text-4xl sm:text-5xl md:text-7xl text-white opacity-90"></i>
        </div>
      </div>
      <p class="max-w-md opacity-80 text-sm md:text-base mx-auto">
        Discover our exclusive collection of premium fashion and accessories. 
        Join our community of style enthusiasts today.
      </p>
    </div>
    
    <!-- Form Section -->
    <div class="flex-1 relative">
      <div class="form-container w-full h-full">
        <div class="form-flip <?= $flip_to_register ? 'flipped' : '' ?>">
          
          <!-- Login Form -->
          <div class="form-front">
            <div class="max-w-md mx-auto w-full">
              <!-- Mobile Branding -->
              <div class="mobile-branding md:hidden mb-6 text-center">
                <h1 class="text-3xl font-bold text-gray-800 mb-1">LUMIÈRE</h1>
                <p class="text-gray-600">Illuminate Your Style</p>
              </div>
              <p class="text-gray-600 mb-6 md:mb-8 text-sm md:text-base">Sign in to access your account</p>
              
              <?php if ($login_error): ?>
                <p class="text-red-600 mb-4"><?= htmlspecialchars($login_error) ?></p>
              <?php endif; ?>
              
              <form method="POST" novalidate>
                <div class="space-y-4 md:space-y-6">
                  <div class="relative">
                    <input 
                      type="text" 
                      id="identifier" 
                      name="identifier" 
                      class="input-field w-full px-4 py-3 border-b-2 border-gray-300 focus:border-indigo-500 outline-none transition bg-transparent" 
                      placeholder=" " 
                      required 
                      autocomplete="username"
                      value="<?= isset($_POST['identifier']) && !$flip_to_register ? htmlspecialchars($_POST['identifier']) : '' ?>"
                    />
                    <label for="identifier" class="absolute left-4 top-3 text-gray-500 transition-all duration-300 pointer-events-none">Email </label>
                  </div>
                  
                  <div class="relative">
                    <input 
                      type="password" 
                      id="password" 
                      name="password" 
                      class="input-field w-full px-4 py-3 border-b-2 border-gray-300 focus:border-indigo-500 outline-none transition bg-transparent" 
                      placeholder=" " 
                      required 
                      autocomplete="current-password"
                    />
                    <label for="password" class="absolute left-4 top-3 text-gray-500 transition-all duration-300 pointer-events-none">Password</label>
                  </div>
                  
                  <button type="submit" name="login_submit" class="w-full gradient-bg text-white py-3 px-4 rounded-lg font-medium hover:opacity-90 transition shadow-lg">
                    Sign In
                  </button>
                  
                  <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                      <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                      <span class="px-2 bg-white text-gray-500">Or continue with</span>
                    </div>
                  </div>
                  
                  <div class="grid grid-cols-3 gap-3">
                    <!-- Google Sign-In Button -->
                    <div id="g_id_onload" data-client_id="<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>"
                    data-login_uri="http://localhost/E-shop/google-callback.php"
                    data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin" data-type="standard"></div>
                  </div>
                  
                  <p class="text-center text-sm text-gray-600">
                    Don't have an account? 
                    <button type="button" onclick="flipForm()" class="text-indigo-600 font-medium hover:text-indigo-500">Sign up</button>
                  </p>
                </div>
              </form>
            </div>
          </div>
          
          <!-- Register Form -->
          <div class="form-back">
            <div class="max-w-md mx-auto w-full">
              <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">Create Account</h2>
              <p class="text-gray-600 mb-6 md:mb-8 text-sm md:text-base">Join Lumière to start shopping</p>
              
              <?php if ($register_error): ?>
                <p class="text-red-600 mb-4"><?= htmlspecialchars($register_error) ?></p>
              <?php elseif ($register_success): ?>
                <p class="text-green-600 mb-4"><?= $register_success ?></p>
              <?php endif; ?>
              
              <form method="POST" novalidate>
                <div class="space-y-4 md:space-y-6">
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="relative">
                      <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="input-field w-full px-4 py-3 border-b-2 border-gray-300 focus:border-indigo-500 outline-none transition bg-transparent" 
                        placeholder=" " 
                        required 
                        autocomplete="given-name"
                        value="<?= isset($_POST['name']) && $flip_to_register ? htmlspecialchars($_POST['name']) : '' ?>"
                      />
                      <label for="name" class="absolute left-4 top-3 text-gray-500 transition-all duration-300 pointer-events-none">Full Name</label>
                    </div>
                    <div class="relative">
                      <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="input-field w-full px-4 py-3 border-b-2 border-gray-300 focus:border-indigo-500 outline-none transition bg-transparent" 
                        placeholder=" " 
                        required 
                        autocomplete="email"
                        value="<?= isset($_POST['email']) && $flip_to_register ? htmlspecialchars($_POST['email']) : '' ?>"
                      />
                      <label for="email" class="absolute left-4 top-3 text-gray-500 transition-all duration-300 pointer-events-none">Email Address</label>
                    </div>
                  </div>
                  
                  <div class="relative">
                    <input 
                      type="password" 
                      id="password" 
                      name="password" 
                      class="input-field w-full px-4 py-3 border-b-2 border-gray-300 focus:border-indigo-500 outline-none transition bg-transparent" 
                      placeholder=" " 
                      required 
                      autocomplete="new-password"
                    />
                    <label for="password" class="absolute left-4 top-3 text-gray-500 transition-all duration-300 pointer-events-none">Password</label>
                  </div>
                  
                  <div class="relative">
                    <input 
                      type="password" 
                      id="confirm_password" 
                      name="confirm_password" 
                      class="input-field w-full px-4 py-3 border-b-2 border-gray-300 focus:border-indigo-500 outline-none transition bg-transparent" 
                      placeholder=" " 
                      required 
                      autocomplete="new-password"
                    />
                    <label for="confirm_password" class="absolute left-4 top-3 text-gray-500 transition-all duration-300 pointer-events-none">Confirm Password</label>
                  </div>
                  
                  <div class="flex items-start">
                    <input type="checkbox" id="terms" name="terms" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mt-1" required />
                    <label for="terms" class="ml-2 block text-sm text-gray-700">
                      I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-500">Terms</a> and <a href="#" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a>
                    </label>
                  </div>
                  
                  <button type="submit" name="register_submit" class="w-full gradient-bg text-white py-3 px-4 rounded-lg font-medium hover:opacity-90 transition shadow-lg">
                    Sign Up
                  </button>
                  
                  <p class="text-center text-sm text-gray-600">
                    Already have an account? 
                    <button type="button" onclick="flipForm()" class="text-indigo-600 font-medium hover:text-indigo-500">Sign in</button>
                  </p>
                </div>
              </form>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  </div>
  
  <script>
    function flipForm() {
      const flipContainer = document.querySelector('.form-flip');
      const body = document.body;
      
      // Prevent body scroll during animation
      body.classList.add('form-animating');
      
      flipContainer.classList.toggle('flipped');
      
      setTimeout(() => {
        body.classList.remove('form-animating');
      }, 600);
    }
    
    // Automatically flip to register form if PHP set flag
    <?php if ($flip_to_register): ?>
      document.addEventListener('DOMContentLoaded', () => {
        const flipContainer = document.querySelector('.form-flip');
        if (!flipContainer.classList.contains('flipped')) {
          flipContainer.classList.add('flipped');
        }
      });
    <?php endif; ?>
    
    // Floating label effect
    document.querySelectorAll('.input-field').forEach(input => {
      input.addEventListener('focus', function() {
        this.nextElementSibling.classList.add('text-indigo-500');
      });
      
      input.addEventListener('blur', function() {
        if (!this.value) {
          this.nextElementSibling.classList.remove('text-indigo-500');
        }
      });
      
      // Trigger label float if input has value on page load
      if (input.value) {
        input.nextElementSibling.classList.add('text-indigo-500');
      }
    });
  </script>
  
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</body>
</html>

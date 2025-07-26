<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.tailwindcss.com https://accounts.google.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com; frame-src https://accounts.google.com; upgrade-insecure-requests;");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
// Secure session cookie params - adjust domain for production
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); 
ini_set('session.use_strict_mode', 1);
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'], 
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}
// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting helpers
function loginRateLimitExceeded($maxAttempts = 5, $decaySeconds = 300) {
    $now = time();
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_attempt_start'] = $now;
        return false;
    }

    // Reset count if decay time passed
    if ($now - $_SESSION['login_attempt_start'] > $decaySeconds) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_attempt_start'] = $now;
        return false;
    }

    return $_SESSION['login_attempts'] >= $maxAttempts;
}

function recordLoginAttempt() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_attempt_start'] = time();
    }
    $_SESSION['login_attempts']++;
}

require 'Admin/includes/db.php';
require_once 'Admin/includes/mailer.php';

// Error handling for production (no output, log errors)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

$login_error = '';
$register_error = '';
$register_success = '';
$flip_to_register = false;

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('Invalid CSRF token.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    if (isset($_POST['login_submit'])) {
        // Rate limiting check
        if (loginRateLimitExceeded()) {
            $login_error = 'Too many login attempts. Please try again after a few minutes.';
        } else {
            $identifier = trim($_POST['identifier']);
            $password = $_POST['password'];

            if (empty($identifier) || empty($password)) {
                $login_error = 'Please fill in all fields.';
            } else {
                // Try admin login
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
                        // Reset login attempts
                        $_SESSION['login_attempts'][$_SERVER['REMOTE_ADDR']] = [
                          'count' => 0,
                          'start' => time()
                        ];
                        header('Location: https://' . $_SERVER['HTTP_HOST'] . '/Admin/index.php');
                        exit();
                    } else {
                        recordLoginAttempt();
                        $login_error = 'Invalid password.';
                    }
                } else {
                    // User login by email
                    $cleanEmail = strtolower(preg_replace('/\s+/', '', $identifier));
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                    $stmt->execute([$cleanEmail]);
                    $user = $stmt->fetch();

                    if ($user) {
                        if (!$user['email_verified']) {
                            $login_error = 'Please verify your email before logging in.';
                        } elseif (password_verify($password, $user['password_hash'])) {
                            session_regenerate_id(true);
                            $_SESSION = [
                                'user_id' => $user['id'],
                                'username' => $user['name'],
                                'email' => $user['email'],
                                'role' => 'user',
                                'logged_in' => true
                            ];
                            // Reset login attempts
                            $_SESSION['login_attempts'] = 0;
                            header('Location: https://' . $_SERVER['HTTP_HOST'] . '/index.php');//for production
                            exit();
                        } else {
                            recordLoginAttempt();
                            $login_error = 'Invalid password.';
                        }
                    } else {
                        recordLoginAttempt();
                        $login_error = 'Account not found. Please check your credentials.';
                    }
                }
            }
        }

    } elseif (isset($_POST['register_submit'])) {
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
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $register_error = 'Email is already registered.';
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $verification_token = bin2hex(random_bytes(32));

                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, email_verified, email_verification_token) VALUES (?, ?, ?, 0, ?)");
                if ($stmt->execute([$name, $email, $password_hash, $verification_token])) {
                    // Email verification
                    $verifyUrl = "https://" . $_SERVER['HTTP_HOST'] . "/verify_email.php?token=$verification_token";
                    $subject = "Verify Your Email - Lumière";
                    $htmlBody = "
                        <h1>Hello, " . htmlspecialchars($name) . "!</h1>
                        <p>Thanks for registering at Lumière. Please verify your email by clicking the link below:</p>
                        <p><a href='$verifyUrl'>$verifyUrl</a></p>
                        <p>If you didn't register, ignore this email.</p>
                        <br>
                        <p>Best Regards,<br>Lumière Team</p>";
                    $plainBody = "Hello, $name!\n\nThanks for registering at Lumière. Please verify your email:\n$verifyUrl\n\nIf you didn't register, ignore this email.\n\nBest Regards,\nLumière Team";


                    sendEmail($email, $name, $subject, $htmlBody, $plainBody);


                    $register_success = 'Registration successful! Please check your email to verify your account.';
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

    <h4 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">Welcome back!</h4>
    <p class="text-gray-600 mb-6 md:mb-8 text-sm md:text-base">Sign in to access your account</p>
    
    <?php if ($login_error): ?>
      <p class="text-red-600 mb-4"><?= htmlspecialchars($login_error) ?></p>
    <?php endif; ?>

    <form method="POST" action="https://<?= $_SERVER['HTTP_HOST'] ?><?= $_SERVER['REQUEST_URI'] ?>" novalidate>
      <!-- CSRF Token input (must always be included) -->
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

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
          <label for="identifier" class="absolute left-4 top-3 text-gray-500 transition-all duration-300 pointer-events-none">Email</label>
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
          <button type="button" 
            class="absolute right-3 top-3 text-gray-400 hover:text-indigo-600 focus:outline-none" 
            aria-label="Toggle password visibility" 
            data-toggle-password="password">
            <i class="fas fa-eye"></i>
          </button>
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
                    data-login_uri="https://<?= htmlspecialchars($_SERVER['HTTP_HOST']) ?>/google-callback.php"
                    data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin" data-type="standard"></div>
                  </div>
                  
        <p class="text-center text-sm text-gray-600">
          Don't have an account? 
          <button type="button" data-action="flip" class="text-indigo-600 font-medium hover:text-indigo-500">Sign up</button>
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

    <form method="POST" action="https://<?= $_SERVER['HTTP_HOST'] ?><?= $_SERVER['REQUEST_URI'] ?>" novalidate>
      <!-- CSRF Token -->
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

      <div class="space-y-4 md:space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="relative">
            <input 
              type="text" 
              id="name" 
              name="name" 
              required 
              autocomplete="given-name"
              class="input-field w-full px-4 py-3 border-b-2 border-gray-300 focus:border-indigo-500 outline-none transition bg-transparent" 
              placeholder=" " 
              value="<?= isset($_POST['name']) && $flip_to_register ? htmlspecialchars($_POST['name']) : '' ?>" 
            />
            <label for="name" class="absolute left-4 top-3 text-gray-500 transition-all duration-300 pointer-events-none">Full Name</label>
          </div>
          <div class="relative">
            <input 
              type="email" 
              id="email" 
              name="email" 
              required 
              autocomplete="email"
              class="input-field w-full px-4 py-3 border-b-2 border-gray-300 focus:border-indigo-500 outline-none transition bg-transparent" 
              placeholder=" " 
              value="<?= isset($_POST['email']) && $flip_to_register ? htmlspecialchars($_POST['email']) : '' ?>" 
            />
            <label for="email" class="absolute left-4 top-3 text-gray-500 transition-all duration-300 pointer-events-none">Email Address</label>
          </div>
        </div>

        <div class="relative">
  <input 
    type="password" 
    id="register_password" 
    name="password" 
    required 
    autocomplete="new-password"
    class="input-field w-full px-4 py-3 pr-10 border-b-2 border-gray-300 focus:border-indigo-500 outline-none transition bg-transparent" 
    placeholder=" " 
  />
  <label for="register_password" class="absolute left-4 top-3 text-gray-500 transition-all duration-300 pointer-events-none">Password</label>

  <button type="button" 
        class="absolute right-3 top-3 text-gray-400 hover:text-indigo-600 focus:outline-none" 
        aria-label="Toggle password visibility" 
        data-toggle-password="register_password">
  <i class="fas fa-eye"></i>
</button>

</div>

<div class="relative">
  <input 
    type="password" 
    id="register_confirm_password" 
    name="confirm_password" 
    required 
    autocomplete="new-password"
    class="input-field w-full px-4 py-3 pr-10 border-b-2 border-gray-300 focus:border-indigo-500 outline-none transition bg-transparent" 
    placeholder=" " 
  />
  <label for="register_confirm_password" class="absolute left-4 top-3 text-gray-500 transition-all duration-300 pointer-events-none">Confirm Password</label>

  <button type="button" 
        class="absolute right-3 top-3 text-gray-400 hover:text-indigo-600 focus:outline-none" 
        aria-label="Toggle confirm password visibility" 
        data-toggle-password="register_confirm_password">
  <i class="fas fa-eye"></i>
</button>

</div>

        <button type="submit" name="register_submit" class="w-full gradient-bg text-white py-3 px-4 rounded-lg font-medium hover:opacity-90 transition shadow-lg">Sign Up</button>

        <p class="text-center text-sm text-gray-600">
          Already have an account? 
          <button type="button" data-action="flip" class="text-indigo-600 font-medium hover:text-indigo-500">Sign in</button>
        </p>
      </div>
    </form>
  </div>
</div>

      
      </div>
    </div>
  </div>
</div>

<script src="./assets/auth.js"></script>

<script src="https://accounts.google.com/gsi/client" async defer></script>
</body>
</html>
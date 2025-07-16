<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();
require 'includes/mailer.php';

if (!isset($_GET['user_id']) && !isset($_POST['user_id'])) {
    header('Location: customers.php');
    exit();
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)$_POST['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die('User not found.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$subject || !$message) {
        $error = 'Please fill in both subject and message.';
    } else {
        $toEmail = $user['email'];
        $toName = $user['name'];
        $htmlBody = nl2br(htmlspecialchars($message));
        $plainBody = strip_tags($htmlBody);

        $sent = sendEmail($toEmail, $toName, $subject, $htmlBody, $plainBody);

        if ($sent) {
            $success = 'Email sent successfully to ' . htmlspecialchars($toEmail);
        } else {
            $error = 'Failed to send email. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" >
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Send Message - <?= htmlspecialchars($user['name']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-50 min-h-screen flex flex-col items-center justify-center p-6">
  <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-lg">
    <h1 class="text-xl font-bold mb-4">Send Message to <?= htmlspecialchars($user['name']) ?></h1>
    <?php if ($error): ?>
      <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="user_id" value="<?= $userId ?>" />
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700" for="to">To</label>
        <input type="email" id="to" name="to" readonly value="<?= htmlspecialchars($user['email']) ?>" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 cursor-not-allowed" />
      </div>
      <div class="mb-4">
        <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
        <input type="text" id="subject" name="subject" required class="mt-1 block w-full rounded-md border-gray-300" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" />
      </div>
      <div class="mb-4">
        <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
        <textarea id="message" name="message" rows="6" required class="mt-1 block w-full rounded-md border-gray-300"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
      </div>
      <div class="flex justify-end space-x-2">
        <a href="customer_details.php?id=<?= $userId ?>" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-100">Cancel</a>
        <button type="submit" class="px-4 py-2 bg-phoenix-primary text-white rounded-md hover:bg-phoenix-primary-dark">
          Send
        </button>
      </div>
    </form>
  </div>
</body>
</html>

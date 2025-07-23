<?php
$pageTitle = "Contact Lumière";
$pageHeading = "Let's Connect";
$searchQuery = '';
$products = [];

$success_message = '';
$error_message = '';
$name = '';
$email = '';
$message = '';
$phone = '';
$subject = 'General Inquiry';

require_once 'Admin/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? 'General Inquiry');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$message) {
        $error_message = "Please fill in all required fields correctly.";
    } else {
        // Prepare email
        $recipient = 'support@lumiere.com';
        $email_subject = "[Contact Form] $subject - From $name";

        // Compose HTML email body
        $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #6d28d9;'>New contact form submission</h2>
            <div style='background: #f8fafc; padding: 20px; border-radius: 8px;'>
                <p><strong style='color: #334155;'>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                <p><strong style='color: #334155;'>Name:</strong> " . htmlspecialchars($name) . "</p>
                <p><strong style='color: #334155;'>Email:</strong> " . htmlspecialchars($email) . "</p>
                <p><strong style='color: #334155;'>Phone:</strong> " . htmlspecialchars($phone) ?: 'Not provided' . "</p>
                <p><strong style='color: #334155;'>Message:</strong></p>
                <div style='background: white; padding: 15px; border-radius: 4px; border-left: 4px solid #6d28d9;'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
            </div>
            <p style='margin-top: 20px; font-size: 0.9em; color: #64748b;'>
                This message was sent from the contact form on Lumière's website.
            </p>
        </div>
        ";

        // Compose plain text fallback body
        $plainBody = "New contact form submission\n"
                   . "Subject: $subject\n"
                   . "Name: $name\n"
                   . "Email: $email\n"
                   . "Phone: " . ($phone ?: 'Not provided') . "\n"
                   . "Message:\n$message";

        // Send mail using your mailer function
        // $emailSent = sendEmail($recipient, 'Lumière Support', $email_subject, $htmlBody, $plainBody, $email);

        if ($emailSent) {
            $success_message = "Thank you for your message! Our team will get back to you within 24 hours.";
            // Clear form fields after success
            $name = $email = $phone = $message = '';
            $subject = 'General Inquiry';
        } else {
            $error_message = "Sorry, we encountered an error while sending your message. Please try again or email us directly at support@lumiere.com.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $pageTitle ?> - Lumière</title>
    <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  />
  <link rel="stylesheet" href="index.css" />

</head>
<body class="bg-gray-50 font-sans">
  <?php include 'header.php'; ?>
<!-- HTML Section -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Contact Information -->
        <div class="space-y-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Our Office</h2>
                <p class="mt-2 text-lg text-gray-600">We'd love to hear from you. Here's how you can reach us.</p>
            </div>

            <div class="space-y-6">
                <!-- Address -->
                <div class="flex items-start">
                    <div class="flex-shrink-0 bg-purple-100 p-3 rounded-lg">
                        <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Headquarters</h3>
                        <p class="mt-1 text-gray-600">123 Fashion Avenue</p>
                        <p class="text-gray-600">Suite 500</p>
                        <p class="text-gray-600">New York, NY 10001</p>
                    </div>
                </div>

                <!-- Contact -->
                <div class="flex items-start">
                    <div class="flex-shrink-0 bg-purple-100 p-3 rounded-lg">
                        <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Contact</h3>
                        <p class="mt-1 text-gray-600">+1 (555) 123-4567</p>
                        <p class="text-gray-600">support@lumiere.com</p>
                    </div>
                </div>

                <!-- Hours -->
                <div class="flex items-start">
                    <div class="flex-shrink-0 bg-purple-100 p-3 rounded-lg">
                        <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Customer Support Hours</h3>
                        <p class="mt-1 text-gray-600">Monday - Friday: 9am - 6pm EST</p>
                        <p class="text-gray-600">Saturday: 10am - 4pm EST</p>
                        <p class="text-gray-600">Sunday: Closed</p>
                    </div>
                </div>
            </div>

            <!-- Social Media -->
            <div class="pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Follow Us</h3>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-500 hover:text-purple-600">
                        <span class="sr-only">Facebook</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-purple-600">
                        <span class="sr-only">Instagram</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-purple-600">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Map -->
            <div class="pt-8">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15858.492246075377!2d3.467291593551617!3d6.442423803232426!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x103bf53ec97e64c3%3A0x6554b322f2ccefa6!2sZedluxe%20Originals!5e0!3m2!1sen!2sng!4v1753260398727!5m2!1sen!2sng" 
                    width="600" 
                    height="450" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="bg-white shadow-lg rounded-lg p-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Send us a message</h2>
            
            <?php if ($success_message): ?>
                <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-lg">
                    <?= $success_message ?>
                </div>
            <?php elseif ($error_message): ?>
                <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg">
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <form action="/contact" method="POST" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full name *</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="<?= htmlspecialchars($name) ?>" 
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm p-3 border"
                            placeholder="John Doe">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email address *</label>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            value="<?= htmlspecialchars($email) ?>" 
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm p-3 border"
                            placeholder="you@example.com">
                    </div>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone number</label>
                    <input 
                        type="tel" 
                        name="phone" 
                        id="phone" 
                        value="<?= htmlspecialchars($phone) ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm p-3 border"
                        placeholder="(123) 456-7890">
                </div>

                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">Subject *</label>
                    <select 
                        id="subject" 
                        name="subject" 
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm p-3 border">
                        <option value="General Inquiry" <?= $subject === 'General Inquiry' ? 'selected' : '' ?>>General Inquiry</option>
                        <option value="Order Support" <?= $subject === 'Order Support' ? 'selected' : '' ?>>Order Support</option>
                        <option value="Product Questions" <?= $subject === 'Product Questions' ? 'selected' : '' ?>>Product Questions</option>
                        <option value="Returns & Exchanges" <?= $subject === 'Returns & Exchanges' ? 'selected' : '' ?>>Returns & Exchanges</option>
                        <option value="Wholesale Inquiry" <?= $subject === 'Wholesale Inquiry' ? 'selected' : '' ?>>Wholesale Inquiry</option>
                        <option value="Other" <?= $subject === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700">Message *</label>
                    <textarea 
                        id="message" 
                        name="message" 
                        rows="5" 
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm p-3 border"
                        placeholder="How can we help you?"><?= htmlspecialchars($message) ?></textarea>
                </div>

                <div class="flex items-center">
                    <input 
                        id="privacy-policy" 
                        name="privacy-policy" 
                        type="checkbox" 
                        required
                        class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                    <label for="privacy-policy" class="ml-2 block text-sm text-gray-700">
                        I agree to the <a href="/privacy" class="text-purple-600 hover:text-purple-500">privacy policy</a> and <a href="/terms" class="text-purple-600 hover:text-purple-500">terms of service</a>.
                    </label>
                </div>

                <div>
                    <button 
                        type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200">
                        Send Message
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-8 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Prefer email?</h3>
                <p class="mt-2 text-gray-600">
                    You can email us directly at <a href="mailto:support@lumiere.com" class="text-purple-600 hover:text-purple-500">support@lumiere.com</a>.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Frequently asked questions</h2>
            <p class="mt-4 max-w-2xl text-xl text-gray-600 mx-auto">
                Can't find what you're looking for? <a href="#contact-form" class="text-purple-600 hover:text-purple-500">Contact us</a>.
            </p>
        </div>

        <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <!-- FAQ Item 1 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900">How long does shipping take?</h3>
                <p class="mt-2 text-gray-600">
                    Standard shipping takes 3-5 business days within the continental US. Expedited options are available at checkout.
                </p>
            </div>
            
            <!-- FAQ Item 2 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900">What's your return policy?</h3>
                <p class="mt-2 text-gray-600">
                    We accept returns within 30 days of purchase. Items must be unused with original tags. See our full return policy for details.
                </p>
            </div>
            
            <!-- FAQ Item 3 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900">Do you offer international shipping?</h3>
                <p class="mt-2 text-gray-600">
                    Yes! We ship to over 50 countries worldwide. International shipping rates and times vary by destination.
                </p>
            </div>
            
            <!-- FAQ Item 4 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900">How can I track my order?</h3>
                <p class="mt-2 text-gray-600">
                    Once your order ships, you'll receive a tracking number via email. You can also check order status in your account.
                </p>
            </div>
            
            <!-- FAQ Item 5 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900">Do you have a physical store?</h3>
                <p class="mt-2 text-gray-600">
                    Our flagship store is located at 123 Fashion Avenue in New York. We're open Monday-Saturday 10am-8pm.
                </p>
            </div>
            
            <!-- FAQ Item 6 -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900">Can I modify or cancel my order?</h3>
                <p class="mt-2 text-gray-600">
                    Orders can be modified or canceled within 1 hour of placement. After that, contact us immediately and we'll try to accommodate.
                </p>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>
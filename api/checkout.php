<?php
session_start();
require_once '../Admin/includes/db.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth.php?redirect=../api/checkout.php');
    exit;
}

$stmt = $pdo->prepare("SELECT name, phone, address1, address2, city, state, country, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$userEmail = $user['email'] ?? '';

$cartToken = $_COOKIE['cart_token'] ?? null;
if (!$cartToken) {
    echo "Your cart is empty.";
    exit;
}

// Fetch cart items
$stmt = $pdo->prepare("
    SELECT ci.product_id, ci.quantity, p.name, p.price, p.image
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    JOIN carts c ON ci.cart_id = c.id
    WHERE c.cart_token = ?
");
$stmt->execute([$cartToken]);
$cartItems = $stmt->fetchAll();

if (!$cartItems) {
    echo "Your cart is empty.";
    exit;
}

$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Fetch distinct deliverable countries
$countries = $pdo->query("SELECT DISTINCT country FROM shipping_zones WHERE active=1 ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);

$selectedCountry = $_GET['country'] ?? ($user['country'] ?? $countries[0] ?? '');

$stmtStates = $pdo->prepare("SELECT state FROM shipping_zones WHERE country=? AND active=1 ORDER BY state");
$stmtStates->execute([$selectedCountry]);
$states = $stmtStates->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Checkout - Lumière</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://js.paystack.co/v1/inline.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-gray-50 font-sans">

<main class="container mx-auto px-4 py-12 max-w-5xl">
    <h1 class="text-4xl font-extrabold mb-10 text-center text-gray-900">Checkout</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <!-- Cart Summary -->
        <section class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-semibold mb-6 border-b pb-2">Your Cart</h2>
            <ul class="divide-y divide-gray-200 mb-6">
                <?php foreach ($cartItems as $item): ?>
                    <li class="flex justify-between py-3 items-center">
                        <div class="flex items-center space-x-4">
                            <?php if (!empty($item['image'])): ?>
                                <img src="/E-shop/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-12 h-12 object-cover rounded" />
                            <?php else: ?>
                                <div class="w-16 h-16 bg-gray-200 rounded"></div>
                            <?php endif; ?>
                            <div>
                                <p class="font-medium text-gray-800"><?= htmlspecialchars($item['name']) ?></p>
                                <p class="text-sm text-gray-500">Quantity: <?= $item['quantity'] ?></p>
                            </div>
                        </div>
                        <span class="font-semibold text-gray-900">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="text-right border-t pt-4 text-gray-800" id="total-container">
                <div>Subtotal: ₦<?= number_format($total, 2) ?></div>
                <div id="shipping_fee_container" class="hidden">Shipping Fee: <span id="shipping_fee_display">0.00</span></div>
                <div class="mt-2 font-bold text-green-600">Total: <span id="total-amount"><?= number_format($total, 2) ?></span></div>
            </div>
        </section>

        <!-- Delivery Details Form -->
        <section class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-semibold mb-6 border-b pb-2">Delivery Details</h2>
            <form id="checkout-form" class="space-y-5">
                <input type="text" name="name" placeholder="Full Name" required value="<?= htmlspecialchars($user['name'] ?? '') ?>" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                
                <input type="email" name="email" value="<?= htmlspecialchars($userEmail) ?>" readonly class="w-full p-3 border rounded-md bg-gray-100 cursor-not-allowed" aria-readonly="true" />
                
                <input type="tel" name="phone" placeholder="Phone Number" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />

                <div>
                    <span class="font-medium">Delivery Type</span>
                    <div class="mt-1 space-x-6" >
                        <label><input type="radio" name="delivery_type" value="pickup" /> Pick Up</label>
                        <label><input type="radio" name="delivery_type" value="door_delivery" /> Door Delivery</label>
                    </div>
                </div>

                <label for="country" class="block font-medium mt-4 mb-1">Country</label>
                <select name="country" id="country" onchange="onCountryChange()" required class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= htmlspecialchars($country) ?>" <?= $country === $selectedCountry ? 'selected' : '' ?>><?= htmlspecialchars($country) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="state" class="block font-medium mt-4 mb-1">State</label>
                <select name="state" id="state" required class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Select State</option>
                    <?php if (empty($states)): ?>
                        <option value="">No states available</option>
                    <?php endif; ?>
                    <?php foreach ($states as $state): ?>
                        <option value="<?= htmlspecialchars($state) ?>"><?= htmlspecialchars($state) ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="city" id="city" placeholder="City" required value="<?= htmlspecialchars($user['city'] ?? '') ?>" class="mt-1 block w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <input type="text" name="address1" id="address1" placeholder="Street address" required value="<?= htmlspecialchars($user['address1'] ?? '') ?>" class="mt-1 block w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <input type="text" name="address2" id="address2" placeholder="Apartment unit, floor." value="<?= htmlspecialchars($user['address2'] ?? '') ?>" class="mt-1 block w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />

                <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-md font-semibold hover:bg-indigo-700 transition">Pay Now</button>
            </form>
        </section>
    </div>

    <a href="../index.php" class="mt-6 inline-block text-indigo-600 hover:text-indigo-800">
        <i class="fas fa-arrow-left"></i> Back to Shopping
    </a>
</main>

<script>
    function onCountryChange() {
        const country = document.getElementById('country').value;
        const url = new URL(window.location.href);
        url.searchParams.set('country', country);
        window.location.href = url.toString();
    }

    const deliveryRadios = document.querySelectorAll('input[name="delivery_type"]');
    const shippingFeeContainer = document.getElementById('shipping_fee_container');
    const shippingFeeDisplay = document.getElementById('shipping_fee_display');
    const countrySelect = document.getElementById('country');
    const stateSelect = document.getElementById('state');

    let shippingFee = 0;

    async function fetchShippingFee(country, state) {
        const res = await fetch(`get_shipping_fee.php?country=${encodeURIComponent(country)}&state=${encodeURIComponent(state)}`);
        if (!res.ok) return 0;
        const data = await res.json();
        return data.shipping_fee ?? 0;
    }

    async function updateShippingFee() {
        const deliveryType = document.querySelector('input[name="delivery_type"]:checked').value;
        if (deliveryType === 'door_delivery') {
            const country = countrySelect.value;
            const state = stateSelect.value;
            shippingFee = await fetchShippingFee(country, state);
            shippingFeeDisplay.textContent = `₦${shippingFee.toFixed(2)}`;
            shippingFeeContainer.classList.remove('hidden');
        } else {
            shippingFee = 0;
            shippingFeeDisplay.textContent = '₦0.00';
            shippingFeeContainer.classList.add('hidden');
        }
        updateTotal();
    }

    function updateTotal() {
        const baseAmount = <?= (int)($total * 100) ?> / 100;
        const total = baseAmount + shippingFee;
        document.getElementById('total-amount').textContent = `₦${total.toFixed(2)}`;
    }

    deliveryRadios.forEach(r => r.addEventListener('change', updateShippingFee));
    countrySelect.addEventListener('change', () => {
        updateShippingFee();
        // Optionally reload states dynamically via AJAX here
    });
    stateSelect.addEventListener('change', updateShippingFee);

    updateShippingFee();

    const PAYSTACK_PUBLIC_KEY = "<?= PAYSTACK_PUBLIC_KEY ?>";
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const name = form.name.value;
        const email = form.email.value;
        const phone = form.phone.value;
        const deliveryType = form.querySelector('input[name="delivery_type"]:checked').value;

        const addressParts = [
            form.address1.value,
            form.address2.value,
            form.city.value,
            form.state.value,
            form.country.value
        ].filter(Boolean);

        const address = addressParts.join(', ');

        const baseAmount = <?= (int)($total * 100) ?>;
        const totalAmount = baseAmount + Math.round(shippingFee * 100);

        PaystackPop.setup({
            key: PAYSTACK_PUBLIC_KEY,
            email: email,
            amount: totalAmount,
            currency: "NGN",
            ref: '' + Math.floor(Math.random() * 1000000000 + 1),
            callback: function(response) {
                window.location.href = `/E-shop/api/paystack_callback.php?reference=${response.reference}&delivery_type=${deliveryType}&shipping_fee=${shippingFee}`;
            },
            metadata: {
                custom_fields: [
                    { display_name: "Name", variable_name: "name", value: name },
                    { display_name: "Phone", variable_name: "phone", value: phone },
                    { display_name: "Address Line 1", variable_name: "address1", value: form.address1.value },
                    { display_name: "Address Line 2", variable_name: "address2", value: form.address2.value },
                    { display_name: "City", variable_name: "city", value: form.city.value },
                    { display_name: "State", variable_name: "state", value: form.state.value },
                    { display_name: "Country", variable_name: "country", value: form.country.value },
                    { display_name: "Delivery Type", variable_name: "delivery_type", value: deliveryType },
                    { display_name: "Shipping Fee", variable_name: "shipping_fee", value: shippingFee },
                    { display_name: "Cart Token", variable_name: "cart_token", value: "<?= $cartToken ?>" }
                ]
            }
        }).openIframe();
    });
</script>

</body>
</html>

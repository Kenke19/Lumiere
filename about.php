<?php
$pageTitle = "About Lumière";
$pageHeading = "Our Story, Your Style";
$searchQuery = '';
$products = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($pageTitle) ?> - Lumière</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6d28d9',
                        secondary: '#8b5cf6',
                        dark: '#1e293b',
                        light: '#f8fafc'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="index.css" />
</head>
<body class="bg-gray-50 font-sans">
    <?php include 'header.php'; ?>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-r from-primary to-secondary py-24 text-white">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-6"><?= htmlspecialchars($pageHeading) ?></h1>
            <p class="text-xl max-w-2xl mx-auto">Discover the passion behind Lumière - where fashion meets purpose</p>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-16 bg-white transform skew-y-1 origin-top-left"></div>
    </section>

    <!-- Our Story -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4 max-w-6xl">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <div class="md:w-1/2">
                    <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" 
                         alt="Lumière boutique" 
                         class="rounded-xl shadow-2xl w-full h-auto object-cover">
                </div>
                <div class="md:w-1/2">
                    <h2 class="text-3xl font-bold mb-6 text-dark">From Vision to Reality</h2>
                    <div class="prose max-w-none text-gray-700">
                        <p class="text-lg mb-4">
                            Founded in 2015, Lumière began as a small boutique in New York with a simple mission: to bring exclusive premium fashion to style-conscious individuals.
                        </p>
                        <p class="mb-4">
                            What started as a single storefront has blossomed into a global e-commerce destination, serving over 500,000 customers worldwide while maintaining our commitment to personal service and curated quality.
                        </p>
                        <p>
                            Today, we continue to honor our roots by combining the intimacy of boutique shopping with the convenience of modern online retail.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4 max-w-6xl">
            <h2 class="text-3xl font-bold text-center mb-12 text-dark">Our Core Values</h2>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Value 1 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <div class="w-14 h-14 bg-primary bg-opacity-10 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-star text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Quality First</h3>
                    <p class="text-gray-600">
                        We meticulously curate every item in our collection, ensuring only the finest craftsmanship and materials make it to your wardrobe.
                    </p>
                </div>
                
                <!-- Value 2 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <div class="w-14 h-14 bg-primary bg-opacity-10 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-leaf text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Sustainable Style</h3>
                    <p class="text-gray-600">
                        Committed to eco-conscious fashion, we partner with brands that prioritize ethical production and sustainable materials.
                    </p>
                </div>
                
                <!-- Value 3 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <div class="w-14 h-14 bg-primary bg-opacity-10 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-heart text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Customer Joy</h3>
                    <p class="text-gray-600">
                        Your satisfaction is our success. We go beyond transactions to create delightful experiences at every touchpoint.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4 max-w-6xl">
            <h2 class="text-3xl font-bold text-center mb-12 text-dark">Meet Our Founders</h2>
            
            <div class="grid md:grid-cols-2 gap-12">
                <!-- Founder 1 -->
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="md:w-1/3">
                        <img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=687&q=80" 
                             alt="Sophia Laurent" 
                             class="rounded-xl w-full h-auto object-cover shadow-md">
                    </div>
                    <div class="md:w-2/3">
                        <h3 class="text-xl font-bold">Sophia Laurent</h3>
                        <p class="text-secondary mb-3">CEO & Creative Director</p>
                        <p class="text-gray-600">
                            With 15 years in fashion design, Sophia brings an impeccable eye for detail and a passion for wearable art to every Lumière collection.
                        </p>
                        <div class="flex mt-4 space-x-3">
                            <a href="#" class="text-gray-500 hover:text-primary"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-gray-500 hover:text-primary"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Founder 2 -->
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="md:w-1/3">
                        <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=687&q=80" 
                             alt="James Chen" 
                             class="rounded-xl w-full h-auto object-cover shadow-md">
                    </div>
                    <div class="md:w-2/3">
                        <h3 class="text-xl font-bold">James Chen</h3>
                        <p class="text-secondary mb-3">COO & Technology Lead</p>
                        <p class="text-gray-600">
                            A retail tech innovator, James ensures Lumière stays at the forefront of e-commerce while maintaining operational excellence.
                        </p>
                        <div class="flex mt-4 space-x-3">
                            <a href="#" class="text-gray-500 hover:text-primary"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="text-gray-500 hover:text-primary"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Milestones -->
    <section class="py-16 bg-primary text-white">
        <div class="container mx-auto px-4 max-w-6xl">
            <h2 class="text-3xl font-bold text-center mb-12">Our Journey</h2>
            
            <div class="relative">
                <!-- Timeline line -->
                <div class="hidden md:block absolute left-1/2 h-full w-1 bg-white bg-opacity-30 transform -translate-x-1/2"></div>
                
                <!-- Milestone 1 -->
                <div class="relative mb-12 md:flex items-center">
                    <div class="md:w-1/2 md:pr-12 md:text-right mb-4 md:mb-0">
                        <h3 class="text-xl font-bold">2015</h3>
                        <p class="text-white text-opacity-90">First boutique opens in SoHo</p>
                    </div>
                    <div class="hidden md:block mx-4">
                        <div class="w-6 h-6 rounded-full bg-white border-4 border-primary"></div>
                    </div>
                    <div class="md:w-1/2 md:pl-12">
                        <div class="bg-white bg-opacity-10 p-6 rounded-xl">
                            <p>Launched with just 5 employees and a curated selection of 50 products</p>
                        </div>
                    </div>
                </div>
                
                <!-- Milestone 2 -->
                <div class="relative mb-12 md:flex items-center">
                    <div class="md:w-1/2 md:pr-12 md:text-right mb-4 md:mb-0 order-1">
                        <div class="bg-white bg-opacity-10 p-6 rounded-xl">
                            <p>Expanded to online sales, reaching customers nationwide</p>
                        </div>
                    </div>
                    <div class="hidden md:block mx-4 order-2">
                        <div class="w-6 h-6 rounded-full bg-white border-4 border-primary"></div>
                    </div>
                    <div class="md:w-1/2 md:pl-12 order-3">
                        <h3 class="text-xl font-bold">2017</h3>
                        <p class="text-white text-opacity-90">E-commerce platform launched</p>
                    </div>
                </div>
                
                <!-- Milestone 3 -->
                <div class="relative mb-12 md:flex items-center">
                    <div class="md:w-1/2 md:pr-12 md:text-right mb-4 md:mb-0">
                        <h3 class="text-xl font-bold">2020</h3>
                        <p class="text-white text-opacity-90">Sustainability initiative begins</p>
                    </div>
                    <div class="hidden md:block mx-4">
                        <div class="w-6 h-6 rounded-full bg-white border-4 border-primary"></div>
                    </div>
                    <div class="md:w-1/2 md:pl-12">
                        <div class="bg-white bg-opacity-10 p-6 rounded-xl">
                            <p>Committed to 100% sustainable packaging and ethical sourcing standards</p>
                        </div>
                    </div>
                </div>
                
                <!-- Milestone 4 -->
                <div class="relative md:flex items-center">
                    <div class="md:w-1/2 md:pr-12 md:text-right mb-4 md:mb-0 order-1">
                        <div class="bg-white bg-opacity-10 p-6 rounded-xl">
                            <p>Recognized by Fashion Forward as "Most Innovative Retailer"</p>
                        </div>
                    </div>
                    <div class="hidden md:block mx-4 order-2">
                        <div class="w-6 h-6 rounded-full bg-white border-4 border-primary"></div>
                    </div>
                    <div class="md:w-1/2 md:pl-12 order-3">
                        <h3 class="text-xl font-bold">2023</h3>
                        <p class="text-white text-opacity-90">Industry recognition</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-16 bg-gray-900 text-white">
        <div class="container mx-auto px-4 text-center max-w-3xl">
            <h2 class="text-3xl font-bold mb-6">Join the Lumière Experience</h2>
            <p class="text-xl mb-8 text-gray-300">
                Discover why thousands of fashion lovers trust us for their style journey
            </p>
            <a href="shop.php" class="inline-block bg-primary hover:bg-secondary text-white font-bold py-3 px-8 rounded-full transition-colors duration-300">
                Shop Now
            </a>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>
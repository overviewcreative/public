<?php
// Set content type to HTML
header('Content-Type: text/html');

// Include required CSS
$theme_url = '/assets';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Happy Place Theme Preview</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/listing.js" defer></script>
    <style>
        .preview-nav {
            background: var(--color-white);
            padding: 1rem;
            border-bottom: 1px solid var(--color-gray-200);
            margin-bottom: 2rem;
        }
        .preview-nav ul {
            list-style: none;
            display: flex;
            gap: 1rem;
            margin: 0;
            padding: 0;
        }
        .preview-nav a {
            color: var(--color-gray-700);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: var(--radius-md);
        }
        .preview-nav a:hover {
            background: var(--color-gray-100);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
    </style>
</head>
<body>
    <nav class="preview-nav">
        <div class="container">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="listings.php">Listings</a></li>
                <li><a href="agents.php">Agents</a></li>
                <li><a href="communities.php">Communities</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <h1>Welcome to Happy Place Theme Preview</h1>
        <p>Select a page from the navigation above to preview different components and layouts.</p>
        
        <section class="preview-section">
            <h2>Sample Cards</h2>
            <div class="grid grid-cols-3 gap-6">
                <!-- Sample Listing Card -->
                <article class="listing-card">
                    <div class="listing-card__image">
                        <img src="https://picsum.photos/800/600" alt="Sample Property">
                        <div class="listing-card__price">$750,000</div>
                        <div class="listing-card__status listing-card__status--active">Active</div>
                    </div>
                    <div class="listing-card__content">
                        <h3 class="listing-card__title">Beautiful Modern Home</h3>
                        <p class="listing-card__address">123 Main St, Anytown, USA</p>
                        <div class="listing-card__meta">
                            <span><i class="fas fa-bed"></i> 4 BD</span>
                            <span><i class="fas fa-bath"></i> 3 BA</span>
                            <span><i class="fas fa-ruler-combined"></i> 2,500 Ft²</span>
                        </div>
                    </div>
                </article>

                <!-- Sample Agent Card -->
                <article class="agent-card">
                    <div class="agent-card__image">
                        <img src="https://picsum.photos/200" alt="Agent Name">
                    </div>
                    <div class="agent-card__content">
                        <h3 class="agent-card__name">
                            <a href="#">John Smith</a>
                        </h3>
                        <div class="agent-card__license">License #: 12345</div>
                        <div class="agent-card__meta">
                            <div class="agent-card__contact">
                                <i class="fas fa-phone"></i>
                                <a href="tel:1234567890">(123) 456-7890</a>
                            </div>
                            <div class="agent-card__contact">
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:john@example.com">john@example.com</a>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Sample Community Card -->
                <article class="community-card">
                    <div class="community-card__image">
                        <img src="https://picsum.photos/800/600" alt="Community Name">
                        <div class="community-card__badge">15 Listings</div>
                    </div>
                    <div class="community-card__content">
                        <h3 class="community-card__title">
                            <a href="#">Riverside Heights</a>
                        </h3>
                        <div class="community-card__location">
                            <i class="fas fa-map-marker-alt"></i>
                            Anytown, USA
                        </div>
                        <div class="community-card__stats">
                            <div class="community-card__stat">
                                <i class="fas fa-home"></i>
                                <span>Avg. $850,000</span>
                            </div>
                            <div class="community-card__stat">
                                <i class="fas fa-building"></i>
                                <span>150 Homes</span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </main>

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
</body>
</html>

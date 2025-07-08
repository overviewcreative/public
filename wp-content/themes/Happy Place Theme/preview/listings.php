<?php
header('Content-Type: text/html');
$theme_url = '/assets';

// Sample listings data
$sample_listings = [
    [
        'id' => 1,
        'title' => 'Modern Waterfront Villa',
        'price' => 1250000,
        'bedrooms' => 4,
        'bathrooms' => 3.5,
        'square_footage' => 3200,
        'main_photo' => 'https://picsum.photos/800/600?random=1',
        'location' => 'Waterfront District',
        'status' => 'active',
        'latitude' => 38.9072,
        'longitude' => -77.0369,
    ],
    [
        'id' => 2,
        'title' => 'Downtown Luxury Penthouse',
        'price' => 2100000,
        'bedrooms' => 3,
        'bathrooms' => 2.5,
        'square_footage' => 2800,
        'main_photo' => 'https://picsum.photos/800/600?random=2',
        'location' => 'City Center',
        'status' => 'active',
        'latitude' => 38.9012,
        'longitude' => -77.0389,
    ],
    [
        'id' => 3,
        'title' => 'Charming Suburban Home',
        'price' => 685000,
        'bedrooms' => 4,
        'bathrooms' => 2,
        'square_footage' => 2400,
        'main_photo' => 'https://picsum.photos/800/600?random=3',
        'location' => 'Pleasant Valley',
        'status' => 'pending',
        'latitude' => 38.9102,
        'longitude' => -77.0329,
    ],
    [
        'id' => 4,
        'title' => 'Historic Brownstone',
        'price' => 1750000,
        'bedrooms' => 5,
        'bathrooms' => 3.5,
        'square_footage' => 3800,
        'main_photo' => 'https://picsum.photos/800/600?random=4',
        'location' => 'Historic District',
        'status' => 'active',
        'latitude' => 38.9052,
        'longitude' => -77.0379,
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties - Happy Place Real Estate</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Mock WordPress localization object
        const hphTheme = {
            ajaxurl: 'mock-ajax-endpoint',
            nonce: 'mock-nonce',
            googleMapsApiKey: 'YOUR_GOOGLE_MAPS_API_KEY'
        };
    </script>
    <script src="../assets/js/listing.js" defer></script>
    <style>
        /* Preview-specific styles */
        body {
            margin: 0;
            padding: 0;
            background-color: var(--color-gray-50);
            font-family: var(--font-sans);
        }

        .preview-nav {
            background: var(--color-white);
            padding: 1rem;
            border-bottom: 1px solid var(--color-gray-200);
            margin-bottom: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .listings-header {
            margin-bottom: 2rem;
        }

        .listings-search {
            background: var(--color-white);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }

        .search-input {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-input input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--color-gray-200);
            border-radius: var(--radius-md);
            font-size: var(--text-base);
        }

        .filter-chips {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hph-filter-group {
            display: flex;
            gap: 0.5rem;
        }

        .hph-filter-chip {
            background: var(--color-white);
            border: 1px solid var(--color-gray-200);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-full);
            cursor: pointer;
            transition: all var(--transition-normal);
        }

        .hph-filter-chip:hover {
            background: var(--color-gray-50);
        }

        .hph-filter-chip.active {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }

        .listings-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .view-toggle {
            display: flex;
            gap: 0.5rem;
        }

        .view-toggle__btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--color-gray-200);
            border-radius: var(--radius-md);
            background: var(--color-white);
            cursor: pointer;
            transition: all var(--transition-normal);
        }

        .view-toggle__btn.active {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }

        .listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        #listings-map {
            height: 600px;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
        }

        .map-listings-preview {
            background: var(--color-white);
            padding: 1rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        .map-preview-card {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--color-gray-100);
        }

        .map-preview-card img {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius-md);
        }

        .map-preview-details {
            flex: 1;
        }

        .map-preview-details h4 {
            margin: 0 0 0.5rem;
            font-size: var(--text-base);
        }

        .map-preview-meta {
            display: flex;
            gap: 1rem;
            color: var(--color-gray-600);
            font-size: var(--text-sm);
            margin: 0.5rem 0;
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
        <div class="listings-header">
            <h1>Find Your Dream Home</h1>
            
            <form id="listings-search-form" class="listings-search">
                <div class="search-input">
                    <input type="text" id="location-search" placeholder="Enter location, neighborhood, or address...">
                    <div id="location-suggestions" class="location-suggestions"></div>
                </div>
                
                <div class="filter-chips">
                    <div class="hph-filter-group">
                        <button type="button" class="hph-filter-chip" data-filter="price" data-value="0-500000">Under $500k</button>
                        <button type="button" class="hph-filter-chip" data-filter="price" data-value="500000-1000000">$500k-$1M</button>
                        <button type="button" class="hph-filter-chip" data-filter="price" data-value="1000000+">$1M+</button>
                    </div>
                    <div class="hph-filter-group">
                        <button type="button" class="hph-filter-chip" data-filter="beds" data-value="1">1+ Beds</button>
                        <button type="button" class="hph-filter-chip" data-filter="beds" data-value="2">2+ Beds</button>
                        <button type="button" class="hph-filter-chip" data-filter="beds" data-value="3">3+ Beds</button>
                        <button type="button" class="hph-filter-chip" data-filter="beds" data-value="4">4+ Beds</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="listings-results">
            <div class="listings-controls">
                <div class="hph-results-count">
                    <span><?php echo count($sample_listings); ?></span> properties found
                </div>

                <div class="listings-actions">
                    <div class="view-toggle">
                        <button class="view-toggle__btn active" data-view="grid">
                            <i class="fas fa-th-large"></i> Grid
                        </button>
                        <button class="view-toggle__btn" data-view="map">
                            <i class="fas fa-map-marker-alt"></i> Map
                        </button>
                    </div>

                    <select id="sort-listings">
                        <option value="newest">Newest First</option>
                        <option value="price-asc">Price (Low to High)</option>
                        <option value="price-desc">Price (High to Low)</option>
                    </select>
                </div>
            </div>

            <div id="listings-container" class="listings-grid">
                <?php foreach ($sample_listings as $listing): ?>
                    <article class="listing-card">
                        <div class="listing-card__image">
                            <img src="<?php echo $listing['main_photo']; ?>" alt="<?php echo $listing['title']; ?>">
                            <div class="listing-card__price">$<?php echo number_format($listing['price']); ?></div>
                            <div class="listing-card__status listing-card__status--<?php echo $listing['status']; ?>"><?php echo ucfirst($listing['status']); ?></div>
                        </div>
                        <div class="listing-card__content">
                            <h3 class="listing-card__title"><?php echo $listing['title']; ?></h3>
                            <p class="listing-card__address"><?php echo $listing['location']; ?></p>
                            <div class="listing-card__meta">
                                <span><i class="fas fa-bed"></i> <?php echo $listing['bedrooms']; ?> BD</span>
                                <span><i class="fas fa-bath"></i> <?php echo $listing['bathrooms']; ?> BA</span>
                                <span><i class="fas fa-ruler-combined"></i> <?php echo number_format($listing['square_footage']); ?> Ft²</span>
                            </div>
                            <div class="listing-card__actions">
                                <a href="#" class="hph-btn hph-btn-primary">View Details</a>
                                <button class="hph-btn-favorite" data-id="<?php echo $listing['id']; ?>">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div id="listings-map" style="display: none;"></div>
            <div id="map-listings-preview" style="display: none;"></div>
        </div>
    </main>

    <script src="https://kit.fontawesome.com/123456789.js" crossorigin="anonymous"></script>
</body>
</html>

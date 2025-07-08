<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo esc_html($data['title']); ?> - Property Details</title>
    <style>
        /* PDF Styles */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .pdf-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .property-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .property-price {
            font-size: 20px;
            color: #2563eb;
            margin-bottom: 10px;
        }

        .property-address {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }

        .property-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            margin-bottom: 30px;
        }

        .property-details {
            margin-bottom: 30px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .detail-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .detail-label {
            font-weight: bold;
            color: #666;
        }

        .features-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .feature-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .agent-info {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .disclaimers {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            font-size: 12px;
            color: #666;
        }

        .mls-logos {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .mls-logo {
            height: 40px;
            width: auto;
        }

        .fair-housing-logo {
            height: 30px;
            width: auto;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="pdf-header">
        <div class="property-title"><?php echo esc_html($data['title']); ?></div>
        <div class="property-price"><?php echo esc_html(hph_format_price($data['price'])); ?></div>
        <div class="property-address"><?php echo esc_html($data['address']); ?></div>
    </div>

    <?php if ($data['gallery'] && !empty($data['gallery'][0])) : ?>
    <img src="<?php echo esc_url($data['gallery'][0]['url']); ?>" alt="<?php echo esc_attr($data['title']); ?>" class="property-image">
    <?php endif; ?>

    <div class="property-details">
        <h2>Property Details</h2>
        <div class="details-grid">
            <?php foreach ($data['details'] as $key => $value) : if ($value) : ?>
            <div class="detail-item">
                <span class="detail-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>:</span>
                <span class="detail-value"><?php echo esc_html($value); ?></span>
            </div>
            <?php endif; endforeach; ?>
        </div>
    </div>

    <?php if ($data['features']) : ?>
    <div class="property-features">
        <h2>Features</h2>
        <div class="features-list">
            <?php foreach ($data['features'] as $feature) : ?>
            <div class="feature-item">
                <span class="feature-label"><?php echo esc_html($feature['label']); ?>:</span>
                <span class="feature-value"><?php echo esc_html($feature['value']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($data['agent']) : ?>
    <div class="agent-info">
        <h2>Contact Information</h2>
        <p>
            <strong><?php echo esc_html($data['agent']['name']); ?></strong><br>
            <?php if (!empty($data['agent']['phone'])) : ?>
            Phone: <?php echo esc_html($data['agent']['phone']); ?><br>
            <?php endif; ?>
            <?php if (!empty($data['agent']['email'])) : ?>
            Email: <?php echo esc_html($data['agent']['email']); ?>
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>

    <div class="disclaimers">
        <?php foreach ($data['disclaimers'] as $type => $text) : ?>
        <p><?php echo esc_html($text); ?></p>
        <?php endforeach; ?>
    </div>

    <div class="mls-logos">
        <img src="<?php echo esc_url(HP_PLUGIN_URL . '/assets/images/bright-mls-logo.png'); ?>" alt="Bright MLS" class="mls-logo">
        <img src="<?php echo esc_url(HP_PLUGIN_URL . '/assets/images/fair-housing-logo.png'); ?>" alt="Fair Housing" class="fair-housing-logo">
    </div>
</body>
</html>

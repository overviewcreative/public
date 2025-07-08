<?php
/**
 * MLS Compliance Trait
 * 
 * Implements validation rules for Delaware and Maryland MLS compliance
 * and Fair Housing Act requirements.
 */

trait MLS_Compliance {
    /**
     * Prohibited words/phrases under Fair Housing Act
     */
    private $fair_housing_prohibited = [
        // Demographics
        'bachelor', 'mature', 'senior', 'adult', 'childless', 'student', 'professional',
        'minority', 'immigrant', 'hispanic', 'asian', 'black', 'white', 'christian', 'jewish', 'muslim',
        // Family Status
        'perfect for', 'ideal for', 'suitable for', 'empty nesters', 'singles', 'couples',
        // Gender
        'masculine', 'feminine', 'bachelor pad', 'man cave', 'her', 'his',
        // Disability
        'handicap', 'wheelchair', 'walk-up', 'walking distance', 'able-bodied',
        // Steering
        'safe', 'secure', 'suburban', 'urban', 'integrated', 'traditional', 'changing neighborhood'
    ];

    /**
     * Required MLS fields for Delaware/Maryland
     */
    private $required_mls_fields = [
        'property_type',
        'list_price',
        'square_footage',
        'bedrooms',
        'bathrooms',
        'year_built',
        'lot_size',
        'zoning',
        'tax_id',
        'listing_agreement_type',
        'listing_date',
        'expiration_date'
    ];

    /**
     * Validate listing for Fair Housing compliance
     */
    private function validate_fair_housing(string $content): array {
        $errors = [];
        $content = strtolower($content);

        foreach ($this->fair_housing_prohibited as $term) {
            if (strpos($content, strtolower($term)) !== false) {
                $errors[] = sprintf(
                    'The term "%s" may violate Fair Housing Act requirements. Please revise.',
                    $term
                );
            }
        }

        return $errors;
    }

    /**
     * Validate required MLS fields
     */
    private function validate_mls_fields(array $data): array {
        $errors = [];

        foreach ($this->required_mls_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = sprintf('The field "%s" is required for MLS compliance', $field);
            }
        }

        // Validate price format (no ranges or modifiers)
        if (isset($data['list_price']) && !$this->is_valid_price_format($data['list_price'])) {
            $errors[] = 'Price must be a specific amount without ranges or modifiers';
        }

        // Validate property measurements
        if (isset($data['square_footage']) && !$this->is_valid_measurement($data['square_footage'])) {
            $errors[] = 'Square footage must be a specific numerical value';
        }

        return $errors;
    }

    /**
     * Validate listing media requirements
     */
    private function validate_mls_media(array $media): array {
        $errors = [];

        // Minimum photo requirements
        if (count($media['photos'] ?? []) < 5) {
            $errors[] = 'MLS requires a minimum of 5 photos';
        }

        // Photo quality requirements
        return $errors;
    }

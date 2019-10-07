<?php

return [
    /**
     * Prevents listed categories from being returned on
     * "/api/v1/product-categories' route as alternative to potentially
     * slow "?used=1&parent_id=null" which is used to display only
     * categories/categories with subcategories what actually have products
     *
     * You can add ids here and to .env as comma separated values
     */
    'disabled_top_categories' => array_merge(
        explode(',', env('DISABLED_TOP_CATEGORIES', '')), [
            
        ]
    )
];
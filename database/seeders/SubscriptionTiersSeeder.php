<?php

namespace Database\Seeders;

use App\Models\SubscriptionTier;
use Illuminate\Database\Seeder;

class SubscriptionTiersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Free Starter',
                'slug' => 'free-starter',
                'description' => 'Perfect for getting started with basic job posting',
                'features' => [
                    '1 job post per month',
                    'Basic job search',
                    'Standard support',
                    '30-day job visibility'
                ],
                'monthly_price' => 0.00,
                'yearly_price' => 0.00,
                'job_posts_limit' => 1,
                'featured_posts_limit' => 0,
                'resume_views_limit' => 5,
                'priority_support' => false,
                'analytics_access' => false,
                'api_access' => false,
                'white_label' => false,
                'active' => true,
                'sort_order' => 1
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Ideal for growing businesses with regular hiring needs',
                'features' => [
                    '10 job posts per month',
                    '2 featured posts per month',
                    'Advanced search filters',
                    'Resume database access (50 views)',
                    'Job alert matching',
                    'Basic analytics dashboard',
                    'Priority email support',
                    '60-day job visibility'
                ],
                'monthly_price' => 49.99,
                'yearly_price' => 499.99, // 2 months free
                'job_posts_limit' => 10,
                'featured_posts_limit' => 2,
                'resume_views_limit' => 50,
                'priority_support' => true,
                'analytics_access' => true,
                'api_access' => false,
                'white_label' => false,
                'active' => true,
                'sort_order' => 2
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large organizations with extensive hiring requirements',
                'features' => [
                    'Unlimited job posts',
                    'Unlimited featured posts',
                    'Advanced analytics & reporting',
                    'Unlimited resume views',
                    'Custom branding options',
                    'API access for integrations',
                    'Dedicated account manager',
                    'Custom job alert campaigns',
                    'ATS integration support',
                    '90-day job visibility'
                ],
                'monthly_price' => 199.99,
                'yearly_price' => 1999.99, // 2 months free
                'job_posts_limit' => 0, // Unlimited
                'featured_posts_limit' => 0, // Unlimited
                'resume_views_limit' => 0, // Unlimited
                'priority_support' => true,
                'analytics_access' => true,
                'api_access' => true,
                'white_label' => true,
                'active' => true,
                'sort_order' => 3
            ],
            [
                'name' => 'Premium Plus',
                'slug' => 'premium-plus',
                'description' => 'Enhanced features for competitive hiring advantage',
                'features' => [
                    '25 job posts per month',
                    '5 featured posts per month',
                    'AI-powered candidate matching',
                    'Resume database access (200 views)',
                    'Advanced analytics dashboard',
                    'Social media job promotion',
                    'Priority support with phone access',
                    'Custom job templates',
                    '75-day job visibility'
                ],
                'monthly_price' => 99.99,
                'yearly_price' => 999.99, // 2 months free
                'job_posts_limit' => 25,
                'featured_posts_limit' => 5,
                'resume_views_limit' => 200,
                'priority_support' => true,
                'analytics_access' => true,
                'api_access' => false,
                'white_label' => false,
                'active' => true,
                'sort_order' => 3
            ]
        ];

        foreach ($tiers as $tier) {
            SubscriptionTier::create($tier);
        }
    }
}
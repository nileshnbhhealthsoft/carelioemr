<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subscription;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the database with Admin user & Paid Subscribers.
     */
    public function run(): void
    {
        // 1. Seed Dynamic Admin User
        $this->call(AdminUserSeeder::class);

        // 2. Seed Paid Subscribers in subscriptions table
        $subscribers = [
            [
                'doctor_name' => 'Dr. Sarah Johnson',
                'email' => 'sarah.johnson@medicalpractice.com',
                'practice_type' => 'General Practice',
                'region' => 'LC',
                'stripe_customer_id' => 'cus_Q98aF123bc',
                'stripe_payment_intent_id' => 'pi_3P98aF123bc45',
                'amount' => 80.00,
                'currency' => 'usd',
                'payment_status' => 'succeeded',
                'setup_cost_status' => 'billed_separately',
                'paid_at' => Carbon::now()->subHours(2),
                'created_at' => Carbon::now()->subHours(2),
            ],
            [
                'doctor_name' => 'Dr. Marcus Etienne',
                'email' => 'm.etienne@victoriahospital.lc',
                'practice_type' => 'Hospitals',
                'region' => 'LC',
                'stripe_customer_id' => 'cus_Q77bF456de',
                'stripe_payment_intent_id' => 'pi_3P77bF456de89',
                'amount' => 80.00,
                'currency' => 'usd',
                'payment_status' => 'succeeded',
                'setup_cost_status' => 'billed_separately',
                'paid_at' => Carbon::now()->subDays(1),
                'created_at' => Carbon::now()->subDays(1),
            ],
            [
                'doctor_name' => 'Dr. Rajesh Nair',
                'email' => 'drnair@specialtyclinic.in',
                'practice_type' => 'Clinics',
                'region' => 'IN',
                'stripe_customer_id' => 'cus_Q55cF789fg',
                'stripe_payment_intent_id' => 'pi_3P55cF789fg12',
                'amount' => 80.00,
                'currency' => 'usd',
                'payment_status' => 'succeeded',
                'setup_cost_status' => 'billed_separately',
                'paid_at' => Carbon::now()->subDays(2),
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'doctor_name' => 'Dr. Fatima Al-Mansoori',
                'email' => 'f.almansoori@emirateshealth.ae',
                'practice_type' => 'Clinics',
                'region' => 'AE',
                'stripe_customer_id' => 'cus_Q33dF901hi',
                'stripe_payment_intent_id' => 'pi_3P33dF901hi34',
                'amount' => 80.00,
                'currency' => 'usd',
                'payment_status' => 'succeeded',
                'setup_cost_status' => 'billed_separately',
                'paid_at' => Carbon::now()->subDays(3),
                'created_at' => Carbon::now()->subDays(3),
            ],
            [
                'doctor_name' => 'Dr. Elena Rostova',
                'email' => 'elena.rostova@stjudehospital.us',
                'practice_type' => 'Hospitals',
                'region' => 'US',
                'stripe_customer_id' => 'cus_Q11eF234jk',
                'stripe_payment_intent_id' => 'pi_3P11eF234jk56',
                'amount' => 80.00,
                'currency' => 'usd',
                'payment_status' => 'succeeded',
                'setup_cost_status' => 'billed_separately',
                'paid_at' => Carbon::now()->subDays(4),
                'created_at' => Carbon::now()->subDays(4),
            ],
        ];

        foreach ($subscribers as $subData) {
            Subscription::updateOrCreate(
                ['email' => $subData['email']],
                $subData
            );
        }
    }
}

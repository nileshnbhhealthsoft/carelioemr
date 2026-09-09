import express from 'express';
import cors from 'cors';
import Stripe from 'stripe';
import dotenv from 'dotenv';

dotenv.config();

const app = express();
const port = 4000;

const stripeSecretKey = process.env.STRIPE_SECRET_KEY || 'sk_test_51UAmt2F73bxUU91GA2FNeqM4PsKc0cH8BVyOzFTfdIP1njSEUfDhCvIQO76LwxgBb7P5Ooa1GNVf1H6xxGHtqXnW00N3PrfvIk';
const stripe = new Stripe(stripeSecretKey);

app.use(cors());
app.use(express.json());

// Endpoint to create Stripe PaymentIntent or Subscription Customer
app.post('/api/create-payment-intent', async (req, res) => {
  try {
    const { amount, currency, email, doctorName, practiceType, selectedRegion } = req.body;

    // Create a PaymentIntent with the order amount and currency ($80 = 8000 cents)
    const paymentIntent = await stripe.paymentIntents.create({
      amount: amount || 8000,
      currency: currency || 'usd',
      receipt_email: email,
      description: `AuraEMR Subscription ($80/mo) - ${practiceType} (${selectedRegion})`,
      metadata: {
        subscriber_name: doctorName,
        practice_type: practiceType,
        region: selectedRegion,
        setup_cost_note: 'Setup cost will be billed as an additional cost'
      },
      automatic_payment_methods: {
        enabled: true,
      },
    });

    res.send({
      clientSecret: paymentIntent.client_secret,
      paymentIntentId: paymentIntent.id,
    });
  } catch (error) {
    console.error('Stripe PaymentIntent Error:', error);
    res.status(500).send({ error: error.message });
  }
});

app.listen(port, () => {
  console.log(`Stripe Backend API server running at http://localhost:${port}`);
});

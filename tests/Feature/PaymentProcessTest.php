<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentProcessTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Generate JWT token
        $this->token = JWTAuth::fromUser($this->user);
    }

    /**
     * Test that payment can only be processed for confirmed orders.
     */
    public function test_payment_can_only_be_processed_for_confirmed_orders(): void
    {
        // Create a pending order
        $order = Order::create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
            'status' => 'pending',
        ]);

        // Attempt to process payment for pending order
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/payments', [
            'order_id' => $order->id,
            'method' => 'stripe',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Payments can only be processed for confirmed orders',
        ]);
    }

    /**
     * Test successful payment processing via Stripe.
     */
    public function test_successful_payment_processing_via_stripe(): void
    {
        // Create a confirmed order
        $order = Order::create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
            'status' => 'confirmed',
        ]);

        // Process payment
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/payments', [
            'order_id' => $order->id,
            'method' => 'stripe',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'payment' => [
                'id',
                'order_id',
                'transaction_id',
                'gateway_name',
                'amount',
                'status',
            ],
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway_name' => 'stripe',
            'amount' => 100.00,
        ]);
    }

    /**
     * Test successful payment processing via PayPal.
     */
    public function test_successful_payment_processing_via_paypal(): void
    {
        // Create a confirmed order
        $order = Order::create([
            'user_id' => $this->user->id,
            'total_amount' => 150.00,
            'status' => 'confirmed',
        ]);

        // Process payment
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/payments', [
            'order_id' => $order->id,
            'method' => 'paypal',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'payment' => [
                'id',
                'order_id',
                'transaction_id',
                'gateway_name',
                'amount',
                'status',
            ],
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'gateway_name' => 'paypal',
            'amount' => 150.00,
        ]);
    }

    /**
     * Test that orders with payments cannot be deleted.
     */
    public function test_order_with_payments_cannot_be_deleted(): void
    {
        // Create a confirmed order
        $order = Order::create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
            'status' => 'confirmed',
        ]);

        // Create a payment for the order
        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'TEST_' . uniqid(),
            'gateway_name' => 'stripe',
            'amount' => 100.00,
            'status' => 'successful',
        ]);

        // Attempt to delete the order
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Cannot delete order with associated payments',
        ]);

        // Verify order still exists
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
        ]);
    }

    /**
     * Test that orders without payments can be deleted.
     */
    public function test_order_without_payments_can_be_deleted(): void
    {
        // Create an order without payments
        $order = Order::create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
            'status' => 'pending',
        ]);

        // Delete the order
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Order deleted successfully',
        ]);

        // Verify order is deleted
        $this->assertDatabaseMissing('orders', [
            'id' => $order->id,
        ]);
    }

    /**
     * Test payment validation - invalid gateway.
     */
    public function test_payment_validation_invalid_gateway(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
            'status' => 'confirmed',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/payments', [
            'order_id' => $order->id,
            'method' => 'invalid_gateway',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test payment validation - missing order_id.
     */
    public function test_payment_validation_missing_order_id(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/payments', [
            'method' => 'stripe',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test getting payments for an order.
     */
    public function test_get_payments_for_order(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
            'status' => 'confirmed',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'TEST_1',
            'gateway_name' => 'stripe',
            'amount' => 100.00,
            'status' => 'successful',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'TEST_2',
            'gateway_name' => 'paypal',
            'amount' => 100.00,
            'status' => 'failed',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/payments/order/{$order->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentManager $paymentManager
    ) {
    }

    /**
     * Process payment for an order.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $order = Order::findOrFail($request->order_id);

        // Ensure user can only process payments for their own orders
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order');
        }

        // Business rule: Payments can only be processed if order status is 'confirmed'
        if (! $order->isConfirmed()) {
            return response()->json([
                'message' => 'Payments can only be processed for confirmed orders',
            ], 422);
        }

        // Resolve the payment gateway
        $gateway = $this->paymentManager->resolve($request->method);

        // Process the payment
        $result = $gateway->process($order->total_amount, $request->all());

        // Create payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'transaction_id' => $result['transaction_id'],
            'gateway_name' => $result['gateway'],
            'amount' => $result['amount'],
            'status' => $result['status'] === 'successful' ? 'successful' : 'failed',
        ]);

        $payment->load('order');

        return response()->json([
            'message' => $result['message'],
            'payment' => new PaymentResource($payment),
        ], $result['status'] === 'successful' ? 201 : 422);
    }

    /**
     * Get payments for a specific order.
     */
    public function getByOrder(int $orderId): AnonymousResourceCollection
    {
        $order = Order::findOrFail($orderId);

        // Ensure user can only view payments for their own orders
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order');
        }

        $payments = Payment::where('order_id', $orderId)
            ->with('order')
            ->get();

        return PaymentResource::collection($payments);
    }

    /**
     * Get all payments for authenticated user.
     */
    public function index(): AnonymousResourceCollection
    {
        $payments = Payment::whereHas('order', function ($query) {
            $query->where('user_id', auth()->id());
        })
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return PaymentResource::collection($payments);
    }
}

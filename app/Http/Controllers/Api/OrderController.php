<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /**
     * Get all orders for authenticated user.
     */
    public function index(): AnonymousResourceCollection
    {
        $query = Order::with(['orderItems', 'user', 'payments'])
            ->where('user_id', auth()->id());

        // Filter by status if provided
        if (request()->has('status')) {
            $query->where('status', request()->status);
        }

        $orders = $query->paginate(15);

        return OrderResource::collection($orders);
    }

    /**
     * Create a new order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $totalAmount = 0;

        // Calculate total amount
        foreach ($request->items as $item) {
            $totalAmount += $item['quantity'] * $item['price'];
        }

        $order = Order::create([
            'user_id' => auth()->id(),
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]);

        // Create order items
        foreach ($request->items as $item) {
            $order->orderItems()->create([
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        $order->load(['orderItems', 'user', 'payments']);

        return response()->json([
            'message' => 'Order created successfully',
            'order' => new OrderResource($order),
        ], 201);
    }

    /**
     * Get a specific order.
     */
    public function show(Order $order): OrderResource
    {
        // Ensure user can only access their own orders
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order');
        }

        $order->load(['orderItems', 'user', 'payments']);

        return new OrderResource($order);
    }

    /**
     * Update an order.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        // Ensure user can only update their own orders
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order');
        }

        // Update order status if provided
        if ($request->has('status')) {
            $order->status = $request->status;
        }

        // Update order items if provided
        if ($request->has('items')) {
            // Delete existing items
            $order->orderItems()->delete();

            // Recalculate total
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }
            $order->total_amount = $totalAmount;

            // Create new items
            foreach ($request->items as $item) {
                $order->orderItems()->create([
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        }

        $order->save();
        $order->load(['orderItems', 'user', 'payments']);

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => new OrderResource($order),
        ]);
    }

    /**
     * Delete an order.
     */
    public function destroy(Order $order): JsonResponse
    {
        // Ensure user can only delete their own orders
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order');
        }

        // Business rule: Cannot delete order if it has payments
        if (! $order->canBeDeleted()) {
            return response()->json([
                'message' => 'Cannot delete order with associated payments',
            ], 422);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully',
        ]);
    }
}

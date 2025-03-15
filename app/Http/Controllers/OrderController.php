<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Jobs\PublishOrderToRabbitMQ;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        // Decode the JSON cart data from request
        $cartItems = json_decode($request->getContent(), true)['cart'] ?? [];

        if (!$cartItems || !is_array($cartItems)) {
            return response()->json(['message' => 'Invalid cart data'], 400);
        }

        try {
            // Calculate total price
            $total = array_sum(array_column($cartItems, 'price'));

            // Create the order
            $order = Order::create([
                'total' => $total
            ]);

            // Insert order items
            foreach ($cartItems as $item) {
                $order->items()->create([
                    'product_id' => $item['id'],
                    'order_id' => $order->id,
                    'order_quantity' => $order->qtyAddedToCart
                ]);

            // Dispatch job with raw data
            PublishOrderToRabbitMQ::dispatch([
                'quantity' => $item['qtyAddedToCart'],
                'product_id' => $item['id']
            ]);

            }

            return response()->json([
                'success'=> true,
                'message' => 'Order created successfully',
                'order_id' => $order->id
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}


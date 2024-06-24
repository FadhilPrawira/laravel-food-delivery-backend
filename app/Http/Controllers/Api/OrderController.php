<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderController extends Controller
{

    // Create order
    public function createOrder(Request $request)
    {
        // Validate the request
        $request->validate([
            'order_items' => 'required|array',
            'order_items.*.product_id' => 'required|integer|exists:products,id',
            'order_items.*.quantity' => 'required|integer',
            'restaurant_id' => 'required|integer|exists:users,id',
            'shipping_cost' => 'required|integer',

            // 'payment_method' => 'required|string',
            // 'shipping_address' => 'required|string',
            // 'shipping_latlong' => 'required|string',

        ]);
        // Get the authenticated user
        $user = $request->user();
        if (
            $user->role == 'customer'
        ) {
            // Check if user_id in order_items is same with restaurant_id
            foreach ($request->order_items as $item) {
                $product = Product::find($item['product_id']);
                if ($product->user_id != $request->restaurant_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Some product not found in this restaurant',
                    ], 404);
                }
            }

            // Total Price
            $totalPrice = 0;
            foreach ($request->order_items as $item) {
                $product = Product::find($item['product_id']);
                $totalPrice += $product->price * $item['quantity'];
            }

            // Total bill
            $totalBill = $totalPrice + $request->shipping_cost;

            // Get the data from request
            $data = $request->all();

            $shippingAddress = $user->address;
            $shippingLatLong = $user->latlong;

            $data['user_id'] = $user->id;
            $data['shipping_address'] = $shippingAddress;
            $data['shipping_latlong'] = $shippingLatLong;
            $data['status'] = 'pending';
            $data['total_price'] = $totalPrice;
            $data['total_bill'] = $totalBill;
            // Create order
            $order = Order::create($data);

            // Create order items
            foreach ($data['order_items'] as $item) {
                // Get the product
                $product = Product::find($item['product_id']);

                // If product found then create order item
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                $order->orderItems()->save($orderItem);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Order has been created',
                'data' => $order
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to create order. Must be user'
            ], 403);
        }
    }


    //    Update purchase status
    public function updatePurchaseStatus(Request $request, int $id)
    {
        // Validate the request
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        // Get the order
        $order = Order::find($id);

        // If order not found then return error
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found',
            ], 404);
        }

        // Update the status
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order status has been updated',
            'data' => $order
        ]);
    }

    // Get all orders for customer
    public function orderHistory(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();
        if ($user->role == 'customer') {
            // Get the orders
            $orders = Order::where('user_id', $user->id)
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No order found',
                    'data' => $orders
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Get all order history',
                'data' => $orders
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to get order history. Must be user'
            ], 403);
        }
    }


    // Cancel order
    public function cancelOrder(Request $request, int $id)
    {
        // Get the order
        $order = Order::find($id);

        // If order not found then return error
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found',
            ], 404);
        }

        // Update the status
        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order has been cancelled',
            'data' => $order
        ]);
    }

    // Get order by status for restaurant
    public function getOrderByStatus(Request $request)
    {
        // Validate the request
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        // Get the authenticated user
        $user = $request->user();
        if ($user->role == 'restaurant') {
            // Get the orders
            $orders = Order::where('restaurant_id', $user->id)
                ->where('status', $request->status)
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No order found',
                    'data' => $orders
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Get all order by status',
                'data' => $orders
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to get order status. Must be restaurant'
            ], 403);
        }
    }

    // Update order status for restaurant
    public function updateOrderStatus(Request $request, int $id)
    {
        // Validate the request
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,ready_for_delivery,prepared',
        ]);

        // Get the authenticated user
        $user = $request->user();

        // Check if user is restaurant
        if ($user->role == 'restaurant') {
            // Get the order
            $order = Order::find($id);

            // Check if order restaurant_id same with authenticated user id
            if ($order->restaurant_id != $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to update this order status',
                ], 403);
            }

            // If order not found then return error
            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found',
                ], 404);
            }

            // Update the status
            $order->status = $request->status;
            $order->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Order status updated successfully',
                'data' => $order
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update order status. Must be restaurant'
            ], 403);
        }
    }

    // Get order status for driver
    public function getOrderByStatusDriver(Request $request)
    {
        // Validate the request
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,ready_for_delivery,prepared',
        ]);

        // Get the authenticated user
        $user = $request->user();
        if ($user->role == 'driver') {

            // Get the orders
            $orders = Order::where('driver_id', $user->id)
                ->where('status', $request->status)
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No order found',
                    'data' => $orders
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Get all order by status',
                'data' => $orders
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to get order status. Must be driver'
            ], 403);
        }
    }

    // Get order status ready for delivery
    public function getOrderStatusReadyForDelivery(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Get the orders
        $orders = Order::with('restaurant')
            ->where('status', 'ready_for_delivery')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all order by status ready for delivery',
            'data' => $orders
        ]);
    }

    // Update order status for driver
    public function updateOrderStatusDriver(Request $request, int $id)
    {
        // Validate the request
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,on_the_way,delivered',
        ]);


        // Get the authenticated user
        $user = $request->user();

        // Check if user is driver
        if ($user->role == 'driver') {
            // Get the order
            $order = Order::find($id);

            // If order not found then return error
            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found',
                ], 404);
            }

            // Update the status
            $order->status = $request->status;
            $order->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Order status updated successfully',
                'data' => $order
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update order status. Must be driver'
            ], 403);
        }
    }
}

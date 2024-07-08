<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Xendit\Configuration;

class OrderController extends Controller
{

    public function __construct()
    {
        Configuration::setXenditKey('xnd_development_2WdZYFWXIGBXjQ78cNNIxdzqJ3tiSQZyyjmNkOQvAXhtKl7UTIy533kYioq171');
    }

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
                    ])->setStatusCode(404);
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
            ])->setStatusCode(201);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to create order. Must be user'
            ])->setStatusCode(403);
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
            ])->setStatusCode(404);
        }

        // Update the status
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order status has been updated',
            'data' => $order
        ])->setStatusCode(200);
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
                ])->setStatusCode(404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Get all order history',
                'data' => $orders
            ])->setStatusCode(200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to get order history. Must be user'
            ])->setStatusCode(403);
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
            ])->setStatusCode(404);
        }

        // Update the status
        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order has been cancelled',
            'data' => $order
        ])->setStatusCode(200);
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
                ])->setStatusCode(404);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Get all order by status',
                'data' => $orders
            ])->setStatusCode(200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to get order status. Must be restaurant'
            ])->setStatusCode(403);
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
                ])->setStatusCode(403);
            }

            // If order not found then return error
            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found',
                ])->setStatusCode(404);
            }

            // Update the status
            $order->status = $request->status;
            $order->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Order status updated successfully',
                'data' => $order
            ])->setStatusCode(200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update order status. Must be restaurant'
            ])->setStatusCode(403);
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
                ])->setStatusCode(404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Get all order by status',
                'data' => $orders
            ])->setStatusCode(200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to get order status. Must be driver'
            ])->setStatusCode(403);
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
        ])->setStatusCode(200);
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
                ])->setStatusCode(404);
            }

            // Update the status
            $order->status = $request->status;
            $order->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Order status updated successfully',
                'data' => $order
            ])->setStatusCode(200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to update order status. Must be driver'
            ])->setStatusCode(403);
        }
    }

    // Get payment methods
    public function getPaymentMethods()
    {

        $paymentMethods = [
            'e_wallet' => [
                'ID_OVO' => 'OVO',
                'ID_DANA' => 'Dana',
                'ID_LINKAJA' => 'LinkAja',
                'ID_SHOPEEPAY' => 'ShopeePay',
            ]
        ];
        return response()->json([
            // 'status' => 'success',
            'message' => 'Payment methods retrieved successfully',
            'payment_methods' => $paymentMethods
        ])->setStatusCode(200);
    }

    public function purchaseOrderWithToken(Request $request, $orderId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,e_wallet',
            'payment_e_wallet' => 'nullable|required_if:payment_method,e_wallet|string',
            'payment_method_id' => 'nullable|required_if:payment_method,e_wallet|string',
        ]);

        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'])->setStatusCode(404);
        }

        if ($validated['payment_method'] === 'e_wallet') {
            $apiInstance = new \Xendit\PaymentRequest\PaymentRequestApi();
            $idempotency_key = uniqid();
            $for_user_id = auth()->id();

            $payment_request_parameters = new \Xendit\PaymentRequest\PaymentRequestParameters([
                'reference_id' => 'order-' . $orderId,
                'amount' => $order->total_bill,
                'currency' => 'IDR',
                'payment_method_id' => $validated['payment_method_id'],
                'metadata' => [
                    'order_id' => $orderId,
                    'user_id' => $order->user->id,
                ]
            ]);

            try {
                $result = $apiInstance->createPaymentRequest($idempotency_key, $for_user_id, $payment_request_parameters);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_type' => $validated['payment_method'],
                    'payment_provider' => $validated['payment_e_wallet'],
                    'amount' => $order->total_bill,
                    'status' => 'pending',
                    'xendit_id' => $result['id'],
                ]);

                return response()->json(['message' => 'Payment created successfully', 'order' => $order, 'payment' => $result])->setStatusCode(200);
            } catch (\Xendit\XenditSdkException $e) {
                return response()->json(['message' => 'Failed to create payment', 'error' => $e->getMessage(), 'full_error' => $e->getFullError()])->setStatusCode(500);
            }
        } else {
            $order->status = 'purchase';
            $order->payment_method = $validated['payment_method'];
            $order->save();

            $this->sendNotification($order->restaurant_id, 'Order Purchased', 'An order has been purchased and is ready to be prepared.');

            return response()->json(['message' => 'Order purchased successfully', 'order' => $order])->setStatusCode(200);
        }
    }

    // Method for send notification to restaurant/user/driver
    public function sendNotification($userId, $title, $message)
    {
        $restaurant = User::find($userId);
        if ($restaurant && $restaurant->fcm_id) {
            $token = $restaurant->fcm_id;

            // Kirim notifikasi ke perangkat Android
            $messaging = app('firebase.messaging');
            $notification = Notification::create($title, $message);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);

            try {
                $messaging->send($message);
            } catch (\Exception $e) {
                Log::error('Failed to send notification', ['error' => $e->getMessage()]);
            }
        }
    }

    //Callback / webhook
    public function webhook(Request $request)
    {
        Log::info('Received webhook:', $request->all());

        $event = $request->input('event');
        $data = $request->input('data');

        if (isset($data['payment_request_id']) && isset($data['status'])) {
            $payment = Payment::where('xendit_id', $data['payment_request_id'])->first();

            if ($payment) {
                $order = Order::where('id', $payment->order_id)->first();

                if (!$order) {
                    return response()->json(['message' => 'Order not found'])->setStatusCode(404);
                }

                if ($event === 'payment.succeeded') {
                    $order->status = 'purchase';
                    $payment->status = 'success';
                    $order->save();
                    $payment->save();

                    // send notification
                    $this->sendNotification($order->restaurant_id, 'Order Purchased', 'An order has been purchased and is ready to be prepared.');

                    return response()->json(['message' => 'Order updated successfully and notification sent'])->setStatusCode(200);
                } elseif ($event === 'payment.failed') {
                    $order->status = 'cancel';
                    $payment->status = 'failed';
                    $order->save();
                    $payment->save();

                    return response()->json(['message' => 'Order updated to cancelled'])->setStatusCode(200);
                }
            } else {
                return response()->json(['message' => 'Payment not found'])->setStatusCode(404);
            }
        }

        return response()->json(['message' => 'Invalid callback data'])->setStatusCode(400);
    }

    ///One-Time Payment via Redirect URL Xendit
    public function purchaseOrder(Request $request, $orderId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,e_wallet',
            'payment_e_wallet' => 'nullable|required_if:payment_method,e_wallet|string',
            'mobile_number' => 'nullable|required_if:payment_e_wallet,ID_OVO|string'
        ]);

        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($validated['payment_method'] === 'e_wallet') {
            $apiInstance = new \Xendit\PaymentRequest\PaymentRequestApi();
            $idempotency_key = uniqid();
            //$for_user_id = auth()->id();

            $channel_properties = [
                'success_return_url' => 'flutter://payment_success?order_id=' . $orderId,
                'failure_return_url' => 'flutter://payment_failed?order_id=' . $orderId,
            ];

            // add mobile_number if e-wallet is OVO
            if ($validated['payment_e_wallet'] === 'ID_OVO') {
                $channel_properties['mobile_number'] = $validated['mobile_number'];
            }

            $payment_request_parameters = new \Xendit\PaymentRequest\PaymentRequestParameters([
                'reference_id' => 'order-' . $orderId,
                'amount' => $order->total_bill,
                'currency' => 'IDR',
                'country' => 'ID',
                'payment_method' => [
                    'type' => 'EWALLET',
                    'ewallet' => [
                        'channel_code' => $validated['payment_e_wallet'],
                        'channel_properties' => $channel_properties
                    ],
                    'reusability' => 'ONE_TIME_USE'
                ]
            ]);

            try {
                $result = $apiInstance->createPaymentRequest($idempotency_key, null, $payment_request_parameters);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_type' => $validated['payment_method'],
                    'payment_provider' => $validated['payment_e_wallet'],
                    'amount' => $order->total_bill,
                    'status' => 'pending',
                    'xendit_id' => $result['id'],
                ]);
                $order->payment_method = $validated['payment_e_wallet'];
                // $order->payment_e_wallet = ;
                $order->save();

                return response()->json(['message' => 'Payment created successfully', 'order' => $order, 'payment' => $result], 200);
            } catch (\Xendit\XenditSdkException $e) {
                return response()->json(['message' => 'Failed to create payment', 'error' => $e->getMessage(), 'full_error' => $e->getFullError()], 500);
            }
        } else {
            // Hanya memperbarui status order jika metode pembayaran bukan e-wallet
            $order->status = 'purchase';
            $order->payment_method = $validated['payment_method'];
            $order->save();

            $this->sendNotification($order->restaurant_id, 'Order Purchased', 'An order has been purchased and is ready to be prepared.');

            return response()->json(['message' => 'Order purchased successfully', 'order' => $order], 200);
        }
    }
}

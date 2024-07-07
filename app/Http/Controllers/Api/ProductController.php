<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // index
    // Get all product from authenticated restaurant
    public function index(Request $request)
    {
        // Get authenticated user
        $user = $request->user();

        if ($user->role == 'restaurant') {
            // Eager loading (with) to get user data from product
            $products = Product::with('user')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();


            if ($products->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No products found'
                ])->setStatusCode(404);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to access this resource. Must be a restaurant'
            ])->setStatusCode(403);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'All products found',
            'data' => $products
        ])->setStatusCode(200);
    }

    // Get all product from a specific restaurant
    public function getProductByRestaurantId(Request $request, int $id)
    {
        // Check if 'id' that requested exist
        $restaurant = User::find($id);

        if (!$restaurant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Restaurant not found'
            ])->setStatusCode(404);
        }

        // Get all products from the restaurant
        $products = Product::with('user')->where('user_id', $id)->get();

        // If the products is empty
        if ($products->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No products found',
                'data' => $products
            ])->setStatusCode(404);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'All products from restaurant id ' . $id . ' found',
            'data' => $products
        ])->setStatusCode(200);
    }

    // Store/create product
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'stock' => 'required|integer',
            'price' => 'required|integer',
            'is_available' => 'required|boolean',
            'is_favorite' => 'required|boolean',
            'image' => 'required|image',
        ]);

        // Get the authenticated user
        $user = $request->user();

        // Store the image in variable
        $product_image = $request->file('image');

        // Set the image name based on epoch time and extension based on MIME type
        // TODO: change the name to the hashName
        $product_image_filename = time() . '.' . $product_image->extension();

        // Store the image in the storage
        $product_image->storeAs('public/images', $product_image_filename);

        // http://localhost:8000/storage/images/YOUR_IMAGE_NAME.EXTENSION

        // Get all request data
        $data = $request->all();

        // Create a new product
        $product = new Product();
        $product->name = $data['name'];
        $product->description = $data['description'];
        $product->stock = $data['stock'];
        $product->price = $data['price'];
        $product->is_available = $data['is_available'];
        $product->is_favorite = $data['is_favorite'];
        // Update the product image path in database
        $product->image = $product_image_filename;
        // Set the user_id based on the authenticated user
        $product->user_id = $user->id;

        // Save the product
        $product->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'data' => $product
        ])->setStatusCode(201);
    }

    // Show detail product from authenticated restaurant
    public function show(Request $request, int $id)
    {
        // Get the authenticated user
        $user = $request->user();

        // Search the product by id
        $product = Product::with('user')
            ->where('user_id', $user->id)
            ->find($id);

        // If the product is not found
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',

            ])->setStatusCode(404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product found',
            'data' => $product
        ])->setStatusCode(200);
    }
    // Update product
    public function update(Request $request, int $id)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'stock' => 'required|integer',
            'price' => 'required|integer',
            'is_available' => 'required|boolean',
            'is_favorite' => 'required|boolean',
        ]);

        // Search the product by id
        $product = Product::find($id);

        // If the product is not found
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ])->setStatusCode(404);
        }

        // Check if the form-data request has 'image' as key
        if ($request->hasFile('image')) {

            // Store the image in variable
            $product_image = $request->file('image');

            // Set the image name based on epoch time and extension based on MIME type
            // TODO: change the name to the hashName
            $product_image_filename = time() . '.' . $product_image->extension();

            // Delete the old image if it exists
            if ($product->image) {
                // path to the image
                $old_image = 'public/images/' . $product->image;
                Storage::delete($old_image);
            }

            // Store the new image in the storage
            $product_image->storeAs('public/images', $product_image_filename);

            // http://localhost:8000/storage/images/YOUR_IMAGE_NAME.EXTENSION

        } else {
            // If the request does not have 'image' key
            // Set the product image to the old image
            $product_image_filename = $product->image;
        }

        // Get all request data
        $data = $request->all();

        // Update the product
        $product->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'stock' => $data['stock'],
            'price' => $data['price'],
            'is_available' => $data['is_available'],
            'is_favorite' => $data['is_favorite'],
            'image' => $product_image_filename
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'data' => $product
        ])->setStatusCode(200);
    }

    // Delete product
    public function destroy(int $id)
    {
        // Search the product by id
        $product = Product::find($id);

        // If the product is not found
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ])->setStatusCode(404);
        }
        // Product found
        // Delete the product image
        Storage::delete('public/images/' . $product->image);

        // Delete the product
        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ])->setStatusCode(200);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Customer register
    public function customerRegister(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'required|string',
        ]);

        // Get all request data
        $data = $request->all();

        // Hash the password
        $data['password'] = Hash::make($data['password']);

        // Set the role to customer
        $data['role'] = 'customer';

        // Create the user
        $user = User::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer registered successfully',
            'data' => $user,
        ])->setStatusCode(201);
    }

    // login
    // TODO: try to use Auth::attempt
    public function login(Request $request)
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email is required',
            'email.email' => 'Email is not valid',
            'password.required' => 'Password is required',
            'password.string' => 'Password must be a string',
        ]);

        // Search by email
        $user = User::where('email', $request->email)->first();

        // Check if the user exists and if password correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email or password is incorrect.'
            ])->setStatusCode(401);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login success',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ])->setStatusCode(200);
    }

    // Logout
    public function logout(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Revoke the token that was used to authenticate the current request
        $user->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout success'
        ])->setStatusCode(200);
    }

    // Restaurant register
    public function restaurantRegister(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'required|string',
            'restaurant_name' => 'required|string',
            'restaurant_address' => 'required|string',
            'photo' => 'required|image',
            'latlong' => 'required|string',
        ]);

        // Store the image in variable
        $restaurant_image_file = $request->file('photo');

        // Set the image name based on epoch time and extension based on MIME type
        // TODO: change the name to the hashName
        $restaurant_image_filename = time() . '.' . $restaurant_image_file->extension();

        // Store the image in the storage
        $restaurant_image_file->storeAs('public/images', $restaurant_image_filename);
        // http://localhost:8000/storage/images/YOUR_IMAGE_NAME.EXTENSION

        // Get all request data
        $data = $request->all();

        // Hash the password
        $data['password'] = Hash::make($data['password']);

        // Set the role to restaurant
        $data['role'] = 'restaurant';

        // Create a new user
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->phone = $data['phone'];
        $user->restaurant_name = $data['restaurant_name'];
        $user->restaurant_address = $data['restaurant_address'];
        // Update the user image path in database
        $user->photo = $restaurant_image_filename;
        $user->role = $data['role'];
        $user->latlong = $data['latlong'];

        // Save the user
        $user->save();


        return response()->json([
            'status' => 'success',
            'message' => 'Restaurant registered successfully',
            'data' => $user,
        ])->setStatusCode(201);
    }

    // Driver register
    public function driverRegister(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'required|string',
            'license_plate' => 'required|string',
            'photo' => 'required|image',
        ]);

        // Store the image in variable
        $driver_image_file = $request->file('photo');

        // Set the image name based on epoch time and extension based on MIME type
        // TODO: change the name to the hashName
        $driver_image_filename = time() . '.' . $driver_image_file->extension();

        // Store the image in the storage
        $driver_image_file->storeAs('public/images', $driver_image_filename);
        // http://localhost:8000/storage/images/YOUR_IMAGE_NAME.EXTENSION

        // Get all request data
        $data = $request->all();

        // Hash the password
        $data['password'] = Hash::make($data['password']);

        // Set the role to driver
        $data['role'] = 'driver';

        // Create a new user
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->phone = $data['phone'];
        // Update the user image path in database
        $user->photo = $driver_image_filename;
        $user->role = $data['role'];
        $user->license_plate = $data['license_plate'];

        // Save the user
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Driver registered successfully',
            'data' => $user,
        ])->setStatusCode(201);
    }

    // Update latlong user
    public function updateLatLong(Request $request)
    {
        // Validate the request
        $request->validate([
            'latlong' => 'required|string',
            'address' => 'required|string',
        ]);

        // Get the authenticated user
        $user = $request->user();

        // Update the latlong
        $user->latlong = $request->latlong;
        // Update the address
        $user->address = $request->address;

        // Save the user
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Latlong updated successfully',
            'data' => $user,
        ])->setStatusCode(200);
    }


    // Get all restaurants
    public function getRestaurants()
    {
        $restaurant = User::where('role', 'restaurant')->get();

        if ($restaurant->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No restaurant found',
            ])->setStatusCode(404);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Get all restaurants',
            'data' => $restaurant,
        ])->setStatusCode(200);
    }

    // Update fcm_id
    public function updateFcmId(Request $request)
    {
        // Validate the request
        $request->validate([
            'fcm_id' => 'required|string',
        ]);

        // Get the authenticated user
        $user = $request->user();

        // Update the fcm_id
        $user->fcm_id = $request->fcm_id;

        // Save the user
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'FCM ID updated successfully',
            'data' => $user,
        ])->setStatusCode(200);
    }
}

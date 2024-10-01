<?php

namespace App\Http\Controllers;

use App\Models\ThirdPartyAuthProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints for user authentication"
 * )
 */
class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'nin_no' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255|unique:users',
            'email' => 'nullable|string|email|max:255|unique:users',
            'agree_to_terms' => 'nullable|boolean',
            'dateOfBirth' => 'nullable|date',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name', // Validate that the role exists
            'vendor_id' => 'nullable|exists:vendors,id',
        ]);

        try {
            // Check if the role exists before creating the user
            if (!Role::where('name', $request->role)->exists()) {
                return response()->json(['message' => 'Role does not exist'], 400);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'status' => $request->status,
                'password' => Hash::make($request->password),
                // Assuming 'status' is a required field, add it if needed
                'nin_no' => $validatedData['nin_no'],
                'phone_number' => $validatedData['phone_number'],
                'dateOfBirth' => $validatedData['dateOfBirth'],
                'agree_to_terms' => $validatedData['agree_to_terms'],
            ]);

            // Sync the user's role
            $user->syncRoles([$request->role]);

            // Handle UserVendor relationship
            if (isset($validatedData['vendor_id'])) {
                $user->vendors()->create(['vendor_id' => $validatedData['vendor_id']]);
            }

            // Check if the role is vendor, inspector, or seller
            if (in_array($request->role, ['Vendor', 'Inspector', 'Seller'])) {
                // Get all admin users
                $adminUsers = User::role('Admin')->get();

                foreach ($adminUsers as $admin) {
                    // Send email to each admin user
                    Mail::send('emails.accountCreated', ['user' => $user, 'admin' => $admin], function ($message) use ($admin, $user) {
                        $message->to($admin->email)->subject('New Account Created: ' . $user->name);
                    });
                }
            }

            // $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'data' => $user,
                // 'access_token' => $token,
                // 'token_type' => 'Bearer',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkLoginStatus()
    {
        // Check if the user is logged in
        if (!Auth::check()) {
            return response()->json(['message' => 'User is not logged in'], 401);
        }

        /** @var \App\Models\User */
        $user = Auth::user();

        // Retrieve the token
        $token = $user->tokens->first()->token ?? ''; // Adjusted to handle potential null value

        $response = [
            'message' => 'Hi ' . $user->name . ', welcome to home',
            'id' => $user->id,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'name' => $user->name,
            'lastlogin' => $user->lastlogin,
            'email' => $user->email,
            'nin_no' => $user->nin_no,
            'phone_number' => $user->phone_number,
            'dateOfBirth' => $user->dateOfBirth,
            'agree_to_terms' => $user->agree_to_terms,
            'status' => $user->status,
            'photo_url' => $user->photo_url,
            'permissions' => $user->getAllPermissions()->pluck('name'), // pluck for simplified array
            'role' => $user->getRoleNames()->first() ?? "",
        ];

        // Check if the user is a Vendor and include vendor details
        if ($user->hasRole('Vendor')) {
            $vendor = $user->vendors()->first(); // Assuming there's a vendors() relationship
            $response['vendor'] = [
                'id' => $vendor->vendor_id ?? null,
                'name' => $vendor->vendor->name ?? 'Unknown Vendor', // Assuming there's a name attribute on the vendor
            ];
        }

        return response()->json($response);
    }

    public function login(Request $request)
    {
        // if (!Auth::attempt($request->only('email', 'password'))) {
        //     return response()->json(['message' => 'Invalid Email Or Password'], 401);
        // }

        // $user = User::where('email', $request['email'])->firstOrFail();

        $credentials = $request->only('email', 'password');

        /** @var \App\Models\User $user **/
        $user = null;

        // Attempt to log in using email and password

        if (Auth::attempt($credentials)) {
            // Retrieve the authenticated user
            $user = Auth::user();
        } elseif (filter_var($request->email, FILTER_VALIDATE_EMAIL) === false) {
            // Attempt to log in using phone and password if email validation fails
            if (!Auth::attempt(['phone_number' => $request->email, 'password' => $request->password])) {
                // If authentication fails for both email and phone, return error response
                return response()->json(['message' => 'Invalid Phone Number Or Password'], 401);
            }
            // Retrieve the authenticated user
            $user = Auth::user();
        } else {
            // If authentication fails for email and no phone provided, return error response
            return response()->json(['message' => 'Invalid Email Or Password'], 401);
        }

        // Check if the user's status is active
        if ($user->status !== 'active') {
            return response()->json(['message' => 'Account is not active'], 403);
        }
        /** @var \App\Models\User $user **/
        // Load providers eagerly
        $user->load('providers');

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = [
            'message' => 'Hi ' . $user->name . ', welcome to home',
            'id' => $user->id,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'name' => $user->name,
            'photo_url' => $user->photo_url,
            'lastlogin' => $user->lastlogin,
            'email' => $user->email,
            'nin_no' => $user->nin_no,
            'phone_number' => $user->phone_number,
            'dateOfBirth' => $user->dateOfBirth,
            'agree_to_terms' => $user->agree_to_terms,
            'status' => $user->status,
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'role' => $user->getRoleNames()->first() ?? "",
            'provider_photo_urls' => $user->providers->pluck('photo_url'),
            'provider_photo_url' => $user->providers->first()?->photo_url,
        ];

        // Include vendor details if the user is a Vendor
        if ($user->hasRole('Vendor')) {
            $vendor = $user->vendors()->first(); // Assuming there's a vendors() relationship
            $response['vendor'] = [
                'id' => $vendor->vendor_id ?? null,
                'name' => $vendor->vendor->name ?? 'Unknown Vendor',
            ];
        }

        return response()->json($response);
    }

    public function thirdPartyLoginAuthentication(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'email' => 'nullable',
                'picture' => 'required',
                'client_id' => 'required',
                'provider' => 'required',
            ]);

            // Check if the email is provided
            if ($request->has('email') && isset($request->email)) {
                // Check if the user already exists with the provided email
                $user = User::where('email', $request->email)->first();
                if (empty($user)) {
                    return response()->json(['message' => 'Invalid Email'], 401);
                }

                // If the user exists, associate the provider with this user
                if ($user) {

                    // Create or update the provider entry
                    $user->providers()->updateOrCreate(
                        [
                            'provider' => $request->provider,
                            'provider_id' => $request->client_id,
                        ],
                        [
                            'photo_url' => $request->picture,
                        ]
                    );

                    // Log the user in
                    Auth::login($user);

                    // Retrieve the token
                    $token = $user->createToken('auth_token')->plainTextToken;

                    // Prepare the response
                    $response = [
                        'message' => 'Hi ' . $user->name . ', welcome to home',
                        'id' => $user->id,
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'name' => $user->name,
                        'lastlogin' => $user->lastlogin,
                        'email' => $user->email,
                        'nin_no' => $user->nin_no,
                        'status' => $user->status,
                        'photo_url' => $user->photo_url,
                        'provider_photo_url' => $request->picture,
                        'permissions' => $user->getAllPermissions()->pluck('name'), // pluck for simplified array
                        'role' => $user->getRoleNames()->first() ?? "",
                    ];

                    // Check if the user is a Vendor and include vendor details
                    if ($user->hasRole('Vendor')) {
                        $vendor = $user->vendors()->first(); // Assuming there's a vendors() relationship
                        $response['vendor'] = [
                            'id' => $vendor->vendor_id ?? null,
                            'name' => $vendor->vendor->name ?? 'Unknown Vendor', // Assuming there's a name attribute on the vendor
                        ];
                    }

                    // Return the response
                    return response()->json($response, 200);
                }
            }

            if (empty($request->has('email'))) {
                // If email is not provided or user does not exist with the provided email, check providers table
                $provider = ThirdPartyAuthProvider::where('provider_id', $request->client_id)->first();

                if (empty($provider->user)) {
                    return response()->json(['message' => 'Invalid Credentials'], 401);
                }

                // If provider found, associate the provider with the user
                if (isset($provider->user)) {
                    // Log the user in
                    Auth::login($provider->user);

                    // Retrieve the token
                    $token = $provider->user->createToken('auth_token')->plainTextToken;

                    // Prepare the response
                    $response = [
                        'message' => 'Hi ' . $provider->user->name . ', welcome to home',
                        'id' => $provider->user->id,
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'name' => $provider->user->name,
                        'lastlogin' => $provider->user->lastlogin,
                        'email' => $provider->user->email,
                        'phone_number' => $provider->user->phone_number,
                        'dateOfBirth' => $provider->user->dateOfBirth,
                        'agree_to_terms' => $provider->user->agree_to_terms,
                        'status' => $provider->user->status,
                        'photo_url' => $provider->user->photo_url,
                        'provider_photo_url' => $provider->photo_url,
                        'permissions' => $provider->user->getAllPermissions()->pluck('name'), // pluck for simplified array
                        'role' => $provider->user->getRoleNames()->first() ?? "",
                    ];

                    // Check if the user is a Vendor and include vendor details
                    if ($provider->user->hasRole('Vendor')) {
                        $vendor = $provider->user->vendors()->first(); // Assuming there's a vendors() relationship
                        $response['vendor'] = [
                            'id' => $vendor->vendor_id ?? null,
                            'name' => $vendor->vendor->name ?? 'Unknown Vendor', // Assuming there's a name attribute on the vendor
                        ];
                    }

                    // Return the response
                    return response()->json($response, 200);
                }
            }

            // If no user or provider found, throw error
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function thirdPartyRegisterAuthentication(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'email' => 'nullable|email|unique:users,email',
                'picture' => 'required',
                'client_id' => 'required',
                'provider' => 'required',
            ]);

            // Check if the email is provided
            if ($request->has('email')) {
                // Check if the user already exists with the provided email
                $user = User::where('email', $request->email)->first();

                if (isset($user)) {
                    return response()->json(['message' => 'Account Already Exists'], 409);
                }

                // $user = User::firstOrCreate(
                //     ['email' => $request->email], // Check by email
                //     [
                //         'email_verified_at' => now(),
                //         'name' => $request->name, // Use name from request
                //         'status' => "active",
                //         'password' => Hash::make($request->client_id),
                //     ]
                // );

                // If the user exists, associate the provider with this user
                if (empty($user)) {

                    $user = User::Create(
                        [
                            'email' => $request->email,
                            'email_verified_at' => now(),
                            'name' => $request->name, // Use name from request
                            'status' => "active",
                            'password' => Hash::make($request->client_id),
                        ]
                    );

                    // Create or update the provider entry
                    $user->providers()->updateOrCreate(
                        [
                            'provider' => $request->provider,
                            'provider_id' => $request->client_id,
                        ],
                        [
                            'photo_url' => $request->picture,
                        ]
                    );

                    // Log the user in
                    // Auth::login($user);

                    // Retrieve the token
                    $token = $user->createToken('auth_token')->plainTextToken;

                    return response()->json([
                        'data' => $user,
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                    ]);
                }
            }

            if (empty($request->has('email'))) {
                // If email is not provided or user does not exist with the provided email, check providers table
                $provider = ThirdPartyAuthProvider::where('provider_id', $request->client_id)->first();

                // If provider found, associate the provider with the user
                if (isset($provider->user)) {
                    return response()->json(['message' => 'Account Already Exists'], 409);
                } else {
                    // If provider is not set, create the user
                    $user = User::create([
                        'name' => $request->name,
                        'email' => null,
                        'status' => 'active', // Assuming default status is true
                        'password' => Hash::make($request->client_id),
                    ]);

                    // Create the provider entry for the new user
                    $newProvider = ThirdPartyAuthProvider::create([
                        'provider' => $request->provider,
                        'provider_id' => $request->client_id,
                        'user_id' => $user->id,
                        'photo_url' => $request->picture,
                    ]);

                    // Log the user in
                    // Auth::login($user);

                    // Retrieve the token
                    $token = $user->createToken('auth_token')->plainTextToken;

                    return response()->json([
                        'data' => $user,
                        'message' => 'Account created successfully',
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                    ]);
                }
            }

            // If no user or provider found, throw error
            throw ValidationException::withMessages([
                'email' => ['User not found.'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    // method for user logout and delete token
    public function logout()
    {
        /** @var \App\Models\User */
        $user = auth()->user(); // Get the authenticated user

        // Delete all tokens for the user
        $user->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * @OA\Post(
     *      path="/logout",
     *      operationId="logout",
     *      tags={"Authentication"},
     *      summary="Logout",
     *      description="Log out the currently authenticated user",
     *      security={
     *          {"bearerAuth": {}}
     *      },
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="integer", example=200),
     *              @OA\Property(property="message", type="string", example="You have successfully logged out and your token has been deleted"),
     *          ),
     *      )
     * )
     */
    // public function logout()
    // {
    //     Auth::user()->currentAccessToken()->delete();

    //     return $this->success(['message' => 'You have successfully logged out and your token has been deleted']);
    // }
}

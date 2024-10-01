<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // if (!Auth::user()->can('view users')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $query = User::query();

        // Order the results by the created_at column in descending order (latest first)
        // $query->latest();

        // Check if vendor_id is provided and not null
        if ($request->has('vendor_id') && $request->vendor_id !== null) {
            // Filter users by the provided vendor_id
            $query->whereHas('vendors', function ($query) use ($request) {
                $query->where('vendor_id', $request->vendor_id);
            });
        }

        // Filter by status if provided
        if ($request->has('status') && $request->status !== null) {
            if (is_array($request->status)) {
                $query->whereIn('status', $request->status);
            } else {
                $query->where('status', $request->status);
            }
        }
        // Filter by role if provided
        if ($request->has('role') && $request->role !== null) {
            $query->role($request->role); // This uses the role scope provided by Spatie's permission package
        }

        // Retrieve all users with their one-to-one relationships
        $users = $query->with(["reviews", "vendors.vendor"])->get();

        // Adding role names to each user
        $users->transform(function ($user) {
            $user->role = $user->getRoleNames()->first() ?? "";
            // Adding permissions
            $user->permissions = $user->getAllPermissions()->pluck('name');
            return $user;
        });

        return response()->json($users);
    }

    public function updateUserStatus(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'status' => 'required|string|in:pending,approved,rejected,active,deactive',
        ]);

        // Retrieve the user by ID
        $user = User::find($validatedData['user_id']);

        // Check if the user exists
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update the user's status
        $user->status = $validatedData['status'];
        $user->save();

        // Send email to the user about their status update
        Mail::send('emails.accountStatusUpdated', ['user' => $user], function ($message) use ($user) {
            $message->to($user->email)->subject('Account Status Updated');
        });

        return response()->json(['message' => 'User status updated successfully']);
    }

    // New method to get user by slug (assuming slug is a unique identifier like username)
    public function getBySlug($slug)
    {
        // Retrieve the user along with related details
        $user = User::with(['reviews', 'vendors.vendor', 'roles', 'permissions'])->where('slug', $slug)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Adding role names to the user
        $user->role = $user->getRoleNames()->first() ?? "";
        // Adding permissions
        $user->permissions = $user->getAllPermissions()->pluck('name');

        return response()->json($user);
    }
    public function getCarInspectors(Request $request)
    {
        // Check user authorization (optional, uncomment if needed)
        // if (!Auth::user()->can('view inspectors')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $query = User::with(['reviews']);

        // Filter by role (using whereHas for better performance)
        $query->whereHas('roles', function ($roleQuery) {
            $roleQuery->where('name', 'Inspector');
        });

        // Filter by active status
        $query->where('status', 'active');

        // Apply search filter (if provided)
        $search = $request->query('search');
        if ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->query('per_page', 10); // Default to 10 per page
        $page = $request->query('page', 1); // Default to first page

        $paginatedUsers = $query->paginate($perPage);

        // Return the paginated response (adjust structure as needed)
        return response()->json($paginatedUsers);
    }

    public function show($id)
    {
        // if (!Auth::user()->can('view user')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $user = User::with(['reviews', "vendors.vendor"])->findOrFail($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Adding role name
        $user->role = $user->getRoleNames()->first() ?? "";

        // Adding permissions
        // $user_perissions = $user->getAllPermissions()->pluck('name');
        // $user->permissions = $user_perissions;
        $user->permissions = $user->getPermissionsViaRoles()->pluck('name');

        return response()->json($user);
    }

    public function store(Request $request)
    {
        // Check permission
        // if (!Auth::user()->can('create user')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255|unique:users',
            'email' => 'nullable|string|email|max:255|unique:users',
            // 'agree_to_terms' => 'nullable|boolean',
            'dateOfBirth' => 'nullable|date',
            'status' => 'required|string|max:255',
            'nin_no' => 'required|string|max:255',
            'lastlogin' => 'nullable|date',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
            'vendor_id' => 'nullable|exists:vendors,id', // validate vendor_id
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // Expect a file for the photo
        ]);

        // Manually handle the `agree_to_terms` field
        // Manually handle the `agree_to_terms` field
        $agreeToTerms = $request->input('agree_to_terms');
        if ($agreeToTerms !== null) {
            if (!in_array($agreeToTerms, ['true', 'false', '1', '0', 1, 0, true, false], true)) {
                return response()->json(['error' => 'The agree_to_terms field must be a boolean.'], 422);
            }
            $validatedData['agree_to_terms'] = filter_var($agreeToTerms, FILTER_VALIDATE_BOOLEAN);
        }

        if (empty($request->phone_number) && empty($request->email)) {
            return response()->json([
                'message' => 'Either phone number or email is required.',
            ], 400); // 400 Bad Request
        }

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'user_photos'); // Save the photo in a specific folder
        }

        DB::beginTransaction();

        try {

            // Create user
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'],
                'dateOfBirth' => $validatedData['dateOfBirth'],
                'agree_to_terms' => $validatedData['agree_to_terms'],
                'nin_no' => $validatedData['nin_no'],
                'status' => $validatedData['status'],
                'lastlogin' => $validatedData['lastlogin'] ?? now(),
                'password' => Hash::make($validatedData['password']),
                'photo_url' => $photoUrl,
            ]);

            // Sync the user's role
            $user->syncRoles([$validatedData['role']]);

            // Optionally get permissions associated with the user's role
            // $permissions = Permission::whereIn('id', $user->roles->first()->permissions->pluck('id'))->pluck('name');
            // $user->permissions = $permissions;

            // Handle UserVendor relationship
            if (isset($validatedData['vendor_id'])) {
                $user->vendors()->create(['vendor_id' => $validatedData['vendor_id']]);
            }

            DB::commit();
            return response()->json(['message' => 'User created successfully!', 'user' => $user], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'User creation failed: ' . $e->getMessage()], 500);
        }
    }

    private function uploadPhoto($photo, $folderPath)
    {
        $publicPath = public_path($folderPath);
        if (!File::exists($publicPath)) {
            File::makeDirectory($publicPath, 0777, true, true);
        }

        $fileName = time() . '_' . $photo->getClientOriginalName();
        $photo->move($publicPath, $fileName);

        return '/' . $folderPath . '/' . $fileName;
    }

    private function deletePhoto($photoUrl)
    {
        $photoPath = parse_url($photoUrl, PHP_URL_PATH);
        $photoPath = public_path($photoPath);
        if (File::exists($photoPath)) {
            File::delete($photoPath);
        }
    }

    public function update(Request $request, $id)
    {

        // Check permission
        // if (!Auth::user()->can('update user')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone_number' => 'nullable|string|max:255|unique:users,phone_number,' . $id,
            'agree_to_terms' => 'nullable|boolean',
            'dateOfBirth' => 'nullable|date',
            'status' => 'required|string|max:255',
            'lastlogin' => 'nullable|date',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // Validation for photo
            'role' => 'sometimes|exists:roles,name',
            'vendor_id' => 'nullable|exists:vendors,id',
        ]);

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $photoUrl = $user->photo_url;
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($photoUrl) {
                $photoPath = parse_url($photoUrl, PHP_URL_PATH);
                $photoPath = ltrim($photoPath, '/');
                if (file_exists(public_path($photoPath))) {
                    unlink(public_path($photoPath));
                }
            }
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'user_photos');
        }

        DB::beginTransaction();

        try {
            $user->update([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'],
                'dateOfBirth' => $validatedData['dateOfBirth'],
                'agree_to_terms' => $validatedData['agree_to_terms'],
                'status' => $validatedData['status'],
                'lastlogin' => $validatedData['lastlogin'] ?? now(),
                'photo_url' => $photoUrl,
            ]);

            if (isset($validatedData['role'])) {
                $user->syncRoles([$validatedData['role']]);
            }

            if (isset($validatedData['vendor_id'])) {
                $user->vendors()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['vendor_id' => $validatedData['vendor_id']]
                );
            }
            // else {
            //     $user->vendors()->delete();
            // }

            DB::commit();
            return response()->json(['message' => 'User updated successfully!', 'user' => $user], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    // ========================== destroy ====================

    public function destroy($id)
    {

        // if (!Auth::user()->can('delete user')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Delete user photo if exists
        if ($user->photo_url) {
            $photoPath = parse_url($user->photo_url, PHP_URL_PATH);
            $photoPath = ltrim($photoPath, '/');
            if (file_exists(public_path($photoPath))) {
                unlink(public_path($photoPath));
            }
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function update_profile_photo(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = User::find(Auth::id());

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $photoUrl = $user->photo_url;

        if ($request->file('photo')) {
            // Delete old photo if it exists
            if ($photoUrl) {
                $this->deletePhoto($photoUrl);
            }
            $photoUrl = $this->uploadPhoto($request->file('photo'), 'user_photos');

            $user->photo_url = $photoUrl;
            $user->save();

            // Save the image file name to the user's photo column

            return response()->json([
                'message' => 'Image uploaded successfully',
                'id' => $user->id,
                'name' => $user->name,
                'photo_url' => $user->photo_url,
                'lastlogin' => $user->lastlogin,
                'email' => $user->email,
                'status' => $user->status,
                // 'permissions' => $user->getAllPermissions()->pluck('name'),
                // 'role' => $user->getRoleNames()->first() ?? "",
            ]);
        }

        return response()->json(['message' => 'Failed to upload image']);
    }

    public function profile_update(Request $request, $id)
    {

        // Check permission
        // if (!Auth::user()->can('update user')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone_number' => 'required|string|max:255|unique:users,phone_number,' . $id,

        ]);

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        DB::beginTransaction();

        try {
            $user->update([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'],
            ]);

            DB::commit();
            return response()->json(['message' => 'User updated successfully!', 'id' => $user->id,
                'name' => $user->name,
                'photo_url' => $user->photo_url,
                'lastlogin' => $user->lastlogin,
                'email' => $user->email,
                'status' => $user->status], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }
}
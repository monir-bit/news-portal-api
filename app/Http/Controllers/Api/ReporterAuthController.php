<?php

namespace App\Http\Controllers\Api;

use App\Applications\Helpers\UtilsHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReporterChangePasswordRequest;
use App\Http\Requests\Api\ReporterLoginRequest;
use App\Http\Requests\Api\ReporterProfileUpdateRequest;
use App\Models\Reporter;
use App\Repositories\MediaHelperRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ReporterAuthController extends Controller
{
    public function __construct(
        private MediaHelperRepositoryInterface $mediaHelper
    ) {}
    /**
     * Reporter login with phone and password.
     */
    public function login(ReporterLoginRequest $request): JsonResponse
    {
        $reporter = Reporter::where('phone', $request->phone)->first();

        if (!$reporter || !Hash::check($request->password, $reporter->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone or password.',
            ], 401);
        }

        if (!$reporter->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'আপনার অ্যাকাউন্টটি ইনঅ্যাক্টিভ আছে, যোগাযোগ করুন',
                'code' => 'reporter_inactive',
            ], 403);
        }

        $reporter->tokens()->delete();

        $token = $reporter->createToken('reporter-auth')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'reporter' => $reporter->load(['categories', 'locations.division', 'locations.district', 'locations.upazila']),
            ],
        ]);
    }

    /**
     * Get authenticated reporter profile.
     */
    public function profile(): JsonResponse
    {
        $reporter = auth('sanctum')->user();

        if (!$reporter) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $reporter->load(['categories', 'locations.division', 'locations.district', 'locations.upazila']);

        return response()->json([
            'success' => true,
            'data' => $reporter,
        ]);
    }

    /**
     * Update reporter profile (name, email, joining_date, image).
     * Phone and designation cannot be changed.
     */
    public function updateProfile(ReporterProfileUpdateRequest $request): JsonResponse
    {
        $reporter = auth('sanctum')->user();

        if (!$reporter) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }


        $reporter->update([
            'name' => $request->name,
            'email' => $request->email ?: null,
            'joining_date' => $request->joining_date ?: null,
        ]);

        if($request->has('image')){
            $reporter->update(['image' => $request->image]);
        }

        $reporter->load(['categories', 'locations.division', 'locations.district', 'locations.upazila']);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $reporter,
        ]);
    }

    /**
     * Upload reporter profile image. Returns path for profile update.
     */
    public function uploadProfileImage(Request $request): JsonResponse
    {
        $reporter = auth('sanctum')->user();

        if (!$reporter) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = UtilsHelper::MonthYearWisePath();
        $uploadedPath = $this->mediaHelper->upload($request->file('image'), $path);

        return response()->json([
            'success' => true,
            'data' => [
                'image_path' => $uploadedPath,
                'image_url' => $this->mediaHelper->url($uploadedPath),
            ],
        ]);
    }

    /**
     * Change reporter password.
     */
    public function changePassword(ReporterChangePasswordRequest $request): JsonResponse
    {
        $reporter = auth('sanctum')->user();

        if (!$reporter) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $reporter->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * Logout (revoke current token).
     */
    public function logout(): JsonResponse
    {
        $reporter = auth('sanctum')->user();

        if ($reporter) {
            $reporter->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}

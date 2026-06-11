<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated($users, UserResource::class);
    }

    public function show(User $user): JsonResponse
    {
        return $this->ok(new UserResource($user));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'avatar' => ['nullable', 'string', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        return $this->created(new UserResource(User::query()->create($data)));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->is_admin || $request->user()->is($user), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email:rfc', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'avatar' => ['nullable', 'string', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        if (! $request->user()->is_admin) {
            unset($data['is_admin']);
        }

        $user->update($data);

        return $this->ok(new UserResource($user->refresh()));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->is_admin || $request->user()->is($user), 403);

        $user->delete();

        return $this->noContent();
    }
}

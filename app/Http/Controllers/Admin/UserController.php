<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $users = $me->visibleUsersQuery()
            ->with('store:id,name')
            ->select('id', 'name', 'email', 'phone', 'role', 'store_id', 'is_active', 'created_at')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                ...$u->toArray(),
                'role_label' => $u->roleLabel(),
                'can_manage' => $me->canManage($u),
                'can_delete' => $me->canDelete($u),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users'      => $users,
            'can_create' => $me->canCreateUsers(),
        ]);
    }

    public function create(Request $request)
    {
        $me = $request->user();
        abort_unless($me->canCreateUsers(), 403);

        return Inertia::render('Admin/Users/Edit', [
            'user'             => null,
            'assignable_roles' => $this->roleOptions($me->assignableRoles()),
            'stores'           => $me->role === 'manager' ? [] : Store::orderBy('name')->get(['id', 'name']),
            'locked_store'     => $me->role === 'manager' ? $me->store?->only(['id', 'name']) : null,
        ]);
    }

    public function store(Request $request)
    {
        $me = $request->user();
        abort_unless($me->canCreateUsers(), 403);

        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email',
            'phone'     => 'nullable|string|max:50',
            'role'      => ['required', Rule::in($me->assignableRoles())],
            'store_id'  => ['nullable', 'exists:stores,id', Rule::requiredIf(fn () => in_array($request->input('role'), User::ROLES_REQUIRING_STORE))],
            'is_active' => 'boolean',
            'password'  => 'required|string|min:8|confirmed',
        ]);

        // 店長只能在自己的分店底下開店員帳號
        if ($me->role === 'manager') {
            $data['store_id'] = $me->store_id;
        }

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('admin.users.index')->with('success', '帳號已建立。');
    }

    public function edit(Request $request, User $user)
    {
        $me = $request->user();
        abort_unless($me->canManage($user), 403);

        // 編輯自己的帳號時，角色維持不變（不能自我升級）；否則依當前使用者可指派的角色範圍
        $assignableRoles = $me->id === $user->id ? [$user->role] : $me->assignableRoles();

        return Inertia::render('Admin/Users/Edit', [
            'user'             => $user,
            'assignable_roles' => $this->roleOptions($assignableRoles ?: [$user->role]),
            'can_change_role'  => $me->id !== $user->id && !empty($me->assignableRoles()),
            'stores'           => $me->role === 'manager' ? [] : Store::orderBy('name')->get(['id', 'name']),
            'locked_store'     => $me->role === 'manager' ? $me->store?->only(['id', 'name']) : null,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $me = $request->user();
        abort_unless($me->canManage($user), 403);

        $editingSelf = $me->id === $user->id;
        $allowedRoles = $editingSelf ? [$user->role] : $me->assignableRoles();

        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'     => 'nullable|string|max:50',
            'role'      => ['required', Rule::in($allowedRoles ?: [$user->role])],
            'store_id'  => ['nullable', 'exists:stores,id', Rule::requiredIf(fn () => in_array($request->input('role'), User::ROLES_REQUIRING_STORE))],
            'is_active' => 'boolean',
            'password'  => 'nullable|string|min:8|confirmed',
        ]);

        // 店長只能管理自己分店底下的店員，不能把店員轉去別的分店
        if ($me->role === 'manager') {
            $data['store_id'] = $me->store_id;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', '帳號已更新。');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($request->user()->canDelete($user), 403);

        $user->delete();

        return back()->with('success', '帳號已刪除。');
    }

    private function roleOptions(array $roles): array
    {
        return array_map(fn ($role) => ['value' => $role, 'label' => User::ROLE_LABELS[$role] ?? $role], $roles);
    }
}

<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\BanUserRequest;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateMyProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexUserRequest $request)
    {
        $data = $request->validated();
        $query = User::query()->withoutRole('superAdmin');
        
        $query->when(isset($data['role']) , function($query) use($data){
            $query->role($data['role']);
        })->when(isset($data['query']) , function($query) use($data){
            $query->where(fn ($query) => $query->where('name' , 'like' , '%' . $data['query'] . '%')
            ->orWhere('email' , 'like' , '%' . $data['query'] . '%'))
            ->orWhere('phone' , 'like' , '%' . $data['query'] . '%');
        })->when(isset($data['ban']) , function($query) use($data){
            if($data['ban']){
                $query->where('ban' , '>' , Carbon::now());
            } else{
                $query->whereNull('ban')->orWhere('ban' , '<' , Carbon::now());
            }
        });

        $users = $query->paginate($data['per_page'] ?? 15);

        return $this->respondOk(UserResource::collection($users)->response()->getData(), 'Users fetched successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $user = User::create($data);
        $user->assignRole($data['role']);
        return $this->respondCreated(new UserResource($user) , 'User created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    public function update(UpdateUserRequest $request , User $user)
    {
        $data = $request->validated();

        if ($user->hasRole('superAdmin')){
            return $this->respondError('You can not update super admin');
        }

        $user->update($data);

        if(isset($data['role'])) {
            $user->syncRoles($data['role']);
        }

        // if ($request->hasFile('image')) {
        //     $user->clearMediaCollection("main");
        //     $user->addMediaFromRequest('image')->toMediaCollection("main");
        // }

        return $this->respondOk(UserResource::make($user) , 'User updated successfully');

    }

    /**
     * Update the specified resource in storage.
     */
    public function update_profile(UpdateMyProfileRequest $request)
    {
        $data = $request->validated();
        $user = $request->user;

        $user->update($data);

        // if ($request->hasFile('image')) {
        //     $user->clearMediaCollection("main");
        //     $user->addMediaFromRequest('image')->toMediaCollection("main");
        // }

        return $this->respondOk(UserResource::make($user) , 'Updated Your Profile successfully');

    }

    public function my_profile(Request $request)
    {
        $user = $request->user;
        return $this->respondOk(UserResource::make($user) , 'User fetched successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->hasRole('superAdmin')) {
            return $this->respondError('super admin can not be deleted');
        }

        $user->delete();
        return $this->respondNoContent();
    }

    public function ban(User $user , BanUserRequest $request)
    {
        $data = $request->validated();

        if ($user->hasRole('superAdmin')) {
            return $this->respondError('super admin can not be banned');
        }

        $user->update($data);
        return $this->respondNoContent();
    }
}

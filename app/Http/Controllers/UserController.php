<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\View;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function register(Request $request) {
        $incomingFields = $request->validate([
            'username' => ['required', 'min:3', 'max:20', Rule::unique('users', 'username')],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'min:8', 'confirmed']
        ]);

        $user = User::create($incomingFields);

        auth()->login($user);

        return redirect('/')->with('success', 'Thank you for registration');;
    }

    public function login(Request $request) {
        $incomingFields = $request->validate([
            'loginusername' => 'required',
            'loginpassword' => 'required'
        ]);

        if (auth()->attempt(['username' => $incomingFields['loginusername'], 'password' => $incomingFields['loginpassword']])) {
            $request->session()->regenerate();
            return redirect('/')->with('success', 'You are now logged in');
        } else {
            return redirect('/')->with('failure', 'Incorrect username or password');
        }
    }

    public function showCorrectHomepage() {
        if(auth()->check()) {
            return view('homepage-feed', ['posts' => auth()->user()->feedPosts()->latest()->paginate(10)]);
        } else {
            return view('homepage');
        }
    }

    public function logout() {
        auth()->logout();
        return redirect('/')->with('success', 'You are now logged out');
    }

    private function getProfileSharedData($user) {
        $currentlyFollowing = 0;

        if (auth()->check()) {
            $currentlyFollowing = Follow::where([['user_id', '=', auth()->user()->id], ['followeduser', '=', $user->id]])->count();
        }

        View::share('sharedData', [
            'avatar' => $user->avatar,
            'username' => $user->username, 
            'postsCount' => $user->posts()->count(),
            'currentlyFollowing' => $currentlyFollowing,
            'userId' => $user->id,
            'followerCount' => $user->followers()->count(),
            'followingCount' => $user->followingTheseUsers()->count()
        ]);
    }

    public function profile(User $user) {
        $this->getProfileSharedData($user);
        return view('profile-posts', [
            'posts' => $user->posts()->latest()->get(),
        ]);
    }

    public function profileRaw(User $user) {
        return response()->json(['theHTML' => view('profile-posts-only', ['posts' => $user->posts()->latest()->get()])->render(), 'docTitle' => $user->username . "'s Profile"]);
    }

    public function profileFollowers(User $user) {
        $this->getProfileSharedData($user);
        return view('profile-followers', [
            'followers' => $user->followers()->latest()->get(),
        ]);
    }

    public function profileFollowersRaw(User $user) {
        return response()->json(['theHTML' => view('profile-followers-only', ['followers' => $user->followers()->latest()->get()])->render(), 'docTitle' => $user->username . "'s Followers"]);
    }

    public function profileFollowing(User $user) {
        $this->getProfileSharedData($user);
        return view('profile-following', [
            'followingTheseUsers' => $user->followingTheseUsers()->latest()->get(),
        ]);
    }

    public function profileFollowingRaw(User $user) {
        return response()->json(['theHTML' => view('profile-following-only', ['followingTheseUsers' => $user->followingTheseUsers()->latest()->get()])->render(), 'docTitle' => 'Who ' . $user->username . " Follows"]);
    }

    public function showAvatarForm() {
        return view('avatar-form');
    }

    public function storeAvatar(Request $request) {
        $request->validate([
            'avatar' => 'required|image|max:3000'
        ]);

        $user = auth()->user();
        $filename = $user->id . '-' . uniqid() . '.jpg';
        $imgData = Image::make($request->file('avatar'))->fit(120)->encode('jpg');

        Storage::put('public/avatars/' . $filename, $imgData);

        $oldAvatar = $user->avatar;

        $user->avatar = $filename;
        $user->save();

        if ($oldAvatar != "/fallback-avatar.jpg") {
            Storage::delete(str_replace("/storage/", "public/", $oldAvatar));
        }

        return back()->with('success', 'Avatar successfully updated');
    }
}

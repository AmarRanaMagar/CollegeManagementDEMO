<?php

namespace App\Http\Controllers;

use App\Interfaces\UserInterface;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request, UserInterface $userRepository)
    {
        $request->validate([
            'photo' => 'nullable|string',
        ]);

        try {
            $userRepository->updateProfilePhoto(auth()->user()->id, $request->input('photo'));

            return back()->with('status', 'Profile photo updated successfully!');
        } catch (\Exception $e) {
            return back()->withError($e->getMessage());
        }
    }
}

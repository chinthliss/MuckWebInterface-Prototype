<?php

namespace App\Http\Controllers;

use App\AvatarService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvatarController extends Controller
{
    public function showAvatarEditor(): View
    {
        return view('multiplayer.avatar');
    }

    public function showAdminDollTest(AvatarService $service): View
    {
        $dolls = array_map(function ($doll) {
            return [
                'name' => $doll,
                'url' => route('admin.avatar.dollthumbnail', ['dollName' => $doll])
            ];
        }, $service->getDolls());

        return view('multiplayer.avatar-doll-test')->with([
            'dolls' => $dolls
        ]);
    }

    public function getThumbnailForDoll(AvatarService $service, string $dollName)
    {
        $image = $service->getDollThumbnail($dollName);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

}

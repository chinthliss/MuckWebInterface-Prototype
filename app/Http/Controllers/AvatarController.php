<?php

namespace App\Http\Controllers;

use App\AvatarService;
use App\Muck\MuckConnection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvatarController extends Controller
{
    public function showAvatarEditor(): View
    {
        return view('multiplayer.avatar');
    }

    public function showAdminDollTest(AvatarService $service, MuckConnection $muckConnection): View
    {
        $dollUsage = $muckConnection->avatarDollUsage();
        // Going to unset entries in dollUsage as they're used, so we can track any remaining.
        $dolls = array_map(function ($doll) use (&$dollUsage) {
            $usage = [];
            if (array_key_exists($doll, $dollUsage)) {
                $usage = $dollUsage[$doll];
                unset($dollUsage[$doll]);
            }
            return [
                'name' => $doll,
                'url' => route('admin.avatar.dollthumbnail', ['dollName' => $doll]),
                'usage' => $usage
            ];
        }, $service->getDolls());

        return view('multiplayer.avatar-doll-test')->with([
            'dolls' => $dolls,
            'invalid' => $dollUsage
        ]);
    }

    public function getThumbnailForDoll(AvatarService $service, string $dollName)
    {
        $image = $service->getDollThumbnail($dollName);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

}

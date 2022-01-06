<?php

namespace App\Http\Controllers;

use App\AvatarInstance;
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

    public function showAdminDollList(AvatarService $service, MuckConnection $muckConnection): View
    {
        $dollUsage = $muckConnection->avatarDollUsage();
        // Going to unset entries in dollUsage as they're used, so we can track any remaining.
        $dolls = array_map(function ($doll) use (&$dollUsage, $service) {
            $usage = [];
            if (array_key_exists($doll, $dollUsage)) {
                $usage = $dollUsage[$doll];
                unset($dollUsage[$doll]);
            }
            return [
                'name' => $doll,
                'url' => route('admin.avatar.dollthumbnail', ['dollName' => $doll]),
                'edit' => route('admin.avatar.dolltest', ['code' => $service->getBaseCodeForDoll($doll)]),
                'usage' => $usage
            ];
        }, $service->getDollNames());

        return view('multiplayer.avatar-doll-list')->with([
            'dolls' => $dolls,
            'invalid' => $dollUsage
        ]);
    }

    public function showAdminDollTest(AvatarService $service, MuckConnection $muckConnection, string $code = '')
    {
        //Redirect to doll list if a code isn't specified
        if (!$code) return redirect()->route('admin.avatar.dolllist');

        $avatar = AvatarInstance::fromCode($code);
        $drawingSteps = $service->getDrawingStepsForAvatar($avatar);
        //Remove 'doll' object from drawingsteps because it's just a reference to the Imagick object
        $drawingSteps = array_map(function($step) {
            unset($step['doll']);
            return $step;
        }, $drawingSteps);

        $dolls = $service->getDollNames();

        return view('multiplayer.avatar-doll-test')->with([
            'code' => $code,
            'drawingSteps' => $drawingSteps,
            'dolls' => $dolls,
            'avatarWidth' => $service->avatarWidth(),
            'avatarHeight' => $service->avatarHeight()
        ]);
    }

    public function getThumbnailForDoll(AvatarService $service, string $dollName)
    {
        $image = $service->getDollThumbnail($dollName);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    public function getAvatarFromCode(AvatarService $service, string $code)
    {
        $avatar = AvatarInstance::fromCode($code);
        $image = $service->renderAvatarInstance($avatar);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

}

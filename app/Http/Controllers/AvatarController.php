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

        return view('admin.avatar-doll-list')->with([
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
        $drawingSteps = array_map(function ($step) {
            unset($step['doll']);
            return $step;
        }, $drawingSteps);

        $dolls = $service->getDollNames();
        $gradients = $service->getGradients();

        return view('admin.avatar-doll-test')->with([
            'code' => $code,
            'drawingSteps' => $drawingSteps,
            'dolls' => $dolls,
            'gradients' => array_keys($gradients),
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

    #region Gradients

    public function showUserAvatarGradients(AvatarService $service)
    {
        $gradients = [];
        foreach ($service->getGradients() as $gradient) {
            $gradients[] = [
                'name' => $gradient->name,
                'desc' => $gradient->desc,
                'free' => $gradient->free,
                'url' => route('avatar.gradient.image', ['name' => $gradient->name])
            ];
        }
        return view('multiplayer.avatar-gradient', [
            'gradients' => $gradients
        ]);
    }

    public function showAdminAvatarGradients(AvatarService $service)
    {
        $gradients = [];
        foreach ($service->getGradients() as $gradient) {
            $gradients[] = [
                'name' => $gradient->name,
                'desc' => $gradient->desc,
                'free' => $gradient->free,
                'owner_aid' => $gradient->owner?->getAid(),
                'owner_url' => $gradient->owner?->getAdminUrl(),
                'url' => route('avatar.gradient.image', ['name' => $gradient->name])
            ];
        }
        return view('admin.avatar-gradient', [
            'gradients' => $gradients
        ]);
    }

    public function getGradient(string $name, AvatarService $service)
    {
        $gradient = $service->getGradient($name);
        if (!$gradient) abort(404);

        $image = $service->getGradientImage($gradient, true);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());

    }
    #endregion Gradients
}

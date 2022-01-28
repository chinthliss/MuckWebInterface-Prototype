<?php

namespace App\Http\Controllers;

use App\Avatar\AvatarGradient;
use App\Avatar\AvatarInstance;
use App\Avatar\AvatarService;
use App\Muck\MuckConnection;
use Illuminate\Http\Response;
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

    public function showAdminDollTest(AvatarService $service, string $code = ''): Mixed
    {
        //Redirect to doll list if a code isn't specified
        if (!$code) return redirect()->route('admin.avatar.dolllist');

        $avatar = AvatarInstance::fromCode($code);
        $drawingSteps = $service->getDrawingPlanForAvatarInstance($avatar);
        //Return simplified version without the doll object
        $drawingSteps = array_map(function ($step) {
            return [
                'dollName' => $step->dollName,
                'part' => $step->part,
                'subPart' => $step->subPart,
                'layers' => $step->layers
            ];
        }, $drawingSteps);

        $dolls = $service->getDollNames();
        $gradients = array_map(function ($gradient) {
            return $gradient->name;
        }, $service->getGradients());

        return view('admin.avatar-doll-test')->with([
            'code' => $code,
            'drawingSteps' => $drawingSteps,
            'dolls' => $dolls,
            'gradients' => $gradients,
            'avatarWidth' => $service::DOLL_WIDTH,
            'avatarHeight' => $service::DOLL_HEIGHT
        ]);
    }

    public function getThumbnailForDoll(AvatarService $service, string $dollName): Response
    {
        $image = $service->getDollThumbnail($dollName);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    public function getAvatarFromCode(AvatarService $service, string $code): Response
    {
        $avatar = AvatarInstance::fromCode($code);
        $image = $service->renderAvatarInstance($avatar);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    #region Gradients

    public function showUserAvatarGradients(AvatarService $service): View
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

    public function showAdminAvatarGradients(AvatarService $service): View
    {
        $gradients = [];
        foreach ($service->getGradients() as $gradient) {
            $gradients[] = [
                'name' => $gradient->name,
                'desc' => $gradient->desc,
                'free' => $gradient->free,
                'created_at' => $gradient->created_at,
                'owner_aid' => $gradient->owner?->getAid(),
                'owner_url' => $gradient->owner?->getAdminUrl(),
                'url' => route('avatar.gradient.image', ['name' => $gradient->name])
            ];
        }
        return view('admin.avatar-gradient', [
            'gradients' => $gradients
        ]);
    }

    public function getGradient(string $name, AvatarService $service): Response
    {
        $gradient = $service->getGradient($name);
        if (!$gradient) abort(404);

        $image = $service->renderGradientImage($gradient, true);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());

    }

    public function getGradientPreview(string $code, AvatarService $service): Response
    {
        $config = json_decode(base64_decode($code), JSON_FORCE_OBJECT);

        if (!array_key_exists('steps', $config)) abort(400);

        $steps = $config['steps'];
        $gradient = new AvatarGradient('_temporary', '_temporary', $steps, true, null);
        $image = $service->renderGradientAvatarPreview($gradient);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }
    #endregion Gradients
}

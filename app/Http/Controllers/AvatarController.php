<?php

namespace App\Http\Controllers;

use App\Avatar\AvatarGradient;
use App\Avatar\AvatarInstance;
use App\Avatar\AvatarService;
use App\Muck\MuckConnection;
use App\Muck\MuckObjectService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Imagick;
use JetBrains\PhpStorm\ArrayShape;

class AvatarController extends Controller
{
    public function showAvatarEditor(AvatarService $service, MuckConnection $muck): View
    {
        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();

        // Need to pick up ownership and whether someone meets the requirements for things from the muck
        $itemCatalog = $service->getAvatarItems();
        $requirements = [];
        foreach ($itemCatalog as $item) {
            if ($item->requirement) $requirements[$item->id] = $item->requirement;
        }
        $muckResponse = $muck->bootAvatarEditor($character, $requirements);

        if (!array_key_exists('gradients', $muckResponse) || !array_key_exists('items', $muckResponse))
            throw new \Exception("Muck response was missing an expected part!");

        // Gradients
        // Format is gradientName:Available
        $gradients = [];
        foreach ($service->getGradients() as $gradient) {
            $gradients[$gradient->name] =
                $gradient->free
                || ($gradient->owner && $gradient->owner === $character->aid())
                || in_array($gradient->name, $muckResponse['gradients']);
        }

        // Items (And backgrounds)
        // Format is an array from the item itself but also included 'earned' and 'owner' flags
        $items = [];
        $backgrounds = [];
        foreach ($itemCatalog as $item) {
            $array = $item->toCatalogArray();
            $earned = false;
            $owner = false;
            if (array_key_exists($item->id, $muckResponse['items'])) {
                if ($muckResponse['items'][$item->id] & 1) $earned = true;
                if ($muckResponse['items'][$item->id] & 2) $owner = true;
            }
            $array['requirement'] = $item->requirement ? true : false;
            $array['earned'] = $earned;
            $array['owner'] = $owner;
            if ($item->type === 'background')
                $backgrounds[] = $array;
            else
                $items[] = $array;
        }

        return view('multiplayer.avatar')->with([
            'gradients' => $gradients,
            'items' => $items,
            'backgrounds' => $backgrounds,
            'avatarWidth' => $service::DOLL_WIDTH,
            'avatarHeight' => $service::DOLL_HEIGHT
        ]);
    }


    /**
     * @return array
     */
    #[ArrayShape([
        'background' => "array|null",
        'items' => "array",
        'colors' => "string[]"
    ])]
    public function getAvatarState(): array
    {
        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();
        $avatar = $character->avatarInstance();

        // Items presently in use
        $presentItems = [];
        $presentBackground = null;
        foreach ($avatar->items as $item) {
            $array = $item->toCatalogArray();
            if ($item->type === 'background')
                $presentBackground = $array;
            else
                $presentItems[] = $array;
        }

        return [
            'background' => $presentBackground,
            'items' => $presentItems,
            'colors' => $avatar->colors
        ];
    }


    public function setAvatarState(AvatarService $service, MuckConnection $muckConnection)
    {

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
                'edit' => route('admin.avatar.dolltest', ['code' => $service->getBaseCodeForDoll($doll, true, true)]),
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

    private function applyOptionsToAvatarImage(Imagick &$avatarImage, Request $request)
    {
        if ($request->has('mode')) {
            $mode = $request->get('mode');
            if ($mode == 'inline') {
                $avatarImage->cropImage(150, 120, 130, 52);
                $avatarImage->setImagePage(150, 120, 0, 0);
                //$avatarImage->scaleImage(85, 60);
            }
        }
    }

    public function getThumbnailForDoll(AvatarService $service, string $dollName): Response
    {
        $image = $service->getDollThumbnail($dollName);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    /**
     * Returns an avatar from a full specification
     * @param AvatarService $service
     * @param string $code
     * @return Response
     * @throws \ImagickException
     */
    public function getAvatarFromAdminCode(AvatarService $service, string $code): Response
    {
        $avatar = AvatarInstance::fromCode($code);
        $image = $service->renderAvatarInstance($avatar);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    /**
     * For the avatar editor - returns an avatar where the avatar doll is always the user's active character
     * @param AvatarService $service
     * @param string $code
     * @return Response
     * @throws \ImagickException
     */
    public function getAvatarFromUserCode(AvatarService $service, string $code = null): Response
    {
        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();
        $config = $character->avatarInstance()->toArray();

        //Overwrite colors with any specified by the editor
        $colors = json_decode(base64_decode($code), true);
        $config['colors'] = $colors ?? [];

        //Remove items/background
        unset($config['items']);
        unset($config['background']);

        $avatar = AvatarInstance::fromArray($config);
        $image = $service->renderAvatarInstance($avatar);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    public function getAvatarFromCharacterName(AvatarService $service, MuckObjectService $muckObjectService,
                                               Request       $request, string $name): Response
    {
        if (str_ends_with(strtolower($name), '.png')) $name = substr($name, 0, -4);
        $character = $muckObjectService->getByPlayerName($name);
        if (!$character) abort(404);
        $image = $service->renderAvatarInstance($character->avatarInstance());
        $this->applyOptionsToAvatarImage($image, $request);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    public function getAllAvatarsAsAGif(AvatarService $service, Request $request): Response
    {
        set_time_limit(500);
        $image = $service->getAnimatedGifOfAllAvatarDolls();
        //Need to apply the options to every frame!
        for ($i = 0; $i < $image->getNumberImages(); $i++) {
            $image->setIteratorIndex($i);
            $this->applyOptionsToAvatarImage($image, $request);
        }
        return response($image->getImagesBlob(), 200)
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

    #region Items

    public function getAvatarItem(AvatarService $service, string $id): Response
    {
        $item = $service->getAvatarItem($id);

        if (!$item) abort(404, "Unrecognized Avatar Item - $id");
        $image = $service->getAvatarItemImage($item);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    public function getAvatarItemPreview(AvatarService $service, string $id): Response
    {
        $item = $service->getAvatarItem($id);

        if (!$item) abort(404, "Unrecognized Avatar Item - $id");
        $image = $service->renderAvatarItemPreview($item);
        return response($image, 200)
            ->header('Content-Type', $image->getImageFormat());
    }

    public function showAdminAvatarItems(AvatarService $service): View
    {
        $items = array_map(function ($item) use ($service) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'filename' => $item->filename,
                'requirement' => $item->requirement,
                'created_at' => $item->createdAt,
                'owner' => $item->owner?->serializeForAdmin(),
                'cost' => $item->cost,
                'x' => $item->x,
                'y' => $item->y,
                'rotate' => $item->rotate,
                'scale' => $item->scale,
                'url' => route('multiplayer.avatar.itempreview', ['id' => $item->name])
            ];
        }, $service->getAvatarItems());

        $usage = $service->getAvatarItemFileUsage();

        return view('admin.avatar-item', [
            'items' => $items,
            'fileUsage' => $usage
        ]);

    }

    #endregion Items
}

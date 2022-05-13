<?php

namespace App\Http\Controllers;

use App\Avatar\AvatarGradient;
use App\Avatar\AvatarInstance;
use App\Avatar\AvatarService;
use App\Muck\MuckCharacter;
use App\Muck\MuckConnection;
use App\Muck\MuckObjectService;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

        $options = $service->getAvatarOptions($muck,$character);

        return view('multiplayer.avatar')->with([
            'gradients' => $options['gradients'],
            'items' => $options['items'],
            'backgrounds' => $options['backgrounds'],
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

    /**
     * Attempts to set the present avatar state
     * @param AvatarService $service
     * @param MuckConnection $muck
     * @param Request $request
     * @return void
     */
    public function setAvatarState(AvatarService $service, MuckConnection $muck, Request $request)
    {
        Log::Debug('Avatar - setAvatarState called with: ' . json_encode($request->all()));

        if (!$request->has('colors') || !$request->has('items') || !$request->has('background'))
            abort (400, 'Missing fields in request.');

        /** @var User $user */
        $user = auth()->user();
        $character = $user->getCharacter();

        //We need to validate things first to make sure they're available and owned/earned.
        $options = $service->getAvatarOptions($muck, $character);

        //Colors
        foreach($request->get('colors') as $slot => $gradientId) {
            if (!$gradientId) continue;
            if (!array_key_exists($gradientId, $options['gradients'])) abort(400, "The gradient '$gradientId' isn't available.");
            $correctedSlot = $slot;
            if ($slot === 'skin1') $correctedSlot = 'fur';
            if ($slot === 'skin2') $correctedSlot = 'fur';
            if ($slot === 'skin3') $correctedSlot = 'skin';
            if (!in_array($correctedSlot, $options['gradients'][$gradientId])) abort(400, "Gradient '$gradientId' isn't available for the color slot '$correctedSlot'.");
        }

        //Background
        $backgroundWanted = $request->get('background');
        if ($backgroundWanted) {
            $backgroundDetails = null;
            foreach ($options['backgrounds'] as $background) {
                if ($background['id'] == $backgroundWanted['id']) $backgroundDetails = $background;
            }
            if (!$backgroundDetails) abort (400, "The requested background '" . $backgroundWanted['name'] . "' wasn't an option.");
            if ($backgroundDetails['cost'] && !$backgroundDetails['earned'] && !$backgroundDetails['owner']) {
                abort (400, "The requested background '" . $backgroundWanted['name'] . "' isn't owned/earned.");
            }
        }

        //Items
        foreach ($request->get('items') as $itemWanted) {
            $itemDetails = null;
            foreach ($options['items'] as $item) {
                if ($item['id'] == $itemWanted['id']) $itemDetails = $item;
            }
            if (!$itemDetails) abort(400, "The requested item '" . $itemWanted['name'] . "' wasn't an option.");
            if ($itemDetails['cost'] && !$itemDetails['earned'] && !$itemDetails['owner']) {
                abort(400, "The requested item '" . $itemWanted['name'] . "' isn't owned/earned.");
            }
        }

        //Pass to muck to save.
        // The colors array is fine, but we need to process just the key details from the items
        $items = $request->get('items') ?? [];
        if ($backgroundWanted) $items[] = $backgroundWanted;
        $items = array_map(function($item) {
            return [
                'id' => $item['id'],
                'x' => $item['x'],
                'y' => $item['y'],
                'z' => $item['z'],
                'rotate' => $item['rotate'],
                'scale' => $item['scale'],
                //The old system needs to know the name of the actual image, which is the id
                //TODO : Remove setting the picture attribute on avatar items after changeover to the new system
                'picture' => $item['id']
            ];
        }, $items);
        $muck->saveAvatarCustomizations(
            $character,
            $request->get('colors'),
            $items
        );
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

    private function applyOptionsToAvatarImage(Imagick $avatarImage, Request $request)
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

    public function buyGradient(Request $request, MuckConnection $muckConnection) {
        if (!$request->has('gradient')) abort(400, "Gradient not specified.");
        $gradient = $request->get('gradient');

        if (!$request->has('slot')) abort(400, "Slot not specified.");
        $slot = $request->get('slot');

        /** @var User $user */
        $user = auth()->user();
        if (!$user) abort(403);

        $character = $user->getCharacter();
        if (!$character) abort(400, "A character isn't set.");

        Log::info("Avatar - Gradient Purchase - {$user}, {$character} buying {$gradient} for slot {$slot}.");

        return $muckConnection->buyAvatarGradient($character, $gradient, $slot);
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

    public function buyItem(Request $request, AvatarService $avatarService, MuckConnection $muckConnection) {
        if (!$request->has('item')) abort(400, "Item not specified.");
        $itemId = $request->get('item');
        $item = $avatarService->getAvatarItem($itemId);
        if (!$item) abort(400, "No item found with the id of '$itemId'.");
        
        /** @var User $user */
        $user = auth()->user();
        if (!$user) abort(403);

        $character = $user->getCharacter();
        if (!$character) abort(400, "A character isn't set.");

        Log::info("Avatar - Item Purchase - {$user}, {$character} buying {$itemId}.");
        return $muckConnection->buyAvatarItem($character, $itemId, $item->name, $item->cost);

    }

    #endregion Items
}

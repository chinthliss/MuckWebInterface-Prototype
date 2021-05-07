<?php
namespace App;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Session\DatabaseSessionHandler;
//use Illuminate\Support\Facades\Auth;

/**
 * Class DatabaseForMuckSessionHandler
 * This is to override the default Laravel DatabaseSessionHandler
 * Which insists on using user_id
 */
class DatabaseForMuckSessionHandler extends DatabaseSessionHandler
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultPayload($data)
    {
        $payload = [
            'payload' => base64_encode($data),
            'last_activity' => $this->currentTime(),
        ];

        if (! $this->container) {
            return $payload;
        }

        return tap($payload, function (&$payload) {
            $this
                //->addUserInformation($payload)
                ->addInformationForMuck($payload)
                ->addRequestInformation($payload);
        });
    }

    /**
     * Saves any additional values that are only in the database so the muck can read them
     *
     * @param  array  $payload
     * @return $this
     */
    protected function addInformationForMuck(&$payload)
    {
        $user = auth()->user();
        if ($user) {
            $payload['aid'] = $user->getAid();
            // No longer saving active character on session since it can be polluted across multiple tabs
            // if (method_exists($user, 'getCharacterDbref') && $user->getCharacterDbref()) $payload['player_dbref'] = $user->getCharacterDbref();
        }
        return $this;
    }
}

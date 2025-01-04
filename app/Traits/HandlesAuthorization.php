<?php

namespace App\Traits;

trait HandlesAuthorization
{
    /**
     * Authorize an action and return a message if denied.
     *
     * @param string $ability
     * @param mixed $arguments
     * @return string|null
     */
    public function authorizeOrFail(string $ability, $arguments = [])
    {
        try {
            $this->authorize($ability, $arguments);
            return null;
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $e->getMessage();
        }
    }
}

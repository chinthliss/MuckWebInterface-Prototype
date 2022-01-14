<?php

namespace App\Avatar;

interface AvatarProvider
{
    #region Gradients

    /**
     * @return AvatarGradient[]
     */
    public function getGradients(): array;

    /**
     * @param string $name
     * @return AvatarGradient
     */
    public function getGradient(string $name): AvatarGradient;

    #endregion Gradients
}

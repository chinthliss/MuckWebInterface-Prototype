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
     * @return AvatarGradient|null
     */
    public function getGradient(string $name): ?AvatarGradient;

    #endregion Gradients

    #region Items

    /**
     * @return AvatarItem[]
     */
    public function getItems(): array;

    /**
     * @param string $itemId
     * @return AvatarItem|null
     */
    public function getItem(string $itemId): ?AvatarItem;

    #endregion Items
}

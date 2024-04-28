<?php

namespace ADP\BaseVersion\Includes\Context\Container;

use ADP\BaseVersion\Includes\Compatibility\Container\ContainerPartProduct;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPartCartItem;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

interface ContainerCompatibility
{
    public function isActive(): bool;

    public function isContainerFacade(WcCartItemFacade $facade): bool;

    public function isFacadeAPartOfContainer(WcCartItemFacade $facade): bool;

    public function isContainerProduct(\WC_Product $wcProduct): bool;

    public function isFacadeAPartOfContainerFacade(
        WcCartItemFacade $partOfContainerFacade,
        WcCartItemFacade $bundle
    ): bool;

    public function adaptContainerPartCartItem(WcCartItemFacade $facade): ContainerPartCartItem;

    /**
     * @param \WC_Product $product
     * @return ContainerPartProduct[]
     */
    public function getListOfPartsOfContainerFromContainerProduct(\WC_Product $product): array;

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @param int $pos
     * @return ContainerCartItem
     */
    public function adaptContainerCartItem(
        WcCartItemFacade $facade,
        array $children,
        int $pos
    ): ContainerCartItem;

    public function adaptContainerWcProduct(\WC_Product $product, $cartItemData = []): ?ContainerCartItem;

    public function overrideContainerReferenceForPartOfContainerFacadeAfterPossibleDuplicates(
        WcCartItemFacade $partOfContainerFacade,
        WcCartItemFacade $containerFacade
    );
}

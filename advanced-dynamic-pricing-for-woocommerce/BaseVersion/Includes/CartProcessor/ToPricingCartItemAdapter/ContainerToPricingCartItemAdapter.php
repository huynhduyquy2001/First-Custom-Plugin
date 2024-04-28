<?php

namespace ADP\BaseVersion\Includes\CartProcessor\ToPricingCartItemAdapter;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPartCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\PriceDisplay\WcProductCalculationWrapper;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

class ContainerToPricingCartItemAdapter implements IToPricingCartItemAdapter
{
    /** @var Context */
    protected $context;

    /** @var Context\Container\ContainerCompatibilityManager */
    protected $containerCmpManager;

    /** @var array<int, ContainerPartCartItem> */
    protected $childrenFacadeBuffer;

    public function __construct()
    {
        $this->context = adp_context();

        $this->containerCmpManager = $this->context->getContainerCompatibilityManager();
        $this->childrenFacadeBuffer = [];
    }

    public function canAdaptFacade(WcCartItemFacade $facade): bool
    {
        return $this->containerCmpManager->getCompatibilityFromPartOfContainerFacade($facade)
            || $this->containerCmpManager->getCompatibilityFromContainerFacade($facade);
    }

    /**
     * @param Cart $cart
     * @param WcCartItemFacade $facade
     * @param int $pos
     * @return bool
     * @throws \Exception
     */
    public function adaptFacadeAndPutIntoCart($cart, WcCartItemFacade $facade, int $pos): bool
    {
        if (($containerCmp = $this->containerCmpManager->getCompatibilityFromPartOfContainerFacade($facade))) {
            $newItems = [];
            $replaced = false;

            foreach ($cart->getItems() as $cartItem) {
                if ($cartItem instanceof ContainerCartItem
                    && $containerCmp->isFacadeAPartOfContainerFacade($facade, $cartItem->getWcItem())
                ) {
                    $partOfContainerFacades = [];
                    foreach ($cartItem->getItems() as $loopItem) {
                        $partOfContainerFacades[] = $loopItem->getWcItem();
                    }
                    $partOfContainerFacades[] = $facade;

                    $containerItem = $containerCmp->adaptContainerCartItem(
                        $cartItem->getWcItem(),
                        array_filter($partOfContainerFacades, function ($childFacade) use ($containerCmp, $cartItem) {
                            return $containerCmp->isFacadeAPartOfContainerFacade($childFacade, $cartItem->getWcItem());
                        }),
                        $cartItem->getInitialCartPosition()
                    );

                    $replaced = true;
                    $newItems[] = $containerItem;
                } else {
                    $newItems[] = $cartItem;
                }
            }

            if ($replaced) {
                $cart->setItems($newItems);
            } else {
                $this->childrenFacadeBuffer[] = $facade;
            }
        } elseif (($containerCmp = $this->containerCmpManager->getCompatibilityFromContainerFacade($facade))) {
            $containerItem = $containerCmp->adaptContainerCartItem(
                $facade,
                array_filter($this->childrenFacadeBuffer, function ($childFacade) use ($containerCmp, $facade) {
                    return $containerCmp->isFacadeAPartOfContainerFacade($childFacade, $facade);
                }),
                $pos
            );

            $cart->addToCart($containerItem);
        }

        return true;
    }

    public function canAdaptWcProduct(\WC_Product $product): bool
    {
        return $this->context->getContainerCompatibilityManager()->isContainerProduct($product);
    }

    public function adaptWcProduct(WcProductCalculationWrapper $wrapper): ?ICartItem
    {
        $containerCmp = $this->context
            ->getContainerCompatibilityManager()
            ->getCompatibilityFromContainerWcProduct($wrapper->getWcProduct());

        return $containerCmp->adaptContainerWcProduct($wrapper->getWcProduct(), $wrapper->getCartItemData());
    }
}

<?php

namespace ADP\BaseVersion\Includes\PriceDisplay;

use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerCartItem;

defined('ABSPATH') or exit;

class ProcessedProductContainer extends ProcessedProductSimple
{
    /** @return array<int,ProcessedProductSimple> */
    public function getContainerItemsByPos($pos = null)
    {
        $item = $this->getItemByPos($pos);

        if (!isset($item)) {
            return [];
        }

        $processedProducts = [];
        foreach ($item->getItems() as $subContainerExistingCartItem) {
            $processedProducts[] = new ProcessedProductSimple(
                $this->context,
                $subContainerExistingCartItem->getWcItem()->getProduct(),
                [$subContainerExistingCartItem]
            );
        }

        return $processedProducts;
    }

    /**
     * @param int|null $pos
     *
     * @return ContainerCartItem|null
     */
    protected function getItemByPos($pos = null)
    {
        $pos = is_numeric($pos) ? intval($pos) : null;
        $item = null;

        if (is_null($pos)) {
            $item = reset($this->cartItems);
            $item = $item !== false ? $item : null;
        } else {
            $counter = floatval(0);
            foreach ($this->cartItems as $cartItem) {
                if ($counter < $pos && $pos <= ($counter + $cartItem->getQty())) {
                    $item = $cartItem;
                    break;
                }

                $counter += $cartItem->getQty();
            }
        }

        return $item;
    }
}

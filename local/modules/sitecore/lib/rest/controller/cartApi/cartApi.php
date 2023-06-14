<?php

namespace SiteCore\Rest\CartApi;

use SiteCore\Cart\Cart;

/**
 * Формат ответов зависит от ожидаемого фронтендом ответа
 */
class CartApi
{
    private Cart $cart;

    public function __construct()
    {
        $this->cart = new Cart();
    }

    // Метод для добавления товара в корзину
    public function addItem($params): array
    {
        if ($this->cart->addProduct($params['productId'], $params['quantity'], $params['jwt'])) {
            return ['message' => 'успешный ответ'];
        } else {
            return ['message' => 'негативный ответ', 'error' => 'неверный токен'];
        }
    }

    // Метод для удаления товара из корзины
    public function removeItem($params): array
    {
        if ($this->cart->removeProduct($params['productId'], $params['jwt'])) {
            return ['message' => 'успешный ответ'];
        } else {
            return ['message' => 'негативный ответ', 'error' => 'неверный токен'];
        }
    }

    // Метод для получения содержимого корзины
    public function getItems($params): array
    {
        return $this->cart->getUserItems($params['jwt']);
    }
}

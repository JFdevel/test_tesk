<?php

namespace SiteCore\Cart;

use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use SiteCore\JWT\JwtToken;

class Cart
{
    private $userId;

    public function __construct()
    {
        $this->userId = Fuser::getId();
    }

    /**
     * Метод для добавления продукта в корзину.
     * @param array $product - массив с информацией о продукте
     * @param int $quantity - количество добавляемого продукта
     * @param string $jwtToken - токен аутентификации пользователя
     * @return bool
     */
    public function addProduct(array $product, int $quantity, string $jwtToken): bool
    {
        // Проверяем токен
        $this->verifyToken($jwtToken);

        // Получаем корзину пользователя
        $basket = $this->getBasket();

        // Добавляем товар в корзину
        $item = $basket->createItem('catalog', $product['id']);
        $item->setFields(array(
            'QUANTITY' => $quantity,
            'CURRENCY' => Bitrix\Currency\CurrencyManager::getBaseCurrency(),
            'LID' => Bitrix\Main\Context::getCurrent()->getSite(),
            'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
        ));
        $basket->save();

        return true;
    }

    /**
     * Метод для удаления товара из корзины пользователя
     * @param int $productId идентификатор товара
     * @param string $jwtToken JWT-токен пользователя
     * @return bool
     */
    public function removeProduct(int $productId, string $jwtToken): bool
    {
        // Проверяем токен
        $this->verifyToken($jwtToken);

        // Получаем корзину пользователя
        $basket = $this->getBasket();

        // Удаляем товар из корзины
        $item = $basket->getExistsItem('catalog', $productId);
        if (!$item) {
            return false; // Товар не найден в корзине
        }
        $basket->deleteItem($item);
        $basket->save();

        return true;
    }

    /**
     * Функция возвращает массив товаров пользователя.
     * @return array Массив товаров пользователя.
     */
    public function getUserItems($jwtToken): array
    {
        // Проверяем токен
        $this->verifyToken($jwtToken);

        $arItems = [];
        $dbRes = Basket::getList([
            'select' => ['NAME', 'QUANTITY'],
            'filter' => [
                '=FUSER_ID' => $this->userId,
                '=ORDER_ID' => null,
                '=LID' => Context::getCurrent()->getSite(),
                '=CAN_BUY' => 'Y',
            ]
        ]);
        while ($item = $dbRes->fetch()) {
            $arItems[] = $item;
        }
        return $arItems;
    }

    private function verifyToken($jwtToken): void
    {
        (new JwtToken)->checkToken($jwtToken, $this->userId);
    }


    private function getBasket()
    {
        Loader::includeModule('sale');

        // Получаем контекст приложения
        $context = Context::getCurrent();

        // Создаем корзину для пользователя
        return Basket::loadItemsForFUser($this->userId, $context->getSite());
    }

}
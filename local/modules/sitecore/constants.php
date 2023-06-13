<?php

namespace SiteCore;

class Constants
{
    /** @var string Название Cookie с JWT-токеном */
    const NAME_COOKIE_JWT = 'Authorization';

    /** @var string Название наблицы с Refresh-токенами */
    const TABLE_REFRESH_TOKEN = 'refresh_tokens';

    /** @var string Название наблицы с Refresh-токенами */
    const JWT_LIFETIME = 7200;

    /** @var string Название наблицы с Refresh-токенами */
    const REFRESH_LIFETIME = 31536000;
}
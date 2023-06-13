<?php

namespace SiteCore\JWT;

use Firebase\JWT\JWT;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use SiteCore\Constants;
use SiteCore\Helpers\DbHelper;

class JwtToken
{
    private string|false $secretKey;
    private string|false $table;

    public function __construct()
    {
        $this->secretKey = file_get_contents('./key.pem');
        $this->table = Constants::TABLE_REFRESH_TOKEN;
    }

    /**
     * Проверяет, является ли переданный JWT-токен действительным для указанного идентификатора пользователя.
     * Если токен недействителен или истек, пытается сгенерировать новый, используя токен обновления.
     * Если токен обновления недоступен или также недействителен, генерирует новый токен с нуля.
     * Устанавливает новый токен в виде cookie и ничего не возвращает.
     *
     * @param string $jwtToken Проверяемый JWT-токен.
     * @param int $userId Идентификатор пользователя, для которого проверяется токен.
     */
    public function checkToken(string $jwtToken, int $userId): void
    {
        if (!$jwtToken) {
            $newJwtToken = $this->GetNewTokens($userId);
        } else {
            if (!$refreshToken = $this->getToken($userId)) {
                $newJwtToken = $this->GetNewTokens($userId);
            } else {
                $newJwtToken = $this->RefreshToken($jwtToken, $refreshToken);
                if (!$newJwtToken) {
                    $newJwtToken = $this->GetNewTokens($userId);
                }
            }
        }
        $this->setTokenCookie($newJwtToken);
    }

    /**
     * Возвращает токен пользователя по его идентификатору
     * @param int $userId - идентификатор пользователя
     * @return string|null - токен пользователя или null, если токен не найден
     */
    public function getToken(int $userId): ?string
    {
        $connection = $this->getConnection();
        $query = "
            SELECT TOKEN 
            FROM {$this->table} 
            WHERE USER_ID = {$userId} 
            ORDER BY CREATED_AT DESC LIMIT 1";
        $result = $connection->query($query);

        if ($row = $result->fetch()) {
            return $row['token'];
        } else {
            return null;
        }
    }

    /**
     * Добавляет токен в базу данных для пользователя с указанным ID.
     *
     * @param int $userId - ID пользователя
     * @param string $token - токен для добавления
     * @param int $createdAt - дата создания токена
     * @param int $expiresAt - дата истечения срока действия токена
     *
     * @return void
     */
    private function addToken(int $userId, string $token, int $createdAt, int $expiresAt): void
    {
        $expiresAt = DbHelper::convertPhpTimeToMysqlDatetime($expiresAt);
        $createdAt = DbHelper::convertPhpTimeToMysqlDatetime($createdAt);
        $connection = $this->getConnection();
        $query = "
            INSERT INTO {$this->table} (USER_ID, TOKEN, CREATED_AT, EXPIRES_AT) 
            VALUES ({$userId}, '{$token}', {$createdAt}, {$expiresAt})";
        $connection->query($query);
    }

    private function updateRefreshToken($userId, $token, $expiresAt): void
    {
        $connection = $this->getConnection();
        $query = "
            UPDATE {$this->table} SET TOKEN = '{$token}', EXPIRES_AT = {$expiresAt} 
            WHERE USER_ID = {$userId} 
            ORDER BY CREATED_AT DESC LIMIT 1";
        $connection->query($query);
    }

    public function setTokenCookie($value): void
    {
        setcookie(Constants::NAME_COOKIE_JWT, $value, time() + Constants::JWT_LIFETIME, '/', '', false, true);
    }

    private function getConnection()
    {
        $application = Application::getInstance();
        /** @var Connection $connection */
        $connection = $application->getConnection();
        return $connection;
    }

    /**
     * Генерирует новые JWT- и refresh-токены для указанного идентификатора пользователя.
     *
     * @param int $user_id Идентификатор пользователя, для которого генерируются токены.
     * @return string Сгенерированный JWT-токен.
     */
    private function GetNewTokens(int $user_id): string
    {
        // создание массива payload для JWT-токена
        $jwt_exp = time() + Constants::JWT_LIFETIME;
        $payload = [
            "fuser_id" => $user_id,
            "exp" => $jwt_exp
        ];

        // создание JWT-токена
        $jwt_token = JWT::encode($payload, $this->secretKey);

        $refresh_exp = time() + Constants::REFRESH_LIFETIME;
        // создание массива payload для refresh-токена
        $payload = [
            "fuser_id" => $user_id,
            "exp" => $refresh_exp
        ];

        // создание refresh-токена
        $refresh_token = JWT::encode($payload, $this->secretKey);

        // сохранение refresh-токена в базу данных
        $this->addToken($user_id, $refresh_token, time(), $refresh_exp);

        // возврат JWT-токена и refresh-токена
        return $jwt_token;
    }

    /**
     * Обновляет JWT-токен используя предоставленный refresh-токен.
     *
     * @param string $jwt_token JWT-токен, который требуется обновить.
     * @param string $refresh_token Refresh-токен, используемый для обновления JWT-токена.
     * @return string|boolean Новый сгенерированный JWT-токен в случае успеха, или false, если срок действия refresh-токена истек или он недействителен.
     */
    private function RefreshToken(string $jwt_token, string $refresh_token): bool|string
    {
        // раскодирование refresh-токена
        $decoded_refresh_token = JWT::decode($refresh_token, $this->secretKey, ['HS256']);

        // проверка времени истечения refresh-токена
        if ($decoded_refresh_token->exp < time()) {
            // todo разлогин пользователя, в нашей ситуации просто генерация новой пары
            return false;
        }

        // раскодирование JWT-токена
        $decoded_jwt_token = JWT::decode($jwt_token, $this->secretKey, ['HS256']);

        // проверка, соответствует ли JWT-токен refresh-токену
        if ($decoded_refresh_token->fuser_id != $decoded_jwt_token->fuser_id) {
            // todo разлогин пользователя, в нашей ситуации просто генерация новой пары
            return false;
        }

        // создание массива payload для JWT-токена
        $payload = [
            "fuser_id" => $decoded_refresh_token->fuser_id,
            "exp" => time() + Constants::JWT_LIFETIME
        ];

        // создание JWT-токена
        // возврат нового JWT-токена
        return JWT::encode($payload, $this->secretKey);
    }
}
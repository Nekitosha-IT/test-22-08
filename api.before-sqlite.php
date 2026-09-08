<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config.php';


/* =========================================================
   JSON RESPONSE
   ========================================================= */

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_PRETTY_PRINT
    );

    exit;
}


/* =========================================================
   HTTP REQUEST TO UTM
   PHP 8.5 SAFE
   ========================================================= */

function requestUtm(
    string $ip,
    int $port,
    string $path,
    int $timeout = 5
): array {

    $url = 'http://' . $ip . ':' . $port . $path;

    $start = microtime(true);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'ignore_errors' => true,
            'protocol_version' => 1.1,
            'header' =>
                "Accept: application/json\r\n" .
                "User-Agent: UTM-MONITOR/2.0\r\n" .
                "Connection: close\r\n"
        ]
    ]);

    /*
     * PHP 8.5:
     * НЕ используем $http_response_header.
     */
    $data = @file_get_contents(
        $url,
        false,
        $context
    );

    $time = (int)round(
        (microtime(true) - $start) * 1000
    );

    /*
     * PHP 8.5 функция получения последних HTTP-заголовков.
     */
    $headers = [];

    if (function_exists('http_get_last_response_headers')) {
        $headers = http_get_last_response_headers();

        if (!is_array($headers)) {
            $headers = [];
        }
    }

    $httpCode = null;

    foreach ($headers as $header) {

        if (
            preg_match(
                '/HTTP\/\d+(?:\.\d+)?\s+(\d+)/i',
                $header,
                $m
            )
        ) {
            $httpCode = (int)$m[1];
        }
    }

    if ($data === false) {

        return [
            'ok' => false,
            'http_code' => $httpCode,
            'time' => $time,
            'data' => null,
            'error' => 'Нет соединения с УТМ',
            'url' => $url
        ];
    }

    if (
        $httpCode !== null &&
        $httpCode >= 400
    ) {

        return [
            'ok' => false,
            'http_code' => $httpCode,
            'time' => $time,
            'data' => $data,
            'error' =>
                'УТМ вернул HTTP ' . $httpCode,
            'url' => $url
        ];
    }

    return [
        'ok' => true,
        'http_code' => $httpCode ?? 200,
        'time' => $time,
        'data' => $data,
        'error' => null,
        'url' => $url
    ];
}


/* =========================================================
   JSON DECODE
   ========================================================= */

function decodeJson($value): ?array
{
    if (
        !is_string($value) ||
        trim($value) === ''
    ) {
        return null;
    }

    $result = json_decode(
        $value,
        true
    );

    if (
        json_last_error() !== JSON_ERROR_NONE
    ) {
        return null;
    }

    return is_array($result)
        ? $result
        : null;
}


/* =========================================================
   DATE
   ========================================================= */

function parseUtmDate($value): ?DateTime
{
    if (
        $value === null ||
        $value === ''
    ) {
        return null;
    }

    $value = trim((string)$value);

    $formats = [
        'Y-m-d H:i:s O',
        'Y-m-d H:i:s P',
        'Y-m-d\TH:i:sO',
        'Y-m-d\TH:i:sP',
        'd.m.Y H:i:s O',
        'd.m.Y H:i:sO',
        DATE_ATOM
    ];

    foreach ($formats as $format) {

        $date = DateTime::createFromFormat(
            $format,
            $value
        );

        if ($date instanceof DateTime) {
            return $date;
        }
    }

    $timestamp = strtotime($value);

    if ($timestamp !== false) {

        $date = new DateTime();

        $date->setTimestamp(
            $timestamp
        );

        return $date;
    }

    return null;
}


/* =========================================================
   CERTIFICATE STATUS
   ========================================================= */

function certificateStatus(
    ?array $certificate
): ?array {

    if (!is_array($certificate)) {
        return null;
    }

    $expireValue =
        $certificate['expireDate']
        ?? $certificate['expire_date']
        ?? $certificate['notAfter']
        ?? null;

    $date = parseUtmDate(
        $expireValue
    );

    $validValue =
        $certificate['isValid']
        ?? $certificate['valid']
        ?? null;

    $isValid = null;

    if ($validValue !== null) {

        $value = strtolower(
            trim((string)$validValue)
        );

        if (
            $validValue === true ||
            $value === 'true' ||
            $value === 'valid'
        ) {
            $isValid = true;
        }

        if (
            $validValue === false ||
            $value === 'false' ||
            $value === 'invalid'
        ) {
            $isValid = false;
        }
    }

    if (!$date) {

        return [
            'status' => 'unknown',
            'status_text' => 'Срок неизвестен',
            'is_valid' => $isValid,
            'expire_date' => null,
            'days_left' => null,
            'warning' =>
                'УТМ не передал дату окончания'
        ];
    }

    $now = new DateTime();

    $seconds =
        $date->getTimestamp() -
        $now->getTimestamp();

    $days =
        (int)floor(
            $seconds / 86400
        );

    if ($seconds < 0) {

        return [
            'status' => 'expired',
            'status_text' => 'ИСТЁК',
            'is_valid' => false,
            'expire_date' =>
                $date->format('d.m.Y H:i:s'),
            'days_left' => $days,
            'warning' =>
                'Сертификат просрочен'
        ];
    }

    if ($isValid === false) {

        return [
            'status' => 'invalid',
            'status_text' => 'НЕДЕЙСТВИТЕЛЕН',
            'is_valid' => false,
            'expire_date' =>
                $date->format('d.m.Y H:i:s'),
            'days_left' => $days,
            'warning' =>
                'Сертификат недействителен'
        ];
    }

    if ($days <= 7) {

        return [
            'status' => 'critical',
            'status_text' => 'КРИТИЧНО',
            'is_valid' => true,
            'expire_date' =>
                $date->format('d.m.Y H:i:s'),
            'days_left' => $days,
            'warning' =>
                'До окончания сертификата 7 дней или меньше'
        ];
    }

    if ($days <= 30) {

        return [
            'status' => 'warning',
            'status_text' => 'ВНИМАНИЕ',
            'is_valid' => true,
            'expire_date' =>
                $date->format('d.m.Y H:i:s'),
            'days_left' => $days,
            'warning' =>
                'До окончания сертификата 30 дней или меньше'
        ];
    }

    return [
        'status' => 'valid',
        'status_text' => 'ДЕЙСТВИТЕЛЕН',
        'is_valid' => true,
        'expire_date' =>
            $date->format('d.m.Y H:i:s'),
        'days_left' => $days,
        'warning' => null
    ];
}


/* =========================================================
   CERTIFICATE INFO
   ========================================================= */

function buildCertificateInfo(
    $certificate
): ?array {

    if (!is_array($certificate)) {
        return null;
    }

    $status =
        certificateStatus($certificate);

    return [
        'cert_type' =>
            $certificate['certType']
            ?? $certificate['cert_type']
            ?? null,

        'issuer' =>
            $certificate['issuer']
            ?? null,

        'start_date' =>
            $certificate['startDate']
            ?? $certificate['start_date']
            ?? null,

        'expire_date' =>
            $status['expire_date']
            ?? null,

        'is_valid' =>
            $status['is_valid']
            ?? null,

        'status' =>
            $status['status']
            ?? 'unknown',

        'status_text' =>
            $status['status_text']
            ?? 'Неизвестно',

        'days_left' =>
            $status['days_left']
            ?? null,

        'warning' =>
            $status['warning']
            ?? null
    ];
}


/* =========================================================
   CERTIFICATE LIST
   ========================================================= */

function getCertificateAliases(
    string $ip,
    int $port,
    int $timeout
): array {

    $answer = requestUtm(
        $ip,
        $port,
        '/api/certificate/list',
        $timeout
    );

    if (!$answer['ok']) {

        return [
            'ok' => false,
            'rsa' => [],
            'gost' => [],
            'raw' => null,
            'error' => $answer['error']
        ];
    }

    $json =
        decodeJson($answer['data']);

    if (
        isset($json['value']) &&
        is_array($json['value'])
    ) {
        $json = $json['value'];
    }

    $rsa = [];
    $gost = [];

    if (is_array($json)) {

        foreach ($json as $item) {

            if (!is_array($item)) {
                continue;
            }

            $algorithm =
                strtoupper(
                    trim(
                        (string)(
                            $item['algorithm']
                            ?? $item['type']
                            ?? $item['certType']
                            ?? ''
                        )
                    )
                );

            $aliases =
                $item['aliasesList']
                ?? $item['aliases']
                ?? [];

            if (!is_array($aliases)) {

                $aliases =
                    $aliases !== ''
                    ? [(string)$aliases]
                    : [];
            }

            foreach ($aliases as $alias) {

                $alias = trim(
                    (string)$alias
                );

                if ($alias === '') {
                    continue;
                }

                if (
                    strpos($algorithm, 'RSA') !== false
                ) {
                    $rsa[] = $alias;
                }

                if (
                    strpos($algorithm, 'GOST') !== false
                ) {
                    $gost[] = $alias;
                }
            }
        }
    }

    return [
        'ok' => true,
        'rsa' => array_values(
            array_unique($rsa)
        ),
        'gost' => array_values(
            array_unique($gost)
        ),
        'raw' => $json,
        'error' => null
    ];
}


/* =========================================================
   UTM INFO
   ========================================================= */

function getUtmInfo(
    string $ip,
    int $port,
    int $timeout
): array {

    return requestUtm(
        $ip,
        $port,
        '/api/info/list',
        $timeout
    );
}


/* =========================================================
   DOCUMENTS SUMMARY
   ========================================================= */

function getDocumentsSummary(
    string $ip,
    int $port,
    int $timeout
): array {

    $result = [
        'incoming' => null,
        'outgoing' => null
    ];

    $incoming = requestUtm(
        $ip,
        $port,
        '/api/db/in/list?limit=1&offset=0',
        $timeout
    );

    if ($incoming['ok']) {

        $json =
            decodeJson($incoming['data']);

        if (is_array($json)) {

            $result['incoming'] =
                $json['total']
                ?? $json['count']
                ?? null;
        }
    }

    $outgoing = requestUtm(
        $ip,
        $port,
        '/api/db/out/list?limit=1&offset=0',
        $timeout
    );

    if ($outgoing['ok']) {

        $json =
            decodeJson($outgoing['data']);

        if (is_array($json)) {

            $result['outgoing'] =
                $json['total']
                ?? $json['count']
                ?? null;
        }
    }

    return $result;
}


/* =========================================================
   UTM STATUS
   ========================================================= */

function buildUtmStatus(
    $id,
    array $utm,
    int $timeout
): array {

    $ip =
        trim(
            (string)($utm['ip'] ?? '')
        );

    $port =
        (int)($utm['port'] ?? 8086);

    if ($ip === '') {

        return [
            'id' => $id,
            'name' =>
                $utm['name']
                ?? ('УТМ №' . $id),
            'ip' => '',
            'port' => $port,
            'online' => false,
            'response_time' => 0,
            'http_code' => null,
            'error' => 'Адрес УТМ не указан',
            'version' => null,
            'contour' => null,
            'owner_id' => null,
            'license' => null,
            'db' => [
                'create_date' => null,
                'owner_id' => null
            ],
            'rsa' => 0,
            'gost' => 0,
            'rsa_aliases' => [],
            'gost_aliases' => [],
            'rsa_info' => null,
            'gost_info' => null,
            'documents' => [
                'incoming' => null,
                'outgoing' => null
            ],
            'certificates' => [
                'rsa' => [],
                'gost' => []
            ],
            'warnings' => []
        ];
    }

    $info =
        getUtmInfo(
            $ip,
            $port,
            $timeout
        );

    $online =
        $info['ok'];

    $infoData = [];

    if ($info['ok']) {

        $decoded =
            decodeJson(
                $info['data']
            );

        if (is_array($decoded)) {
            $infoData = $decoded;
        }
    }

    $aliases =
        getCertificateAliases(
            $ip,
            $port,
            $timeout
        );

    $rsaInfo =
        buildCertificateInfo(
            $infoData['rsa']
            ?? null
        );

    $gostInfo =
        buildCertificateInfo(
            $infoData['gost']
            ?? null
        );

    $documents =
        getDocumentsSummary(
            $ip,
            $port,
            $timeout
        );

    $warnings = [];

    if (!$online) {

        $warnings[] = [
            'type' => 'utm',
            'level' => 'critical',
            'message' =>
                $info['error']
                ?? 'УТМ недоступен'
        ];
    }

    if (
        is_array($rsaInfo) &&
        !empty($rsaInfo['warning'])
    ) {

        $warnings[] = [
            'type' => 'rsa',
            'level' =>
                $rsaInfo['status'],
            'message' =>
                $rsaInfo['warning']
        ];
    }

    if (
        is_array($gostInfo) &&
        !empty($gostInfo['warning'])
    ) {

        $warnings[] = [
            'type' => 'gost',
            'level' =>
                $gostInfo['status'],
            'message' =>
                $gostInfo['warning']
        ];
    }

    if (
        array_key_exists(
            'license',
            $infoData
        ) &&
        $infoData['license'] === false
    ) {

        $warnings[] = [
            'type' => 'license',
            'level' => 'critical',
            'message' =>
                'Лицензия УТМ недействительна'
        ];
    }

    return [
        'id' => $id,

        'name' =>
            $utm['name']
            ?? ('УТМ №' . $id),

        'ip' => $ip,

        'port' => $port,

        'online' => $online,

        'response_time' =>
            $info['time'],

        'http_code' =>
            $info['http_code'],

        'error' =>
            $info['error'],

        'version' =>
            $infoData['version']
            ?? $infoData['utmVersion']
            ?? null,

        'contour' =>
            $infoData['contour']
            ?? null,

        'owner_id' =>
            $infoData['ownerId']
            ?? $infoData['ownerID']
            ?? null,

        'license' =>
            $infoData['license']
            ?? null,

        'db' => [
            'create_date' =>
                $infoData['db']['createDate']
                ?? null,

            'owner_id' =>
                $infoData['db']['ownerId']
                ?? null
        ],

        'rsa' =>
            count($aliases['rsa']),

        'gost' =>
            count($aliases['gost']),

        'rsa_aliases' =>
            $aliases['rsa'],

        'gost_aliases' =>
            $aliases['gost'],

        'rsa_info' =>
            $rsaInfo,

        'gost_info' =>
            $gostInfo,

        'documents' =>
            $documents,

        'certificates' => [
            'rsa' =>
                $aliases['rsa'],

            'gost' =>
                $aliases['gost']
        ],

        'warnings' =>
            $warnings
    ];
}


/* =========================================================
   TIMEOUT
   ========================================================= */

function getTimeout(): int
{
    global $config;

    return max(
        1,
        (int)(
            $config['timeout']
            ?? 5
        )
    );
}


/* =========================================================
   STATUS
   ========================================================= */

function actionStatus(): void
{
    global $config;

    $result = [];

    $timeout =
        getTimeout();

    foreach (
        ($config['utms'] ?? [])
        as $id => $utm
    ) {

        if (
            empty($utm['enabled']) ||
            empty($utm['ip'])
        ) {
            continue;
        }

        try {

            $result[] =
                buildUtmStatus(
                    $id,
                    $utm,
                    $timeout
                );

        } catch (Throwable $e) {

            $result[] = [
                'id' => $id,
                'name' =>
                    $utm['name']
                    ?? ('УТМ №' . $id),
                'ip' =>
                    $utm['ip'],
                'port' =>
                    $utm['port']
                    ?? 8086,
                'online' => false,
                'response_time' => 0,
                'http_code' => null,
                'error' =>
                    'Ошибка мониторинга: ' .
                    $e->getMessage(),
                'warnings' => [
                    [
                        'type' => 'monitor',
                        'level' => 'critical',
                        'message' =>
                            $e->getMessage()
                    ]
                ]
            ];
        }
    }

    jsonResponse([
        'success' => true,
        'utms' => $result,
        'server_time' =>
            date('d.m.Y H:i:s')
    ]);
}


/* =========================================================
   CERTIFICATES
   ========================================================= */

function actionCertificates(): void
{
    global $config;

    $id =
        (int)($_GET['id'] ?? 0);

    if (!isset($config['utms'][$id])) {

        jsonResponse([
            'success' => false,
            'error' => 'УТМ не найден'
        ], 404);
    }

    $utm =
        $config['utms'][$id];

    $timeout =
        getTimeout();

    $info =
        getUtmInfo(
            $utm['ip'],
            (int)$utm['port'],
            $timeout
        );

    $aliases =
        getCertificateAliases(
            $utm['ip'],
            (int)$utm['port'],
            $timeout
        );

    $infoData = [];

    if ($info['ok']) {

        $decoded =
            decodeJson(
                $info['data']
            );

        if (is_array($decoded)) {
            $infoData = $decoded;
        }
    }

    jsonResponse([
        'success' => true,

        'utm' => [
            'id' => $id,
            'name' =>
                $utm['name'],
            'ip' =>
                $utm['ip'],
            'port' =>
                $utm['port']
        ],

        'rsa' => [
            'aliases' =>
                $aliases['rsa'],
            'info' =>
                buildCertificateInfo(
                    $infoData['rsa']
                    ?? null
                )
        ],

        'gost' => [
            'aliases' =>
                $aliases['gost'],
            'info' =>
                buildCertificateInfo(
                    $infoData['gost']
                    ?? null
                )
        ]
    ]);
}


/* =========================================================
   INFO
   ========================================================= */

function actionInfo(): void
{
    global $config;

    $id =
        (int)($_GET['id'] ?? 0);

    if (!isset($config['utms'][$id])) {

        jsonResponse([
            'success' => false,
            'error' => 'УТМ не найден'
        ], 404);
    }

    $utm =
        $config['utms'][$id];

    $answer =
        getUtmInfo(
            $utm['ip'],
            (int)$utm['port'],
            getTimeout()
        );

    if (!$answer['ok']) {

        jsonResponse([
            'success' => false,
            'error' =>
                $answer['error'],
            'http_code' =>
                $answer['http_code'],
            'utm_response' =>
                $answer['data']
        ], 502);
    }

    $data =
        decodeJson(
            $answer['data']
        );

    if ($data === null) {

        jsonResponse([
            'success' => false,
            'error' =>
                'УТМ вернул некорректный JSON',
            'raw' =>
                $answer['data']
        ], 502);
    }

    jsonResponse([
        'success' => true,
        'data' => $data,
        'response_time' =>
            $answer['time']
    ]);
}


/* =========================================================
   MARK CODE VALIDATION
   ========================================================= */

function validateMarkCode(string $code): array
{
    $code = trim($code);

    $length =
        strlen($code);

    if ($length !== 68 && $length !== 150) {

        return [
            'valid' => false,
            'length' => $length,
            'error' =>
                'Код марки должен содержать 68 или 150 символов'
        ];
    }

    /*
     * В DataMatrix могут встречаться специальные
     * символы GS/FNC1, поэтому здесь НЕ запрещаем
     * произвольные символы.
     */

    return [
        'valid' => true,
        'length' => $length,
        'error' => null
    ];
}


/* =========================================================
   MARK CHECK
   ========================================================= */

function actionMarkCheck(): void
{
    global $config;

    $id =
        (int)($_GET['id'] ?? 0);

    $code =
        trim(
            (string)($_GET['code'] ?? '')
        );

    if (!isset($config['utms'][$id])) {

        jsonResponse([
            'success' => false,
            'error' => 'УТМ не найден'
        ], 404);
    }

    if ($code === '') {

        jsonResponse([
            'success' => false,
            'error' =>
                'Не указан код марки'
        ], 400);
    }

    $validation =
        validateMarkCode($code);

    if (!$validation['valid']) {

        jsonResponse([
            'success' => false,
            'error' =>
                $validation['error'],
            'code_length' =>
                $validation['length'],
            'allowed_lengths' => [
                68,
                150
            ]
        ], 400);
    }

    $utm =
        $config['utms'][$id];

    $ip =
        trim(
            (string)$utm['ip']
        );

    $port =
        (int)$utm['port'];

    /*
     * Сначала убеждаемся, что сам УТМ жив.
     */
    $info =
        getUtmInfo(
            $ip,
            $port,
            max(5, getTimeout())
        );

    if (!$info['ok']) {

        jsonResponse([
            'success' => false,
            'stage' => 'utm',
            'error' =>
                'Сам УТМ недоступен',
            'utm_ip' => $ip,
            'utm_port' => $port,
            'http_code' =>
                $info['http_code'],
            'utm_response' =>
                $info['data'],
            'response_time' =>
                $info['time']
        ], 502);
    }

    /*
     * Получаем информацию о сертификатах.
     */
    $aliases =
        getCertificateAliases(
            $ip,
            $port,
            max(5, getTimeout())
        );

    /*
     * Отправляем запрос именно УТМ.
     */
    $answer =
        requestUtm(
            $ip,
            $port,
            '/api/mark/check?code=' .
            rawurlencode($code),
            15
        );

    if (!$answer['ok']) {

        $utmError =
            trim(
                (string)(
                    $answer['data']
                    ?? ''
                )
            );

        /*
         * Особый случай, который сейчас видим
         * у твоего УТМ:
         *
         * filter-utm.egais.ru:8443 failed to respond
         */
        $filterProblem =
            stripos(
                $utmError,
                'filter-utm.egais.ru'
            ) !== false;

        $diagnostics = [
            'utm_online' => true,

            'utm_ip' => $ip,

            'utm_port' => $port,

            'utm_http_code' =>
                $answer['http_code'],

            'utm_response_time' =>
                $answer['time'],

            'rsa_aliases' =>
                $aliases['rsa'],

            'gost_aliases' =>
                $aliases['gost'],

            'filter_error' =>
                $filterProblem,

            'message' =>
                $filterProblem
                ? 'УТМ доступен, но проверка марки не может получить ответ от сервера ЕГАИС filter-utm.egais.ru:8443'
                : 'УТМ вернул ошибку при проверке марки'
        ];

        jsonResponse([
            'success' => false,

            'stage' => 'utm_mark_check',

            'error' =>
                $filterProblem
                ? 'Ошибка связи УТМ с сервером проверки ЕГАИС'
                : $answer['error'],

            'code_length' =>
                $validation['length'],

            'utm' => $diagnostics,

            'http_code' =>
                $answer['http_code'],

            'utm_response' =>
                $utmError,

            'response_time' =>
                $answer['time']
        ], 502);
    }

    $data =
        decodeJson(
            $answer['data']
        );

    jsonResponse([
        'success' => true,

        'stage' => 'utm_mark_check',

        'code' => $code,

        'code_length' =>
            $validation['length'],

        'result' =>
            $data !== null
            ? $data
            : $answer['data'],

        'response_time' =>
            $answer['time']
    ]);
}


/* =========================================================
   DOCUMENT LIST
   ========================================================= */

function actionDocuments(
    string $direction
): void {

    global $config;

    $id =
        (int)($_GET['id'] ?? 0);

    $limit =
        (int)($_GET['limit'] ?? 50);

    $offset =
        (int)($_GET['offset'] ?? 0);

    $limit =
        max(
            1,
            min(500, $limit)
        );

    $offset =
        max(
            0,
            $offset
        );

    if (!isset($config['utms'][$id])) {

        jsonResponse([
            'success' => false,
            'error' => 'УТМ не найден'
        ], 404);
    }

    $utm =
        $config['utms'][$id];

    $path =
        $direction === 'in'
        ? '/api/db/in/list?limit=' .
          $limit .
          '&offset=' .
          $offset
        : '/api/db/out/list?limit=' .
          $limit .
          '&offset=' .
          $offset;

    $answer =
        requestUtm(
            $utm['ip'],
            (int)$utm['port'],
            $path,
            10
        );

    if (!$answer['ok']) {

        jsonResponse([
            'success' => false,
            'error' =>
                $answer['error'],
            'http_code' =>
                $answer['http_code'],
            'utm_response' =>
                $answer['data']
        ], 502);
    }

    jsonResponse([
        'success' => true,
        'data' =>
            decodeJson(
                $answer['data']
            )
    ]);
}


/* =========================================================
   TTN
   ========================================================= */

function actionTtn(): void
{
    global $config;

    $id =
        (int)($_GET['id'] ?? 0);

    $limit =
        (int)($_GET['limit'] ?? 50);

    $offset =
        (int)($_GET['offset'] ?? 0);

    $limit =
        max(
            1,
            min(500, $limit)
        );

    $offset =
        max(
            0,
            $offset
        );

    if (!isset($config['utms'][$id])) {

        jsonResponse([
            'success' => false,
            'error' => 'УТМ не найден'
        ], 404);
    }

    $utm =
        $config['utms'][$id];

    $answer =
        requestUtm(
            $utm['ip'],
            (int)$utm['port'],
            '/opt/out?limit=' .
            $limit .
            '&offset=' .
            $offset,
            10
        );

    if (!$answer['ok']) {

        jsonResponse([
            'success' => false,
            'error' =>
                $answer['error'],
            'http_code' =>
                $answer['http_code'],
            'utm_response' =>
                $answer['data']
        ], 502);
    }

    jsonResponse([
        'success' => true,
        'data' =>
            decodeJson(
                $answer['data']
            )
            ?? $answer['data']
    ]);
}


/* =========================================================
   DIAGNOSTICS
   ========================================================= */

function actionDiagnostics(): void
{
    global $config;

    $id =
        (int)($_GET['id'] ?? 0);

    if (!isset($config['utms'][$id])) {

        jsonResponse([
            'success' => false,
            'error' => 'УТМ не найден'
        ], 404);
    }

    $utm =
        $config['utms'][$id];

    $ip =
        trim(
            (string)$utm['ip']
        );

    $port =
        (int)$utm['port'];

    $info =
        getUtmInfo(
            $ip,
            $port,
            5
        );

    $certificates =
        getCertificateAliases(
            $ip,
            $port,
            5
        );

    jsonResponse([
        'success' => true,

        'utm' => [
            'id' => $id,
            'name' =>
                $utm['name']
                ?? null,
            'ip' => $ip,
            'port' => $port
        ],

        'info' => [
            'ok' =>
                $info['ok'],
            'http_code' =>
                $info['http_code'],
            'response_time' =>
                $info['time'],
            'data' =>
                $info['ok']
                ? decodeJson($info['data'])
                : null,
            'error' =>
                $info['error']
        ],

        'certificates' => [
            'ok' =>
                $certificates['ok'],
            'rsa' =>
                $certificates['rsa'],
            'gost' =>
                $certificates['gost'],
            'error' =>
                $certificates['error']
        ],

        'mark_check' => [
            'endpoint' =>
                '/api/mark/check',
            'note' =>
                'Запрос выполняется самим УТМ. PHP не подменяет сертификат УТМ.'
        ]
    ]);
}


/* =========================================================
   MAIN
   ========================================================= */

$action =
    $_GET['action'] ?? '';

try {

    switch ($action) {

        case 'status':
            actionStatus();
            break;

        case 'certificates':
            actionCertificates();
            break;

        case 'info':
            actionInfo();
            break;

        case 'mark_check':
            actionMarkCheck();
            break;

        case 'incoming':
            actionDocuments('in');
            break;

        case 'outgoing':
            actionDocuments('out');
            break;

        case 'ttn':
            actionTtn();
            break;

        case 'diagnostics':
            actionDiagnostics();
            break;

        default:

            jsonResponse([
                'success' => false,
                'error' =>
                    'Неизвестная команда',
                'available_actions' => [
                    'status',
                    'certificates',
                    'info',
                    'mark_check',
                    'incoming',
                    'outgoing',
                    'ttn',
                    'diagnostics'
                ],
                'action' =>
                    $action
            ], 400);
    }

} catch (Throwable $e) {

    jsonResponse([
        'success' => false,
        'error' =>
            'Ошибка API: ' .
            $e->getMessage()
    ], 500);
}
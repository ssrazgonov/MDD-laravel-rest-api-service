<?php

namespace App\Modules\Provider\Enums;

enum Endpoint: string
{
    case GET_ACCESS_TOKEN = '/auth/realms/external-providers/protocol/openid-connect/token';
    case GET_USAGE = '/api/integration/get-usage';
}

<?php

namespace App\Modules\S2P\Enums;

enum Endpoint: string
{
    case GET_ACCESS_TOKEN = '/auth/realms/nfp-providers-admin/protocol/openid-connect/token';
    case GET_USAGE = '/api/integration/get-usage';
}

<?php

namespace App\Modules\User\Http\Controllers\Api\V1;

use App\Modules\User\Actions\GetUserMembers;
use App\Modules\User\Dto\Actions\GetUserMembersDto;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * @response array{status: bool, error: array{error: string, code: int} , data: GroupMember[]}
     */
    public function members(GetUserMembers $getUserMembers)
    {
        $dto = new GetUserMembersDto(
            userId: Auth::id(),
            memberType: 'dependent', // Only children
            active: true
        );

        return response()->json()->api(
            $getUserMembers->handleResult($getUserMembers($dto)),
            Response::HTTP_OK
        );
    }
}

<?php

namespace Asivas\ABM\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $buttons;

    protected $baseNavigation;

    public function __construct()
    {
        $this->createBaseNavigation();
    }

    protected function createBaseNavigation() {
        $this->baseNavigation = [
        ];
    }

    public function getButtons()
    {
        return $this->buttons;
    }

    public function getNavigation(Request $request) : array
    {
        $navigation = [];
        foreach ($this->baseNavigation as $item){
            $resurceAbility = true;
            if($item->getResource()!=null)
                $resurceAbility = $request->user()->can('viewAny',$item->getResource());
            if (Gate::allows($item['ability']) && $resurceAbility ) {
                array_push($navigation,$item->toArray()['nav']);
            }
        }
        return $navigation;
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getToken()
    {
        /**
         * if (!$token = auth($this->guard)->attempt($credentials)) {
        return response()->json(['error' => 'Unauthorized'], 401);
        }
         */
        return $this->respondWithToken(auth('api')->getToken());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $guard = 'api';
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth($guard)->factory()->getTTL() * 60,
            'user' => auth()->user(),
        ]);
    }


    protected function log($msg,$context,$level='info') {
        try {
            Log::$level($msg,$context);
        } catch (\Exception $exception)
        {

        }

    }
}

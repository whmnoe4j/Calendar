<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function success($data = [])
    {
        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => '操作成功！',
            'data'    => $data,
        ]);
    }

    public function fail($code=400, $data = [], $message = "操作失败!")
    {
        return response()->json([
            'status'  => false,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ]);
    }
}

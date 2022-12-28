<?php

namespace App\Http\Controllers\ActivityPub\Instance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SharedInboxController extends Controller
{
    /**
     *
     * @param \Illuminate\Http\Request $request
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return never
     */
    public function __invoke(Request $request)
    {
        info(__CLASS__, ['request' => $request]);
        abort(418);
    }
}

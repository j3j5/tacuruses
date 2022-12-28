<?php

declare(strict_types=1);

namespace App\Http\Controllers\ActivityPub\Instance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HostMetaController extends Controller
{
    /**
     *
     * @param \Illuminate\Http\Request $request
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $xml = <<<XML
        <xml version="1.0" encoding="UTF-8"?>
        <XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">
            <Link rel="lrdd" template="https://bots.remote-dev.j3j5.uy/.well-known/webfinger?resource={uri}" type="application/xrd+xml" />
        </XRD>
XML;
        return response($xml);
    }
}

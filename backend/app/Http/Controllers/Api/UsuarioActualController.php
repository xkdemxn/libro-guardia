<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;

class UsuarioActualController extends Controller
{
    /**
     * El operador con sesión iniciada. Sin sesión, el middleware responde 401.
     */
    public function __invoke(Request $request): UsuarioResource
    {
        return new UsuarioResource($request->user());
    }
}

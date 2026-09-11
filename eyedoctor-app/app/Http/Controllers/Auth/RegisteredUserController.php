<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegisteredUserController extends Controller
{
    /**
     * Public self-registration is intentionally disabled.
     *
     * RETINA accounts are created only after the professional
     * access-request workflow is reviewed and approved by an
     * administrator.
     */
    public function create(): never
    {
        throw new NotFoundHttpException();
    }

    /**
     * Public self-registration is intentionally disabled.
     */
    public function store(): never
    {
        throw new NotFoundHttpException();
    }
}

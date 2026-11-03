<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Livewire\Mechanisms\HandleRequests\HandleRequests;
use Symfony\Component\HttpFoundation\Response;

final class LivewireUpdateController extends Controller
{
    public function __invoke(HandleRequests $handler): Response
    {
        $response = $handler->handleUpdate();

        if ($response instanceof Response) {
            return $response;
        }

        // Encode the final payload here, before the router creates its default
        // JSON response. Signed snapshots are already encoded by Livewire.
        return new JsonResponse($response, options: JSON_INVALID_UTF8_SUBSTITUTE);
    }
}

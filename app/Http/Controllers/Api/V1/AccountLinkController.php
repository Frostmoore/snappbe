<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\LinkAccountRequest;
use App\Http\Resources\AccountLinkResource;
use App\Http\Responses\ApiResponse;
use App\Services\WordPress\AccountLinker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Collegamento dell'account app all'account WordPress SNA (pivot + ereditarietà livello).
 */
class AccountLinkController extends Controller
{
    public function __construct(private AccountLinker $linker) {}

    /** Stato del collegamento dell'utente corrente. */
    public function index(Request $request): JsonResponse
    {
        $link = $request->user()->accountLink()->with('wpAccount')->first();

        return ApiResponse::ok($link ? new AccountLinkResource($link) : null);
    }

    /** Collega l'account SNA dato identificativo (email o username) + password. */
    public function store(LinkAccountRequest $request): JsonResponse
    {
        $link = $this->linker->link(
            $request->user(),
            (string) $request->string('identifier'),
            (string) $request->string('password'),
        );

        return ApiResponse::created(new AccountLinkResource($link));
    }

    /** Scollega l'account SNA e azzera il livello ereditato. */
    public function destroy(Request $request): JsonResponse
    {
        $this->linker->unlink($request->user());

        return ApiResponse::ok(['message' => 'Account SNA scollegato.']);
    }
}

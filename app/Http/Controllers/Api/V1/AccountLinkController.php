<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\WordPressUnavailableException;
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

    /**
     * Proposta di collegamento automatico: l'email (verificata) dell'utente
     * combacia con un account del sito SNA? La proposta si fa UNA volta sola.
     */
    public function suggestion(Request $request): JsonResponse
    {
        $user = $request->user();

        // Già collegato o già proposto → niente proposta.
        if ($user->sna_link_prompted_at !== null || $user->accountLink()->exists()) {
            return ApiResponse::ok(['available' => false]);
        }

        try {
            $data = $this->linker->findByUserEmail($user);
        } catch (WordPressUnavailableException $e) {
            // WP momentaneamente non raggiungibile: NON marcare, si riproverà.
            return ApiResponse::ok(['available' => false]);
        }

        // Nessun account SNA con questa email: marca come proposto (no re-check).
        if (! $data) {
            $user->forceFill(['sna_link_prompted_at' => now()])->save();

            return ApiResponse::ok(['available' => false]);
        }

        return ApiResponse::ok([
            'available'   => true,
            'level_label' => $data['level_label'] ?? null,
            'username'    => $data['username'] ?? null,
        ]);
    }

    /**
     * Accetta la proposta: collega per match email (no password, l'email è già
     * verificata) e segna la proposta come fatta.
     */
    public function acceptSuggestion(Request $request): JsonResponse
    {
        $user = $request->user();

        $link = $this->linker->linkByEmail($user);
        $user->forceFill(['sna_link_prompted_at' => now()])->save();

        if (! $link) {
            return ApiResponse::error('Nessun account SNA collegabile con questa email.', status: 422);
        }

        return ApiResponse::ok(new AccountLinkResource($link));
    }

    /** Rifiuta la proposta: la segna come fatta così non si ripropone. */
    public function dismissSuggestion(Request $request): JsonResponse
    {
        $request->user()->forceFill(['sna_link_prompted_at' => now()])->save();

        return ApiResponse::ok(['message' => 'Proposta di collegamento ignorata.']);
    }
}

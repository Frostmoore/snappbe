<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StoreDeviceRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Device;
use Illuminate\Http\JsonResponse;

/**
 * Registrazione del token FCM del device. Pubblico: anche i device anonimi
 * possono registrarsi (per le notifiche pubbliche). Se la richiesta è autenticata,
 * il device viene associato all'utente.
 */
class DeviceController extends Controller
{
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        $device = Device::updateOrCreate(
            ['fcm_token' => $request->string('fcm_token')],
            [
                'user_id' => $user?->id,
                'platform' => $request->input('platform'),
                'last_seen_at' => now(),
            ]
        );

        return ApiResponse::ok(['id' => $device->id]);
    }
}

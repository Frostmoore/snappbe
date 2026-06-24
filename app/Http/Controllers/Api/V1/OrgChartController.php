<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\OrgChartMember;
use Illuminate\Http\JsonResponse;

class OrgChartController extends Controller
{
    /**
     * Organigramma raggruppato per SEZIONE (come sul sito): ogni gruppo ha un
     * titolo e la lista dei membri. L'ordine dei gruppi segue il `sort_order`
     * (il gruppo del membro con sort_order più basso compare per primo); i membri
     * dentro al gruppo sono ordinati per `sort_order`.
     */
    public function index(): JsonResponse
    {
        $members = OrgChartMember::query()
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        $groups = [];
        foreach ($members as $m) {
            $title = trim((string) $m->group) !== '' ? $m->group : 'Altri';
            $groups[$title][] = [
                'id'    => $m->id,
                'name'  => $m->name,
                'role'  => $m->role,
                'photo' => $m->photoUrl(),
                'email' => $m->email,
            ];
        }

        $data = [];
        foreach ($groups as $title => $list) {
            $data[] = ['group' => $title, 'members' => $list];
        }

        return ApiResponse::ok($data);
    }
}

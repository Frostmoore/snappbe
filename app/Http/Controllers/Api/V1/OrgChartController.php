<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Responses\ApiResponse;
use App\Models\OrgChartMember;
use App\Models\OrgChartSection;
use Illuminate\Http\JsonResponse;

class OrgChartController extends Controller
{
    /**
     * Organigramma raggruppato per SEZIONE (come sul sito): ogni gruppo ha un
     * titolo, una descrizione e la lista dei membri. L'ordine dei gruppi e le
     * descrizioni vengono da `org_chart_sections`; i membri vi appartengono per
     * `group` = titolo della sezione, ordinati per `sort_order`.
     */
    public function index(): JsonResponse
    {
        $sections = OrgChartSection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        $byGroup = OrgChartMember::query()
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->groupBy('group');

        $map = static fn ($members) => $members->map(fn ($m) => [
            'id'    => $m->id,
            'name'  => $m->name,
            'role'  => $m->role,
            'note'  => $m->note,
            'link'  => $m->link,
            'photo' => $m->photoUrl(),
            'email' => $m->email,
        ])->values()->all();

        $data = [];
        $used = [];

        // Sezioni gestite (con descrizione e ordine), solo se hanno membri.
        foreach ($sections as $s) {
            $members = $byGroup->get($s->title);
            if (! $members || $members->isEmpty()) {
                continue;
            }
            $used[] = $s->title;
            $data[] = [
                'group'       => $s->title,
                'subtitle'    => $s->subtitle,
                'description' => $s->description,
                'members'     => $map($members),
            ];
        }

        // Eventuali gruppi senza sezione corrispondente (in coda, senza descrizione).
        foreach ($byGroup as $group => $members) {
            $title = trim((string) $group) !== '' ? (string) $group : 'Altri';
            if (in_array($title, $used, true)) {
                continue;
            }
            $data[] = [
                'group'       => $title,
                'subtitle'    => null,
                'description' => null,
                'members'     => $map($members),
            ];
        }

        return ApiResponse::ok($data);
    }
}

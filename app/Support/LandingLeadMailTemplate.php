<?php

namespace App\Support;

use App\Models\LandingLead;
use App\Models\SiteSetting;

final class LandingLeadMailTemplate
{
    /**
     * @var list<string>
     */
    public const PLACEHOLDERS = [
        '{name}',
        '{email}',
        '{phone}',
        '{type}',
        '{plan}',
        '{brand}',
        '{company_email}',
        '{company_phone}',
        '{admin_url}',
    ];

    /**
     * @param  array<string, string>  $extraReplacements
     */
    public static function render(string $template, LandingLead $lead, SiteSetting $site, array $extraReplacements = []): string
    {
        $replacements = [
            '{name}' => trim((string) $lead->name),
            '{email}' => trim((string) ($lead->email ?? '')),
            '{phone}' => trim((string) $lead->phone),
            '{type}' => $lead->typeLabel(),
            '{plan}' => trim((string) ($lead->recommended_plan_title ?? '')),
            '{brand}' => trim((string) ($site->org_brand_name ?? '')) ?: 'ЛогистРу',
            '{company_email}' => trim((string) ($site->org_email ?? '')),
            '{company_phone}' => trim((string) ($site->org_phone ?? '')),
            '{admin_url}' => '',
        ];

        $replacements = array_merge($replacements, $extraReplacements);

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}

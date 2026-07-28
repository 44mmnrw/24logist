<?php

namespace App\Services;

final class LlmsTxtService
{
    public function __construct(
        private readonly SiteSettingsService $settings,
    ) {}

    public function generate(): string
    {
        $content = str_replace(
            ["\r\n", "\r"],
            "\n",
            (string) ($this->settings->get()->llms_txt_extra ?? ''),
        );

        return $content === '' ? '' : rtrim($content, "\n")."\n";
    }
}

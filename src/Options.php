<?php

declare(strict_types=1);

namespace HM;

use SensitiveParameter;

final readonly class Options
{
    public function __construct(
        public string $harvestAccountId,
        #[SensitiveParameter]
        public string $harvestPat,
        public string $kimaiHost,
        #[SensitiveParameter]
        public string $kimaiToken,
        public string $defaultTimezone,
        public string $defaultLanguageCode,
        public string $defaultCurrencyCode,
        public string $defaultCountryCode,
    ) {
    }
}

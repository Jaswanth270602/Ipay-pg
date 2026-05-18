<?php

namespace App\Support;

use Illuminate\Http\Request;

class DashboardFxContext
{
    public const MODE_HISTORICAL = 'historical';

    public const MODE_LIVE = 'live';

    public function __construct(
        public readonly string $displayCurrency,
        public readonly string $mode = self::MODE_HISTORICAL,
    ) {}

    public function isLive(): bool
    {
        return $this->mode === self::MODE_LIVE;
    }

    public function isHistorical(): bool
    {
        return ! $this->isLive();
    }

    public static function fromRequest(Request $request): self
    {
        $supported = config('ipay.dashboard_display.supported_currencies', ['USD', 'INR', 'KES']);
        $defaultDisplay = strtoupper((string) config('ipay.dashboard_display.currency', 'KES'));
        $defaultMode = (string) config('ipay.dashboard_display.default_fx_mode', self::MODE_HISTORICAL);

        $display = strtoupper(trim((string) $request->get('display_currency', $defaultDisplay)));
        if (! in_array($display, $supported, true)) {
            $display = $defaultDisplay;
        }

        $mode = strtolower(trim((string) $request->get('fx_mode', $defaultMode)));
        if (! in_array($mode, [self::MODE_HISTORICAL, self::MODE_LIVE], true)) {
            $mode = $defaultMode;
        }

        return new self($display, $mode);
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return [
            'display_currency' => $this->displayCurrency,
            'fx_mode' => $this->mode,
            'fx_base_currency' => strtoupper((string) config('ipay.fx.base_currency', 'USD')),
        ];
    }
}

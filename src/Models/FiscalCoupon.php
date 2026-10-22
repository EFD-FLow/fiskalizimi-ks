<?php

namespace EfdFlow\FiskalizimiKs\Models;

use EfdFlow\FiskalizimiKs\Generated\PosCoupon;
use Illuminate\Database\Eloquent\Model;

class FiscalCoupon extends Model
{
    const STATUS_PENDING     = 'pending';
    const STATUS_SUBMITTING  = 'submitting';
    const STATUS_FISCALIZED  = 'fiscalized';
    const STATUS_QUEUED      = 'queued';
    const STATUS_FAILED      = 'failed';
    const STATUS_REJECTED    = 'rejected';

    /**
     * ATK received the submission but its outcome could not be read, and retrying
     * did not clear it. The coupon may or may not be recorded at ATK, so it is
     * held for an operator decision rather than resolved by guessing.
     */
    const STATUS_UNRESOLVED  = 'unresolved';

    protected $guarded = [];

    protected $casts = [
        'fiscal_response'    => 'array',
        'fiscal_time'        => 'integer',
        // Deliberately not cast to integer: ATK's transaction number is a uint64
        // whose upper half wraps negative in PHP's signed int. It is stored and
        // read as a decimal string.
        'atk_transaction_no' => 'string',
        'fiscalized_at'      => 'datetime',
        // When ATK first answered this submission unreadably. Optional: only
        // consumers that retry an unknown result before escalating populate it.
        'unknown_since'      => 'datetime',
    ];

    /**
     * Whether the signed payload that went to ATK carried negative amounts.
     *
     * The payload, not the coupon type, records what ATK received: only
     * corrections signed by 0.12.0 went out with negative amounts, those before
     * and after it went out positive, so a return's type says nothing about its
     * sign. Missing or unreadable details report false.
     */
    public function carriesNegativeAmounts(): bool
    {
        $details = $this->fiscal_details;

        if (! is_string($details) || $details === '') {
            return false;
        }

        $payload = base64_decode($details, true);

        if ($payload === false) {
            return false;
        }

        try {
            $posCoupon = new PosCoupon();
            $posCoupon->mergeFromString($payload);

            return $posCoupon->getTotal() < 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getTable(): string
    {
        return config('fiskalizimi.table', 'kuponat_fiskal');
    }
}

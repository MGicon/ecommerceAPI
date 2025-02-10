<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class OrderStatusType extends Enum
{
    const Pending = 0;
    const Confirmed = 1;
    const Rejected = 2;
    const Cancelled = 3;
    const Completed = 4;

}

<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A quest action no longer applies, usually because the quest changed under
 * the user. The message is safe to show to them.
 */
class QuestActionException extends RuntimeException {}

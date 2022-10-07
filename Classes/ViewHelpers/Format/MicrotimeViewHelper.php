<?php

declare(strict_types=1);

namespace CoStack\Logs\ViewHelpers\Format;

use Closure;
use DateTime;
use DateTimeZone;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function date_default_timezone_get;
use function sprintf;
use function strpos;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class MicrotimeViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('microTime', 'float', 'Value returned by microtime(true)', true);
        $this->registerArgument('format', 'string', 'Resulting format', false, 'Y-m-d H:i:s.u');
    }

    public function render(): string
    {
        return static::renderStatic(
            [
                'microTime' => $this->arguments['microTime'],
                'format' => $this->arguments['format'],
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $microTime = (string)$arguments['microTime'];
        $format = $arguments['format'];
        $timezoneIdentifier = date_default_timezone_get();
        $timezone = new DateTimeZone($timezoneIdentifier);

        if (false !== strpos($microTime, '.')) {
            $dateTime = DateTime::createFromFormat('U.u', $microTime);
        } elseif (false !== strpos($microTime, ' ')) {
            $dateTime = DateTime::createFromFormat('u U', $microTime);
        } else {
            $dateTime = DateTime::createFromFormat('U', $microTime);
        }
        $dateTime->setTimezone($timezone);

        return sprintf(
            '<span title="Timezone: %s">%s</span>',
            $timezoneIdentifier,
            $dateTime->format($format)
        );
    }
}

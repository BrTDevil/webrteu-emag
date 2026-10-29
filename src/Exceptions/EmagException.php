<?php

declare(strict_types=1);

namespace WeBRTeu\Emag\Exceptions;

use RuntimeException;

class EmagException extends RuntimeException
{
    /**
     * @param  array<array-key, mixed>  $messages
     */
    public static function fromMessages(string $endpoint, int $status, array $messages): self
    {
        $texts = [];

        foreach ($messages as $message) {
            if (is_string($message)) {
                $texts[] = $message;
            } elseif (is_array($message) && is_string($message['text'] ?? null)) {
                $texts[] = $message['text'];
            }
        }

        return new self(sprintf('eMAG %s a eșuat (HTTP %d): %s', $endpoint, $status, $texts === [] ? 'fără mesaj' : implode('; ', $texts)));
    }

    public static function missingCredentials(): self
    {
        return new self('Lipsesc EMAG_USERNAME / EMAG_PASSWORD.');
    }
}

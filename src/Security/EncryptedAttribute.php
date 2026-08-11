<?php

declare(strict_types=1);

namespace Richness\RichPayments\Security;

use Illuminate\Support\Facades\Crypt;

trait EncryptedAttribute
{
    public function getAttribute($key): mixed
    {
        $value = parent::getAttribute($key);

        if (empty($this->encryptedAttributes) || ! in_array($key, $this->encryptedAttributes, true) || $value === null || $value === '') {
            return $value;
        }

        return Crypt::decryptString($value);
    }

    public function setAttribute($key, $value): mixed
    {
        if (! empty($this->encryptedAttributes) && in_array($key, $this->encryptedAttributes, true) && $value !== null && $value !== '') {
            $value = Crypt::encryptString((string) $value);
        }

        return parent::setAttribute($key, $value);
    }
}

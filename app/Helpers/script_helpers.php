<?php

/**
 * Remove todos os caracteres não numéricos de uma string.
 *
 * @param string|null $value
 * @return string
 */
function onlyNumbers(?string $value): string
{
    if (empty($value)) {
        return '';
    }

    return preg_replace('/\D/', '', $value);
}
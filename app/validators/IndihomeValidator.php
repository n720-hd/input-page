<?php
declare(strict_types=1);

use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\StringLength;
use Phalcon\Filter\Validation\Validator\Regex;

class IndihomeValidator extends Validation
{
    public function initialize(): void
    {
        $this->add('subscription_number', new PresenceOf([
            'message' => 'Subscription number is required'
        ]));

        $this->add('subscription_number', new StringLength([
            'min' => 12,
            'max' => 14,
            'messageMinimum' => 'Subscription number must be at least 12 characters',
            'messageMaximum' => 'Subscription number must not exceed 14 characters'
        ]));

        $this->add('subscription_number', new Regex([
            'pattern' => '/^[0-9]+$/',
            'message' => 'Subscription number must contain only numbers'
        ]));
    }
}
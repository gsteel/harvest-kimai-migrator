<?php

declare(strict_types=1);

namespace HM\Kimai\Model;

/** @mago-expect lint:too-many-properties */
final class CreateCustomer
{
    public string|null $number;
    public string|null $comment;
    public string|null $company;
    public string|null $vatId;
    public string|null $contact;
    public string|null $addressLine1;
    public string|null $addressLine2;
    public string|null $addressLine3;
    public string|null $postCode;
    public string|null $city;
    public string|null $phone;
    public string|null $fax;
    public string|null $mobile;
    public string|null $email;
    public string|null $homepage;
    public string|null $invoiceText;
    public string|null $invoiceTemplate;
    public string|null $invoiceEmail;
    public string|null $buyerReference;
    /** @var list<int> */
    public array $teams = [];
    public string|null $color;
    public mixed $budget;
    public string|null $timeBudget;
    public string|null $budgetType;
    public bool $visible = true;
    public bool $billable = true;

    public function __construct(
        public string $name,
        public string $country,
        public string $currency,
        public string $timezone,
        public string $language = 'en',
    ) {
    }
}

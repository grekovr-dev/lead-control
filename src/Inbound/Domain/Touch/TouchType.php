<?php

declare(strict_types=1);

namespace Inbound\Domain\Touch;

enum TouchType: string
{
    case PhoneClick = 'phone_click';
    case LeadFormClick = 'lead_form_click';
    case MessengerClick = 'messenger_click';
    case WorksClick = 'works_click';
}

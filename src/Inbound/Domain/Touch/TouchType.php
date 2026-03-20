<?php

declare(strict_types=1);

namespace Inbound\Domain\Touch;

enum TouchType: string
{
    case PhoneClick = 'phone_click';
    case FormSubmit = 'form_submit';
    case MessengerClick = 'messenger_click';
    case CtaClick = 'cta_click';
}

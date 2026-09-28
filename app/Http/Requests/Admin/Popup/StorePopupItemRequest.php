<?php

namespace App\Http\Requests\Admin\Popup;

use App\Http\Requests\Admin\Popup\Concerns\PopupItemValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StorePopupItemRequest extends FormRequest
{
    use PopupItemValidationRules;
}

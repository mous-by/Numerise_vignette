<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\SmsStatus;
use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use Illuminate\View\View;

/**
 * Journal des SMS envoyés par la plateforme (W14), en lecture seule : supervision nationale.
 */
class SmsController extends Controller
{
    public function index(): View
    {
        $messages = SmsMessage::query()->latest('id')->limit(500)->get();

        return view('sms.index', [
            'messages' => $messages,
            'counts' => $messages->groupBy(fn (SmsMessage $sms) => $sms->status->value)->map->count(),
            'statuses' => SmsStatus::cases(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends BaseController
{
    public function list()
    {
        try {
            $ev = Event::with('coach')->orderBy('id','desc')->get();
            return $this->successResponse($ev, __('All data'));
        } catch (\Exception $ex) {
            return $this->errorResponse($ex->getMessage(), 'Please retry again!!');
        }
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImplementationPageConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $implementation_id = request()->input('implementation_id');
        logger()->info('implementation_id: '. print_r($implementation_id, true));
        return parent::toArray($request);
    }
}

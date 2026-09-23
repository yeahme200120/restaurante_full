<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyModuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'module_id' => $this->module_id,
            'status' => $this->status,
            'enabled_at' => $this->enabled_at,
            'disabled_at' => $this->disabled_at,
            'metadata' => $this->metadata,
            'company' => $this->whenLoaded('company'),
            'module' => $this->whenLoaded('module'),
        ];
    }
}